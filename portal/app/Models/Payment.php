<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['due_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function scopeOverdue(Builder $q): Builder
    {
        return $q->where('status', 'open')->where('due_at', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'open' && $this->due_at && $this->due_at->isPast();
    }
}
