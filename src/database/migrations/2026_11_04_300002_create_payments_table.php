<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->unique()->constrained()->cascadeOnDelete();
            // PAY.JPから返却されるcharge IDのみ保存。カード情報は一切保存しない
            $table->string('payjp_charge_id', 255)->nullable();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->string('failure_reason', 255)->nullable();
            $table->unsignedInteger('refund_amount')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
