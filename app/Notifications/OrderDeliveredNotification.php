<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class OrderDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int>  $fileIds
     */
    public function __construct(
        public Order $order,
        public ?string $message = null,
        public array $fileIds = []
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = $notifiable->name ?? $this->order->client?->name ?? 'there';

        $mail = (new MailMessage)
            ->subject("Order Delivered: {$this->order->order_number}")
            ->greeting("Hi {$name},")
            ->line("Your order has been completed and delivered.")
            ->line("**Order:** {$this->order->order_number}")
            ->line("**Title:** {$this->order->title}");

        if ($this->message) {
            $mail->line("---")
                ->line("**Message from our team:**")
                ->line($this->message);
        }

        $mail->line("---")
            ->line('Thank you for your business!');

        // Attach selected files
        if (! empty($this->fileIds)) {
            $files = OrderFile::whereIn('id', $this->fileIds)
                ->where('order_id', $this->order->id)
                ->get();

            foreach ($files as $file) {
                $disk = Storage::disk($file->disk);
                if ($disk->exists($file->path)) {
                    $mail->attach($disk->path($file->path), [
                        'as' => $file->original_name,
                        'mime' => $file->mime_type,
                    ]);
                }
            }
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
