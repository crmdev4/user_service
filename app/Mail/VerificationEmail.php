<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $token;
    public $verificationUrl;
    public $employee;

    public function __construct($token, $verificationUrl, $employee)
    {
        $this->token = $token;
        $this->verificationUrl = $verificationUrl;
        $this->employee = $employee;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verification Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email',
            with: [
                'url' => $this->verificationUrl,
                'token' => $this->token,
                'employee' => $this->employee,
            ],
        );
    }
}
