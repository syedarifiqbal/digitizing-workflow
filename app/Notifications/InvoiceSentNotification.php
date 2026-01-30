<?php

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Invoice $invoice,
        private ?string $message = null,
        private ?string $pdfData = null,
        private ?string $pdfFilename = null
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
        $dueDate = $invoice->due_date?->format('F j, Y');

        $mail = (new MailMessage())
            ->subject("Invoice {$invoice->invoice_number} is ready")
            ->greeting('Hello!')
            ->line("We've sent you invoice {$invoice->invoice_number} totaling {$invoice->currency} " . number_format((float) $invoice->total_amount, 2) . '.')
            ->line($dueDate ? "Payment is due on {$dueDate}." : 'Please review and settle the invoice at your earliest convenience.');

        if ($this->message) {
            $mail->line($this->message);
        }

        if ($this->pdfData) {
            $mail->attachData($this->pdfData, $this->pdfFilename ?? 'invoice.pdf', ['mime' => 'application/pdf']);
        }

        return $mail->line('Thank you for your business!');
    }
}
