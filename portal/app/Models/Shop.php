<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shop extends Model
{
    public const STAGES = [
        'not_paid' => 'Not paid',
        'onboarding' => 'Onboarding',
        'in_build' => 'In build',
        'review' => 'Their review',
        'live' => 'Live',
        'cancelled' => 'Cancelled',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'products' => 'array',
            'font_files' => 'array',
            'current_period_end' => 'datetime',
            'setup_paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function addonRequests(): HasMany
    {
        return $this->hasMany(AddonRequest::class);
    }

    public function planConfig(): array
    {
        return config('portal.plans.'.$this->plan) ?? ['name' => ucfirst($this->plan), 'monthly' => 0, 'setup' => 0];
    }

    public function planName(): string
    {
        return $this->planConfig()['name'];
    }

    /** Recurring income from this shop, in pence a month (outright hosting spread over 12 months). */
    public function monthlyPence(): int
    {
        if (! $this->isPaying()) {
            return 0;
        }
        if ($this->plan === 'outright') {
            return intdiv(config('portal.outright.yearly'), 12);
        }

        return (int) ($this->planConfig()['monthly'] ?? 0);
    }

    public function isPaying(): bool
    {
        return $this->setup_paid_at !== null && $this->stage !== 'cancelled' && $this->stage !== 'not_paid';
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }

    public function hasFeature(string $from): bool
    {
        $order = ['starter' => 1, 'pro' => 2, 'complete' => 3, 'outright' => 3];

        return ($order[$this->plan] ?? 0) >= ($order[$from] ?? 99);
    }

    public function rnpsStatus(): string
    {
        $doc = $this->documents()->where('type', 'rnps')->latest()->first();

        return $doc ? $doc->status : 'missing';
    }

    public function overdueBalancePence(): int
    {
        return (int) $this->payments()->where('status', 'open')->where('due_at', '<', now())->sum('amount_pence');
    }

    /** The plate maker's to-do list, in order. */
    public function onboardingSteps(): array
    {
        $p = $this->products ?? [];

        return [
            ['key' => 'pay', 'title' => 'Pay your set-up fee', 'note' => $this->setup_paid_at ? 'Paid. Thank you.' : 'Your shop build starts once this is paid.', 'done' => (bool) $this->setup_paid_at, 'route' => 'billing'],
            ['key' => 'company', 'title' => 'Company details', 'note' => 'Company name, number and registered address.', 'done' => filled($this->legal_name) && filled($this->company_number) && filled($this->registered_address), 'route' => 'details'],
            ['key' => 'logo', 'title' => 'Upload your logo', 'note' => 'SVG or PNG, for your shop header and emails.', 'done' => filled($this->logo_path), 'route' => 'details', 'anchor' => 'logo'],
            ['key' => 'brand', 'title' => 'Brand colours and fonts', 'note' => 'We use these across your shop and builder.', 'done' => filled($this->accent_colour) && filled($this->dark_colour) && filled($this->heading_font), 'route' => 'details', 'anchor' => 'brand'],
            ['key' => 'contact', 'title' => 'Contact and supplier details', 'note' => 'Shown on your shop, and the supplier name and postcode on each plate.', 'done' => filled($this->contact_email) && filled($this->supplier_name) && filled($this->supplier_postcode) && filled($this->rnps_number), 'route' => 'details', 'anchor' => 'contact'],
            ['key' => 'products', 'title' => 'Choose products and set prices', 'note' => 'Tick what you sell and set your prices.', 'done' => ! empty($p['styles']) && ! empty($p['sizes']), 'started' => ! empty($p), 'route' => 'products'],
            ['key' => 'rnps', 'title' => 'RNPS documents', 'note' => match ($this->rnpsStatus()) {
                'approved' => 'Checked and approved.',
                'pending' => 'Uploaded. We\'ll check it shortly.',
                'rejected' => 'We couldn\'t accept this one. Please upload it again.',
                default => 'Proof of your DVLA RNPS registration, for us to check.',
            }, 'done' => $this->rnpsStatus() === 'approved', 'started' => $this->rnpsStatus() === 'pending', 'route' => 'details', 'anchor' => 'docs'],
        ];
    }

    public static function newLicenceKey(): string
    {
        do {
            $key = strtoupper(implode('-', str_split(Str::random(20), 5)));
        } while (static::where('licence_key', $key)->exists());

        return $key;
    }
}
