<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_THROTTLED) {
            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Please wait before requesting another password reset link.']);
        }

        if (! in_array($status, [Password::RESET_LINK_SENT, Password::INVALID_USER], true)) {
            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Unable to send a password reset link right now.']);
        }

        return redirect()->route('password.request')
            ->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }
}
