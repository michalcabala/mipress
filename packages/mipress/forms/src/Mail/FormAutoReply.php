<?php

declare(strict_types=1);

namespace MiPress\Forms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;

class FormAutoReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Form $form,
        public FormSubmission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: (string) ($this->form->auto_reply_subject ?: 'Dekuujeme za zpravu'));
    }

    public function content(): Content
    {
        return new Content(view: 'mipress-forms::mail.auto-reply');
    }
}
