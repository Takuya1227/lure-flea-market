<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LureImage extends Model
{
    use HasFactory;

    protected $fillable = ['lure_id', 'image_path', 'display_order'];

    public function lure(): BelongsTo
    {
        return $this->belongsTo(Lure::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image_path);
    }
}
