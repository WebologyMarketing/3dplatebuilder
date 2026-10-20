<?php

namespace App\Mail;

use App\Models\Shop;
use App\Support\EmailSettings;
use App\Support\SiteConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * "Send me a test": the plate maker's order email or review request, with their wording,
 * logo-free brand colours and an example order, so they can read it as a customer would.
 */
class CustomerEmailTest extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Shop $shop, public string $which, public array $e) {}

    public function envelope(): Envelope
    {
        $name = $this->shop->trading_name ?: $this->shop->name;

        return new Envelope(subject: '[Test] '.($this->which === 'review'
            ? EmailSettings::fill($this->e['review_heading'], $this->shop)
            : "Your {$name} order #1042"));
    }

    public function content(): Content
    {
        $accent = $this->shop->accent_colour ?: '#F7D333';

        return new Content(view: 'emails.customer-test', with: [
            'name' => $this->shop->trading_name ?: $this->shop->name,
            'accent' => $accent,
            'ink' => SiteConfig::inkFor($accent),
            'dark' => $this->shop->dark_colour ?: '#16191C',
            't' => fn (string $key) => EmailSettings::fill((string) $this->e[$key], $this->shop),
        ]);
    }
}
