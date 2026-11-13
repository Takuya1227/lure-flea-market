<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name', 50);
            $table->string('branch_name', 50);
            $table->enum('account_type', ['ordinary', 'checking'])->default('ordinary');
            $table->string('account_number', 10);
            // 口座名義はカナ（金融機関の仕様に合わせる）
            $table->string('account_holder_name', 50);
            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
