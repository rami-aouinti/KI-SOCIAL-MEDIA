# Backend operations

## Authentication and initial access

The dashboard and generated weekly reports require an authenticated web
session. Login is rate-limited to five attempts per minute per client IP.
Successful login regenerates the session ID; logout invalidates the session
and rotates its CSRF token. There is deliberately no public registration route.

Create the first workspace user from the application container:

```bash
docker compose exec laravel.test php artisan social:user:create
```

The command prompts for a name, email address and password (minimum 12
characters). Do not seed a known default account in production.

## API authentication and permissions

The versioned JSON API is available under `/api/v1`. It uses opaque bearer
tokens. Only the SHA-256 hash is stored in the database; the plaintext token
is printed once by the creation command and cannot be recovered later.
Tokens expire after a configurable lifetime (1-365 days) and can be revoked.

Create a token for an existing workspace user:

```bash
docker compose exec laravel.test php artisan social:api-token:create editor@example.com --name="CI integration" --abilities=plans:read,content:read --days=90
```

Copy the token directly into a secret manager. Never commit it, add it to
frontend JavaScript, or put it in a URL. Revoke it using its database ID:

```bash
docker compose exec laravel.test php artisan social:api-token:revoke 12
```

Available token abilities:

- `plans:read`: list and view weekly plans.
- `plans:write`: generate and persist a weekly plan.
- `content:read`: view content ideas and content versions.
- `content:write`: create a new immutable content version and submit it for review.
- `content:approve`: approve or reject a version.

Each API request is limited to 60 requests per minute per client IP. Missing,
invalid and expired tokens return HTTP 401; missing abilities return HTTP 403.
Validation errors return HTTP 422. The API is currently a single shared
workspace: all valid tokens address the same workspace data. Per-user
ownership, organization isolation and role management are not implemented;
do not issue tokens to untrusted users.

### API endpoints

All endpoints require `Authorization: Bearer <token>`.

| Method | Endpoint | Ability | Purpose |
|---|---|---|---|
| GET | `/api/v1/weekly-plans` | `plans:read` | Paginated weekly plans |
| GET | `/api/v1/weekly-plans/{id}` | `plans:read` | Plan, ideas, allocations and briefings |
| POST | `/api/v1/weekly-plans` | `plans:write` | Validate and generate a weekly plan |
| GET | `/api/v1/content-ideas/{id}` | `content:read` | Idea and its briefing/version history |
| GET | `/api/v1/content-versions/{id}` | `content:read` | Immutable snapshot and review/handoff history |
| POST | `/api/v1/content-briefings/{id}/versions` | `content:write` | Create the next immutable version |
| POST | `/api/v1/content-versions/{id}/submit-review` | `content:write` | Submit a draft for review |
| POST | `/api/v1/content-versions/{id}/approve` | `content:approve` | Approve a ready version |
| POST | `/api/v1/content-versions/{id}/reject` | `content:approve` | Reject a version with a comment |

Example:

```bash
curl --fail-with-body \
  -H "Authorization: Bearer $SOCIAL_MEDIA_API_TOKEN" \
  -H "Accept: application/json" \
  http://localhost/api/v1/weekly-plans
```

Weekly-plan creation accepts the same validated payload as
`WeeklyPlanGenerator`: week start, capacity limits, at least three inspiration
references and exactly five ideas (three priorities and two reserves). Content
version creation requires a hook, body, channel variants, channels and
scheduled date. Approval uses the authenticated token owner's name as reviewer;
rejection additionally requires a comment.

The API does not expose a real social-platform publish endpoint. Handoff and
AI generation remain simulated until their external adapters and credentials
are configured.

## Health check

Run dependency checks from the application root:

```bash
./vendor/bin/sail artisan social:health-check
./vendor/bin/sail artisan social:health-check --json
```

The command checks the configured database connection, cache round-trip, local
filesystem write/read/delete, AI generator container binding and queue
configuration. It returns exit code 0 only when every check succeeds. The JSON
option is intended for shell scripts and container health probes.

The check does not call an external AI provider or publish content. The current
AI adapter is a deterministic mock, and the current handoff remains simulated.

## Docker services and queue worker

The Compose stack starts a dedicated `queue-worker` service. It uses the same
application image, source volume, environment and database network as the web
container. It waits for MySQL to become healthy and restarts automatically.
The worker processes the `default` database queue with three attempts, a
60-second job timeout and a one-hour maximum worker lifetime.

Start or inspect the complete stack:

```bash
./vendor/bin/sail up -d
docker compose ps
docker compose logs -f queue-worker
```

Restart the worker after changing queue configuration or deploying new code:

```bash
docker compose restart queue-worker
```

The worker can also be started manually when running outside Compose:

```bash
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=60 --max-time=3600
```

The queue connection uses `after_commit=true`, so queued work is dispatched
only after the surrounding database transaction commits. The worker timeout
(60 seconds) is shorter than the database queue `retry_after` (90 seconds).

## Queue diagnostics and failed jobs

Inspect pending and failed jobs:

```bash
./vendor/bin/sail artisan social:queue-status
./vendor/bin/sail artisan social:queue-status --json
./vendor/bin/sail artisan social:queue-status --queue=default
```

Laravel's built-in commands remain available:

```bash
./vendor/bin/sail artisan queue:failed
./vendor/bin/sail artisan queue:retry all
./vendor/bin/sail artisan queue:forget <failed-job-id>
```

A pending count greater than zero is not automatically an error: jobs may be
scheduled for later execution. Check the oldest pending timestamp and worker
logs before retrying jobs. The queue-status command currently reports database
queue metrics; other queue drivers are explicitly reported as not applicable.

The weekly report refresh job logs a structured error with the content version
ID, attempt count and exception details after its final failure. Correlate that
log entry with the failed-job record before retrying it.

## AI generation provenance

The current generator is deterministic mock data; it does not call an LLM.
Successful plan and briefing generations persist the provider name, simulated
flag, model identifier and generation duration in milliseconds. The
`cost_estimate` field remains null until a real provider can return token usage
or billing data. Do not interpret mock timings as model latency or the null
cost as a free production request.

When a real provider is introduced, implement it behind
`AiContentGeneratorInterface`. Add provider-specific timeout, retry and
rate-limit behavior in that adapter, and only retry requests when the provider
supports idempotency or the failure is known to have occurred before request
acceptance. Keep generated output validation and human approval in the
application workflow.

## Performance and operational guidance

- Keep slow AI, report generation and future social-platform HTTP calls out of
  request handlers; dispatch bounded queue jobs instead.
- Use pagination for growing content collections and select only required
  columns for reporting queries.
- Add indexes only for measured query patterns; validate them with `EXPLAIN`
  against production-like data.
- The dashboard's planned-date ordering and approval lookup now have dedicated
  composite indexes. Re-check query plans with production-like data before
  adding more indexes.
- Use Redis for cache and queue only when the deployment provides and monitors
  Redis; the local MVP remains compatible with its database-backed defaults.
- Use bounded retries and provider-specific rate limits before enabling real
  external integrations.
- A publishing adapter must persist an idempotency key and remote publication
  identifier. A timeout after sending a request is an ambiguous result and
  must not trigger an unconditional duplicate publish.
- Configure retention for failed jobs and generated reports according to the
  deployment's data-retention policy.
