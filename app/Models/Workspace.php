<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    protected $fillable = [
        'name', 'owner_id', 'plan', 'currency',
        'plan_status', 'plan_expires_at',
        'invoices_this_month', 'last_invoice_month',
        'logo_url', 'company_email', 'company_phone',
        'company_address', 'tax_id', 'website',
        'invoice_footer', 'brand_color',
        'bank_name', 'bank_account_number', 'bank_account_name',
        'tax_type', 'tax_rate', 'tax_label', 'tax_inclusive',
        'invoice_template',
        'referral_code', 'referral_count', 'referral_bonus_invoices', 'referred_by',
    ];

    protected $casts = [
        'plan_expires_at'         => 'datetime',
        'invoices_this_month'     => 'integer',
        'tax_rate'                => 'float',
        'tax_inclusive'           => 'boolean',
        'referral_count'          => 'integer',
        'referral_bonus_invoices' => 'integer',
    ];

    const FREE_BASE_INVOICES   = 2;
    const FREE_MAX_INVOICES    = 5;
    const REFERRAL_BONUS_EACH  = 1;
    const MAX_REFERRAL_BONUS   = 3;

    // Plan limits configuration
    const PLAN_LIMITS = [
        'free' => [
            'invoices_per_month' => 2,  // base quota; referral bonus can raise this to 5
            'max_users' => 1,
            'custom_branding' => false,
            'price' => 0,
        ],
        'pro' => [
            'invoices_per_month' => -1,
            'max_users' => 5,
            'custom_branding' => true,
            'price' => 10000,
        ],
        'business' => [
            'invoices_per_month' => -1,
            'max_users' => -1,
            'custom_branding' => true,
            'price' => 20000,
        ],
    ];

    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 8));
        } while (self::where('referral_code', $code)->exists());

        return $code;
    }

    public function freeCap(): int
    {
        $bonus = min((int)($this->referral_bonus_invoices ?? 0), self::MAX_REFERRAL_BONUS);
        return self::FREE_BASE_INVOICES + $bonus;
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function getLimits()
    {
        return self::PLAN_LIMITS[$this->plan] ?? self::PLAN_LIMITS['free'];
    }

    public function canCreateInvoice()
    {
        $limits = $this->getLimits();
        if ($limits['invoices_per_month'] === -1) return true;

        // Free plan: lifetime cap (never resets), referral bonus raises the ceiling
        return $this->invoices_this_month < $this->freeCap();
    }

    public function incrementInvoiceCount()
    {
        $limits = $this->getLimits();

        if ($limits['invoices_per_month'] === -1) {
            // Paid plans: track monthly usage (resets each month)
            $currentMonth = now()->format('Y-m');
            if ($this->last_invoice_month !== $currentMonth) {
                $this->update(['invoices_this_month' => 1, 'last_invoice_month' => $currentMonth]);
            } else {
                $this->increment('invoices_this_month');
            }
            return;
        }

        // Free plan: increment total (never resets — it's a lifetime trial cap)
        $this->increment('invoices_this_month');
    }
}