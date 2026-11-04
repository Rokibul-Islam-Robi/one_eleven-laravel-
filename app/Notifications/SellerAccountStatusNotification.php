<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SellerAccountStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $event,
        private readonly ?string $reason = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('bHerb seller account update')
            ->greeting('Hello '.$notifiable->owner_name.',')
            ->line(match ($this->event) {
                'approved' => 'Your seller application has been approved. Your dashboard access will be enabled after activation.',
                'activated' => 'Your seller account is now active.',
                'rejected' => 'Your seller application was not approved.',
                'suspended' => 'Your seller account has been suspended.',
                'inactive' => 'Your seller account has been deactivated.',
                default => 'Your seller account status has changed.',
            });

        if ($this->reason) {
            $message->line('Reason: '.$this->reason);
        }

        return $message->action('Seller dashboard', url('/seller/dashboard'))
            ->line('If you believe this update was made in error, contact bHerb support.');
    }
}
