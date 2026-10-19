<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AddonRequest extends Model
{
    protected $guarded = ['id'];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function name(): string
    {
        return config('portal.addons.'.$this->addon.'.name', $this->addon);
    }
}
