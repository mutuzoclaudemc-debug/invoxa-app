<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->workspace;

        $query = Expense::where('workspace_id', $workspace->id)
            ->with('createdBy');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('vendor', 'like', "%{$s}%")
                  ->orWhere('category', 'like', "%{$s}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('from')) {
            $query->where('expense_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('expense_date', '<=', $request->to);
        }

        $expenses = $query->orderBy('expense_date', 'desc')->paginate(20);

        $totalAmount = Expense::where('workspace_id', $workspace->id)->sum('amount');

        return response()->json([
            'success' => true,
            'data' => $expenses,
            'meta' => ['total_amount' => $totalAmount],
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $request->user()->workspace;

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'required|string|max:100',
            'amount'       => 'required|numeric|min:0',
            'currency'     => 'required|string|size:3',
            'expense_date' => 'required|date',
            'description'  => 'nullable|string',
            'vendor'       => 'nullable|string|max:255',
            'receipt_url'  => 'nullable|url|max:500',
        ]);

        $expense = Expense::create([
            ...$validated,
            'workspace_id'   => $workspace->id,
            'created_by_id'  => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Expense recorded',
            'data'    => $expense,
        ], 201);
    }

    public function show(Expense $expense, Request $request)
    {
        if ($expense->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        if ($expense->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title'        => 'sometimes|string|max:255',
            'category'     => 'sometimes|string|max:100',
            'amount'       => 'sometimes|numeric|min:0',
            'currency'     => 'sometimes|string|size:3',
            'expense_date' => 'sometimes|date',
            'description'  => 'nullable|string',
            'vendor'       => 'nullable|string|max:255',
            'receipt_url'  => 'nullable|url|max:500',
            'status'       => 'sometimes|in:recorded,reimbursed',
        ]);

        $expense->update($validated);

        return response()->json(['success' => true, 'data' => $expense]);
    }

    public function destroy(Expense $expense, Request $request)
    {
        if ($expense->workspace_id !== $request->user()->workspace->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $expense->delete();

        return response()->json(['success' => true, 'message' => 'Expense deleted']);
    }
}
