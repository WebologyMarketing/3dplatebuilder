<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Release extends Model
{
    protected $guarded = ['id'];

    public static function latestRelease(): ?self
    {
        return static::all()->sort(fn ($a, $b) => version_compare($b->version, $a->version))->first();
    }
}
