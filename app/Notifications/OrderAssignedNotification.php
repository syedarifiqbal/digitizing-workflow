<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $companyName = $order->tenant->getSetting('company_details.name', config('app.name'));

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), $companyName)
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

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'assigned_by' => $this->assignedBy->id,
        ];
    }
}
