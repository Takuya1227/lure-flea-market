<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Lure;
use App\Models\Payment;
use App\Models\ShippingAddress;
use App\Models\Transaction;
use App\Models\User;
use App\Services\PayjpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * フェーズ5: 取引・決済テスト
 *
 * PayjpService はモック化してテスト。実際の PAY.JP API は呼ばない。
 * v2 フロー: createCheckoutSession → PAY.JP Checkout ページ（外部）
 *           → returnCallback で Checkout Session 確認 → 決済確定
 */
class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private User $seller;
    private Lure $lure;
    private ShippingAddress $address;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->create(['balance' => 0, 'sales_count' => 0]);
        $this->buyer  = User::factory()->create();

        $this->lure = Lure::factory()->create([
            'seller_id' => $this->seller->id,
            'status'    => 'active',
            'price'     => 3000,
        ]);

        $this->address = ShippingAddress::factory()->create([
            'user_id' => $this->buyer->id,
        ]);
    }

    // ===== 5-1: 購入確認画面 =====

    /** 購入確認画面が正常に表示される */
    public function test_purchase_form_is_displayed_for_authenticated_buyer(): void
    {
        $response = $this->actingAs($this->buyer)
            ->get(route('transactions.create', ['lure_id' => $this->lure->id]));

        $response->assertOk();
        $response->assertViewIs('transactions.create');
        $response->assertSee($this->lure->model_name);
    }

    /** ゲストは購入確認画面にアクセスできない（ログインページへリダイレクト） */
    public function test_guest_cannot_access_purchase_form(): void
    {
        $this->get(route('transactions.create', ['lure_id' => $this->lure->id]))
            ->assertRedirect(route('login'));
    }

    /** 出品者は自分の商品の購入確認画面を開くとリダイレクトされる */
    public function test_seller_cannot_view_own_lure_purchase_form(): void
    {
        $this->actingAs($this->seller)
            ->get(route('transactions.create', ['lure_id' => $this->lure->id]))
            ->assertRedirect(route('lures.show', $this->lure))
            ->assertSessionHas('error');
    }

    /** 出品中でない商品は購入確認画面を開けない */
    public function test_inactive_lure_cannot_be_purchased(): void
    {
        $this->lure->update(['status' => 'suspended']);

        $this->actingAs($this->buyer)
            ->get(route('transactions.create', ['lure_id' => $this->lure->id]))
            ->assertRedirect(route('lures.show', $this->lure))
            ->assertSessionHas('error');
    }

    // ===== 5-2: 購入処理（PAY.JP v2 Checkout Session モック） =====

    /**
     * 正常な購入フロー:
     *   store()          → checkout_url を返す / Payment pending / Transaction pending_payment
     *   returnCallback() → paid / Payment succeeded / lure sold / 出品者残高 ++
     */
    public function test_successful_purchase_flow(): void
    {
        $this->mockPayjpSuccess();

        // ステップ①: store
        $storeResponse = $this->actingAs($this->buyer)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $this->address->id,
            ]);

        $storeResponse->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure(['success', 'checkout_url']);

        // store 完了時点: pending_payment かつ Payment は pending
        $transaction = Transaction::where('buyer_id', $this->buyer->id)->firstOrFail();
        $this->assertEquals('pending_payment', $transaction->status);

        $payment = Payment::where('transaction_id', $transaction->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals('cs_test_session_id', $payment->checkout_session_id);
        $this->assertNull($payment->payjp_flow_id);

        // ステップ②: PAY.JP Checkout 完了後のコールバック
        $this->actingAs($this->buyer)
            ->get(route('transactions.return', $transaction))
            ->assertRedirect(route('transactions.show', $transaction));

        // 最終状態確認
        $transaction->refresh();
        $this->assertEquals('paid', $transaction->status);
        $this->assertEquals(3000, $transaction->price);
        $this->assertEquals(300, $transaction->fee_amount);   // 10%
        $this->assertEquals(2700, $transaction->seller_earnings);
        $this->assertNotNull($transaction->paid_at);

        // Payment が succeeded になり flow_id が設定されている
        $payment->refresh();
        $this->assertEquals('succeeded', $payment->status);
        $this->assertEquals('pfw_test_flow_id', $payment->payjp_flow_id);

        // 商品が sold になっている
        $this->lure->refresh();
        $this->assertEquals('sold', $this->lure->status);
        $this->assertNotNull($this->lure->sold_at);

        // paid 時点では残高・件数はまだ加算されない
        $this->seller->refresh();
        $this->assertEquals(0, $this->seller->balance);
        $this->assertEquals(0, $this->seller->sales_count);
    }

    /** 決済失敗時（Checkout Session 作成エラー）: 取引レコードが cancelled になり、商品は active のまま */
    public function test_failed_checkout_session_creation_cancels_transaction(): void
    {
        $this->mockPayjpFailure();

        $this->actingAs($this->buyer)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $this->address->id,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $transaction = Transaction::where('buyer_id', $this->buyer->id)->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('cancelled', $transaction->status);

        // 商品は active のまま
        $this->lure->refresh();
        $this->assertEquals('active', $this->lure->status);
    }

    /** 他ユーザーの配送先住所は使用できない（認可テスト） */
    public function test_buyer_cannot_use_other_users_shipping_address(): void
    {
        $otherAddress = ShippingAddress::factory()->create([
            'user_id' => $this->seller->id,
        ]);

        $this->actingAs($this->buyer)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $otherAddress->id,
            ])
            ->assertStatus(404);
    }

    /** 出品者は自分の商品を購入できない */
    public function test_seller_cannot_purchase_own_lure(): void
    {
        $sellerAddress = ShippingAddress::factory()->create(['user_id' => $this->seller->id]);

        $this->actingAs($this->seller)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $sellerAddress->id,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertEquals(0, Transaction::count());
    }

    /** returnCallback: Checkout Session が complete でない場合はキャンセル */
    public function test_return_callback_cancels_if_session_not_complete(): void
    {
        $this->mockPayjpSessionIncomplete();

        // store で Transaction と Payment を作成
        $this->mockPayjpSuccessForStore();
        $storeResponse = $this->actingAs($this->buyer)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $this->address->id,
            ]);
        $transaction = Transaction::where('buyer_id', $this->buyer->id)->firstOrFail();

        // モックを incomplete に切り替えてコールバック
        $this->mockPayjpSessionIncomplete();
        $this->actingAs($this->buyer)
            ->get(route('transactions.return', $transaction))
            ->assertRedirect(route('lures.show', $this->lure))
            ->assertSessionHas('error');

        $transaction->refresh();
        $this->assertEquals('cancelled', $transaction->status);
    }

    /** 決済中に他ユーザーが購入済みの場合、returnCallback はリダイレクト（商品詳細 + エラー） */
    public function test_return_callback_fails_if_lure_sold_by_another_buyer(): void
    {
        $this->mockPayjpSuccess();

        $this->actingAs($this->buyer)
            ->postJson(route('transactions.store'), [
                'lure_id'             => $this->lure->id,
                'shipping_address_id' => $this->address->id,
            ]);
        $transaction = Transaction::where('buyer_id', $this->buyer->id)->firstOrFail();

        // 決済中に別ユーザーが商品を購入（sold に変更）
        $this->lure->update(['status' => 'sold']);

        $this->actingAs($this->buyer)
            ->get(route('transactions.return', $transaction))
            ->assertRedirect(route('lures.show', $this->lure))
            ->assertSessionHas('error');

        $transaction->refresh();
        $this->assertEquals('cancelled', $transaction->status);
    }

    // ===== 5-3: 取引詳細・ステータス管理 =====

    /** 購入者・出品者は取引詳細を閲覧できる */
    public function test_buyer_and_seller_can_view_transaction(): void
    {
        $transaction = $this->createPaidTransaction();

        $this->actingAs($this->buyer)
            ->get(route('transactions.show', $transaction))
            ->assertOk()
            ->assertViewIs('transactions.show');

        $this->actingAs($this->seller)
            ->get(route('transactions.show', $transaction))
            ->assertOk();
    }

    /** 第三者は取引詳細を閲覧できない（認可テスト） */
    public function test_third_party_cannot_view_transaction(): void
    {
        $transaction  = $this->createPaidTransaction();
        $thirdParty   = User::factory()->create();

        $this->actingAs($thirdParty)
            ->get(route('transactions.show', $transaction))
            ->assertForbidden();
    }

    /** 出品者: paid → shipped への遷移 */
    public function test_seller_can_mark_as_shipped(): void
    {
        $transaction = $this->createPaidTransaction();

        $this->actingAs($this->seller)
            ->patch(route('transactions.updateStatus', $transaction), ['action' => 'shipped'])
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $this->assertEquals('shipped', $transaction->status);
        $this->assertNotNull($transaction->shipped_at);
    }

    /** 購入者: shipped → review_pending への遷移（受取確認） */
    public function test_buyer_can_mark_as_received_after_shipped(): void
    {
        $transaction = $this->createPaidTransaction(['status' => 'shipped']);

        $this->actingAs($this->buyer)
            ->patch(route('transactions.updateStatus', $transaction), ['action' => 'received'])
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $this->assertEquals('review_pending', $transaction->status);
    }

    /** 購入者: review_pending → completed への遷移（取引完了） */
    public function test_buyer_can_complete_transaction_after_review_pending(): void
    {
        $transaction = $this->createPaidTransaction(['status' => 'review_pending']);

        $this->actingAs($this->buyer)
            ->patch(route('transactions.updateStatus', $transaction), ['action' => 'complete'])
            ->assertRedirect(route('transactions.show', $transaction));

        $transaction->refresh();
        $this->assertEquals('completed', $transaction->status);
        $this->assertNotNull($transaction->completed_at);

        // 取引完了後に出品者の残高・件数が加算される
        $this->seller->refresh();
        $this->assertEquals($transaction->seller_earnings, $this->seller->balance);
        $this->assertEquals(1, $this->seller->sales_count);
    }

    /** 購入者は発送アクションを実行できない（認可テスト） */
    public function test_buyer_cannot_mark_as_shipped(): void
    {
        $transaction = $this->createPaidTransaction();

        $this->actingAs($this->buyer)
            ->patch(route('transactions.updateStatus', $transaction), ['action' => 'shipped'])
            ->assertForbidden();
    }

    /** 出品者は受取確認できない（認可テスト） */
    public function test_seller_cannot_complete_transaction(): void
    {
        $transaction = $this->createPaidTransaction(['status' => 'shipped']);

        $this->actingAs($this->seller)
            ->patch(route('transactions.updateStatus', $transaction), ['action' => 'complete'])
            ->assertForbidden();
    }

    // ===== 5-4: 振込先口座 =====

    /** 振込先口座の登録 */
    public function test_user_can_register_bank_account(): void
    {
        $this->actingAs($this->buyer)
            ->post(route('bank-accounts.store'), [
                'bank_name'           => '三菱UFJ銀行',
                'branch_name'         => '渋谷支店',
                'account_type'        => 'ordinary',
                'account_number'      => '1234567',
                'account_holder_name' => 'ヤマダ タロウ',
            ])
            ->assertRedirect(route('bank-accounts.index'));

        $this->assertDatabaseHas('bank_accounts', [
            'user_id'   => $this->buyer->id,
            'bank_name' => '三菱UFJ銀行',
        ]);
    }

    /** 口座番号が7桁でない場合はバリデーションエラー */
    public function test_bank_account_number_must_be_7_digits(): void
    {
        $this->actingAs($this->buyer)
            ->post(route('bank-accounts.store'), [
                'bank_name'           => '三菱UFJ銀行',
                'branch_name'         => '渋谷支店',
                'account_type'        => 'ordinary',
                'account_number'      => '12345',  // 5桁（不正）
                'account_holder_name' => 'ヤマダ タロウ',
            ])
            ->assertSessionHasErrors('account_number');
    }

    /** 他ユーザーの口座は削除できない（認可テスト） */
    public function test_user_cannot_delete_others_bank_account(): void
    {
        $account = BankAccount::factory()->create(['user_id' => $this->seller->id]);

        $this->actingAs($this->buyer)
            ->delete(route('bank-accounts.destroy', $account))
            ->assertForbidden();
    }

    // ===== ヘルパーメソッド =====

    private function createPaidTransaction(array $overrides = []): Transaction
    {
        return Transaction::factory()->create(array_merge([
            'lure_id'             => $this->lure->id,
            'buyer_id'            => $this->buyer->id,
            'seller_id'           => $this->seller->id,
            'shipping_address_id' => $this->address->id,
            'price'               => 3000,
            'fee_rate'            => 0.100,
            'fee_amount'          => 300,
            'seller_earnings'     => 2700,
            'status'              => 'paid',
        ], $overrides));
    }

    /** 正常フロー: store と returnCallback の両方が成功するモック */
    private function mockPayjpSuccess(): void
    {
        $this->app->instance(PayjpService::class, new class extends PayjpService {
            public function __construct() {}
            public function createCheckoutSession(int $amount, string $productName, string $successUrl, string $cancelUrl): array
            {
                return ['success' => true, 'session_id' => 'cs_test_session_id', 'checkout_url' => 'https://checkout.pay.jp/test', 'error' => null];
            }
            public function getCheckoutSession(string $sessionId): array
            {
                return ['success' => true, 'status' => 'complete', 'flow_id' => 'pfw_test_flow_id', 'error' => null];
            }
        });
    }

    /** store のみ成功するモック（returnCallback のテスト前準備用） */
    private function mockPayjpSuccessForStore(): void
    {
        $this->app->instance(PayjpService::class, new class extends PayjpService {
            public function __construct() {}
            public function createCheckoutSession(int $amount, string $productName, string $successUrl, string $cancelUrl): array
            {
                return ['success' => true, 'session_id' => 'cs_test_session_id', 'checkout_url' => 'https://checkout.pay.jp/test', 'error' => null];
            }
            public function getCheckoutSession(string $sessionId): array
            {
                return ['success' => true, 'status' => 'complete', 'flow_id' => 'pfw_test_flow_id', 'error' => null];
            }
        });
    }

    /** returnCallback で Checkout Session が incomplete を返すモック */
    private function mockPayjpSessionIncomplete(): void
    {
        $this->app->instance(PayjpService::class, new class extends PayjpService {
            public function __construct() {}
            public function createCheckoutSession(int $amount, string $productName, string $successUrl, string $cancelUrl): array
            {
                return ['success' => true, 'session_id' => 'cs_test_session_id', 'checkout_url' => 'https://checkout.pay.jp/test', 'error' => null];
            }
            public function getCheckoutSession(string $sessionId): array
            {
                return ['success' => true, 'status' => 'open', 'flow_id' => null, 'error' => null];
            }
        });
    }

    /** 決済失敗フロー: createCheckoutSession がエラーを返すモック */
    private function mockPayjpFailure(): void
    {
        $this->app->instance(PayjpService::class, new class extends PayjpService {
            public function __construct() {}
            public function createCheckoutSession(int $amount, string $productName, string $successUrl, string $cancelUrl): array
            {
                return ['success' => false, 'session_id' => null, 'checkout_url' => null, 'error' => 'セッションの作成に失敗しました。'];
            }
        });
    }
}
