<?php

namespace App\Mail;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyReportedTivuchFee extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Property $property) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.reported_tivuch_fee.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.property.reported-tivuch-fee',
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
