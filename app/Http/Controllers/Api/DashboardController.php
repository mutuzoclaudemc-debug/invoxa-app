<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Customer;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $workspace = $request->user()->workspace;
        $workspaceId = $workspace->id;

        $totalRevenue = Invoice::where('workspace_id', $workspaceId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalOutstanding = Invoice::where('workspace_id', $workspaceId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->sum('total_amount');

        $totalInvoices = Invoice::where('workspace_id', $workspaceId)->count();

        $totalCustomers = Customer::where('workspace_id', $workspaceId)->count();

        $overdueCount = Invoice::where('workspace_id', $workspaceId)
            ->where('due_date', '<', now())
            ->whereIn('status', ['sent', 'partially_paid'])
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_outstanding' => $totalOutstanding,
                'total_invoices' => $totalInvoices,
                'total_customers' => $totalCustomers,
                'overdue_count' => $overdueCount,
            ],
        ]);
    }
}