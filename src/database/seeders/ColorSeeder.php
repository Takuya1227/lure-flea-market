<?php

namespace Database\Seeders;

use App\Models\Color;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $colors = [
            ['name' => 'チャートリュース', 'name_kana' => 'チャートリュース'],
            ['name' => 'ゴールド', 'name_kana' => 'ゴールド'],
            ['name' => 'シルバー', 'name_kana' => 'シルバー'],
            ['name' => 'ナチュラル', 'name_kana' => 'ナチュラル'],
            ['name' => 'ホワイト', 'name_kana' => 'ホワイト'],
            ['name' => 'ブラック', 'name_kana' => 'ブラック'],
            ['name' => 'レッド', 'name_kana' => 'レッド'],
            ['name' => 'ピンク', 'name_kana' => 'ピンク'],
            ['name' => 'ブルー', 'name_kana' => 'ブルー'],
            ['name' => 'グリーン', 'name_kana' => 'グリーン'],
            ['name' => 'パープル', 'name_kana' => 'パープル'],
            ['name' => 'オレンジ', 'name_kana' => 'オレンジ'],
            ['name' => 'イワシ', 'name_kana' => 'イワシ'],
            ['name' => 'アユ', 'name_kana' => 'アユ'],
            ['name' => 'ワカサギ', 'name_kana' => 'ワカサギ'],
            ['name' => 'レインボー', 'name_kana' => 'レインボー'],
            ['name' => 'クリア', 'name_kana' => 'クリア'],
            ['name' => 'ゴーストアユ', 'name_kana' => 'ゴーストアユ'],
            ['name' => 'マットチャート', 'name_kana' => 'マットチャート'],
            ['name' => 'パールホワイト', 'name_kana' => 'パールホワイト'],
        ];

        foreach ($colors as $color) {
            Color::firstOrCreate(['name' => $color['name']], array_merge($color, ['is_active' => true]));
        }
    }
}
