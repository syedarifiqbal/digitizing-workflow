<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice->invoice_number ?? sprintf('#%05d', $invoice->id) }}</title>

    <style>
        @page { margin: 18px; }

        /* html, body { height: 100%; } */

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        .page {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 18px 20px;
            /* height: 100%; */
            padding-bottom: 70px;
        }

        /* Full-height page table */
        .page-table {
            width: 100%;
            /* height: 100%; */
            border-collapse: collapse;
        }
        .page-table td {
            padding: 0;
        }

        /* Bottom area sticks to bottom */
        .page-bottom {
            /* vertical-align: bottom; */
            padding-top: 10px;
        }

        .muted { color: #64748b; }
        .small { font-size: 11px; }
        .h1 { font-size: 26px; font-weight: 700; margin: 0; }
        .h2 { font-size: 14px; font-weight: 700; margin: 0 0 4px; }
        .mt-16 { margin-top: 12px; }
        .mt-20 { margin-top: 14px; }
        .mt-24 { margin-top: 16px; }

        .logo {
            max-height: 56px;
            max-width: 220px;
            display: block;
            margin-bottom: 10px;
        }

        /* Header layout (table = PDF safe) */
        .header {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: top;
        }
        .right { text-align: right; }

        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            border: 1px solid;
        }

        .meta {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }
        .meta td {
            padding: 3px 0;
        }
        .meta .label {
            width: 110px;
            color: #64748b;
            font-weight: 600;
        }

        /* Cards row */
        .cards {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 12px;
        }
        .card-wrap {
            padding-right: 10px;
        }
        .card-wrap:last-child {
            padding-right: 0;
        }
        .section-title {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 0.10em;
            text-transform: uppercase;
            color: #94a3b8;
            margin: 0 0 8px;
        }
        .big-amount {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
        }

        /* Items table */
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }
        .items thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 10px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            text-align: left;
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .items tbody td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .items tbody tr:nth-child(even) td {
            background: #fcfdff;
        }
        .desc {
            font-weight: 700;
        }
        .nowrap { white-space: nowrap; }

        /* Totals panel */
        .totals-wrap {
            width: 100%;
            margin-top: 8px;
        }
        .totals {
            width: 300px;
            margin-left: auto;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }
        .totals table {
            width: 100%;
            border-collapse: collapse;
        }
        .totals td {
            padding: 7px 10px;
            font-size: 11px;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals tr:last-child td {
            border-bottom: none;
            background: #f0f9ff;
            font-weight: 800;
            font-size: 12px;
        }
        .totals .label {
            color: #64748b;
            font-weight: 700;
        }
        .totals .value {
            text-align: right;
            font-weight: 700;
        }

        /* Notes / Bank */
        .block {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 14px;
        }
        .block pre {
            margin: 0;
            font-family: inherit;
            white-space: pre-line;
            color: #334155;
            line-height: 1.5;
        }
        .block p {
            margin: 0;
            color: #334155;
            line-height: 1.5;
        }

        /* Not fixed anymore (bottom row handles placement) */
        .footer {
  position: fixed;
  left: 18px;
  right: 18px;
  bottom: 18px;
  text-align: center;
  font-size: 10px;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: #94a3b8;
}

    </style>
</head>

<body>
<div class="page">
    <table class="page-table">
        <!-- TOP CONTENT -->
        <tr>
            <td style="vertical-align: top;">
                <table class="header">
                    <tr>
                        <td style="width: 62%;">
                            @if ($companyLogo)
                                <img class="logo" src="{{ $companyLogo }}" alt="Company logo">
                            @endif

                            <div class="h2">{{ $companyDetails['name'] ?? 'Your Company' }}</div>
                            <div class="muted" style="line-height: 1.5;">
                                {{ $companyDetails['address'] ?? '—' }}<br>
                                @if (!empty($companyDetails['phone'])) {{ $companyDetails['phone'] }} @endif
                                @if (!empty($companyDetails['email'])) &middot; {{ $companyDetails['email'] }} @endif
                            </div>
                        </td>

                        <td class="right" style="width: 38%;">
                            <div class="h1">Invoice</div>
                            <div class="muted" style="font-size: 14px; margin-top: 4px;">
                                {{ $invoice->invoice_number ?? sprintf('#%05d', $invoice->id) }}
                            </div>

                            <div style="margin-top: 10px;">
                                <span
                                    class="status"
                                    style="background-color: {{ $statusInfo['background'] }}; border-color: {{ $statusInfo['border'] }}; color: {{ $statusInfo['color'] }};"
                                >
                                    {{ $statusInfo['label'] }}
                                </span>
                            </div>

                            <table class="meta" style="margin-top: 12px;">
                                <tr>
                                    <td class="label">Issue Date</td>
                                    <td>{{ optional($invoice->issue_date)->format('F j, Y') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Due Date</td>
                                    <td>{{ optional($invoice->due_date)->format('F j, Y') ?? '—' }}</td>
                                </tr>
                                <tr>
                                    <td class="label">Terms</td>
                                    <td>{{ $invoice->payment_terms ?? ($invoice->tenant?->getSetting('default_payment_terms') ?? 'Due on Receipt') }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>

                <table class="cards mt-20">
                    <tr>
                        <td class="card-wrap" style="width: 60%;">
                            <div class="card">
                                <div class="section-title">Bill To</div>
                                <div class="h2">{{ $invoice->client?->name ?? 'Client' }}</div>
                                <div class="muted" style="line-height: 1.5;">
                                    {{ $invoice->client?->company ?? '' }}<br>
                                    {{ $invoice->client?->email ?? '' }}
                                </div>
                            </div>
                        </td>

                        <td class="card-wrap" style="width: 40%;">
                            <div class="card">
                                <div class="section-title">Balance Due</div>
                                <p class="big-amount">{{ $invoice->currency }} {{ number_format($balance, 2) }}</p>
                                <div class="muted small" style="margin-top: 6px;">
                                    Remaining amount
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>

                <table class="items mt-20">
                    <thead>
                        <tr>
                            <th style="width: 46%;">Description</th>
                            <th style="width: 14%;" class="right">Qty</th>
                            <th style="width: 20%;" class="right">Rate</th>
                            <th style="width: 20%;" class="right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="desc">{{ $item->description }}</div>
                                @if ($item->order?->order_number)
                                    <div class="muted small" style="margin-top: 4px;">Order: {{ $item->order->order_number }}</div>
                                @endif
                                @if ($item->note)
                                    <div class="muted small" style="margin-top: 2px;">{{ $item->note }}</div>
                                @endif
                            </td>

                            <td class="right nowrap">
                                {{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }}
                            </td>

                            <td class="right nowrap">
                                {{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}
                            </td>

                            <td class="right nowrap">
                                {{ $invoice->currency }} {{ number_format($item->amount, 2) }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </td>
        </tr>

        <!-- BOTTOM CONTENT (sticks to bottom when there is free space) -->
        <tr>
            <td class="page-bottom">
                <div class="totals-wrap">
                    <div class="totals">
                        <table>
                            <tr>
                                <td class="label">Subtotal</td>
                                <td class="value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Tax ({{ number_format($invoice->tax_rate, 2) }}%)</td>
                                <td class="value">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Discount</td>
                                <td class="value">- {{ $invoice->currency }} {{ number_format($invoice->discount_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Total</td>
                                <td class="value">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="label">Paid</td>
                                <td class="value">{{ $invoice->currency }} {{ number_format($paidAmount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Balance Due</td>
                                <td class="value">{{ $invoice->currency }} {{ number_format($balance, 2) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                @if (! empty($bankDetails))
                    <div class="mt-20">
                        <div class="section-title">Payment Instructions</div>
                        <div class="block">
                            <pre>{{ $bankDetails }}</pre>
                        </div>
                    </div>
                @endif

                @if ($invoice->notes)
                    <div class="mt-16">
                        <div class="section-title">Notes</div>
                        <div class="block">
                            <p>{{ $invoice->notes }}</p>
                        </div>
                    </div>
                @endif

            </td>
        </tr>
    </table>
</div>
<div class="footer">Thank you for your business</div>

@if (!empty($printView))
<script>
    window.onload = function () { window.print(); };
</script>
@endif
</body>
</html>
