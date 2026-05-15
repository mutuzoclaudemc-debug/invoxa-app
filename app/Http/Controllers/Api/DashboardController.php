<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Customer;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $workspace   = $request->user()->workspace;
        $workspaceId = $workspace->id;

        $totalRevenue = Invoice::where('workspace_id', $workspaceId)
            ->where('status', 'paid')
            ->sum('total_amount');

        $totalOutstanding = Invoice::where('workspace_id', $workspaceId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])
            ->sum('total_amount');

        $totalInvoices  = Invoice::where('workspace_id', $workspaceId)->count();
        $totalCustomers = Customer::where('workspace_id', $workspaceId)->count();

        $overdueCount = Invoice::where('workspace_id', $workspaceId)
            ->where('due_date', '<', now())
            ->whereIn('status', ['sent', 'partially_paid'])
            ->count();

        $totalQuotations = Quotation::where('workspace_id', $workspaceId)->count();

        $pendingQuotations = Quotation::where('workspace_id', $workspaceId)
            ->whereIn('status', ['draft', 'sent'])
            ->count();

        $totalExpenses = Expense::where('workspace_id', $workspaceId)->sum('amount');

        // Monthly revenue for last 6 months
        $monthlyRevenue = Invoice::where('workspace_id', $workspaceId)
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subMonths(6)->startOfMonth())
            ->select(
                DB::raw("strftime('%Y-%m', paid_at) as month"),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn($r) => ['month' => $r->month, 'revenue' => (float)$r->revenue]);

        // Invoice status breakdown
        $statusBreakdown = Invoice::where('workspace_id', $workspaceId)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue'      => (float)$totalRevenue,
                'total_outstanding'  => (float)$totalOutstanding,
                'total_invoices'     => $totalInvoices,
                'total_customers'    => $totalCustomers,
                'overdue_count'      => $overdueCount,
                'total_quotations'   => $totalQuotations,
                'pending_quotations' => $pendingQuotations,
                'total_expenses'     => (float)$totalExpenses,
                'monthly_revenue'    => $monthlyRevenue,
                'status_breakdown'   => $statusBreakdown,
            ],
        ]);
    }
}
