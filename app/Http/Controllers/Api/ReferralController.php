<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function info(Request $request)
    {
        $workspace = $request->user()->workspace;

        // Lazily generate referral code if missing (for existing workspaces)
        if (!$workspace->referral_code) {
            $workspace->update(['referral_code' => Workspace::generateReferralCode()]);
        }

        $cap       = $workspace->freeCap();
        $isPaid    = $workspace->plan !== 'free';
        $used      = $workspace->invoices_this_month;
        $remaining = $isPaid ? 'unlimited' : max(0, $cap - $used);

        return response()->json([
            'success' => true,
            'data' => [
                'referral_code'   => $workspace->referral_code,
                'referral_count'  => (int)$workspace->referral_count,
                'bonus_invoices'  => (int)$workspace->referral_bonus_invoices,
                'base_free'       => Workspace::FREE_BASE_INVOICES,
                'max_free'        => Workspace::FREE_MAX_INVOICES,
                'free_cap'        => $cap,
                'invoices_used'   => $used,
                'invoices_remaining' => $remaining,
                'is_paid'         => $isPaid,
                'max_bonus'       => Workspace::MAX_REFERRAL_BONUS,
            ],
        ]);
    }
}
