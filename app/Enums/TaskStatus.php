<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'Open';
    case InProgress = 'In Progress';
    case Completed = 'Completed';
}
