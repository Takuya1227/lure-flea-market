<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lure_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            // 同じユーザーが同じルアーに二重いいねできない
            $table->unique(['user_id', 'lure_id']);
            // いいね数カウントの高速化
            $table->index('lure_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
