<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 'payjp_flow_id', 'checkout_session_id', 'amount',
        'status', 'failure_reason', 'refund_amount', 'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'refunded_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
