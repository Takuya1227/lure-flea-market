<?php

namespace Tests\Feature;

use App\Models\Lure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * フェーズ3: ルアー出品・管理テスト
 */
class LureTest extends TestCase
{
    use RefreshDatabase;

    private function validLureData(array $overrides = []): array
    {
        return array_merge([
            'category'            => 'bass',
            'model_name'          => 'テストミノー 100F',
            'brand_name_custom'   => 'テストブランド',
            'condition'           => 'like_new',
            'description'         => 'テスト用の商品説明文です。',
            'price'               => 2000,
            'shipping_cost_type'  => 'included',
            'shipping_days'       => '1_2_days',
            'shipping_prefecture' => '東京都',
            'form_action'         => 'publish',
        ], $overrides);
    }

    // ---- 正常な出品フロー ----

    public function test_authenticated_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('lures.create'))
            ->assertOk()
            ->assertViewIs('lures.create');
    }

    public function test_user_can_publish_a_lure(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('lures.store'), $this->validLureData());

        $response->assertRedirect();
        $this->assertDatabaseHas('lures', [
            'seller_id'  => $user->id,
            'model_name' => 'テストミノー 100F',
            'status'     => 'active',
        ]);
    }

    public function test_user_can_save_lure_as_draft(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post(route('lures.store'), [
            'category'    => 'bass',
            'form_action' => 'draft',
        ]);

        $this->assertDatabaseHas('lures', [
            'seller_id' => $user->id,
            'status'    => 'draft',
        ]);
    }

    public function test_user_can_upload_images_when_creating_lure(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // GDのJPEGサポートが無い環境でも動作するようPNGを使用
        $response = $this->actingAs($user)->post(route('lures.store'), array_merge(
            $this->validLureData(),
            ['images' => [
                UploadedFile::fake()->image('lure1.png', 100, 100),
                UploadedFile::fake()->image('lure2.png', 100, 100),
            ]]
        ));

        $lure = Lure::where('seller_id', $user->id)->first();
        $this->assertNotNull($lure);
        $this->assertCount(2, $lure->images);
    }

    // ---- 不正ファイルアップロードの拒否 ----

    public function test_non_image_mime_type_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // text/plainのMIMEタイプを持つファイルは画像バリデーションで弾かれる
        $fakeText = UploadedFile::fake()->create('document.txt', 100, 'text/plain');

        $response = $this->actingAs($user)->post(route('lures.store'), array_merge(
            $this->validLureData(),
            ['images' => [$fakeText]]
        ));

        // バリデーションエラーになること（imageルールが弾く）
        $response->assertSessionHasErrors('images.0');
    }

    public function test_php_file_disguised_as_image_is_rejected(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        // application/x-php MIMEタイプのファイルは弾かれる
        $fakePhp = UploadedFile::fake()->create('shell.php', 100, 'application/x-php');

        $response = $this->actingAs($user)->post(route('lures.store'), array_merge(
            $this->validLureData(),
            ['images' => [$fakePhp]]
        ));

        $response->assertSessionHasErrors('images.0');
    }

    // ---- 認可テスト ----

    public function test_user_cannot_edit_another_users_lure(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $lure = Lure::factory()->create(['seller_id' => $userA->id]);

        $this->actingAs($userB)
            ->get(route('lures.edit', $lure))
            ->assertForbidden();
    }

    public function test_user_cannot_update_another_users_lure(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $lure = Lure::factory()->create(['seller_id' => $userA->id]);

        $this->actingAs($userB)
            ->put(route('lures.update', $lure), $this->validLureData())
            ->assertForbidden();
    }

    public function test_user_cannot_delete_another_users_lure(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $lure = Lure::factory()->create(['seller_id' => $userA->id]);

        $this->actingAs($userB)
            ->delete(route('lures.destroy', $lure))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_cannot_access_create_form(): void
    {
        $this->get(route('lures.create'))->assertRedirect('/login');
    }

    // ---- バリデーション ----

    public function test_required_fields_are_validated_on_publish(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('lures.store'), [
            'category'    => 'bass',
            'form_action' => 'publish',
            // model_name, condition, description, price などを省略
        ]);

        $response->assertSessionHasErrors(['model_name', 'condition', 'description', 'price']);
    }

    // ---- 商品管理 ----

    public function test_user_can_suspend_their_lure(): void
    {
        $user = User::factory()->create();
        $lure = Lure::factory()->create(['seller_id' => $user->id, 'status' => 'active']);

        $this->actingAs($user)
            ->patch(route('lures.suspend', $lure))
            ->assertRedirect();

        $this->assertSame('suspended', $lure->fresh()->status);
    }

    public function test_user_can_resume_their_suspended_lure(): void
    {
        $user = User::factory()->create();
        $lure = Lure::factory()->create(['seller_id' => $user->id, 'status' => 'suspended']);

        $this->actingAs($user)
            ->patch(route('lures.resume', $lure))
            ->assertRedirect();

        $this->assertSame('active', $lure->fresh()->status);
    }

    public function test_user_can_mark_down_price(): void
    {
        $user = User::factory()->create();
        $lure = Lure::factory()->create(['seller_id' => $user->id, 'price' => 5000]);

        $this->actingAs($user)
            ->patch(route('lures.mark-down', $lure), ['price' => 3000])
            ->assertRedirect();

        $this->assertSame(3000, $lure->fresh()->price);
    }

    public function test_mark_down_must_be_lower_than_current_price(): void
    {
        $user = User::factory()->create();
        $lure = Lure::factory()->create(['seller_id' => $user->id, 'price' => 5000]);

        $response = $this->actingAs($user)
            ->patch(route('lures.mark-down', $lure), ['price' => 6000]);

        $response->assertSessionHasErrors('price');
    }

    public function test_user_can_soft_delete_their_lure(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $lure = Lure::factory()->create(['seller_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('lures.destroy', $lure))
            ->assertRedirect(route('lures.index'));

        $this->assertSame('deleted', $lure->fresh()->status);
    }
}
