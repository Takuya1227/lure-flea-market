<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bank_account_id', 'amount', 'status', 'processed_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'    => '申請中',
            'processing' => '処理中',
            'completed'  => '振込完了',
            'rejected'   => '却下',
            default      => $this->status,
        };
    }
}
