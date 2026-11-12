# Admin area

The admin area is a read-only workspace review interface. Admin accounts can inspect accounts, tasks, projects, reports, and audit events, but do not receive write access to workspace records.

## Access and pages

All routes under `/admin` require authentication and the `admin` middleware. The admin route group contains GET endpoints only:

| Route | Purpose |
| --- | --- |
| `/admin` | Workspace overview and recent activity |
| `/admin/users` | Searchable, filterable user directory |
| `/admin/users/{user}` | Whitelisted JSON details for the user details dialog |
| `/admin/tasks` | Read-only task directory |
| `/admin/projects` | Read-only project directory |
| `/admin/reports` | Account, task, project, and audit summaries |
| `/admin/reports/export/{dataset}` | Stream CSV exports for users, tasks, projects, and audit logs |
| `/admin/audit-log` | Searchable and filterable audit history |
| `/admin/search` | Search users, tasks, and projects |
| `/admin/settings` | Read-only role permission reference |

Personal account preferences remain available from the existing `/settings` page. Admin access does not provide a route to change user roles or account status; role changes remain an explicit command-line operation.

## Data handling

- The user detail endpoint returns only the account fields rendered in the dialog; it does not serialize the model or expose password, token, or session attributes.
- Admin listings paginate records, select required columns, and eager-load displayed relationships.
- CSV responses are streamed and include only the selected report fields. Spreadsheet formula-leading text is prefixed before CSV encoding. Audit exports omit metadata, IP addresses, and user-agent values.
- Audit entries are append-only through the model. The scheduled `audit:prune` command deletes entries older than the configured command-line retention period, which defaults to 180 days.
- The admin screens do not expose create, update, delete, archive, or role-change controls.

## Local demo data

`DatabaseSeeder` creates a deterministic, idempotent sample set of 28 accounts and 28 audit events only in `local` and `testing` environments. It does not create demo accounts in production. The sample administrator is `admin@momentum.test`; factory-created demo accounts use the factory's local test password, `password`. Change or remove these demo credentials before exposing a local or test database to others.

## Verification

Run the Laravel tests and frontend build from the project directory:

```shell
docker compose exec -T app php artisan test --compact
npm run build
docker compose exec -T app php artisan route:list --path=admin
```
