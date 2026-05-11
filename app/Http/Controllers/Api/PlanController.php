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
        $limits = $workspace->getLimits();
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_plan' => $workspace->plan,
                'plan_status' => $workspace->plan_status,
                'plan_expires_at' => $workspace->plan_expires_at,
                'limits' => $limits,
                'usage' => [
                    'invoices_this_month' => $workspace->invoices_this_month,
                    'invoices_limit' => $limits['invoices_per_month'],
                    'invoices_remaining' => $limits['invoices_per_month'] === -1 
                        ? 'unlimited' 
                        : max(0, $limits['invoices_per_month'] - $workspace->invoices_this_month),
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
