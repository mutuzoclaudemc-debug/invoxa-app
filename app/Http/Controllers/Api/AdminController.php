<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Invoice;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    private function checkAdmin($user)
    {
        if (!$user || $user->role !== 'admin') {
            abort(403, 'Admin access required');
        }
    }

    public function dashboard(Request $request)
    {
        $this->checkAdmin($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'total_users' => User::count(),
                'total_workspaces' => Workspace::count(),
                'free_subscribers' => Workspace::where('plan', 'free')->count(),
                'pro_subscribers' => Workspace::where('plan', 'pro')->count(),
                'business_subscribers' => Workspace::where('plan', 'business')->count(),
                'total_invoices' => Invoice::count(),
                'monthly_revenue' => (Workspace::where('plan', 'pro')->count() * 15000) + (Workspace::where('plan', 'business')->count() * 35000),
            ]
        ]);
    }

    public function subscribers(Request $request)
    {
        $this->checkAdmin($request->user());

        $plan = $request->get('plan');
        $query = Workspace::with('owner');
        
        if ($plan) {
            $query->where('plan', $plan);
        }
        
        $workspaces = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $workspaces->map(function($ws) {
                return [
                    'id' => $ws->id,
                    'workspace_name' => $ws->name,
                    'owner_name' => $ws->owner ? $ws->owner->first_name . ' ' . $ws->owner->last_name : 'N/A',
                    'owner_email' => $ws->owner ? $ws->owner->email : 'N/A',
                    'plan' => $ws->plan,
                    'plan_status' => $ws->plan_status ?? 'active',
                    'plan_expires_at' => $ws->plan_expires_at,
                    'invoices_count' => $ws->invoices_this_month ?? 0,
                    'created_at' => $ws->created_at,
                    'last_billing_sent' => null,
                ];
            })
        ]);
    }

    public function sendBillingInvoice(Request $request, $workspaceId)
    {
        $this->checkAdmin($request->user());

        $workspace = Workspace::with('owner')->find($workspaceId);
        if (!$workspace) {
            return response()->json(['success' => false, 'message' => 'Workspace not found'], 404);
        }

        if ($workspace->plan === 'free') {
            return response()->json(['success' => false, 'message' => 'Cannot bill free plan workspaces'], 400);
        }

        $price = $workspace->plan === 'pro' ? 15000 : 35000;
        $planName = ucfirst($workspace->plan);
        
        try {
            $apiKey = env('RESEND_API_KEY');
            if (!$apiKey) {
                return response()->json(['success' => false, 'message' => 'Email service not configured'], 500);
            }

            $html = view('emails.billing-invoice', [
                'workspace' => $workspace,
                'price' => $price,
                'planName' => $planName,
                'invoiceNumber' => 'BILL-' . now()->format('Ymd') . '-' . $workspace->id,
            ])->render();

            $resend = \Resend::client($apiKey);
            $resend->emails->send([
                'from' => 'Invoxa Billing <onboarding@resend.dev>',
                'to' => [$workspace->owner->email],
                'subject' => 'Your Invoxa ' . $planName . ' Plan - Monthly Invoice',
                'html' => $html,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Billing invoice sent to ' . $workspace->owner->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send: ' . $e->getMessage()
            ], 500);
        }
    }

    public function sendBillingToAll(Request $request)
    {
        $this->checkAdmin($request->user());

        $workspaces = Workspace::with('owner')
            ->whereIn('plan', ['pro', 'business'])
            ->get();
        
        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($workspaces as $workspace) {
            try {
                $price = $workspace->plan === 'pro' ? 15000 : 35000;
                $planName = ucfirst($workspace->plan);
                
                $html = view('emails.billing-invoice', [
                    'workspace' => $workspace,
                    'price' => $price,
                    'planName' => $planName,
                    'invoiceNumber' => 'BILL-' . now()->format('Ymd') . '-' . $workspace->id,
                ])->render();

                $resend = \Resend::client(env('RESEND_API_KEY'));
                $resend->emails->send([
                    'from' => 'Invoxa Billing <onboarding@resend.dev>',
                    'to' => [$workspace->owner->email],
                    'subject' => 'Your Invoxa ' . $planName . ' Plan - Monthly Invoice',
                    'html' => $html,
                ]);
                $sent++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $workspace->owner->email . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent: {$sent}, Failed: {$failed}",
            'data' => ['sent' => $sent, 'failed' => $failed, 'errors' => $errors]
        ]);
    }

    public function updateUserRole(Request $request, $userId)
    {
        $this->checkAdmin($request->user());

        $validated = $request->validate([
            'role' => 'required|in:user,admin',
        ]);

        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->update(['role' => $validated['role']]);

        return response()->json([
            'success' => true,
            'message' => 'Role updated',
            'data' => $user
        ]);
    }
}
