<?php

namespace App\Http\Controllers;

use App\Services\PermissionLandingPageResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect(app(PermissionLandingPageResolver::class)->pathFor($request->user()));
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt([...$credentials, 'is_active' => true])) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password tidak sesuai, atau akun belum aktif.',
            ]);
        }

        $request->session()->regenerate();

        $intendedUrl = $request->session()->pull('url.intended');

        return redirect(app(PermissionLandingPageResolver::class)->pathFor($request->user(), is_string($intendedUrl) ? $intendedUrl : null));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

}
