<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->workspace;

        $invoices = Invoice::where('workspace_id', $workspace->id)
            ->with(['customer', 'items'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $invoices,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $workspace = $request->user()->workspace;

        $count = Invoice::where('workspace_id', $workspace->id)->count() + 1;
        $invoiceNumber = 'INV-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $subtotal = 0;
        $taxAmount = 0;

        foreach ($validated['items'] as $item) {
            $itemSubtotal = $item['quantity'] * $item['unit_price'];
            $itemTax = $itemSubtotal * (($item['tax_rate'] ?? 0) / 100);
            $subtotal += $itemSubtotal;
            $taxAmount += $itemTax;
        }

        $totalAmount = $subtotal + $taxAmount;

        $invoice = Invoice::create([
            'workspace_id' => $workspace->id,
            'customer_id' => $validated['customer_id'],
            'created_by_id' => $request->user()->id,
            'invoice_number' => $invoiceNumber,
            'status' => 'draft',
            'issue_date' => $validated['issue_date'],
            'due_date' => $validated['due_date'],
            'currency' => $validated['currency'],
            'notes' => $validated['notes'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        foreach ($validated['items'] as $itemData) {
            $itemSubtotal = $itemData['quantity'] * $itemData['unit_price'];
            $itemTax = $itemSubtotal * (($itemData['tax_rate'] ?? 0) / 100);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'tax_rate' => $itemData['tax_rate'] ?? 0,
                'line_total' => $itemSubtotal + $itemTax,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Invoice created',
            'data' => $invoice->load(['customer', 'items']),
        ], 201);
    }

    public function show(Invoice $invoice)
    {
        return response()->json([
            'success' => true,
            'data' => $invoice->load(['customer', 'items']),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft invoices can be edited',
            ], 422);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'due_date' => 'sometimes|date',
        ]);

        $invoice->update($validated);

        return response()->json([
            'success' => true,
            'data' => $invoice,
        ]);
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted',
        ]);
    }
}