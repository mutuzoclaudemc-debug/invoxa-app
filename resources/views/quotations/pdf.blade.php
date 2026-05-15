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
    .doc-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 4px; }
    .doc-number { font-size: 24px; font-weight: bold; font-family: monospace; }
    .status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 9px; text-transform: uppercase; background: #e5e7eb; margin-top: 6px; }
    .status-accepted { background: #d1fae5; color: #065f46; }
    .status-rejected { background: #fee2e2; color: #991b1b; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-expired { background: #fef3c7; color: #92400e; }
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
    .validity-notice { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 10px 14px; margin-bottom: 20px; font-size: 10px; color: #92400e; }
</style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $quotation->workspace->name ?? 'Company' }}</div>
            @if($quotation->workspace->company_address)
                <div class="info">{{ $quotation->workspace->company_address }}</div>
            @endif
            @if($quotation->workspace->company_email)
                <div class="info">{{ $quotation->workspace->company_email }}</div>
            @endif
            @if($quotation->workspace->company_phone)
                <div class="info">{{ $quotation->workspace->company_phone }}</div>
            @endif
            @if($quotation->workspace->tax_id)
                <div class="info">Tax ID: {{ $quotation->workspace->tax_id }}</div>
            @endif
            @if($quotation->workspace->website)
                <div class="info">{{ $quotation->workspace->website }}</div>
            @endif
        </div>
        <div class="header-right">
            <div class="doc-label">Quotation</div>
            <div class="doc-number">{{ $quotation->quotation_number }}</div>
            <span class="status status-{{ $quotation->status }}">{{ $quotation->status }}</span>
        </div>
    </div>

    <div class="billing">
        <div class="billing-left">
            <div class="label">Prepared For</div>
            <div class="customer-name">{{ $quotation->customer->name }}</div>
            @if($quotation->customer->company_name)
                <div class="info">{{ $quotation->customer->company_name }}</div>
            @endif
            <div class="info">{{ $quotation->customer->email }}</div>
            @if($quotation->customer->phone)
                <div class="info">{{ $quotation->customer->phone }}</div>
            @endif
            @if($quotation->customer->billing_address)
                <div class="info">{{ $quotation->customer->billing_address }}</div>
            @endif
        </div>
        <div class="billing-right">
            <div class="date-row">
                <div class="date-label">Issue Date</div>
                <div class="date-value">{{ \Carbon\Carbon::parse($quotation->issue_date)->format('M d, Y') }}</div>
            </div>
            <div class="date-row">
                <div class="date-label">Valid Until</div>
                <div class="date-value">{{ \Carbon\Carbon::parse($quotation->valid_until)->format('M d, Y') }}</div>
            </div>
            <div class="date-row">
                <div class="date-label">Currency</div>
                <div class="date-value">{{ $quotation->currency }}</div>
            </div>
        </div>
    </div>

    @if(\Carbon\Carbon::parse($quotation->valid_until)->isPast() && $quotation->status !== 'accepted')
    <div class="validity-notice">
        ⚠ This quotation expired on {{ \Carbon\Carbon::parse($quotation->valid_until)->format('M d, Y') }}.
        Please contact us for a renewed quotation.
    </div>
    @endif

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
            @foreach($quotation->items as $item)
            <tr>
                <td>{{ $item->description }}</td>
                <td class="right">{{ $item->quantity }}</td>
                <td class="right">{{ $quotation->currency }} {{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ $item->tax_rate }}%</td>
                <td class="right">{{ $quotation->currency }} {{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span class="totals-label">Subtotal</span>
            <span class="totals-value">{{ $quotation->currency }} {{ number_format($quotation->subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span class="totals-label">Tax</span>
            <span class="totals-value">{{ $quotation->currency }} {{ number_format($quotation->tax_amount, 2) }}</span>
        </div>
        <div class="totals-row totals-total">
            <span class="totals-label">Total</span>
            <span class="totals-value">{{ $quotation->currency }} {{ number_format($quotation->total_amount, 2) }}</span>
        </div>
    </div>
    <div class="clearfix"></div>

    @if($quotation->notes)
    <div class="notes">
        <div class="label">Notes</div>
        <div class="info">{{ $quotation->notes }}</div>
    </div>
    @endif

    @if($quotation->workspace->bank_name || $quotation->workspace->bank_account_number)
    <div style="margin-top: 20px; padding: 16px; background: #f6f6f5; border-radius: 8px; border: 1px solid #ececec;">
        <div class="label">Payment Details (upon acceptance)</div>
        <table style="width: 100%; font-size: 11px; margin-top: 8px;">
            @if($quotation->workspace->bank_name)
            <tr>
                <td style="color: #6b7280; padding: 2px 0; width: 120px;">Bank:</td>
                <td style="font-weight: 600;">{{ $quotation->workspace->bank_name }}</td>
            </tr>
            @endif
            @if($quotation->workspace->bank_account_number)
            <tr>
                <td style="color: #6b7280; padding: 2px 0;">Account:</td>
                <td style="font-weight: 600; font-family: monospace;">{{ $quotation->workspace->bank_account_number }}</td>
            </tr>
            @endif
            @if($quotation->workspace->bank_account_name)
            <tr>
                <td style="color: #6b7280; padding: 2px 0;">Name:</td>
                <td style="font-weight: 600;">{{ $quotation->workspace->bank_account_name }}</td>
            </tr>
            @endif
        </table>
    </div>
    @endif

    <div class="footer">
        @if($quotation->workspace->invoice_footer)
            {{ $quotation->workspace->invoice_footer }}<br>
        @else
            Thank you for considering our services!<br>
        @endif
        Generated by Invoxa | Powered by iremeCloud
    </div>
</body>
</html>
