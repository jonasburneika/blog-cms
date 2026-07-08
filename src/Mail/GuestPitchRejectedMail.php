<?php

namespace BlogCms\Mail;

use BlogCms\Models\GuestBlogRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Notifies the guest that their pitch was not accepted. */
class GuestPitchRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public GuestBlogRequest $request) {}

    public function build(): self
    {
        return $this->subject('Update on your guest post pitch')
            ->view('blog::mail.guest-rejected', [
                'guest' => $this->request,
            ]);
    }
}
