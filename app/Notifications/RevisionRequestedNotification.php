<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Order $order,
        public User $requestedBy,
        public ?string $notes = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Revision Requested: {$this->order->order_number}")
            ->greeting("Hi {$notifiable->name},")
            ->line("A revision has been requested for your order.")
            ->line("**Order:** {$this->order->order_number}")
            ->line("**Title:** {$this->order->title}")
            ->line("**Requested by:** {$this->requestedBy->name}");

        if ($this->notes) {
            $mail->line("**Notes:** {$this->notes}");
        }

        return $mail
            ->action('View Order', url("/orders/{$this->order->id}"))
            ->line('Please review the feedback and submit updated work.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'requested_by' => $this->requestedBy->id,
        ];
    }
}
