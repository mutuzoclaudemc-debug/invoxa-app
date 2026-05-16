<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function current(Request $request)
    {
        $workspace = $request->user()->workspace;
        $limits    = $workspace->getLimits();
        $isPaid    = $limits['invoices_per_month'] === -1;

        // For free plan, the actual cap includes referral bonus
        $effectiveLimit = $isPaid ? -1 : $workspace->freeCap();
        $used           = $workspace->invoices_this_month;
        $remaining      = $isPaid ? 'unlimited' : max(0, $effectiveLimit - $used);

        return response()->json([
            'success' => true,
            'data' => [
                'current_plan'    => $workspace->plan,
                'plan_status'     => $workspace->plan_status,
                'plan_expires_at' => $workspace->plan_expires_at,
                'limits'          => array_merge($limits, ['invoices_per_month' => $effectiveLimit]),
                'usage' => [
                    'invoices_this_month' => $used,
                    'invoices_limit'      => $effectiveLimit,
                    'invoices_remaining'  => $remaining,
                ],
                'referral' => [
                    'code'          => $workspace->referral_code,
                    'bonus_invoices'=> (int)$workspace->referral_bonus_invoices,
                    'referral_count'=> (int)$workspace->referral_count,
                    'max_free'      => Workspace::FREE_MAX_INVOICES,
                    'base_free'     => Workspace::FREE_BASE_INVOICES,
                ],
                'all_plans' => Workspace::PLAN_LIMITS,
            ]
        ]);
    }

    public function upgrade(Request $request)
    {
        $validated = $request->validate([
            'plan' => 'required|in:free,pro,business',
            'payment_method' => 'required|in:mtn,airtel',
            'phone_number' => 'required|string',
        ]);

        $workspace = $request->user()->workspace;
        $newPlan = $validated['plan'];
        $price = Workspace::PLAN_LIMITS[$newPlan]['price'];

        // TODO: In production, integrate with MTN MoMo / Airtel Money API here
        // For now, simulate successful payment
        
        $transactionId = 'TXN-' . strtoupper(uniqid());
        
        // Update workspace plan
        $workspace->update([
            'plan' => $newPlan,
            'plan_status' => 'active',
            'plan_expires_at' => now()->addMonth(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Plan upgraded successfully! 🎉',
            'data' => [
                'plan' => $newPlan,
                'transaction_id' => $transactionId,
                'amount_paid' => $price,
                'expires_at' => $workspace->plan_expires_at,
            ]
        ]);
    }
}
