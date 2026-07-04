<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    public function show(Request $request)
    {
        $company = $request->user()->company;

        if (! $company) {
            return response()->json([
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => $company,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'max:255', 'regex:/^[0-9+()\-\s]{7,255}$/'],
            'address' => 'nullable|string|max:2000',
            'website' => 'nullable|url:http,https|max:255',
            'tax_id' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'logo' => 'nullable|file|mimetypes:image/jpeg,image/png,image/webp|extensions:jpg,jpeg,png,webp|max:4096',
            'remove_logo' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $company = $user->company ?? new Company([
            'user_id' => $user->id,
        ]);

        $company->fill([
            'name' => InputSanitizer::text($validated['name']),
            'email' => isset($validated['email']) ? mb_strtolower(trim($validated['email'])) : null,
            'phone' => InputSanitizer::text($validated['phone'] ?? null),
            'address' => InputSanitizer::multilineText($validated['address'] ?? null),
            'website' => isset($validated['website']) ? trim($validated['website']) : null,
            'tax_id' => InputSanitizer::text($validated['tax_id'] ?? null),
            'industry' => InputSanitizer::text($validated['industry'] ?? null),
            'currency' => isset($validated['currency']) ? strtoupper(trim($validated['currency'])) : null,
        ]);

        if (($validated['remove_logo'] ?? false) && $company->logo_path && $company->logo_disk) {
            Storage::disk($company->logo_disk)->delete($company->logo_path);
            $company->logo_path = null;
            $company->logo_disk = null;
        }

        if ($request->hasFile('logo')) {
            if ($company->logo_path && $company->logo_disk) {
                Storage::disk($company->logo_disk)->delete($company->logo_path);
            }

            $disk = 'public';
            $filename = Str::uuid() . '.' . $request->file('logo')->getClientOriginalExtension();
            $path = $request->file('logo')->storeAs('company-logos', $filename, $disk);

            $company->logo_path = $path;
            $company->logo_disk = $disk;
        }

        $company->save();

        return response()->json([
            'message' => 'Company settings updated successfully.',
            'data' => $company->fresh(),
        ]);
    }
}
