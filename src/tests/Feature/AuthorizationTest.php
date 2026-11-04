<?php

namespace Tests\Feature;

use App\Models\ShippingAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 認証・認可のセキュリティテスト
 * 「未ログインユーザーが保護ページにアクセスできないか」
 * 「他ユーザーのデータを操作できないか」を検証する
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    // ---- 未認証ユーザーのアクセス拒否 ----

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $this->get('/profile')->assertRedirect('/login');
    }

    public function test_unauthenticated_user_cannot_access_shipping_addresses(): void
    {
        $this->get('/shipping-addresses')->assertRedirect('/login');
    }

    // ---- 他ユーザーの住所への不正アクセス（認可テスト）----

    public function test_user_cannot_edit_another_users_shipping_address(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // userA の住所を作成
        $address = ShippingAddress::factory()->create(['user_id' => $userA->id]);

        // userB としてログインして userA の住所を編集しようとする → 403
        $response = $this->actingAs($userB)->patch("/shipping-addresses/{$address->id}", [
            'name'          => 'Hacked',
            'postal_code'   => '100-0001',
            'prefecture'    => '東京都',
            'city'          => '千代田区',
            'address_line1' => '1-1',
            'phone_number'  => '090-0000-0000',
        ]);

        $response->assertForbidden(); // 403
        // userA の住所は変わっていない
        $this->assertSame($address->name, $address->fresh()->name);
    }

    public function test_user_cannot_delete_another_users_shipping_address(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $address = ShippingAddress::factory()->create(['user_id' => $userA->id]);

        // userB が userA の住所を削除しようとする → 403
        $this->actingAs($userB)
            ->delete("/shipping-addresses/{$address->id}")
            ->assertForbidden();

        // 住所はまだ存在している
        $this->assertDatabaseHas('shipping_addresses', ['id' => $address->id]);
    }

    public function test_user_cannot_view_edit_form_of_another_users_shipping_address(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $address = ShippingAddress::factory()->create(['user_id' => $userA->id]);

        // userB が userA の住所編集フォームを開こうとする → 403
        $this->actingAs($userB)
            ->get("/shipping-addresses/{$address->id}/edit")
            ->assertForbidden();
    }
}
