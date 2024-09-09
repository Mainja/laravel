<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class addStudent extends Mailable
{
    use Queueable, SerializesModels;

    public $computer_number;
    public $name;
    public $url;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $computer_number, $url)
    {
        $this->name = $name;
        $this->computer_number = $computer_number;
        $this->url = $url;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'STUDENT PORTAL LOGIN CREDENTIALS',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.addStudent',
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
