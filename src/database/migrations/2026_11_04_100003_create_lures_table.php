<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lures', function (Blueprint $table) {
            $table->id();

            // 出品者・ブランド
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('brand_name_custom', 100)->nullable();
            $table->string('model_name', 100)->nullable();
            $table->enum('category', ['bass', 'trout', 'salt']);
            $table->string('lure_type', 50)->nullable();

            // コンディション
            $table->enum('condition', ['new_unopened', 'new_opened', 'like_new', 'good', 'fair', 'poor'])->nullable();
            $table->boolean('is_handmade')->default(false);
            $table->boolean('is_discontinued')->default(false);
            $table->boolean('is_limited')->default(false);

            // 外観・スペック
            $table->foreignId('color_id')->nullable()->constrained('colors')->nullOnDelete();
            $table->string('color_name_custom', 100)->nullable();
            $table->smallInteger('size_mm')->unsigned()->nullable();
            $table->decimal('weight_g', 6, 1)->nullable();
            $table->enum('material', ['plastic', 'wood', 'metal', 'rubber', 'other'])->nullable();

            // アクション・特性
            $table->enum('action', ['wobbling', 'rolling', 'wobble_and_roll', 'slide', 'i_shaped', 'no_action', 'other'])->nullable();
            $table->enum('buoyancy', ['floating', 'suspending', 'sinking'])->nullable();
            $table->enum('range', ['surface', 'shallow', 'middle', 'deep', 'bottom'])->nullable();

            // フック・付属品
            $table->string('hook_size', 10)->nullable();
            $table->enum('hook_condition', ['new', 'replaced', 'used', 'rusty'])->nullable();
            $table->string('accessories', 255)->nullable();

            // カテゴリ別詳細属性（JSON）
            $table->json('category_attributes')->nullable();

            // 商品説明・価格
            $table->text('description')->nullable();
            $table->unsignedInteger('price')->nullable();

            // 配送情報
            $table->enum('shipping_cost_type', ['included', 'cash_on_delivery'])->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->enum('shipping_days', ['1_2_days', '3_7_days', 'over_7_days'])->nullable();
            $table->string('shipping_prefecture', 10)->nullable();

            // ステータス
            $table->enum('status', ['draft', 'active', 'suspended', 'sold', 'deleted'])->default('draft');

            // カウンター
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('favorites_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);

            // 日時
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            // インデックス
            $table->index('seller_id');
            $table->index('status');
            $table->index('category');
            $table->index('price');
            $table->index(['category', 'status', 'price']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lures');
    }
};
