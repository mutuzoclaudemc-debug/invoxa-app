<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationItem;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = Quotation::where('workspace_id', $request->user()->workspace->id)
            ->with(['customer', 'items'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $quotations
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $workspace = $user->workspace;
        
        if (!$workspace) {
            return response()->json(['success' => false, 'message' => 'No workspace found'], 404);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date',
            'currency' => 'required|string|max:3',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Generate quotation number
        $lastQuotation = Quotation::where('workspace_id', $workspace->id)
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = $lastQuotation ? (int) substr($lastQuotation->quotation_number, 4) + 1 : 1;
        $quotationNumber = 'QUO-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Calculate totals
        $subtotal = 0;
        $taxAmount = 0;
        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineSubtotal * (($item['tax_rate'] ?? 0) / 100);
            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
        }
        $total = $subtotal + $taxAmount;

        // Create quotation
        $quotation = Quotation::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $validated['customer_id'],
            'created_by_id' => $user->id,
            'quotation_number' => $quotationNumber,
            'status' => 'draft',
            'issue_date' => $validated['issue_date'],
            'valid_until' => $validated['valid_until'],
            'currency' => $validated['currency'],
            'notes' => $validated['notes'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $total,
        ]);

        // Create line items
        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineSubtotal * (($item['tax_rate'] ?? 0) / 100);
            $quotation->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'line_total' => $lineSubtotal + $lineTax,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quotation created',
            'data' => $quotation->load(['customer', 'items'])
        ]);
    }

    public function show(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $quotation->load(['customer', 'items', 'workspace'])
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'sometimes|in:draft,sent,accepted,rejected,expired',
        ]);

        $quotation->update($validated);

        return response()->json([
            'success' => true,
            'data' => $quotation
        ]);
    }

    public function destroy(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $quotation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quotation deleted'
        ]);
    }

    public function convertToInvoice(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $workspace = $request->user()->workspace;

        // Check plan limit
        if (!$workspace->canCreateInvoice()) {
            $limits = $workspace->getLimits();
            return response()->json([
                'success' => false,
                'message' => 'You have reached your monthly invoice limit.',
                'error_code' => 'PLAN_LIMIT_REACHED',
                'data' => [
                    'current_plan' => $workspace->plan,
                    'invoices_used' => $workspace->invoices_this_month,
                    'invoices_limit' => $limits['invoices_per_month'],
                ]
            ], 403);
        }

        // Generate invoice number
        $lastInvoice = \App\Models\Invoice::where('workspace_id', $workspace->id)
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = $lastInvoice ? (int) substr($lastInvoice->invoice_number, 4) + 1 : 1;
        $invoiceNumber = 'INV-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // Create invoice from quotation
        $invoice = \App\Models\Invoice::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $quotation->customer_id,
            'created_by_id' => $request->user()->id,
            'invoice_number' => $invoiceNumber,
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => $quotation->currency,
            'notes' => 'Converted from ' . $quotation->quotation_number . '. ' . ($quotation->notes ?? ''),
            'subtotal' => $quotation->subtotal,
            'tax_amount' => $quotation->tax_amount,
            'total_amount' => $quotation->total_amount,
            'paid_amount' => 0,
        ]);

        // Copy items
        foreach ($quotation->items as $item) {
            $invoice->items()->create([
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
                'line_total' => $item->line_total,
            ]);
        }

        // Update quotation status
        $quotation->update(['status' => 'accepted']);

        // Increment invoice count
        $workspace->incrementInvoiceCount();

        return response()->json([
            'success' => true,
            'message' => 'Quotation converted to invoice',
            'data' => $invoice->load(['customer', 'items'])
        ]);
    }
}
