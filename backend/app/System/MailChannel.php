<?php

namespace App\System;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Channels\MailChannel as DefaultChannel;

class MailChannel extends DefaultChannel
{
    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toMail')) {
            return;
        }

        $message = $notification->toMail($notifiable);

        if (! $notifiable->routeNotificationFor('mail', $notification) && ! $message instanceof Mailable) {
            return;
        }

        $message->replyTo(
            address: config('mail.from.reply_to.address'),
            name: config('mail.from.reply_to.name')
        );

        if ($message instanceof Mailable) {
            return $message->send($this->mailer);
        }

        return $this->mailer->mailer($message->mailer ?? null)->send(
            $this->buildView($message),
            array_merge($message->data(), $this->additionalMessageData($notification)),
            $this->messageBuilder($notifiable, $notification, $message)
        );
    }
}
