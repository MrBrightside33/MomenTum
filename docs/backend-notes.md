# Task progress

## Stored values and status rules

The progress stages are defined in `config/progress.php` and are the only accepted values for a manual progress update.

| Progress | Label | Stored task status |
| ---: | --- | --- |
| 0% | Not started | Open |
| 25% | Getting started | In Progress |
| 50% | Halfway | In Progress |
| 75% | Almost done | In Progress |
| 100% | Completed | Completed |

`tasks.progress` stores the current stage. When a task is completed, `tasks.progress_before_complete` remembers its previous stage. Reopening restores that value; if no prior stage was recorded, reopening to In Progress starts at 25%, and reopening to Open starts at 0%.

The task migration backfills existing records to 0%, 25%, or 100% based on their current status. `php artisan tasks:sync-progress` reconciles records after deployment and is safe to run repeatedly. `progress_updated_at` and `progress_updated_by_id` identify the last progress change. `completed_by_id` identifies the user who most recently completed the task.

## Write and read routes

| Method and route | Purpose | Authorization and response |
| --- | --- | --- |
| `POST /tasks` | Create a task using its submitted status | Uses the existing task-creation policy and validation; initial progress is set from status |
| `PATCH /tasks/{task}` | Edit task details and status | Uses the existing task-update policy; progress is synchronized with status |
| `PATCH /tasks/{task}/status` | Change a task's status | Uses the existing status-change policy; completion and reopening apply the progress rules above |
| `PATCH /tasks/{task}/progress` | Set a progress stage | Allowed for the task creator, an assignee, or a manager; administrators and project owners who are not creators/assignees cannot edit progress |
| `GET /tasks/{task}` | Return task details and progress | Requires task visibility; the presenter returns display names, not email addresses |
| `GET /projects` | List visible high-priority task cards | Eager-loads task details and activity for the project cards |
| `GET /projects/{task}` | Open a task's details on the Projects page | Hidden or missing tasks redirect to `/projects` with a generic message |

### Progress update payload

Send JSON with the requested stage and the stage currently shown to the user:

```json
{
  "progress": 50,
  "expected_progress": 25
}
```

`progress` must be one of the configured stages. `expected_progress` enables optimistic concurrency: if another update has changed the task since it was read, the endpoint responds with `409 Conflict` and the canonical current task data. A successful response also contains the canonical task representation, including its current status and progress label.

## Activity and audit records

Progress changes create task activity and `task.progress_changed` audit records. If a progress change also changes status, its activity records the status transition and the progress metadata. Status notifications continue to be sent through the existing notification flow.
