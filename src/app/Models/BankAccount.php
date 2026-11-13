<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bank_name', 'branch_name',
        'account_type', 'account_number', 'account_holder_name',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function getAccountTypeLabelAttribute(): string
    {
        return $this->account_type === 'ordinary' ? '普通' : '当座';
    }

    // 口座番号は末尾4桁のみ表示（個人情報保護）
    public function getMaskedAccountNumberAttribute(): string
    {
        return str_repeat('*', max(0, strlen($this->account_number) - 4))
            . substr($this->account_number, -4);
    }
}
