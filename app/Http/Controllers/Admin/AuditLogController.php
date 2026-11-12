<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'action' => ['nullable', 'string', 'max:60'],
            'group' => ['nullable', Rule::in(['security'])],
        ]);
        $search = trim($validated['q'] ?? '');

        $auditLogs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'subject_type', 'subject_id', 'created_at'])
            ->with('actor:id,name,email')
            ->when(isset($validated['group']), fn (Builder $query): Builder => $query->where('action', 'like', 'auth.%'))
            ->when(isset($validated['action']), fn (Builder $query): Builder => $query->where('action', $validated['action']))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('actor', function (Builder $query) use ($search): void {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('created_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        $actions = AuditLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', [
            'auditLogs' => $auditLogs,
            'actions' => $actions,
            'filters' => $validated,
        ]);
    }
}
