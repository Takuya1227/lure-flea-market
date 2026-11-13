<?php

namespace Tests\Feature;

use App\Models\Favorite;
use App\Models\Lure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * フェーズ4: お気に入り（いいね）機能テスト
 */
class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveLure(?User $seller = null): Lure
    {
        $seller ??= User::factory()->create();

        return Lure::factory()->create([
            'seller_id'          => $seller->id,
            'status'             => 'active',
            'category'           => 'bass',
            'model_name'         => 'テストルアー',
            'brand_name_custom'  => 'テストブランド',
            'condition'          => 'like_new',
            'price'              => 2000,
            'shipping_cost_type' => 'included',
            'shipping_days'      => '1_2_days',
            'shipping_prefecture'=> '東京都',
        ]);
    }

    // ---- いいね追加 ----

    public function test_authenticated_user_can_favorite_a_lure(): void
    {
        $user = User::factory()->create();
        $lure = $this->createActiveLure();

        $res = $this->actingAs($user)
            ->postJson(route('lures.favorite.toggle', $lure));

        $res->assertOk()
            ->assertJson(['favorited' => true, 'count' => 1]);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'lure_id' => $lure->id,
        ]);
    }

    // ---- いいね解除（トグル） ----

    public function test_favorite_toggles_off_when_already_favorited(): void
    {
        $user = User::factory()->create();
        $lure = $this->createActiveLure();

        // まず追加
        Favorite::create(['user_id' => $user->id, 'lure_id' => $lure->id]);

        // トグルで解除
        $res = $this->actingAs($user)
            ->postJson(route('lures.favorite.toggle', $lure));

        $res->assertOk()
            ->assertJson(['favorited' => false, 'count' => 0]);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'lure_id' => $lure->id,
        ]);
    }

    // ---- 未認証ユーザー ----

    public function test_guest_cannot_favorite_a_lure(): void
    {
        $lure = $this->createActiveLure();

        // 未認証ユーザーは 302 リダイレクト（login へ）
        $this->post(route('lures.favorite.toggle', $lure))
            ->assertRedirect(route('login'));
    }

    // ---- 削除済み・非公開商品 ----

    public function test_cannot_favorite_a_deleted_lure(): void
    {
        $user = User::factory()->create();
        $lure = $this->createActiveLure();
        $lure->update(['status' => 'deleted']);

        $this->actingAs($user)
            ->postJson(route('lures.favorite.toggle', $lure))
            ->assertNotFound();
    }

    public function test_cannot_favorite_a_suspended_lure(): void
    {
        $user = User::factory()->create();
        $lure = $this->createActiveLure();
        $lure->update(['status' => 'suspended']);

        $this->actingAs($user)
            ->postJson(route('lures.favorite.toggle', $lure))
            ->assertNotFound();
    }

    // ---- いいね一覧 ----

    public function test_authenticated_user_can_view_favorites_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertViewIs('favorites.index');
    }

    public function test_favorites_index_shows_favorited_lures(): void
    {
        $user = User::factory()->create();
        $lure = $this->createActiveLure();

        Favorite::create(['user_id' => $user->id, 'lure_id' => $lure->id]);

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee($lure->model_name);
    }

    public function test_favorites_index_does_not_show_other_users_favorites(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $lure  = $this->createActiveLure();

        // user2 のいいね
        Favorite::create(['user_id' => $user2->id, 'lure_id' => $lure->id]);

        // user1 のいいね一覧には表示されない
        $this->actingAs($user1)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertDontSee($lure->model_name);
    }

    public function test_guest_cannot_view_favorites_index(): void
    {
        $this->get(route('favorites.index'))
            ->assertRedirect(route('login'));
    }

    // ---- いいね数の正確性 ----

    public function test_favorite_count_reflects_all_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $lure  = $this->createActiveLure();

        Favorite::create(['user_id' => $user1->id, 'lure_id' => $lure->id]);

        $res = $this->actingAs($user2)
            ->postJson(route('lures.favorite.toggle', $lure));

        // user1 と user2 の 2件になる
        $res->assertJson(['count' => 2]);
    }
}
