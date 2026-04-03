<?php

declare(strict_types=1);

namespace MiPress\Forms\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;

class FormSubmissionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Form $form,
        public FormSubmission $submission,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nove odeslani: '.$this->form->title);
    }

    public function content(): Content
    {
        return new Content(view: 'mipress-forms::mail.submission-notification');
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return $this->submission->attachments
            ->filter(static fn ($attachment): bool => Storage::disk('local')->exists($attachment->path))
            ->map(static fn ($attachment): Attachment => Attachment::fromStorageDisk('local', $attachment->path)
                ->as($attachment->filename)
                ->withMime($attachment->mime_type))
            ->values()
            ->all();
    }
}
