<?php

namespace App\Mail;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PropertyTakenWarning extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Property $property) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your property listing will be marked as taken soon',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.property.taken-warning',
            with: [
                'property' => $this->property,
                'daysUntilTaken' => 3,
            ],
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
