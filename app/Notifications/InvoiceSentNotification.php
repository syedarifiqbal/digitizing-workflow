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
        $companyName = $invoice->tenant->getSetting('company_details.name', config('app.name'));
        $name = $notifiable->name ?? 'Valued Customer';
        $dueDate = $invoice->due_date?->format('F j, Y');
        $amount = number_format((float) $invoice->total_amount, 2);

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), $companyName)
            ->subject("Invoice {$invoice->invoice_number} — {$invoice->currency} {$amount} Due {$dueDate}")
            ->greeting("Dear {$name},")
            ->line("Please find below the details for your invoice. We kindly request that payment be made by the due date.");

        $mail->line('**Invoice Details:**')
            ->line("- **Invoice Number:** {$invoice->invoice_number}")
            ->line("- **Amount:** {$invoice->currency} {$amount}")
            ->line("- **Currency:** {$invoice->currency}");

        if ($dueDate) {
            $mail->line("- **Due Date:** {$dueDate}");
        }

        if ($this->message) {
            $mail->line('')
                ->line('**Additional Notes:**')
                ->line($this->message);
        }

        $mail->line('')
            ->line('Please review the invoice and arrange payment at your earliest convenience.')
            ->action('View Invoice', route('client.invoices.show', $invoice->id))
            ->line("Thank you for your business with {$companyName}.");

        if ($this->pdfData) {
            $mail->attachData($this->pdfData, $this->pdfFilename ?? 'invoice.pdf', ['mime' => 'application/pdf']);
        }

        return $mail;
    }
}
