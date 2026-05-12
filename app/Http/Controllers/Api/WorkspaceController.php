<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function show(Request $request)
    {
        $workspace = $request->user()->workspace;
        return response()->json([
            'success' => true,
            'data' => $workspace
        ]);
    }

    public function update(Request $request)
    {
        $workspace = $request->user()->workspace;
        
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'currency' => 'sometimes|string|in:USD,EUR,GBP,RWF',
            'logo_url' => 'nullable|string|max:500',
            'company_email' => 'nullable|email|max:255',
            'company_phone' => 'nullable|string|max:50',
            'company_address' => 'nullable|string|max:1000',
            'tax_id' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:255',
            'invoice_footer' => 'nullable|string|max:1000',
            'brand_color' => 'nullable|string|max:20',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
        ]);
        
        $workspace->update($validated);
        
        return response()->json([
            'success' => true,
            'message' => 'Company details updated successfully',
            'data' => $workspace->fresh()
        ]);
    }
}
