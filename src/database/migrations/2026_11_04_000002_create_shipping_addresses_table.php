<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);           // 受取人氏名
            $table->string('postal_code', 8);     // 郵便番号（例: 123-4567）
            $table->string('prefecture', 10);     // 都道府県
            $table->string('city', 50);           // 市区町村
            $table->string('address_line1', 100); // 番地
            $table->string('address_line2', 100)->nullable(); // 建物名・部屋番号
            $table->string('phone_number', 15);   // 電話番号
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};
