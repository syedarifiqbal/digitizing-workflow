<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number ?? sprintf('#%05d', $invoice->id) }}</title>
    <style>
        @page { margin: 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 0;
            font-size: 12px;
            background-color: #f8fafc;
        }
        .invoice-wrapper {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 28px 32px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 24px;
        }
        .brand-block {
            max-width: 65%;
        }
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 6px;
            color: #111827;
        }
        .brand-meta {
            margin: 0;
            color: #475569;
            line-height: 1.5;
        }
        .logo {
            max-height: 64px;
            max-width: 240px;
            object-fit: contain;
            display: block;
            margin-bottom: 12px;
        }
        .meta-block {
            text-align: right;
        }
        .invoice-title {
            font-size: 26px;
            font-weight: 700;
            margin: 0;
            color: #0f172a;
        }
        .invoice-number {
            font-size: 16px;
            color: #475569;
            margin: 4px 0 12px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            border: 1px solid;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .meta-table th,
        .meta-table td {
            text-align: left;
            padding: 4px 0;
            font-size: 12px;
        }
        .section-title {
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.08em;
            color: #94a3b8;
            margin-bottom: 6px;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            background-color: #fdfefe;
        }
        .grid {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .grid .card {
            flex: 1;
        }
        .amount-due {
            font-size: 22px;
            font-weight: 700;
            color: #0f172a;
            margin: 4px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th {
            background-color: #f8fafc;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.06em;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding: 10px 8px;
        }
        .items-table td {
            border-bottom: 1px solid #f1f5f9;
            padding: 10px 8px;
            vertical-align: top;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table .description {
            color: #0f172a;
            font-weight: 600;
        }
        .items-table small {
            color: #64748b;
            display: block;
            margin-top: 4px;
            font-size: 11px;
        }
        .totals {
            margin-top: 16px;
            width: 320px;
            margin-left: auto;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }
        .totals table {
            width: 100%;
        }
        .totals td {
            padding: 8px 12px;
            font-size: 12px;
        }
        .totals tr:not(:last-child) td {
            border-bottom: 1px solid #f1f5f9;
        }
        .totals tr:last-child td {
            background-color: #f0f9ff;
            font-weight: 700;
            font-size: 13px;
        }
        .notes,
        .bank-details {
            margin-top: 20px;
        }
        .notes p,
        .bank-details p {
            margin: 0;
            line-height: 1.5;
            color: #475569;
        }
        .bank-details pre {
            font-family: inherit;
            margin: 0;
            white-space: pre-line;
            color: #334155;
        }
        .payment-summary {
            display: flex;
            gap: 16px;
            margin-top: 12px;
        }
        .payment-summary .card {
            flex: 1;
            text-align: center;
        }
        .summary-amount {
            font-size: 18px;
            font-weight: 700;
            margin-top: 6px;
            color: #0f172a;
        }
        .thank-you {
            margin-top: 28px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }
    </style>
</head>
<body>
    <div class="invoice-wrapper">
        <div class="header">
            <div class="brand-block">
                @if ($companyLogo)
                    <img class="logo" src="{{ $companyLogo }}" alt="Company logo">
                @endif
                <p class="brand-name">{{ $companyDetails['name'] ?? 'Your Company' }}</p>
                <p class="brand-meta">
                    {{ $companyDetails['address'] ?? '—' }}<br>
                    {{ $companyDetails['phone'] ?? '' }}
                    @if (! empty($companyDetails['email']))
                        &middot; {{ $companyDetails['email'] }}
                    @endif
                </p>
            </div>
            <div class="meta-block">
                <p class="invoice-title">Invoice</p>
                <p class="invoice-number">{{ $invoice->invoice_number ?? sprintf('#%05d', $invoice->id) }}</p>
                <span
                    class="status-badge"
                    style="background-color: {{ $statusInfo['background'] }}; border-color: {{ $statusInfo['border'] }}; color: {{ $statusInfo['color'] }};"
                >
                    {{ $statusInfo['label'] }}
                </span>
                <table class="meta-table">
                    <tr>
                        <th>Issue Date</th>
                        <td>{{ optional($invoice->issue_date)->format('F j, Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Due Date</th>
                        <td>{{ optional($invoice->due_date)->format('F j, Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Payment Terms</th>
                        <td>{{ $invoice->payment_terms ?? ($invoice->tenant?->getSetting('default_payment_terms') ?? 'Due on Receipt') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <p class="section-title">Bill To</p>
                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #0f172a;">
                    {{ $invoice->client?->name ?? 'Client' }}
                </p>
                <p style="margin: 4px 0 0; color: #475569;">
                    {{ $invoice->client?->company ?? '' }}<br>
                    {{ $invoice->client?->email ?? '' }}
                </p>
            </div>
            <div class="card">
                <p class="section-title">Balance Due</p>
                <p class="amount-due">{{ $invoice->currency }} {{ number_format($balance, 2) }}</p>
                <p style="margin: 4px 0 0; color: #64748b; font-size: 11px;">
                    Total {{ strtolower($statusInfo['label']) }} amount remaining
                </p>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 45%;">Description</th>
                    <th style="width: 15%;">Qty</th>
                    <th style="width: 20%;">Rate</th>
                    <th style="width: 20%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>
                            <span class="description">{{ $item->description }}</span>
                            @if ($item->order?->order_number)
                                <small>Order: {{ $item->order->order_number }}</small>
                            @endif
                            @if ($item->note)
                                <small>{{ $item->note }}</small>
                            @endif
                        </td>
                        <td>{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}</td>
                        <td>{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                        <td>{{ $invoice->currency }} {{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td>Tax ({{ number_format($invoice->tax_rate, 2) }}%)</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Discount</td>
                    <td style="text-align: right;">- {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Total</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td>Paid</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($paidAmount, 2) }}</td>
                </tr>
                <tr>
                    <td>Balance Due</td>
                    <td style="text-align: right;">{{ $invoice->currency }} {{ number_format($balance, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="payment-summary">
            <div class="card">
                <p class="section-title">Amount Paid</p>
                <p class="summary-amount">{{ $invoice->currency }} {{ number_format($paidAmount, 2) }}</p>
            </div>
            <div class="card">
                <p class="section-title">Balance Remaining</p>
                <p class="summary-amount">{{ $invoice->currency }} {{ number_format($balance, 2) }}</p>
            </div>
        </div>

        @if (! empty($bankDetails))
            <div class="bank-details">
                <p class="section-title">Payment Instructions</p>
                <div class="card">
                    <pre>{{ $bankDetails }}</pre>
                </div>
            </div>
        @endif

        @if ($invoice->notes)
            <div class="notes">
                <p class="section-title">Additional Notes</p>
                <div class="card">
                    <p>{{ $invoice->notes }}</p>
                </div>
            </div>
        @endif

        <p class="thank-you">Thank you for your business</p>
    </div>

    @if (!empty($printView))
    <script>
        window.onload = function () { window.print(); };
    </script>
    @endif
</body>
</html>
