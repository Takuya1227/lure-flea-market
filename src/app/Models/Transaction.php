<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'lure_id', 'buyer_id', 'seller_id', 'shipping_address_id',
        'price', 'fee_rate', 'fee_amount', 'seller_earnings',
        'status', 'cancelled_by', 'cancel_reason',
        'auto_complete_at', 'paid_at', 'shipped_at', 'delivered_at',
        'completed_at', 'cancelled_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            $transaction->order_number = static::generateOrderNumber();
        });
    }

    public static function generateOrderNumber(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // I/O/0/1 は視認性の問題で除外
        do {
            $code = '';
            for ($i = 0; $i < 10; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (static::where('order_number', $code)->exists());

        return $code;
    }

    // ルートモデルバインディングを order_number で行う（URL: /transactions/A3K9P2XQ7M）
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    protected function casts(): array
    {
        return [
            'fee_rate'         => 'decimal:3',
            'auto_complete_at' => 'datetime',
            'paid_at'          => 'datetime',
            'shipped_at'       => 'datetime',
            'delivered_at'     => 'datetime',
            'completed_at'     => 'datetime',
            'cancelled_at'     => 'datetime',
        ];
    }

    // ---- リレーション ----

    public function lure(): BelongsTo
    {
        return $this->belongsTo(Lure::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(ShippingAddress::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    // ---- スコープ ----

    public function scopeForBuyer($query, int $userId)
    {
        return $query->where('buyer_id', $userId);
    }

    public function scopeForSeller($query, int $userId)
    {
        return $query->where('seller_id', $userId);
    }

    // ---- ヘルパー ----

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending_payment' => '支払待ち',
            'paid'            => '支払い完了',
            'shipped'         => '発送完了',
            'review_pending'  => 'レビュー待ち',
            'completed'       => '取引完了',
            'cancelled'       => 'キャンセル',
            'disputed'        => 'トラブル対応中',
            default           => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending_payment' => 'yellow',
            'paid'            => 'blue',
            'shipped'         => 'indigo',
            'review_pending'  => 'purple',
            'completed'       => 'green',
            'cancelled'       => 'gray',
            'disputed'        => 'red',
            default           => 'gray',
        };
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, ['pending_payment', 'paid']);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
