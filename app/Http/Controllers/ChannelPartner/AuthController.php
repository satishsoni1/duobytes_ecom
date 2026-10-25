<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Http\Controllers\Controller;
use App\Models\ChannelPartner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function landing()
    {
        if (Auth::check() && Auth::user()->user_type === 'channel_partner') {
            return redirect()->route('channel_partner.dashboard');
        }

        return view('channel_partner.landing');
    }

    public function showRegister()
    {
        return view('channel_partner.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9\s\-]{8,18}$/'],
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'email.unique' => translate('An account with this email already exists. Please log in.'),
            'phone.regex' => translate('Please enter a valid mobile number.'),
            'terms.accepted' => translate('Please accept the partner terms to continue.'),
        ]);

        $phone = $this->normalizePhone($data['phone']);
        if (User::whereIn('phone', [ltrim($phone, '+'), '+' . ltrim($phone, '+')])->exists()) {
            return back()->withInput($request->except('password'))
                ->withErrors(['phone' => translate('This mobile number is already registered.')]);
        }

        $user = DB::transaction(function () use ($data, $phone) {
            $user = new User;
            $user->name = $data['name'];
            $user->email = strtolower($data['email']);
            $user->phone = $phone;
            $user->password = Hash::make($data['password']);
            $user->user_type = 'channel_partner';
            // Identity is verified through the KYC review instead of an email link.
            $user->email_verified_at = now();
            $user->save();

            ChannelPartner::create([
                'user_id' => $user->id,
                'company_name' => $data['company_name'],
                'contact_person' => $data['name'],
                'status' => ChannelPartner::STATUS_DRAFT,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('channel_partner.onboarding');
    }

    public function showLogin()
    {
        return view('channel_partner.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->input('login'));
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', strtolower($login))->first();
        } else {
            // Accept the number with or without the leading "+"
            $digits = ltrim($this->normalizePhone($login), '+');
            $user = $digits === '' ? null : User::whereIn('phone', [$digits, '+' . $digits])
                ->orderByRaw("user_type = 'channel_partner' DESC")
                ->first();
        }

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => translate('Invalid login details. Please try again.')]);
        }

        if ($user->user_type !== 'channel_partner') {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => translate('This is not a partner account. Please use the main login.')]);
        }

        if ($user->banned) {
            return back()->withInput($request->only('login'))
                ->withErrors(['login' => translate('Your partner account has been disabled. Please contact us.')]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $partner = $user->channelPartner;
        if ($partner && $partner->isApproved()) {
            return redirect()->intended(route('channel_partner.dashboard'));
        }

        return redirect()->route('channel_partner.onboarding');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('channel_partner.login');
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9+]/', '', $phone);
        $hasPlus = str_starts_with($digits, '+');
        $digits = str_replace('+', '', $digits);

        return $hasPlus ? '+' . $digits : $digits;
    }
}
