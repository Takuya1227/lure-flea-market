<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lure_id')->constrained()->restrictOnDelete();
            $table->foreignId('buyer_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('seller_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('shipping_address_id')->constrained()->restrictOnDelete();

            // 取引時の金額を確定値として記録（後から変更されても履歴が残る）
            $table->unsignedInteger('price');
            $table->decimal('fee_rate', 4, 3)->default(0.100);
            $table->unsignedInteger('fee_amount');
            $table->unsignedInteger('seller_earnings');

            $table->enum('status', [
                'pending_payment',
                'paid',
                'preparing_shipment',
                'shipped',
                'delivered',
                'completed',
                'cancelled',
                'disputed',
            ])->default('pending_payment');

            $table->enum('cancelled_by', ['buyer', 'seller', 'admin'])->nullable();
            $table->text('cancel_reason')->nullable();

            // 発送後一定期間で自動取引完了
            $table->timestamp('auto_complete_at')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index('buyer_id');
            $table->index('seller_id');
            $table->index('lure_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
