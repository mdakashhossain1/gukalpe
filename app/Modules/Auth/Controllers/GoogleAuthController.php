<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);

            return redirect('/')->with('google_auth_error', 'Google sign-in failed. Please try again.');
        }

        // Match on google_id first; fall back to an existing account with the
        // same email (e.g. someone who'll later add password login) rather
        // than creating a duplicate row.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first()
            ?? new User();

        $isNew = ! $user->exists;

        $user->fill([
            'google_id' => $googleUser->getId(),
            'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'GullakPe User'),
            'email' => $googleUser->getEmail(),
            'avatar' => $googleUser->getAvatar(),
        ]);

        if ($isNew) {
            // Real column requires a value; Google-only users never use it
            // for login, so it's random and never surfaced.
            $user->password = Hash::make(Str::random(40));

            $code = $request->session()->get('pending_referral_code');
            $referrer = $code ? User::where('referral_code', $code)->first() : null;
            if ($referrer) {
                $user->referred_by = $referrer->id;
            }
        }

        $user->save();
        $request->session()->forget('pending_referral_code');

        Auth::login($user, remember: true);

        if ($isNew) {
            AdminNotification::notify('user_registered', 'New user signed up', "{$user->name} · {$user->email}");
        }

        Log::info('Google login', ['user_id' => $user->id, 'email' => $user->email, 'new_account' => $isNew]);

        return redirect('/')->with('google_user', [
            'googleId' => $user->google_id,
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $user->avatar,
            'phone' => $user->phone,
        ]);
    }

    /**
     * Links a phone number (captured via the existing client-side phone/OTP
     * step) to the currently Google-authenticated user. Called once that
     * step completes for a first-time Google sign-in - see
     * resources/js/modules/auth.js's finalizeLogin().
     */
    public function linkPhone(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['error' => 'Not authenticated.'], 401);
        }

        $validated = $request->validate([
            'phone' => 'required|digits:10',
        ]);

        $user = Auth::user();

        $conflict = User::where('phone', $validated['phone'])->where('id', '!=', $user->id)->exists();
        if ($conflict) {
            return response()->json(['error' => 'This phone number is already linked to another account.'], 422);
        }

        $user->phone = $validated['phone'];
        $user->save();

        Log::info('Google account linked to phone', ['user_id' => $user->id]);

        return response()->json(['ok' => true]);
    }
}
