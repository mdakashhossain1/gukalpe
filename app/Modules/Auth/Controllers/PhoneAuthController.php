<?php

namespace App\Modules\Auth\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\PhoneOtp;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Real phone + OTP + MPIN authentication - replaces the client-side
 * simulation that used to live entirely in resources/js/modules/auth.js
 * (a hardcoded '123456' OTP, MPINs stored in a localStorage-only "database").
 * See DESIGN.md's "Real phone authentication" section for the full design
 * and MEMORY.md for why this exists.
 *
 * There is no SMS gateway configured in this app - generateFor() in
 * PhoneOtp is the single point where a real provider (MSG91/Twilio/etc.)
 * would send the code instead of it being flashed back to the page in
 * "demo mode".
 */
class PhoneAuthController extends Controller
{
    private const MPIN_MAX_ATTEMPTS = 5;
    private const MPIN_LOCKOUT_SECONDS = 900; // 15 minutes

    public function showPhoneForm(): View
    {
        return view('Auth::phone');
    }

    public function submitPhone(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'digits:10'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'You must accept the Terms of Use and Privacy Policy.',
        ]);

        $phone = $validated['phone'];
        $user = User::where('phone', $phone)->first();

        if ($user && $user->mpin) {
            // Returning user with an MPIN already set - no OTP needed.
            $request->session()->put('auth_flow_phone', $phone);

            return redirect()->route('login.mpin');
        }

        // New phone, or an existing (e.g. Google-linked) account that never
        // set an MPIN yet - either way, verify the phone via OTP first.
        $code = PhoneOtp::generateFor($phone);

        $request->session()->put('auth_flow_phone', $phone);
        $request->session()->put('auth_flow_purpose', 'signup');

        Log::info('Phone OTP generated', ['phone' => $phone]);

        return redirect()->route('login.verify-otp')->with('demo_otp', $code);
    }

    public function showOtpForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('auth_flow_phone')) {
            return redirect()->route('login');
        }

        return view('Auth::verify-otp', [
            'phone' => $request->session()->get('auth_flow_phone'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $result = PhoneOtp::attemptVerify($phone, $validated['otp']);

        $messages = [
            'invalid' => 'Incorrect OTP. Please try again.',
            'expired' => 'This OTP has expired. Request a new one.',
            'locked' => 'Too many incorrect attempts. Please restart and request a new OTP.',
            'not_found' => 'No OTP request found. Please request a new one.',
        ];

        if ($result !== 'ok') {
            if (in_array($result, ['expired', 'locked', 'not_found'], true)) {
                $request->session()->forget(['auth_flow_phone', 'auth_flow_purpose']);
            }

            return back()->withErrors(['otp' => $messages[$result]]);
        }

        $request->session()->put('auth_flow_otp_verified', true);

        return redirect()->route('login.set-mpin');
    }

    public function resendOtp(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $code = PhoneOtp::generateFor($phone);

        return redirect()->route('login.verify-otp')->with('demo_otp', $code)->with('success', 'A new OTP has been generated.');
    }

    public function showSetMpinForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone || ! $request->session()->get('auth_flow_otp_verified')) {
            return redirect()->route('login');
        }

        return view('Auth::set-mpin', [
            'phone' => $phone,
            'isNewUser' => ! User::where('phone', $phone)->exists(),
        ]);
    }

    public function setMpin(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone || ! $request->session()->get('auth_flow_otp_verified')) {
            return redirect()->route('login');
        }

        $user = User::where('phone', $phone)->first();
        $isNewUser = ! $user;

        $rules = [
            'mpin' => ['required', 'digits:4', 'confirmed'],
        ];
        if ($isNewUser) {
            $rules['name'] = ['required', 'string', 'max:255'];
        }

        $validated = $request->validate($rules);

        if (! $user) {
            $user = new User([
                'phone' => $phone,
                'email' => "{$phone}@phone.gullakpe.local",
                // Real column requires a value; phone/MPIN users never use
                // it to log in, same convention as Google-only accounts in
                // GoogleAuthController.
                'password' => Hash::make(Str::random(40)),
            ]);
        }

        if ($isNewUser) {
            $user->name = $validated['name'];
            $this->attributeReferral($request, $user);
        }
        $user->phone = $phone;
        $user->mpin = $validated['mpin'];
        $user->save();

        Auth::login($user, remember: true);
        Cache::forget("mpin-failures:{$phone}");

        $request->session()->forget(['auth_flow_phone', 'auth_flow_purpose', 'auth_flow_otp_verified', 'pending_referral_code']);

        if ($isNewUser) {
            AdminNotification::notify('user_registered', 'New user signed up', "{$user->name} · {$user->phone}");
        }

        Log::info('Phone auth: MPIN set, user logged in', ['user_id' => $user->id, 'new_account' => $isNewUser]);

        return redirect()->route('home')->with('phone_auth_bridge', [
            'phone' => $user->phone,
            'name' => $user->name,
            'isNewSignup' => $isNewUser,
        ]);
    }

    public function showMpinForm(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        return view('Auth::mpin', ['phone' => $phone]);
    }

    public function verifyMpin(Request $request): RedirectResponse
    {
        $phone = $request->session()->get('auth_flow_phone');
        if (! $phone) {
            return redirect()->route('login');
        }

        $lockKey = "mpin-failures:{$phone}";
        $failures = (int) Cache::get($lockKey, 0);
        if ($failures >= self::MPIN_MAX_ATTEMPTS) {
            return back()->withErrors(['mpin' => 'Too many incorrect attempts. Please try again later or reset your MPIN.']);
        }

        $validated = $request->validate(['mpin' => ['required', 'digits:4']]);

        $user = User::where('phone', $phone)->first();

        if (! $user || ! $user->mpin || ! Hash::check($validated['mpin'], $user->mpin)) {
            Cache::put($lockKey, $failures + 1, self::MPIN_LOCKOUT_SECONDS);

            return back()->withErrors(['mpin' => 'Incorrect MPIN. Please try again.']);
        }

        Cache::forget($lockKey);
        Auth::login($user, remember: true);
        $request->session()->forget(['auth_flow_phone', 'auth_flow_purpose']);

        Log::info('Phone auth: MPIN login', ['user_id' => $user->id]);

        return redirect()->route('home')->with('phone_auth_bridge', [
            'phone' => $user->phone,
            'name' => $user->name,
            'isNewSignup' => false,
        ]);
    }

    public function showForgotMpinForm(): View
    {
        return view('Auth::forgot-mpin');
    }

    public function submitForgotMpin(Request $request): RedirectResponse
    {
        $validated = $request->validate(['phone' => ['required', 'digits:10']]);

        $user = User::where('phone', $validated['phone'])->first();
        if (! $user) {
            return back()->withErrors(['phone' => 'No account found with this phone number.']);
        }

        $code = PhoneOtp::generateFor($validated['phone']);

        $request->session()->put('auth_flow_phone', $validated['phone']);
        $request->session()->put('auth_flow_purpose', 'reset');

        return redirect()->route('login.verify-otp')->with('demo_otp', $code);
    }

    // Profile's "Logout" button called a dead window.performLogout() left
    // over from the JS that's since been removed - there was no real logout
    // path at all before this, on the main (non-admin) side of the app.
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /**
     * Attaches whichever referral code HomeController::captureReferralCode()
     * stashed in the session (if any) to a brand-new signup. Only called for
     * new accounts - referred_by is set once, at creation, never overwritten.
     */
    private function attributeReferral(Request $request, User $user): void
    {
        $code = $request->session()->get('pending_referral_code');
        if (! $code) {
            return;
        }

        $referrer = User::where('referral_code', $code)->first();
        if ($referrer) {
            $user->referred_by = $referrer->id;
        }
    }
}
