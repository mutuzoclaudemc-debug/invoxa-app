<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f6f6f5;font-family:-apple-system,BlinkMacSystemFont,sans-serif;">
    <div style="max-width:600px;margin:40px auto;background:white;border-radius:16px;overflow:hidden;border:1px solid #ececec;">
        <div style="background:#0b0d10;padding:32px 40px;">
            <span style="color:white;font-size:24px;font-weight:600;letter-spacing:-0.5px;">invoxa</span>
        </div>
        <div style="padding:40px;">
            <h1 style="margin:0 0 16px;font-size:24px;letter-spacing:-1px;">Monthly Subscription Invoice</h1>
            <p style="color:#6b7280;line-height:1.6;">Hi {{ $workspace->owner->first_name }},</p>
            <p style="color:#6b7280;line-height:1.6;">Thank you for being an Invoxa {{ $planName }} subscriber! Your monthly invoice is ready.</p>
            
            <div style="background:#f6f6f5;border-radius:12px;padding:24px;margin:24px 0;">
                <div style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">INVOICE NUMBER</div>
                    <div style="font-weight:600;font-family:monospace;">{{ $invoiceNumber }}</div>
                </div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">BILLING PERIOD</div>
                    <div style="font-weight:600;">{{ now()->format('F Y') }}</div>
                </div>
                <div style="margin-bottom:12px;">
                    <div style="font-size:12px;color:#6b7280;margin-bottom:4px;">PLAN</div>
                    <div style="font-weight:600;">{{ $planName }} Plan</div>
                </div>
                <div style="padding-top:16px;border-top:1px solid #ececec;display:flex;justify-content:space-between;">
                    <span style="font-weight:600;font-size:18px;">Total Due</span>
                    <span style="font-weight:700;font-size:24px;">RWF {{ number_format($price) }}</span>
                </div>
            </div>

            <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:16px;margin:24px 0;">
                <strong style="color:#92400e;">Payment Methods:</strong>
                <div style="margin-top:8px;color:#92400e;font-size:14px;">
                    📱 MTN Mobile Money: *182*8*1*INVOXA#<br>
                    📱 Airtel Money: *500*1*INVOXA#<br>
                    🏦 Bank Transfer: Available on request
                </div>
            </div>

            <p style="color:#6b7280;line-height:1.6;font-size:14px;">Please process this payment by the end of the month to keep your account active.</p>
            <p style="color:#6b7280;line-height:1.6;font-size:14px;">Questions? Reply to this email anytime.</p>
        </div>
        <div style="padding:24px 40px;background:#f6f6f5;text-align:center;border-top:1px solid #ececec;">
            <p style="margin:0;color:#6b7280;font-size:12px;">© {{ date('Y') }} Invoxa. Your business finances, simplified.</p>
        </div>
    </div>
</body>
</html>
