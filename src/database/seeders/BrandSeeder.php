<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'メガバス', 'name_kana' => 'メガバス'],
            ['name' => 'シマノ', 'name_kana' => 'シマノ'],
            ['name' => 'ダイワ', 'name_kana' => 'ダイワ'],
            ['name' => 'バスデイ', 'name_kana' => 'バスデイ'],
            ['name' => 'DUO', 'name_kana' => 'デュオ'],
            ['name' => 'ジャッカル', 'name_kana' => 'ジャッカル'],
            ['name' => 'O.S.P', 'name_kana' => 'オーエスピー'],
            ['name' => 'エバーグリーン', 'name_kana' => 'エバーグリーン'],
            ['name' => 'デプス', 'name_kana' => 'デプス'],
            ['name' => 'ティムコ', 'name_kana' => 'ティムコ'],
            ['name' => 'スミス', 'name_kana' => 'スミス'],
            ['name' => 'ラッキークラフト', 'name_kana' => 'ラッキークラフト'],
            ['name' => 'ノリーズ', 'name_kana' => 'ノリーズ'],
            ['name' => 'ガンクラフト', 'name_kana' => 'ガンクラフト'],
            ['name' => 'ブルーブルー', 'name_kana' => 'ブルーブルー'],
            ['name' => 'アイマ', 'name_kana' => 'アイマ'],
            ['name' => 'マングローブスタジオ', 'name_kana' => 'マングローブスタジオ'],
            ['name' => 'ジップベイツ', 'name_kana' => 'ジップベイツ'],
            ['name' => 'ラパラ', 'name_kana' => 'ラパラ'],
            ['name' => '邪道', 'name_kana' => 'ジャドウ'],
            ['name' => 'アムズデザイン（ima）', 'name_kana' => 'アムズデザイン'],
            ['name' => 'ヤマリア', 'name_kana' => 'ヤマリア'],
            ['name' => 'マリア', 'name_kana' => 'マリア'],
            ['name' => 'バレーヒル', 'name_kana' => 'バレーヒル'],
            ['name' => 'ハンクル', 'name_kana' => 'ハンクル'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(['name' => $brand['name']], array_merge($brand, ['is_active' => true]));
        }
    }
}
