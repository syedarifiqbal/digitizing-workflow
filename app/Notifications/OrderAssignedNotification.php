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
        return (new MailMessage)
            ->subject("New Order Assigned: {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("You have been assigned a new order.")
            ->line("**Order:** {$this->order->order_number}")
            ->line("**Title:** {$this->order->title}")
            ->line("**Priority:** " . ucfirst($this->order->priority->value))
            ->line("**Client:** {$this->order->client->name}")
            ->when($this->order->due_at, fn ($mail) => $mail->line("**Due:** {$this->order->due_at->format('M d, Y')}"))
            ->action('View Order', url("/orders/{$this->order->id}"))
            ->line("Assigned by: {$this->assignedBy->name}");
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
