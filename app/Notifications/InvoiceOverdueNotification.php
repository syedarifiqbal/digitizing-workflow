<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private Invoice $invoice)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $invoice = $this->invoice;
        $balance = max((float) $invoice->total_amount - $invoice->payments()->sum('amount'), 0);

        return (new MailMessage())
            ->subject("Invoice {$invoice->invoice_number} is overdue")
            ->greeting('Hello!')
            ->line('This is a friendly reminder that invoice ' . $invoice->invoice_number . ' is now overdue.')
            ->line('Outstanding balance: ' . $invoice->currency . ' ' . number_format($balance, 2))
            ->line('Please arrange payment at your earliest convenience or reach out if you need assistance.')
            ->line('Thank you.');
    }
}
