<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    public const STATUSES = [
        'new' => 'New',
        'waiting_on_us' => 'Waiting on us',
        'waiting_on_them' => 'Waiting on you',
        'closed' => 'Closed',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['last_reply_at' => 'datetime'];
    }

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->oldest();
    }

    /** Status wording depends on who's reading: "waiting on them" is "waiting on you" to the customer. */
    public function statusLabel(bool $forAdmin = false): string
    {
        if ($forAdmin) {
            return ['new' => 'New', 'waiting_on_us' => 'Waiting on you', 'waiting_on_them' => 'Waiting on them', 'closed' => 'Closed'][$this->status] ?? $this->status;
        }

        return ['new' => 'Sent', 'waiting_on_us' => 'With us', 'waiting_on_them' => 'Waiting on you', 'closed' => 'Closed'][$this->status] ?? $this->status;
    }
}
