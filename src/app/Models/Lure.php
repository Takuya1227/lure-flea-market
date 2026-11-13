<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Favorite;

class Lure extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id', 'brand_id', 'brand_name_custom', 'model_name',
        'category', 'lure_type', 'condition',
        'is_handmade', 'is_discontinued', 'is_limited',
        'color_id', 'color_name_custom', 'size_mm', 'weight_g',
        'material', 'action', 'buoyancy', 'ranges', 'video_path',
        'hook_size', 'hook_condition', 'accessories',
        'category_attributes',
        'description', 'price',
        'shipping_cost_type', 'shipping_method', 'shipping_days', 'shipping_prefecture',
        'status', 'sold_at',
    ];

    protected function casts(): array
    {
        return [
            'category_attributes' => 'array',
            'ranges'              => 'array',
            'is_handmade'         => 'boolean',
            'is_discontinued'     => 'boolean',
            'is_limited'          => 'boolean',
            'sold_at'             => 'datetime',
        ];
    }

    // ---- リレーション ----

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(LureImage::class)->orderBy('display_order');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }

    // ---- スコープ ----

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForSeller($query, int $sellerId)
    {
        return $query->where('seller_id', $sellerId)
                     ->whereNotIn('status', ['deleted']);
    }

    // ---- アクセサ ----

    // ブランド表示名（マスター選択 or 自由入力）
    public function getBrandDisplayNameAttribute(): string
    {
        return $this->brand?->name ?? $this->brand_name_custom ?? '不明';
    }

    // カラー表示名（マスター選択 or 自由入力）
    public function getColorDisplayNameAttribute(): string
    {
        return $this->color?->name ?? $this->color_name_custom ?? '';
    }

    // カテゴリ表示名
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            'bass'  => 'バス',
            'trout' => 'トラウト',
            'salt'  => 'ソルト',
            default => $this->category,
        };
    }

    // コンディション表示名
    public function getConditionLabelAttribute(): string
    {
        return match($this->condition) {
            'new_unopened' => '新品未開封',
            'new_opened'   => '新品開封済み',
            'like_new'     => '未使用に近い',
            'good'         => '目立った傷なし',
            'fair'         => 'やや傷あり',
            'poor'         => '傷・汚れあり',
            default        => $this->condition,
        };
    }

    // ステータス表示名
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draft'     => '下書き',
            'active'    => '出品中',
            'suspended' => '一時停止',
            'sold'      => '売却済み',
            'deleted'   => '削除済み',
            default     => $this->status,
        };
    }

    // サムネイル（1枚目の画像）
    public function getThumbnailUrlAttribute(): ?string
    {
        $first = $this->images->first();
        return $first?->url;
    }

    // 下書きか
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    // 出品中か
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // 編集可能か（売却済み・削除済みは不可）
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'active', 'suspended']);
    }
}
