<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoicePaymentRecordedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Invoice $invoice,
        private float $amount,
        private float $balance
    )
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $invoice = $this->invoice;

        return (new MailMessage())
            ->subject("Payment received for Invoice {$invoice->invoice_number}")
            ->greeting('Hello!')
            ->line('We received a payment of ' . $invoice->currency . ' ' . number_format($this->amount, 2) . ' for your invoice ' . $invoice->invoice_number . '.')
            ->line('Current balance: ' . $invoice->currency . ' ' . number_format($this->balance, 2))
            ->line('Thank you for your prompt payment.');
    }
}
