<?php

namespace BlogCms\Mail;

use BlogCms\Models\GuestBlogRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Emails the guest their tokenized "submit your article" link. */
class GuestPitchApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GuestBlogRequest $request) {}

    public function build(): self
    {
        return $this->subject('Your guest post pitch was approved')
            ->view('blog::mail.guest-approved', [
                'guest' => $this->request,
                'submitUrl' => $this->request->submitUrl(),
            ]);
    }
}
