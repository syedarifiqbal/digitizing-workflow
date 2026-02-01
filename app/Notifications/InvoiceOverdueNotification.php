<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Support\TenantMailer;
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
        $tenant = $invoice->tenant;
        $fromAddress = $tenant->getSetting('mail_from_address') ?: config('mail.from.address');
        $fromName = $tenant->getSetting('mail_from_name') ?: $tenant->getSetting('company_details.name', config('app.name'));
        $name = $notifiable->name ?? 'Valued Customer';
        $balance = max((float) $invoice->total_amount - $invoice->payments()->sum('amount'), 0);
        $formattedBalance = number_format($balance, 2);
        $dueDate = $invoice->due_date?->format('F j, Y');

        $mail = (new MailMessage)
            ->from($fromAddress, $fromName)
            ->subject("Payment Reminder: Invoice {$invoice->invoice_number} is Overdue")
            ->greeting("Dear {$name},")
            ->line("This is a reminder that payment for the following invoice is now past due. We kindly request your prompt attention to this matter.")
            ->line('**Invoice Details:**')
            ->line("- **Invoice Number:** {$invoice->invoice_number}")
            ->line("- **Outstanding Balance:** {$invoice->currency} {$formattedBalance}")
            ->line("- **Original Due Date:** {$dueDate}")
            ->line('')
            ->line('Please arrange payment at your earliest convenience. If you have already submitted payment, please disregard this notice.')
            ->action('View Invoice', route('client.invoices.show', $invoice->id))
            ->line("If you have any questions or need assistance, please do not hesitate to contact us. Thank you for your attention to this matter.");

        $mailer = TenantMailer::configureForTenant($tenant);
        if ($mailer) {
            $mail->mailer($mailer);
        }

        return $mail;
    }
}
