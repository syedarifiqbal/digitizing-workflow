<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class InvoicePdfService
{
    public function __construct(private readonly ViewFactory $view)
    {
    }

    public function make(Invoice $invoice)
    {
        $invoice->loadMissing(['client', 'items.order', 'payments']);
        $companyDetails = $invoice->tenant?->getSetting('company_details', []) ?? [];
        $paid = (float) $invoice->payments->sum('amount');
        $balance = max((float) $invoice->total_amount - $paid, 0);

        $data = [
            'invoice' => $invoice,
            'companyDetails' => $companyDetails,
            'paidAmount' => $paid,
            'balance' => $balance,
            'companyLogo' => $this->logoData($invoice->tenant?->getSetting('company_logo_path')),
            'bankDetails' => $invoice->tenant?->getSetting('bank_details', ''),
            'statusInfo' => $this->statusStyles($invoice),
        ];

        try {
            $pdf = app('dompdf.wrapper');
        } catch (\Throwable $e) {
            throw new RuntimeException('PDF generation requires barryvdh/laravel-dompdf. ' . $e->getMessage());
        }
        $pdf->loadHTML($this->view->make('pdf.invoice', $data)->render());

        return $pdf;
    }

    private function logoData(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        try {
            $contents = $disk->get($path);
        } catch (\Throwable) {
            return null;
        }

        $mime = $disk->mimeType($path) ?? 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($contents);
    }

    private function statusStyles(Invoice $invoice): array
    {
        $status = $invoice->status ?? InvoiceStatus::DRAFT;
        $styles = match ($status) {
            InvoiceStatus::PAID => [
                'background' => '#dcfce7',
                'border' => '#86efac',
                'color' => '#15803d',
            ],
            InvoiceStatus::PARTIALLY_PAID => [
                'background' => '#fef3c7',
                'border' => '#fcd34d',
                'color' => '#b45309',
            ],
            InvoiceStatus::OVERDUE => [
                'background' => '#fee2e2',
                'border' => '#fecaca',
                'color' => '#b91c1c',
            ],
            InvoiceStatus::VOID, InvoiceStatus::CANCELLED => [
                'background' => '#f3f4f6',
                'border' => '#e5e7eb',
                'color' => '#4b5563',
            ],
            InvoiceStatus::SENT => [
                'background' => '#dbeafe',
                'border' => '#bfdbfe',
                'color' => '#1d4ed8',
            ],
            default => [
                'background' => '#f3f4f6',
                'border' => '#e5e7eb',
                'color' => '#374151',
            ],
        };

        return array_merge($styles, [
            'label' => $status->label(),
        ]);
    }
}
