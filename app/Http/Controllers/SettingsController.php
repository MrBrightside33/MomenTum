<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use RuntimeException;

class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $settings = array_replace_recursive($this->defaultSettings(), $user->settings ?? []);

        return view('settings', [
            'user' => $user,
            'settings' => $settings,
            'defaultPages' => config('settings.default_pages'),
            'taskViews' => config('settings.task_views'),
            'timezones' => config('settings.timezones'),
            'appearanceModes' => config('settings.appearance_modes'),
            'notificationOptions' => config('settings.notifications'),
            'initials' => Str::upper(Str::substr($user->name, 0, 2)),
            'avatarUrl' => $user->avatar_path
                ? Storage::disk('public')->url($user->avatar_path)
                : null,
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $request->merge([
            'email' => Str::lower((string) $request->input('email')),
        ]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'job_title' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $avatar = $validated['avatar'] ?? null;
        unset($validated['avatar']);

        $previousAvatarPath = $user->avatar_path;
        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($avatar !== null) {
            $avatarPath = $avatar->storePublicly("avatars/{$user->id}", 'public');

            if ($avatarPath === false) {
                throw new RuntimeException('Unable to store the profile photo.');
            }

            $user->avatar_path = $avatarPath;
        }

        $user->save();

        if (
            $avatar !== null
            && $previousAvatarPath !== null
            && $previousAvatarPath !== $user->avatar_path
            && str_starts_with($previousAvatarPath, "avatars/{$user->id}/")
        ) {
            Storage::disk('public')->delete($previousAvatarPath);
        }

        return redirect()->route('settings')->with('status', 'Profile changes saved.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', Password::min(8), 'confirmed'],
        ]);

        Auth::logoutOtherDevices($validated['current_password']);
        $request->user()->update([
            'password' => $validated['password'],
        ]);

        return redirect()->route('settings')->with('status', 'Password changed successfully.');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        $notificationKeys = array_keys(config('settings.notifications'));
        $notificationRules = [
            'workspace_name' => ['required', 'string', 'max:80'],
            'default_page' => ['required', Rule::in(array_keys(config('settings.default_pages')))],
            'default_task_view' => ['required', Rule::in(array_keys(config('settings.task_views')))],
            'timezone' => ['required', Rule::in(array_keys(config('settings.timezones')))],
            'appearance' => ['required', Rule::in(array_keys(config('settings.appearance_modes')))],
            'notifications' => ['required', 'array:'.implode(',', $notificationKeys)],
        ];

        foreach ($notificationKeys as $notificationKey) {
            $notificationRules["notifications.{$notificationKey}"] = ['required', 'boolean'];
        }

        $validated = $request->validate($notificationRules);
        foreach ($notificationKeys as $notificationKey) {
            $validated['notifications'][$notificationKey] = (bool) $validated['notifications'][$notificationKey];
        }

        $user = $request->user();
        $user->settings = array_replace_recursive($user->settings ?? [], $validated);
        $user->save();

        return redirect()->route('settings')->with('status', 'Workspace preferences saved.');
    }

    public function updateAppearance(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'appearance' => ['required', Rule::in(['light', 'dark'])],
        ]);

        $user = $request->user();
        $user->settings = array_replace_recursive($user->settings ?? [], $validated);
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['appearance' => $validated['appearance']]);
        }

        return redirect()->back()->with('status', 'Appearance updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultSettings(): array
    {
        return [
            'workspace_name' => config('app.name'),
            'default_page' => 'dashboard',
            'default_task_view' => 'list',
            'timezone' => 'UTC',
            'appearance' => 'system',
            'notifications' => array_fill_keys(array_keys(config('settings.notifications')), true),
        ];
    }
}
