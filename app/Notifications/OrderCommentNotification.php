<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderComment;
use App\Models\User;
use App\Support\TenantMailer;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCommentNotification extends Notification
{
    public function __construct(
        public Order $order,
        public OrderComment $comment,
        public User $commenter
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->order->tenant->getSetting('notify_on_comment', true)) {
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
        $name = $notifiable->name ?? 'there';
        $preview = \Illuminate\Support\Str::limit($this->comment->body, 200);

        $mailer = TenantMailer::configureForTenant($tenant);

        $mail = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("New Comment on Order {$order->order_number} — {$order->title}")
            ->greeting("Dear {$name},")
            ->line("{$this->commenter->name} has added a new comment on order **{$order->order_number}**.")
            ->line('')
            ->line('**Comment:**')
            ->line("> {$preview}")
            ->action('View Order', $this->getOrderUrl($notifiable))
            ->line('Thank you.');

        if ($mailer) {
            $mail->mailer($mailer);
        }

        return $mail;
    }

    private function getOrderUrl(object $notifiable): string
    {
        if ($notifiable->client_id) {
            return route('client.orders.show', $this->order->id);
        }

        return url("/orders/{$this->order->id}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'order_title' => $this->order->title,
            'comment_id' => $this->comment->id,
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'comment_preview' => \Illuminate\Support\Str::limit($this->comment->body, 100),
            'url' => $this->getOrderUrl($notifiable),
        ];
    }
}
