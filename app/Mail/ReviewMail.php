<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReviewMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $reviewerName,
        public string $reviewerEmail,
        public ?string $reviewerRole,
        public string $reviewMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Review from '.$this->reviewerName,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.review',
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
