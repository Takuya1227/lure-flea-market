<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_number', 10)->nullable()->after('id');
        });

        // 既存レコードに採番（主にシードデータ向け）
        foreach (DB::table('transactions')->whereNull('order_number')->get() as $row) {
            do {
                $code = static::generate();
            } while (DB::table('transactions')->where('order_number', $code)->exists());

            DB::table('transactions')->where('id', $row->id)->update(['order_number' => $code]);
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_number', 10)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique(['order_number']);
            $table->dropColumn('order_number');
        });
    }

    private static function generate(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // 紛らわしい I/O/0/1 を除外
        $result = '';
        for ($i = 0; $i < 10; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
};
