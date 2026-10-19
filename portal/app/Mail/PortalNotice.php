<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** One plain, branded email for every portal notice: a subject, a few lines and one button. */
class PortalNotice extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $heading,
        public array $lines,
        public ?string $url = null,
        public ?string $button = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->heading.' | 3dPlateBuilder.com');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.notice', text: 'emails.notice-text');
    }
}
