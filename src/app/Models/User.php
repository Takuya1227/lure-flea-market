<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BankAccount;
use App\Models\Lure;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// MustVerifyEmail を implements することで、登録後にメール確認が必須になる
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'profile_image_path',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'   => 'datetime',
            'identity_verified_at' => 'datetime',
            'password'            => 'hashed',
            'is_banned'           => 'boolean',
            'rating_avg'          => 'float',
        ];
    }

    public function shippingAddresses(): HasMany
    {
        return $this->hasMany(ShippingAddress::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'buyer_id');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function lures(): HasMany
    {
        return $this->hasMany(Lure::class, 'seller_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(\App\Models\Favorite::class);
    }

    // 表示名：ニックネームがあればそちらを優先
    public function getDisplayNameAttribute(): string
    {
        return $this->nickname ?? $this->name;
    }
}
