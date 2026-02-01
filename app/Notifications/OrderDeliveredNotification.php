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
        $order = $this->order;
        $companyName = $order->tenant->getSetting('company_details.name', config('app.name'));
        $name = $notifiable->name ?? $order->client?->name ?? 'Valued Customer';

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), $companyName)
            ->subject("Your Order {$order->order_number} Has Been Delivered — {$order->title}")
            ->greeting("Dear {$name},")
            ->line("We are pleased to inform you that your order has been completed and is now ready for your review.");

        // Order details
        $mail->line('**Order Details:**')
            ->line("- **Order Number:** {$order->order_number}")
            ->line("- **Title:** {$order->title}")
            ->line('- **Type:** ' . ucwords(str_replace('_', ' ', $order->type->value)))
            ->line('- **Priority:** ' . ucwords($order->priority->value));

        // Work specifications (if submitted dimensions exist)
        $hasSpecs = $order->submitted_width || $order->submitted_height || $order->submitted_stitch_count;
        if ($hasSpecs) {
            $mail->line('')
                ->line('**Work Specifications:**');
            if ($order->submitted_width) {
                $mail->line("- **Width:** {$order->submitted_width}");
            }
            if ($order->submitted_height) {
                $mail->line("- **Height:** {$order->submitted_height}");
            }
            if ($order->submitted_stitch_count) {
                $mail->line("- **Stitch Count:** " . number_format($order->submitted_stitch_count));
            }
        }

        // Delivery message
        if ($this->message) {
            $mail->line('')
                ->line('**Message from our team:**')
                ->line("> {$this->message}");
        }

        // File count
        if (! empty($this->fileIds)) {
            $fileCount = count($this->fileIds);
            $mail->line('')
                ->line("{$fileCount} file(s) have been attached to this delivery.");
        }

        $mail->action('View Your Order', route('client.orders.show', $order->id))
            ->line('')
            ->line("Thank you for choosing {$companyName}. We appreciate your business and look forward to working with you again.");

        // Attach selected files
        if (! empty($this->fileIds)) {
            $files = OrderFile::whereIn('id', $this->fileIds)
                ->where('order_id', $order->id)
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
