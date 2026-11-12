<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials + ['is_active' => true], $request->boolean('remember'))) {
            Auth::guard('web')->logout();

            return back()
                ->withErrors(['email' => 'These credentials do not match our records.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        if ($user->role === UserRole::Admin) {
            $intendedUrl = $request->session()->get('url.intended');
            $intendedPath = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_PATH) : null;
            $intendedHost = is_string($intendedUrl) ? parse_url($intendedUrl, PHP_URL_HOST) : null;
            $isAdminUrl = is_string($intendedPath)
                && ($intendedPath === '/admin' || Str::startsWith($intendedPath, '/admin/'));
            $isLocalUrl = $intendedHost === null || $intendedHost === $request->getHost();

            if ($isAdminUrl && $isLocalUrl) {
                return redirect()->intended(route('admin.overview'));
            }

            return redirect()->route('admin.overview');
        }

        return redirect()->intended(route($user->defaultPageRoute()));
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::create($validated);
            $user->ownedProjects()->create([
                'name' => 'Personal',
                'description' => 'Keep individual goals and focused work in one place.',
            ]);

            return $user;
        });
        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill([
            'last_login_at' => now(),
            'last_seen_at' => now(),
        ])->save();
        event(new Registered($user));

        return redirect()->route($user->defaultPageRoute());
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
