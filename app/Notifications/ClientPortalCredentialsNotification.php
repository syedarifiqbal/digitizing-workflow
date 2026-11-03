<?php

namespace App\Notifications;

use App\Models\Tenant;
use App\Support\TenantMailer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientPortalCredentialsNotification extends Notification
{
    public function __construct(
        private Tenant $tenant,
        private string $password
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenant = $this->tenant;
        $fromAddress = $tenant->getSetting('mail_from_address') ?: config('mail.from.address');
        $fromName = $tenant->getSetting('mail_from_name') ?: $tenant->getSetting('company_details.name', config('app.name'));
        $companyName = $tenant->getSetting('company_details.name', config('app.name'));
        $name = $notifiable->name ?? 'there';

        $mail = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("Your {$companyName} portal login")
            ->greeting("Dear {$name},")
            ->line("Your account on the {$companyName} client portal is ready. You can sign in right away to place orders and view or pay your invoices.")
            ->line('**Your login details:**')
            ->line("- **Email:** {$notifiable->email}")
            ->line("- **Password:** {$this->password}")
            ->action('Sign in', route('login'))
            ->line('For your security, please keep these details private.');

        $mailer = TenantMailer::configureForTenant($tenant);
        if ($mailer) {
            $mail->mailer($mailer);
        }

        return $mail;
    }
}
