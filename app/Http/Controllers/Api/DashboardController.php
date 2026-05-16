<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
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

    public function reports(Request $request)
    {
        $workspace   = $request->user()->workspace;
        $workspaceId = $workspace->id;

        // Revenue stats
        $totalPaid = Invoice::where('workspace_id', $workspaceId)->where('status', 'paid')->sum('total_amount');
        $thisMonth = Invoice::where('workspace_id', $workspaceId)->where('status', 'paid')
            ->whereRaw("strftime('%Y-%m', paid_at) = strftime('%Y-%m', 'now')")->sum('total_amount');
        $thisYear  = Invoice::where('workspace_id', $workspaceId)->where('status', 'paid')
            ->whereRaw("strftime('%Y', paid_at) = strftime('%Y', 'now')")->sum('total_amount');

        // Outstanding
        $outstanding = Invoice::where('workspace_id', $workspaceId)
            ->whereIn('status', ['sent', 'overdue', 'partially_paid'])->sum('total_amount');
        $overdue = Invoice::where('workspace_id', $workspaceId)
            ->where('status', 'overdue')->sum('total_amount');

        // Top clients by paid revenue
        $topClients = Invoice::where('invoices.workspace_id', $workspaceId)
            ->where('invoices.status', 'paid')
            ->join('customers', 'invoices.customer_id', '=', 'customers.id')
            ->select('customers.name', DB::raw('SUM(invoices.total_amount) as total'), DB::raw('COUNT(invoices.id) as count'))
            ->groupBy('customers.id', 'customers.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        // Top items by revenue
        $topItems = DB::table('invoice_items')
            ->join('invoices', 'invoice_items.invoice_id', '=', 'invoices.id')
            ->where('invoices.workspace_id', $workspaceId)
            ->where('invoices.status', 'paid')
            ->select(
                'invoice_items.description',
                DB::raw('SUM(invoice_items.quantity * invoice_items.unit_price) as revenue'),
                DB::raw('SUM(invoice_items.quantity) as units_sold'),
                DB::raw('COUNT(DISTINCT invoice_items.invoice_id) as invoice_count')
            )
            ->groupBy('invoice_items.description')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        // Expense breakdown by category
        $expensesByCategory = Expense::where('workspace_id', $workspaceId)
            ->select('category', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $totalExpenses = Expense::where('workspace_id', $workspaceId)->sum('amount');

        return response()->json([
            'success' => true,
            'data' => [
                'revenue' => [
                    'total_paid' => (float)$totalPaid,
                    'this_month' => (float)$thisMonth,
                    'this_year'  => (float)$thisYear,
                    'outstanding'=> (float)$outstanding,
                    'overdue'    => (float)$overdue,
                ],
                'top_clients'          => $topClients,
                'top_items'            => $topItems,
                'expenses_by_category' => $expensesByCategory,
                'total_expenses'       => (float)$totalExpenses,
            ],
        ]);
    }
}
