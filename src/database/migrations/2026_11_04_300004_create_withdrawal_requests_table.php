<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdrawal_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->timestamp('processed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_requests');
    }
};
