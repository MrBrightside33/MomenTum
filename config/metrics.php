<?php

use App\Enums\ProjectStatus;
use App\Enums\TaskStatus;
use App\Enums\UserRole;

return [
    'users' => [
        'admin_role' => UserRole::Admin->value,
        'statuses' => [
            'active' => [
                'is_active' => true,
                'last_login' => 'required',
            ],
            'pending' => [
                'is_active' => true,
                'last_login' => 'missing',
            ],
            'inactive' => [
                'is_active' => false,
                'last_login' => 'any',
            ],
        ],
    ],
    'tasks' => [
        'open_status' => TaskStatus::Open->value,
        'completed_status' => TaskStatus::Completed->value,
        'in_progress_status' => TaskStatus::InProgress->value,
    ],
    'projects' => [
        'archived_status' => ProjectStatus::Archived->value,
        'statuses' => array_column(ProjectStatus::cases(), 'value'),
        'progress_method' => 'average_task_progress',
    ],
];
