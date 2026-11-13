<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingAddress extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'postal_code',
        'prefecture',
        'city',
        'address_line1',
        'address_line2',
        'phone_number',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 1行で住所を返すヘルパー（画面表示用）
    public function getFullAddressAttribute(): string
    {
        $parts = [
            '〒' . $this->postal_code,
            $this->prefecture . $this->city . $this->address_line1,
            $this->address_line2,
        ];

        return implode(' ', array_filter($parts));
    }
}
