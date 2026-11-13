<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lure_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lure_id')->constrained('lures')->cascadeOnDelete();
            $table->string('image_path', 255);
            $table->tinyInteger('display_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['lure_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lure_images');
    }
};
