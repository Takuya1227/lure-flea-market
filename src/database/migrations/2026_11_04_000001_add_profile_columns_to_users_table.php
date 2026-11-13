<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // nameカラムの後にnicknameを追加（表示名・ユニーク制約）
            $table->string('nickname', 50)->unique()->nullable()->after('name');
            $table->string('profile_image_path', 255)->nullable()->after('email_verified_at');
            $table->text('bio')->nullable()->after('profile_image_path');

            // 評価・実績カウンター（レビュー投稿時に更新）
            $table->decimal('rating_avg', 3, 2)->nullable()->after('bio');
            $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            $table->unsignedInteger('sales_count')->default(0)->after('rating_count');

            // 売上残高（円）
            $table->unsignedInteger('balance')->default(0)->after('sales_count');

            // 本人確認（将来のeKYC連携用に今から用意）
            $table->enum('kyc_status', ['unverified', 'pending', 'verified'])->default('unverified')->after('balance');
            $table->timestamp('identity_verified_at')->nullable()->after('kyc_status');

            // 管理者によるBANフラグ
            $table->boolean('is_banned')->default(false)->after('identity_verified_at');

            // 退会対応のソフトデリート
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nickname',
                'profile_image_path',
                'bio',
                'rating_avg',
                'rating_count',
                'sales_count',
                'balance',
                'kyc_status',
                'identity_verified_at',
                'is_banned',
                'deleted_at',
            ]);
        });
    }
};
