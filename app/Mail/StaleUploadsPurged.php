<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StaleUploadsPurged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly int $uploadCount,
        public readonly int $mediaCount,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Weekly Temp Upload Cleanup Report',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.temp-uploads.purged',
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
