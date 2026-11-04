<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Lure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * フェーズ4: 検索・閲覧機能テスト
 */
class SearchTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveLure(array $attrs = []): Lure
    {
        $seller = User::factory()->create();

        return Lure::factory()->create(array_merge([
            'seller_id'          => $seller->id,
            'status'             => 'active',
            'category'           => 'bass',
            'model_name'         => 'テストミノー',
            'brand_name_custom'  => 'テストブランド',
            'condition'          => 'like_new',
            'price'              => 2000,
            'shipping_cost_type' => 'included',
            'shipping_days'      => '1_2_days',
            'shipping_prefecture'=> '東京都',
        ], $attrs));
    }

    // ---- 商品一覧（トップ / 検索ページ） ----

    public function test_guest_can_view_top_page(): void
    {
        $this->get(route('home'))->assertOk();
    }

    public function test_guest_can_view_search_page(): void
    {
        $this->get(route('search.index'))->assertOk();
    }

    public function test_active_lures_appear_in_search_results(): void
    {
        $lure = $this->createActiveLure(['model_name' => 'フラットサイドクランク']);

        $this->get(route('search.index'))
            ->assertOk()
            ->assertSee('フラットサイドクランク');
    }

    public function test_deleted_lures_do_not_appear_in_search(): void
    {
        $this->createActiveLure(['model_name' => '削除済み商品', 'status' => 'deleted']);

        $this->get(route('search.index'))
            ->assertOk()
            ->assertDontSee('削除済み商品');
    }

    public function test_draft_lures_do_not_appear_in_search(): void
    {
        $this->createActiveLure(['model_name' => '下書き商品', 'status' => 'draft']);

        $this->get(route('search.index'))
            ->assertOk()
            ->assertDontSee('下書き商品');
    }

    // ---- キーワード検索 ----

    public function test_keyword_search_filters_by_model_name(): void
    {
        $this->createActiveLure(['model_name' => 'フラッシュミノー100F']);
        $this->createActiveLure(['model_name' => 'ジャークベイト']);

        $res = $this->get(route('search.index', ['q' => 'フラッシュ']));

        $res->assertOk()
            ->assertSee('フラッシュミノー100F')
            ->assertDontSee('ジャークベイト');
    }

    public function test_keyword_search_filters_by_brand(): void
    {
        $this->createActiveLure(['model_name' => '商品A', 'brand_name_custom' => 'ジャッカル']);
        $this->createActiveLure(['model_name' => '商品B', 'brand_name_custom' => 'ダイワ']);

        $res = $this->get(route('search.index', ['q' => 'ジャッカル']));

        $res->assertOk()
            ->assertSee('商品A')
            ->assertDontSee('商品B');
    }

    public function test_keyword_search_matches_brand_english_name(): void
    {
        $seller = User::factory()->create();
        $brand  = \App\Models\Brand::create(['name' => 'ジャッカル', 'name_en' => 'JACKALL', 'is_active' => true]);

        \App\Models\Lure::factory()->create([
            'seller_id' => $seller->id,
            'status'    => 'active',
            'brand_id'  => $brand->id,
            'model_name'=> 'ポッパー60',
            'condition' => 'like_new',
            'price'     => 1500,
            'shipping_cost_type'  => 'included',
            'shipping_days'       => '1_2_days',
            'shipping_prefecture' => '東京都',
        ]);
        $this->createActiveLure(['model_name' => '無関係な商品', 'brand_name_custom' => 'ダイワ']);

        // 英語表記で検索してもヒットする
        $res = $this->get(route('search.index', ['q' => 'jackall']));

        $res->assertOk()
            ->assertSee('ポッパー60')
            ->assertDontSee('無関係な商品');
    }

    public function test_space_separated_keywords_work_as_and_search(): void
    {
        $this->createActiveLure(['model_name' => 'ジャッカルミノー', 'brand_name_custom' => 'ジャッカル']);
        $this->createActiveLure(['model_name' => 'ジャッカルクランク', 'brand_name_custom' => 'ジャッカル']);
        $this->createActiveLure(['model_name' => 'ダイワミノー', 'brand_name_custom' => 'ダイワ']);

        // 「ジャッカル ミノー」→ 両方含む1件のみヒット
        $res = $this->get(route('search.index', ['q' => 'ジャッカル ミノー']));

        $res->assertOk()
            ->assertSee('ジャッカルミノー')
            ->assertDontSee('ジャッカルクランク')
            ->assertDontSee('ダイワミノー');
    }

    public function test_fullwidth_space_also_works_as_and_separator(): void
    {
        $this->createActiveLure(['model_name' => 'ジャッカルミノー', 'brand_name_custom' => 'ジャッカル']);
        $this->createActiveLure(['model_name' => 'ジャッカルクランク', 'brand_name_custom' => 'ジャッカル']);

        // 全角スペースでも同様に動作する
        $res = $this->get(route('search.index', ['q' => 'ジャッカル　ミノー']));

        $res->assertOk()
            ->assertSee('ジャッカルミノー')
            ->assertDontSee('ジャッカルクランク');
    }

    // ---- カテゴリフィルター ----

    public function test_category_filter_returns_correct_category(): void
    {
        $this->createActiveLure(['model_name' => 'バス用ルアー', 'category' => 'bass']);
        $this->createActiveLure(['model_name' => 'ソルト用ルアー', 'category' => 'salt']);

        $res = $this->get(route('search.index', ['category' => 'bass']));

        $res->assertOk()
            ->assertSee('バス用ルアー')
            ->assertDontSee('ソルト用ルアー');
    }

    public function test_invalid_category_is_ignored(): void
    {
        // 不正なカテゴリを渡してもエラーにならない
        $this->get(route('search.index', ['category' => 'invalid_category']))
            ->assertOk();
    }

    // ---- 価格帯フィルター ----

    public function test_price_min_filter(): void
    {
        $this->createActiveLure(['model_name' => '安い商品', 'price' => 500]);
        $this->createActiveLure(['model_name' => '高い商品', 'price' => 5000]);

        $res = $this->get(route('search.index', ['price_min' => 1000]));

        $res->assertOk()
            ->assertSee('高い商品')
            ->assertDontSee('安い商品');
    }

    public function test_price_max_filter(): void
    {
        $this->createActiveLure(['model_name' => '安い商品', 'price' => 500]);
        $this->createActiveLure(['model_name' => '高い商品', 'price' => 5000]);

        $res = $this->get(route('search.index', ['price_max' => 1000]));

        $res->assertOk()
            ->assertSee('安い商品')
            ->assertDontSee('高い商品');
    }

    // ---- ゼロ件表示 ----

    public function test_empty_results_show_appropriate_message(): void
    {
        $this->get(route('search.index', ['q' => '存在しない商品名XYZ']))
            ->assertOk()
            ->assertSee('該当する商品が見つかりませんでした');
    }

    // ---- SQLインジェクション対策 ----

    public function test_sql_injection_in_keyword_is_handled_safely(): void
    {
        $this->createActiveLure(['model_name' => '正常な商品']);

        // 不正な SQL 文字列を渡してもエラーにならず、安全に処理される
        $this->get(route('search.index', ['q' => "' OR '1'='1"]))
            ->assertOk()
            ->assertDontSee('正常な商品'); // AND 条件なので一致しない
    }

    public function test_sql_injection_in_price_is_handled_safely(): void
    {
        // 数値以外を価格に渡してもエラーにならない
        $this->get(route('search.index', ['price_min' => 'abc; DROP TABLE lures;--']))
            ->assertOk();
    }

    // ---- 商品詳細 ----

    public function test_guest_can_view_lure_detail(): void
    {
        $lure = $this->createActiveLure(['model_name' => '詳細ページのルアー']);

        $this->get(route('lures.show', $lure))
            ->assertOk()
            ->assertSee('詳細ページのルアー');
    }

    public function test_deleted_lure_detail_returns_404(): void
    {
        $lure = $this->createActiveLure(['status' => 'deleted']);

        $this->get(route('lures.show', $lure))->assertNotFound();
    }
}
