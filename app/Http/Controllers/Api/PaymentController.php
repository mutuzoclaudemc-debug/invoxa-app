<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request, Invoice $invoice)
    {
        if ($invoice->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $invoice->payments()->orderBy('payment_date', 'desc')->get(),
        ]);
    }

    public function store(Request $request, Invoice $invoice)
    {
        if ($invoice->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'method'       => 'required|in:cash,bank_transfer,mtn_momo,airtel_money,other',
            'reference'    => 'nullable|string|max:255',
            'notes'        => 'nullable|string',
        ]);

        $payment = Payment::create([
            ...$validated,
            'workspace_id' => $invoice->workspace_id,
            'invoice_id'   => $invoice->id,
            'currency'     => $invoice->currency,
        ]);

        // Recalculate amount paid & update status
        $totalPaid = $invoice->payments()->sum('amount') ;
        $invoice->update(['amount_paid' => $totalPaid]);

        if ($totalPaid >= $invoice->total_amount) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        } elseif ($totalPaid > 0) {
            $invoice->update(['status' => 'partially_paid']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment recorded',
            'data'    => [
                'payment'        => $payment,
                'invoice_status' => $invoice->fresh()->status,
                'amount_paid'    => $invoice->fresh()->amount_paid,
                'balance_due'    => $invoice->total_amount - $invoice->fresh()->amount_paid,
            ],
        ], 201);
    }

    public function destroy(Request $request, Invoice $invoice, Payment $payment)
    {
        if ($invoice->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $payment->delete();

        // Recalculate
        $totalPaid = $invoice->payments()->sum('amount');
        $invoice->update(['amount_paid' => $totalPaid]);

        if ($totalPaid <= 0) {
            $invoice->update(['status' => 'sent', 'paid_at' => null]);
        } elseif ($totalPaid < $invoice->total_amount) {
            $invoice->update(['status' => 'partially_paid', 'paid_at' => null]);
        }

        return response()->json(['success' => true, 'message' => 'Payment removed']);
    }
}
