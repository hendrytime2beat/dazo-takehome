<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function showLogin(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/Login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $normalized = preg_replace('/[\s-]/', '', $value);
                    $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL);
                    $isPhone = (bool) preg_match('/^(?:\+?62|628|08)\d{7,13}$/', $normalized);

                    if (! $isEmail && ! $isPhone) {
                        $fail('Masukkan email atau nomor WhatsApp yang valid.');
                    }
                },
            ],
            'password' => ['required', 'string'],
        ]);

        $loginValue = $credentials['email'];
        $normalized = preg_replace('/[\s-]/', '', $loginValue);
        $isPhone = preg_match('/^(?:\+?62|628|08)\d{7,13}$/', $normalized);

        $field = $isPhone && \Illuminate\Support\Facades\Schema::hasColumn('users', 'phone')
            ? 'phone'
            : 'email';

        if (! Auth::attempt([$field => $loginValue, 'password' => $credentials['password']], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email / nomor WhatsApp atau password salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showForgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }
}