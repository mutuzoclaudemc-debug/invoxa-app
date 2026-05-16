<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WorkspaceController extends Controller
{
    public function show(Request $request)
    {
        $workspace = $request->user()->workspace;
        return response()->json([
            'success' => true,
            'data' => $workspace,
        ]);
    }

    public function update(Request $request)
    {
        $workspace = $request->user()->workspace;

        $validated = $request->validate([
            'name'               => 'sometimes|string|max:255',
            'currency'           => 'sometimes|string|in:USD,EUR,GBP,RWF',
            'logo_url'           => 'nullable|string|max:500',
            'company_email'      => 'nullable|email|max:255',
            'company_phone'      => 'nullable|string|max:50',
            'company_address'    => 'nullable|string|max:1000',
            'tax_id'             => 'nullable|string|max:100',
            'website'            => 'nullable|string|max:255',
            'invoice_footer'     => 'nullable|string|max:1000',
            'brand_color'        => 'nullable|string|max:20',
            'bank_name'          => 'nullable|string|max:255',
            'bank_account_number'=> 'nullable|string|max:255',
            'bank_account_name'  => 'nullable|string|max:255',
        ]);

        $workspace->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Company details updated successfully',
            'data'    => $workspace->fresh(),
        ]);
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,jpg,png,svg,webp|max:2048',
        ], [
            'logo.image'    => 'File must be an image (JPEG, PNG, SVG, WebP)',
            'logo.mimes'    => 'Accepted formats: JPEG, PNG, SVG, WebP',
            'logo.max'      => 'Logo must be smaller than 2 MB',
        ]);

        $workspace = $request->user()->workspace;

        // Remove old logo file if it was locally uploaded
        if ($workspace->logo_url && str_contains($workspace->logo_url, '/storage/logos/')) {
            $oldPath = str_replace(url('/storage/'), '', $workspace->logo_url);
            Storage::disk('public')->delete($oldPath);
        }

        $file = $request->file('logo');
        $path = $file->store("logos/{$workspace->id}", 'public');
        $url  = url(Storage::url($path));

        $workspace->update(['logo_url' => $url]);

        return response()->json([
            'success'  => true,
            'message'  => 'Logo uploaded successfully',
            'logo_url' => $url,
        ]);
    }

    public function removeLogo(Request $request)
    {
        $workspace = $request->user()->workspace;

        if ($workspace->logo_url && str_contains($workspace->logo_url, '/storage/logos/')) {
            $oldPath = str_replace(url('/storage/'), '', $workspace->logo_url);
            Storage::disk('public')->delete($oldPath);
        }

        $workspace->update(['logo_url' => null]);

        return response()->json(['success' => true, 'message' => 'Logo removed']);
    }
}
