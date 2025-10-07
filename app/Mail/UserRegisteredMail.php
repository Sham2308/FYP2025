<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserRegisteredMail extends Mailable
{
    use Queueable, SerializesModels;

    /** Make these public so the email view can access them */
    public $user;
    public $tempPassword;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $user
     * @param  string|null  $tempPassword
     * @return void
     */
    public function __construct(User $user, $tempPassword = null)
    {
        $this->user = $user;
        $this->tempPassword = $tempPassword;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(config('mail.from.address'), config('mail.from.name'))
                    ->subject('Welcome to TapNBorrow')
                    ->markdown('emails.user_registered')        // HTML/Markdown version
                    ->text('emails.user_registered_plain')       // Plain-text fallback for Outlook alerts
                    ->with([
                        'user' => $this->user,
                        'tempPassword' => $this->tempPassword,
                    ])
                    ->withSymfonyMessage(function ($message) {
                        $headers = $message->getHeaders();
                        $headers->addTextHeader('X-Priority', '1');
                        $headers->addTextHeader('Importance', 'high');
                    });
    }
}
