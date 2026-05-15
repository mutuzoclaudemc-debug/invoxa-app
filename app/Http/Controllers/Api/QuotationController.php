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
        $workspaceId = $request->user()->workspace->id;

        $query = Quotation::where('workspace_id', $workspaceId)
            ->with(['customer', 'items']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('quotation_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quotations = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json(['success' => true, 'data' => $quotations]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $workspace = $user->workspace;

        if (!$workspace) {
            return response()->json(['success' => false, 'message' => 'No workspace found'], 404);
        }

        $validated = $request->validate([
            'customer_id'  => 'required|exists:customers,id',
            'issue_date'   => 'required|date',
            'valid_until'  => 'required|date',
            'currency'     => 'required|string|max:3',
            'notes'        => 'nullable|string',
            'items'        => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity'    => 'required|numeric|min:0.01',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.tax_rate'    => 'nullable|numeric|min:0|max:100',
        ]);

        $lastQuotation = Quotation::where('workspace_id', $workspace->id)
            ->orderBy('id', 'desc')->first();
        $nextNumber = $lastQuotation ? (int) substr($lastQuotation->quotation_number, 4) + 1 : 1;
        $quotationNumber = 'QUO-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        $subtotal = 0;
        $taxAmount = 0;
        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineSubtotal * (($item['tax_rate'] ?? 0) / 100);
            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
        }

        $quotation = Quotation::create([
            'workspace_id'     => $workspace->id,
            'customer_id'      => $validated['customer_id'],
            'created_by_id'    => $user->id,
            'quotation_number' => $quotationNumber,
            'status'           => 'draft',
            'issue_date'       => $validated['issue_date'],
            'valid_until'      => $validated['valid_until'],
            'currency'         => $validated['currency'],
            'notes'            => $validated['notes'] ?? null,
            'subtotal'         => $subtotal,
            'tax_amount'       => $taxAmount,
            'total_amount'     => $subtotal + $taxAmount,
        ]);

        foreach ($validated['items'] as $item) {
            $lineSubtotal = $item['quantity'] * $item['unit_price'];
            $lineTax = $lineSubtotal * (($item['tax_rate'] ?? 0) / 100);
            $quotation->items()->create([
                'description' => $item['description'],
                'quantity'    => $item['quantity'],
                'unit_price'  => $item['unit_price'],
                'tax_rate'    => $item['tax_rate'] ?? 0,
                'line_total'  => $lineSubtotal + $lineTax,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Quotation created',
            'data'    => $quotation->load(['customer', 'items']),
        ], 201);
    }

    public function show(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => $quotation->load(['customer', 'items', 'workspace']),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status'      => 'sometimes|in:draft,sent,accepted,rejected,expired',
            'notes'       => 'nullable|string',
            'valid_until' => 'sometimes|date',
        ]);

        $quotation->update($validated);

        return response()->json(['success' => true, 'data' => $quotation]);
    }

    public function destroy(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $quotation->delete();

        return response()->json(['success' => true, 'message' => 'Quotation deleted']);
    }

    public function convertToInvoice(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $workspace = $request->user()->workspace;

        if (!$workspace->canCreateInvoice()) {
            $limits = $workspace->getLimits();
            return response()->json([
                'success'    => false,
                'message'    => 'You have reached your monthly invoice limit.',
                'error_code' => 'PLAN_LIMIT_REACHED',
                'data'       => [
                    'current_plan'   => $workspace->plan,
                    'invoices_used'  => $workspace->invoices_this_month,
                    'invoices_limit' => $limits['invoices_per_month'],
                ],
            ], 403);
        }

        $count = \App\Models\Invoice::where('workspace_id', $workspace->id)->count() + 1;
        $invoiceNumber = 'INV-' . str_pad($count, 5, '0', STR_PAD_LEFT);

        $invoice = \App\Models\Invoice::create([
            'workspace_id'   => $workspace->id,
            'customer_id'    => $quotation->customer_id,
            'created_by_id'  => $request->user()->id,
            'invoice_number' => $invoiceNumber,
            'status'         => 'draft',
            'issue_date'     => now()->toDateString(),
            'due_date'       => now()->addDays(30)->toDateString(),
            'currency'       => $quotation->currency,
            'notes'          => 'Converted from ' . $quotation->quotation_number . ($quotation->notes ? '. ' . $quotation->notes : ''),
            'subtotal'       => $quotation->subtotal,
            'tax_amount'     => $quotation->tax_amount,
            'total_amount'   => $quotation->total_amount,
            'amount_paid'    => 0,
        ]);

        foreach ($quotation->items as $item) {
            $invoice->items()->create([
                'product_id'  => $item->product_id,
                'description' => $item->description,
                'quantity'    => $item->quantity,
                'unit_price'  => $item->unit_price,
                'tax_rate'    => $item->tax_rate,
                'line_total'  => $item->line_total,
            ]);
        }

        $quotation->update(['status' => 'accepted']);
        $workspace->incrementInvoiceCount();

        return response()->json([
            'success' => true,
            'message' => 'Quotation converted to invoice',
            'data'    => $invoice->load(['customer', 'items']),
        ]);
    }

    // --- Sharing & PDF (parity with InvoiceController) ---

    public function getShareToken(Quotation $quotation, Request $request)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $hash = substr(md5($quotation->id . $quotation->quotation_number . $quotation->created_at), 0, 12);
        $token = $quotation->id . '-' . $hash;

        return response()->json([
            'success' => true,
            'data'    => ['token' => $token, 'quotation_number' => $quotation->quotation_number],
        ]);
    }

    public function publicView(string $token)
    {
        $parts = explode('-', $token, 2);
        if (count($parts) !== 2) {
            return response()->json(['success' => false, 'message' => 'Invalid link'], 404);
        }

        $quotation = Quotation::with(['items', 'customer', 'workspace'])->find($parts[0]);

        if (!$quotation) {
            return response()->json(['success' => false, 'message' => 'Quotation not found'], 404);
        }

        $expectedHash = substr(md5($quotation->id . $quotation->quotation_number . $quotation->created_at), 0, 12);
        if ($parts[1] !== $expectedHash) {
            return response()->json(['success' => false, 'message' => 'Invalid link'], 404);
        }

        return response()->json(['success' => true, 'data' => $quotation]);
    }

    public function downloadPdf(Quotation $quotation, Request $request)
    {
        if ($request->has('token')) {
            $parts = explode('-', $request->token, 2);
            if (count($parts) !== 2 || (int)$parts[0] !== $quotation->id) abort(404);
            $expectedHash = substr(md5($quotation->id . $quotation->quotation_number . $quotation->created_at), 0, 12);
            if ($parts[1] !== $expectedHash) abort(404);
        } else {
            if (!$request->user() || $quotation->workspace_id !== $request->user()->workspace->id) {
                abort(403);
            }
        }

        $quotation->load(['items', 'customer', 'workspace']);
        $pdf = \PDF::loadView('quotations.pdf', ['quotation' => $quotation]);
        return $pdf->download($quotation->quotation_number . '.pdf');
    }

    public function sendEmail(Request $request, Quotation $quotation)
    {
        if ($quotation->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email'   => 'required|email',
            'message' => 'nullable|string|max:1000',
        ]);

        try {
            $quotation->load(['items', 'customer', 'workspace']);

            $hash = substr(md5($quotation->id . $quotation->quotation_number . $quotation->created_at), 0, 12);
            $token = $quotation->id . '-' . $hash;
            $shareUrl = config('app.frontend_url', 'http://localhost:5173') . '/q/' . $token;

            $apiKey = env('RESEND_API_KEY');
            if (!$apiKey) {
                return response()->json(['success' => false, 'message' => 'Email service not configured'], 500);
            }

            $currencySymbols = ['RWF' => 'RWF ', 'USD' => '$', 'EUR' => '€', 'GBP' => '£'];
            $symbol = $currencySymbols[$quotation->currency] ?? $quotation->currency . ' ';
            $total = $symbol . number_format($quotation->total_amount, 2);

            $customMessage = $validated['message'] ?? '';
            $html = $this->buildQuotationEmail($quotation, $total, $shareUrl, $customMessage);

            $resend = \Resend::client($apiKey);
            $resend->emails->send([
                'from'    => 'Invoxa <onboarding@resend.dev>',
                'to'      => [$validated['email']],
                'subject' => 'Quotation ' . $quotation->quotation_number . ' from ' . $quotation->workspace->name,
                'html'    => $html,
            ]);

            $quotation->update(['status' => 'sent']);

            return response()->json([
                'success' => true,
                'message' => 'Quotation sent successfully',
                'data'    => $quotation->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function buildQuotationEmail(Quotation $quotation, string $total, string $shareUrl, string $customMessage): string
    {
        $wsName   = htmlspecialchars($quotation->workspace->name);
        $custName = htmlspecialchars($quotation->customer->name);
        $qNum     = htmlspecialchars($quotation->quotation_number);
        $validUntil = $quotation->valid_until ? date('F j, Y', strtotime((string)$quotation->valid_until)) : 'N/A';
        $msg      = htmlspecialchars($customMessage ?: 'Please find attached your quotation. We look forward to working with you.');

        return '<!DOCTYPE html><html><body style="font-family:sans-serif;background:#f5f5f5;margin:0;padding:20px;">'
            . '<div style="max-width:600px;margin:0 auto;background:#fff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">'
            . '<div style="background:#111;padding:24px 32px;">'
            . '<h1 style="color:#fff;margin:0;font-size:20px;">' . $wsName . '</h1>'
            . '<p style="color:#9ca3af;margin:4px 0 0;font-size:13px;">Quotation ' . $qNum . '</p>'
            . '</div>'
            . '<div style="padding:32px;">'
            . '<p style="color:#374151;margin:0 0 16px;">Dear ' . $custName . ',</p>'
            . '<p style="color:#374151;margin:0 0 24px;">' . $msg . '</p>'
            . '<div style="background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-bottom:24px;">'
            . '<table style="width:100%;border-collapse:collapse;">'
            . '<tr><td style="padding:6px 0;color:#6b7280;font-size:14px;">Quotation No.</td><td style="padding:6px 0;text-align:right;font-weight:600;font-size:14px;">' . $qNum . '</td></tr>'
            . '<tr><td style="padding:6px 0;color:#6b7280;font-size:14px;">Valid Until</td><td style="padding:6px 0;text-align:right;font-weight:600;font-size:14px;">' . $validUntil . '</td></tr>'
            . '<tr style="border-top:2px solid #111;"><td style="padding:12px 0 0;font-weight:700;font-size:16px;">Total</td><td style="padding:12px 0 0;text-align:right;font-weight:700;font-size:20px;">' . $total . '</td></tr>'
            . '</table></div>'
            . '<a href="' . $shareUrl . '" style="display:inline-block;background:#111;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;">View Quotation</a>'
            . '</div></div></body></html>';
    }
}
