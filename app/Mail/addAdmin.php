<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class addAdmin extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $name;
    public $password;
    public $url;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $email, $password, $url)
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->url = $url;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'POMIC STAFF LOGIN CREDENTIALS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.addAdmin',
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
