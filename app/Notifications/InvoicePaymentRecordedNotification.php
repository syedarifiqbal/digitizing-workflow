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
        $companyName = $invoice->tenant->getSetting('company_details.name', config('app.name'));
        $name = $notifiable->name ?? 'Valued Customer';
        $formattedAmount = number_format($this->amount, 2);
        $formattedBalance = number_format($this->balance, 2);

        $mail = (new MailMessage)
            ->from(config('mail.from.address'), $companyName)
            ->subject("Payment Confirmed: {$invoice->currency} {$formattedAmount} for Invoice {$invoice->invoice_number}")
            ->greeting("Dear {$name},")
            ->line('We are writing to confirm that we have received your payment. Thank you for your promptness.')
            ->line('**Payment Details:**')
            ->line("- **Invoice Number:** {$invoice->invoice_number}")
            ->line("- **Payment Amount:** {$invoice->currency} {$formattedAmount}")
            ->line("- **Remaining Balance:** {$invoice->currency} {$formattedBalance}");

        if ($this->balance <= 0) {
            $mail->line('')
                ->line('Your invoice has been paid in full. Thank you!');
        }

        $mail->action('View Invoice', route('client.invoices.show', $invoice->id))
            ->line("Thank you for your business with {$companyName}. We truly appreciate it.");

        return $mail;
    }
}
