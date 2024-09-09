<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class sendMessage extends Mailable
{
    use Queueable, SerializesModels;

    public $name;
    public $sender_email;
    public $phone_number;
    public $sent_message;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $sender_email, $sent_message, $phone_number)
    {
        $this->name = $name;
        $this->sender_email = $sender_email;
        $this->sent_message = $sent_message;
        $this->phone_number = $phone_number;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'POMIC WEBSITE MAIL - Mail From Website',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.form_email',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
