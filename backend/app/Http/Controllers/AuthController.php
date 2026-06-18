<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthUserResource;
use App\Models\User;
use App\Support\WaitlistAccessManager;
use App\Support\SubscriptionBillingManager;
use App\Notifications\VerifyEmailAddress;
use App\Http\Controllers\Controller;
use App\Support\InputSanitizer;
use Laravel\Sanctum\TransientToken;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
            'role' => 'nullable|in:' . implode(',', User::roles()),
        ]);

        $email = mb_strtolower(trim((string) $request->email));
        app(WaitlistAccessManager::class)->ensureCanAccess($email);

        $user = User::create([
            'name' => InputSanitizer::text($request->name),
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => $request->input('role', User::ROLE_OWNER),
        ]);

        app(SubscriptionBillingManager::class)->startTrial($user);
        app(WaitlistAccessManager::class)->markActivated($email);
        $this->sendVerificationEmail($user);
        $this->issueSessionForUser($request, $user);
        $this->logAuthEvent('info', 'Registration succeeded.', $request, $user);

        return response()->json([
            'user' => new AuthUserResource($user->fresh()),
            'requires_email_verification' => ! $this->isEmailVerified($user),
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $email = mb_strtolower(trim((string) $request->email));
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $this->logAuthEvent('warning', 'Login failed.', $request, null, [
                'email' => $email,
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        app(SubscriptionBillingManager::class)->startTrial($user);
        app(WaitlistAccessManager::class)->markActivated($email);
        $this->issueSessionForUser($request, $user);
        $this->logAuthEvent('info', 'Login succeeded.', $request, $user);

        return response()->json([
            'user' => new AuthUserResource($user),
            'requires_email_verification' => ! $this->isEmailVerified($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        $currentToken = $request->user()?->currentAccessToken();
        if ($currentToken && ! $currentToken instanceof TransientToken && method_exists($currentToken, 'delete')) {
            $currentToken->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->logAuthEvent('info', 'Logout succeeded.', $request, $user);

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($user->google_id && $validated['email'] !== $user->email) {
            throw ValidationException::withMessages([
                'email' => ['Google-linked accounts cannot change email from the profile page.'],
            ]);
        }

        $emailChanged = $validated['email'] !== $user->email;

        $user->name = InputSanitizer::text($validated['name']);
        $user->email = mb_strtolower(trim($validated['email']));

        if ($emailChanged) {
            $user->email_verified_at = null;
            $user->email_verification_token = null;
            $user->email_verification_sent_at = null;
        }

        $user->save();

        if ($emailChanged && ! $user->google_id) {
            $this->sendVerificationEmail($user);
        }

        $this->logAuthEvent('info', 'Profile updated.', $request, $user, [
            'email_changed' => $emailChanged,
        ]);

        return response()->json([
            'message' => $emailChanged
                ? 'Profile updated. Please verify your new email address.'
                : 'Profile updated successfully.',
            'user' => new AuthUserResource($user->fresh()),
        ]);
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        if ($user->google_id) {
            throw ValidationException::withMessages([
                'password' => ['Password changes are unavailable for Google-linked accounts.'],
            ]);
        }

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Your current password is incorrect.'],
            ]);
        }

        $user->password = $validated['password'];
        $user->save();

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        $this->logAuthEvent('info', 'Password changed.', $request, $user);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        Password::sendResetLink($request->only('email'));
        $this->logAuthEvent('info', 'Password reset link requested.', $request, null, [
            'email' => mb_strtolower(trim((string) $request->input('email'))),
        ]);

        return response()->json([
            'message' => 'If the account exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => ['required', 'string', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        $logLevel = $status === Password::PASSWORD_RESET ? 'info' : 'warning';
        $this->logAuthEvent($logLevel, 'Password reset processed.', $request, null, [
            'email' => mb_strtolower(trim((string) $request->input('email'))),
            'status' => $status,
        ]);

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    public function resendVerification(Request $request)
    {
        $user = $request->user();

        if ($this->isEmailVerified($user)) {
            return response()->json(['message' => 'Email already verified.']);
        }

        $this->sendVerificationEmail($user);
        $this->logAuthEvent('info', 'Verification email resent.', $request, $user);

        return response()->json(['message' => 'Verification email sent.']);
    }

    public function verifyEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'token' => 'required|string|size:64|regex:/^[A-Za-z0-9]+$/',
        ]);

        $user = User::where('email', mb_strtolower(trim($validated['email'])))->firstOrFail();

        if ($this->isEmailVerified($user)) {
            return response()->json(['message' => 'Email already verified.']);
        }

        if ($this->emailVerificationTokenExpired($user)) {
            throw ValidationException::withMessages([
                'token' => ['The email verification token has expired.'],
            ]);
        }

        if (! hash_equals((string) $user->email_verification_token, hash('sha256', $validated['token']))) {
            throw ValidationException::withMessages([
                'token' => ['The email verification token is invalid.'],
            ]);
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_sent_at' => null,
        ])->save();

        $this->logAuthEvent('info', 'Email verified.', $request, $user);

        return response()->json([
            'message' => 'Email verified successfully.',
            'user' => new AuthUserResource($user),
        ]);
    }

    public function googleRedirect()
    {
        $state = Str::random(40);
        Cache::put("google_oauth_state:{$state}", true, now()->addMinutes(10));

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ]);

        return redirect()->away("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }

    public function googleCallback(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:2048',
            'state' => 'required|string|size:40|regex:/^[A-Za-z0-9]+$/',
        ]);

        if (! Cache::pull("google_oauth_state:{$request->state}")) {
            $this->logAuthEvent('warning', 'Google OAuth state validation failed.', $request);
            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_state');
        }

        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $request->code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect_uri'),
                'grant_type' => 'authorization_code',
            ]);
        } catch (ConnectionException $exception) {
            Log::warning('Google OAuth token exchange failed due to SSL/connectivity issue.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_connection');
        }

        if (! $tokenResponse->successful()) {
            $this->logAuthEvent('warning', 'Google OAuth token exchange failed.', $request, null, [
                'status' => $tokenResponse->status(),
            ]);
            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_token');
        }

        $accessToken = $tokenResponse->json('access_token');
        try {
            $profileResponse = Http::withToken($accessToken)->get('https://openidconnect.googleapis.com/v1/userinfo');
        } catch (ConnectionException $exception) {
            Log::warning('Google OAuth profile lookup failed due to SSL/connectivity issue.', [
                'message' => $exception->getMessage(),
            ]);

            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_connection');
        }

        if (! $profileResponse->successful()) {
            $this->logAuthEvent('warning', 'Google OAuth profile fetch failed.', $request, null, [
                'status' => $profileResponse->status(),
            ]);
            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_profile');
        }

        $profile = $profileResponse->json();
        $email = $profile['email'] ?? null;

        if (! $email) {
            $this->logAuthEvent('warning', 'Google OAuth profile missing email.', $request);
            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=oauth_email');
        }

        $user = User::firstOrNew(['email' => $email]);
        if (! $user->exists && ! app(WaitlistAccessManager::class)->canAccess($email)) {
            $this->logAuthEvent('warning', 'Google OAuth invite check failed.', $request, null, [
                'email' => $email,
            ]);

            return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/login?error=invite_required');
        }

        $user->name = $user->exists ? $user->name : ($profile['name'] ?? 'Google User');
        $user->role = $user->role ?: User::ROLE_OWNER;
        $user->google_id = $profile['sub'] ?? $user->google_id;
        $user->email_verified_at = now();

        if (! $user->exists) {
            $user->password = Str::password(32);
        }

        $user->save();
        $frontendUrl = rtrim(config('app.frontend_url'), '/');
        app(SubscriptionBillingManager::class)->startTrial($user);
        app(WaitlistAccessManager::class)->markActivated($email);
        $this->issueSessionForUser($request, $user);
        $this->logAuthEvent('info', 'Google OAuth login succeeded.', $request, $user);

        return redirect()->away("{$frontendUrl}/auth/callback?status=success");
    }

    private function sendVerificationEmail(User $user): void
    {
        $plainTextToken = Str::random(64);

        $user->forceFill([
            'email_verification_token' => hash('sha256', $plainTextToken),
            'email_verification_sent_at' => now(),
        ])->save();

        $user->notify(new VerifyEmailAddress($plainTextToken));
    }

    private function isEmailVerified(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    private function issueSessionForUser(Request $request, User $user): void
    {
        $this->ensureSessionStore($request);

        $request->session()->forget([
            'support_impersonator_user_id',
            'support_impersonator_name',
            'support_impersonator_email',
            'support_impersonated_at',
        ]);

        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }

    private function emailVerificationTokenExpired(User $user): bool
    {
        if (! $user->email_verification_sent_at) {
            return true;
        }

        return $user->email_verification_sent_at->lt(
            now()->subMinutes((int) env('AUTH_EMAIL_VERIFICATION_EXPIRE', 60))
        );
    }

    private function logAuthEvent(
        string $level,
        string $message,
        Request $request,
        ?User $user = null,
        array $context = []
    ): void {
        Log::channel('auth')->log($level, $message, array_merge([
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user?->id,
            'email' => $user?->email,
        ], $context));
    }
}
