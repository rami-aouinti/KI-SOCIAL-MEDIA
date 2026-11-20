# Backend operations

## Health check

Run the dependency checks from the application root:

```bash
php artisan social:health-check
php artisan social:health-check --json
```

The command checks the configured database connection, cache round-trip, local
filesystem write/read/delete, AI generator container binding and queue
configuration. It returns exit code 0 only when every check succeeds. The JSON
option is intended for shell scripts and container health probes.

The check does not call an external AI provider or publish content. The current
AI adapter is a deterministic mock, and the current handoff remains simulated.

## Queue workers

The application uses Laravel's configured queue connection (database by
default). Start a worker locally:

```bash
php artisan queue:work --queue=default --tries=3 --timeout=60
```

Monitor and retry failed jobs with Laravel's built-in commands:

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:forget <failed-job-id>
```

Queue connections are configured to dispatch jobs after the surrounding
database transaction commits. This prevents a worker from observing records
that have not committed yet. In production, keep the worker timeout shorter
than the queue connection's `retry_after` value and run workers under a process
supervisor. Do not use the synchronous queue driver for long-running generation
or report jobs.

## Performance and operational guidance

- Keep slow AI, report generation and future social-platform HTTP calls out of
  request handlers; dispatch bounded queue jobs instead.
- Use pagination for growing content collections and select only required
  columns for reporting queries.
- Add indexes only for measured query patterns; validate them with
  `EXPLAIN` against production-like data.
- Use Redis for cache and queue only when the deployment provides and monitors
  Redis; the local MVP remains compatible with its database-backed defaults.
- Use bounded retries and provider-specific rate limits before enabling real
  external integrations.
- A publishing adapter must persist an idempotency key and remote publication
  identifier. A timeout after sending a request is an ambiguous result and
  must not trigger an unconditional duplicate publish.
- Configure retention for failed jobs and generated reports according to the
  deployment's data-retention policy.
