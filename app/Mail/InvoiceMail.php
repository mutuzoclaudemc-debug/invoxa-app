<?php

namespace App\Mail;

use App\Models\Invoice;

class InvoiceMail
{
    public function __construct(public Invoice $invoice, public string $customMessage = '') {}
    
    public function build(): string
    {
        $inv = $this->invoice;
        $items = '';
        foreach ($inv->items as $item) {
            $items .= '<tr>
                <td style="padding:12px;border-bottom:1px solid #ececec;">' . htmlspecialchars($item->description) . '</td>
                <td style="padding:12px;border-bottom:1px solid #ececec;text-align:right;">' . $item->quantity . '</td>
                <td style="padding:12px;border-bottom:1px solid #ececec;text-align:right;">' . number_format($item->unit_price, 2) . '</td>
                <td style="padding:12px;border-bottom:1px solid #ececec;text-align:right;font-weight:600;">' . number_format($item->line_total, 2) . '</td>
            </tr>';
        }
        
        $currency = $inv->currency;
        $customMsg = $this->customMessage ? '<p style="color:#6b7280;margin:20px 0;line-height:1.6;">' . nl2br(htmlspecialchars($this->customMessage)) . '</p>' : '';
        
        return '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f6f6f5;font-family:-apple-system,BlinkMacSystemFont,sans-serif;">
    <div style="max-width:600px;margin:40px auto;background:white;border-radius:16px;overflow:hidden;border:1px solid #ececec;">
        
        <!-- Header with Logo -->
        <div style="background:#0b0d10;padding:32px 40px;display:flex;align-items:center;gap:14px;">
            <div style="width:48px;height:48px;background:#fff;border-radius:11px;display:inline-block;text-align:center;line-height:48px;">
                <span style="color:#0b0d10;font-weight:700;font-size:24px;letter-spacing:-1px;">IX</span>
            </div>
            <span style="color:white;font-size:24px;font-weight:600;letter-spacing:-0.5px;margin-left:14px;vertical-align:middle;">invoxa</span>
        </div>
        
        <!-- Body -->
        <div style="padding:40px;">
            <h1 style="margin:0 0 8px;font-size:28px;letter-spacing:-1px;">Invoice ' . $inv->invoice_number . '</h1>
            <p style="color:#6b7280;margin:0 0 32px;">From <strong style="color:#0b0d10;">' . htmlspecialchars($inv->workspace->name) . '</strong></p>
            
            ' . $customMsg . '
            
            <!-- Summary Box -->
            <div style="background:#f6f6f5;border-radius:12px;padding:24px;margin:24px 0;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:#6b7280;">Issue date</span>
                    <span><strong>' . $inv->issue_date->format('M d, Y') . '</strong></span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:#6b7280;">Due date</span>
                    <span><strong>' . $inv->due_date->format('M d, Y') . '</strong></span>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid #ececec;margin-top:12px;">
                    <span style="color:#6b7280;">Total amount</span>
                    <span style="font-size:24px;font-weight:700;letter-spacing:-0.5px;">' . $currency . ' ' . number_format($inv->total_amount, 2) . '</span>
                </div>
            </div>
            
            <!-- Items table -->
            <h3 style="margin:32px 0 12px;font-size:14px;text-transform:uppercase;color:#6b7280;letter-spacing:1px;">Line items</h3>
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <thead>
                    <tr style="background:#f6f6f5;">
                        <th style="padding:12px;text-align:left;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Description</th>
                        <th style="padding:12px;text-align:right;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Qty</th>
                        <th style="padding:12px;text-align:right;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Price</th>
                        <th style="padding:12px;text-align:right;font-size:12px;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">Total</th>
                    </tr>
                </thead>
                <tbody>' . $items . '</tbody>
            </table>
            
            <!-- Totals -->
            <div style="margin-top:24px;padding-top:24px;border-top:2px solid #0b0d10;">
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:#6b7280;">Subtotal</span>
                    <span>' . $currency . ' ' . number_format($inv->subtotal, 2) . '</span>
                </div>
                <div style="display:flex;justify-content:space-between;margin-bottom:8px;">
                    <span style="color:#6b7280;">Tax</span>
                    <span>' . $currency . ' ' . number_format($inv->tax_amount, 2) . '</span>
                </div>
                <div style="display:flex;justify-content:space-between;padding-top:12px;border-top:1px solid #ececec;font-size:20px;font-weight:700;">
                    <span>Total</span>
                    <span>' . $currency . ' ' . number_format($inv->total_amount, 2) . '</span>
                </div>
            </div>
            
            ' . ($inv->notes ? '<div style="margin-top:32px;padding:16px;background:#f6f6f5;border-radius:8px;"><strong style="display:block;margin-bottom:8px;">Notes</strong><p style="margin:0;color:#6b7280;">' . nl2br(htmlspecialchars($inv->notes)) . '</p></div>' : '') . '
        </div>
        
        <!-- Footer -->
        <div style="padding:24px 40px;background:#f6f6f5;border-top:1px solid #ececec;text-align:center;">
            <p style="margin:0;color:#6b7280;font-size:13px;">Sent via <strong style="color:#0b0d10;">Invoxa</strong> — Your business finances, simplified.</p>
        </div>
    </div>
</body>
</html>';
    }
}
