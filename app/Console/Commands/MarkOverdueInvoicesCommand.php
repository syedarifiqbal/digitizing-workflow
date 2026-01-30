<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoiceWorkflowService;
use Illuminate\Console\Command;

class MarkOverdueInvoicesCommand extends Command
{
    protected $signature = 'invoices:mark-overdue';

    protected $description = 'Mark SENT and PARTIALLY_PAID invoices as OVERDUE when past due date';

    public function handle(InvoiceWorkflowService $workflowService): int
    {
        $overdueInvoices = Invoice::query()
            ->withoutGlobalScope('tenant')
            ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');

            return self::SUCCESS;
        }

        $count = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $workflowService->transitionTo($invoice, InvoiceStatus::OVERDUE);
                $count++;
                $this->line("Marked invoice {$invoice->invoice_number} as overdue.");
            } catch (\Exception $e) {
                $this->error("Failed to mark invoice {$invoice->invoice_number}: {$e->getMessage()}");
            }
        }

        $this->info("Marked {$count} invoice(s) as overdue.");

        return self::SUCCESS;
    }
}
