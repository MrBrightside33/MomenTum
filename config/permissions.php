<?php

return [
    'admin' => [
        'label' => 'Administrator',
        'description' => 'Full access',
        'dot' => 'bg-indigo-500',
        'capabilities' => [
            'Review all users' => true,
            'Review all tasks' => true,
            'Review all projects' => true,
            'Review reports' => true,
            'Review the audit log' => true,
            'Create or modify workspace data' => false,
            'Change roles or account access' => false,
        ],
    ],
    'manager' => [
        'label' => 'Manager',
        'description' => 'Manage tasks & teams',
        'dot' => 'bg-violet-500',
        'capabilities' => [
            'View and manage all tasks' => true,
            'Create and archive projects' => true,
            'Review all users and reports' => false,
            'Review the audit log' => false,
            'Change roles or account access' => false,
        ],
    ],
    'member' => [
        'label' => 'Member',
        'description' => 'Own tasks & projects',
        'dot' => 'bg-emerald-500',
        'capabilities' => [
            'Create tasks' => true,
            'Edit or delete own tasks' => true,
            'View tasks created or assigned to you' => true,
            'Comment and change status on visible tasks' => true,
            'Manage all tasks or users' => false,
        ],
    ],
];
