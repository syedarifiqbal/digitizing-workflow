<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use App\Support\TenantMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(
        public Order $order,
        public User $assignedBy
    ) {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->order->tenant->getSetting('notify_on_assignment', true)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $tenant = $order->tenant;
        $fromAddress = $tenant->getSetting('mail_from_address') ?: config('mail.from.address');
        $fromName = $tenant->getSetting('mail_from_name') ?: $tenant->getSetting('company_details.name', config('app.name'));

        $mail = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("New Order Assignment: {$order->order_number} — {$order->title}")
            ->greeting("Dear {$notifiable->name},")
            ->line('A new order has been assigned to you. Please review the details below and begin work at your earliest convenience.');

        $mail->line('**Order Details:**')
            ->line("- **Order Number:** {$order->order_number}")
            ->line("- **Title:** {$order->title}")
            ->line('- **Type:** ' . ucwords(str_replace('_', ' ', $order->type->value)))
            ->line('- **Priority:** ' . ucwords($order->priority->value))
            ->line("- **Client:** {$order->client->name}");

        if ($order->due_at) {
            $mail->line("- **Due Date:** {$order->due_at->format('M d, Y')}");
        }

        $mail->line('')
            ->line("Assigned by: {$this->assignedBy->name}")
            ->action('View Order', url("/orders/{$order->id}"))
            ->line("Thank you for your continued dedication.");

        $mailer = TenantMailer::configureForTenant($tenant);
        if ($mailer) {
            $mail->mailer($mailer);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'order_title' => $this->order->title,
            'assigned_by' => $this->assignedBy->id,
        ];
    }
}
