<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\LastActive;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(array_map(
                static fn (UserRole $role): string => $role->value,
                UserRole::cases(),
            ))],
            'status' => ['nullable', Rule::in(['active', 'pending', 'inactive'])],
        ]);
        $search = trim($validated['q'] ?? '');
        $timezone = $request->user()->settings['timezone'] ?? config('app.timezone');

        $users = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'is_active',
                'job_title',
                'avatar_path',
                'created_at',
                'last_login_at',
                'last_seen_at',
            ])
            ->withCount(['assignedTasks', 'ownedProjects'])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('job_title', 'like', "%{$search}%");
                });
            })
            ->when(isset($validated['role']), fn (Builder $query): Builder => $query->where('role', $validated['role']))
            ->when(isset($validated['status']), fn (Builder $query): Builder => $query->accountStatus($validated['status']))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $users->getCollection()->each(function (User $user) use ($timezone): void {
            $user->setAttribute(
                'last_active_label',
                LastActive::label($user->last_seen_at, $user->last_login_at, $timezone),
            );
        });

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $validated,
            'roles' => UserRole::cases(),
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        $user->loadCount(['assignedTasks', 'ownedProjects']);
        $timezone = $request->user()->settings['timezone'] ?? config('app.timezone');
        $role = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        return response()->json([
            'data' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => config("permissions.{$role}.label", ucfirst($role)),
                'status' => $user->accountStatus(),
                'job_title' => $user->job_title,
                'bio' => $user->bio,
                'joined' => $user->created_at?->format('M j, Y'),
                'last_active' => LastActive::label($user->last_seen_at, $user->last_login_at, $timezone),
                'assigned_tasks' => $user->assigned_tasks_count,
                'owned_projects' => $user->owned_projects_count,
            ],
        ]);
    }
}
