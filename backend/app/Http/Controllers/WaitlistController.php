<?php

namespace App\Http\Controllers;

use App\Models\WaitlistSignup;
use App\Support\InputSanitizer;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:100'],
        ]);

        $email = mb_strtolower(trim((string) $validated['email']));
        $name = InputSanitizer::text($validated['name'] ?? null);
        $name = $name !== '' ? $name : null;
        $source = InputSanitizer::text($validated['source'] ?? null);
        $source = $source !== '' ? $source : 'homepage';

        WaitlistSignup::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'source' => $source,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            ]
        );

        return response()->json([
            'message' => "You're on the waitlist. We'll email you when an invitation is ready.",
        ]);
    }
}
