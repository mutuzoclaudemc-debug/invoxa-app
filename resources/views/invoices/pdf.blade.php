<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    * { box-sizing: border-box; }
    body { font-family: 'Helvetica', sans-serif; color: #0b0d10; margin: 0; padding: 30px; font-size: 11px; line-height: 1.5; }
    .header { display: table; width: 100%; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #0b0d10; }
    .header-left, .header-right { display: table-cell; vertical-align: top; }
    .header-right { text-align: right; }
    .company-name { font-size: 22px; font-weight: bold; margin-bottom: 4px; }
    .invoice-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px; }
    .invoice-number { font-size: 24px; font-weight: bold; font-family: monospace; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 9px; text-transform: uppercase; background: #e5e7eb; margin-top: 6px; }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-overdue { background: #fee2e2; color: #991b1b; }
    .info { color: #6b7280; font-size: 10px; line-height: 1.4; }
    .billing { display: table; width: 100%; margin-bottom: 30px; }
    .billing-left, .billing-right { display: table-cell; vertical-align: top; width: 50%; }
    .billing-right { text-align: right; }
    .label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .customer-name { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
    .date-row { margin-bottom: 12px; }
    .date-label { font-size: 9px; color: #6b7280; text-transform: uppercase; }
    .date-value { font-weight: 600; }
    table.items { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    table.items thead tr { border-bottom: 2px solid #0b0d10; }
    table.items th { text-align: left; padding: 10px 6px; font-size: 9px; text-transform: uppercase; color: #6b7280; letter-spacing: 1px; }
    table.items td { padding: 10px 6px; border-bottom: 1px solid #ececec; }
    table.items .right { text-align: right; }
    .totals { float: right; width: 250px; margin-bottom: 20px; }
    .totals-row { display: table; width: 100%; padding: 4px 0; }
    .totals-label { display: table-cell; color: #6b7280; }
    .totals-value { display: table-cell; text-align: right; }
    .totals-total { padding-top: 10px; margin-top: 10px; border-top: 2px solid #0b0d10; font-size: 18px; font-weight: bold; }
    .clearfix { clear: both; }
    .notes { padding-top: 20px; border-top: 1px solid #ececec; margin-top: 20px; }
    .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #ececec; text-align: center; font-size: 9px; color: #6b7280; }
</style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $invoice->workspace->name ?? 'Company' }}</div>
            @if($invoice->workspace->company_address)
                <div class="info">{{ $invoice->workspace->company_address }}</div>
            @endif
            @if($invoice->workspace->company_email)
                <div class="info">{{ $invoice->workspace->company_email }}</div>
            @endif
            @if($invoice->workspace->company_phone)
                <div class="info">{{ $invoice->workspace->company_phone }}</div>
            @endif
            @if($invoice->workspace->tax_id)
                <div class="info">Tax ID: {{ $invoice->workspace->tax_id }}</div>
            @endif
            @if($invoice->workspace->website)
                <div class="info">{{ $invoice->workspace->website }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="invoice-label">Invoice</div>
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <span class="status status-{{ $invoice->status }}">{{ $invoice->status }}</span>
        </div>
    </div>

    <div class="billing">
        <div class="billing-left">
            <div class="label">Bill To</div>
            <div class="customer-name">{{ $invoice->customer->name }}</div>
            @if($invoice->customer->company_name)
                <div class="info">{{ $invoice->customer->company_name }}</div>
            @endif
            <div class="info">{{ $invoice->customer->email }}</div>
            @if($invoice->customer->phone)
                <div class="info">{{ $invoice->customer->phone }}</div>
            @endif
            @if($invoice->customer->billing_address)
                <div class="info">{{ $invoice->customer->billing_address }}</div>
            @endif
        </div>
        <div class="billing-right">
            <div class="date-row">
                <div class="date-label">Issue Date</div>
                <div class="date-value">{{ \Carbon\Carbon::parse($invoice->issue_date)->format('M d, Y') }}</div>
            </div>
            <div class="date-row">
                <div class="date-label">Due Date</div>
                <div class="date-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</div>
            </div>
            <div class="date-row">
                <div class="date-label">Currency</div>
                <div class="date-value">{{ $invoice->currency }}</div>
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="right">Qty</th>
                <th class="right">Price</th>
                <th class="right">Tax</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ $invoice->currency }} {{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ $item->tax_rate }}%</td>
                <td class="right">{{ $invoice->currency }} {{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal</span>
            <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">Tax</span>
            <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->tax_amount, 2) }}</span>
        </div>
        <div class="totals-row totals-total">
            <span class="totals-label">Total</span>
            <span class="totals-value">{{ $invoice->currency }} {{ number_format($invoice->total_amount, 2) }}</span>
        </div>
    </div>
    <div class="clearfix"></div>

    @if($invoice->notes)
    <div class="notes">
        <div class="label">Notes</div>
        <div class="info">{{ $invoice->notes }}</div>
    </div>
    @endif
    @if($invoice->workspace->bank_name || $invoice->workspace->bank_account_number)
    <div style="margin-top: 20px; padding: 16px; background: #f6f6f5; border-radius: 8px; border: 1px solid #ececec;">
        <div class="label">Payment Details</div>
        <table style="width: 100%; font-size: 11px; margin-top: 8px;">
            @if($invoice->workspace->bank_name)
            <tr>
                <td style="color: #6b7280; padding: 2px 0; width: 120px;">Bank:</td>
                <td style="font-weight: 600;">{{ $invoice->workspace->bank_name }}</td>
            </tr>
            @endif
            @if($invoice->workspace->bank_account_number)
            <tr>
                <td style="color: #6b7280; padding: 2px 0;">Account:</td>
                <td style="font-weight: 600; font-family: monospace;">{{ $invoice->workspace->bank_account_number }}</td>
            </tr>
            @endif
            @if($invoice->workspace->bank_account_name)
            <tr>
                <td style="color: #6b7280; padding: 2px 0;">Name:</td>
                <td style="font-weight: 600;">{{ $invoice->workspace->bank_account_name }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    @if($invoice->workspace->invoice_footer)
    <div class="footer">
        {{ $invoice->workspace->invoice_footer }}
    </div>
    @else
    <div class="footer">
        @if($invoice->workspace->invoice_footer)
            {{ $invoice->workspace->invoice_footer }}<br>
        @else
            Thank you for your business!<br>
        @endif
        Generated by Invoxa | Powered by iremeCloud
    </div>
    @endif
</body>
</html>
