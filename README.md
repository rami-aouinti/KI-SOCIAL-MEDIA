# KI Social Media

A strictly time-boxed **4-hour MVP** for preparing AI-assisted social-media content with a human approval step and a safe, simulated handoff.

The goal is to demonstrate one understandable end-to-end editorial workflow—not to build a complete social-media management platform.

## Project documentation

The detailed architecture, relational model, workflow, business rules, tests, scope decisions and their rationale are documented in French and German:

- **[Architecture et décisions techniques / Architektur und technische Entscheidungen](docs/ARCHITECTURE.md)**

That document is the design reference for implementation. It explicitly distinguishes implemented behavior, simulation and future concepts.

## 1. Time budget and delivery rule

**Maximum total time: 4 hours (240 minutes), including setup, implementation, tests and documentation.**

This limit is strict. If a task takes longer than expected, reduce optional polish and preserve the core workflow. Record the actual time spent honestly in the final delivery notes.

| Work area | Target | Result |
|---|---:|---|
| Environment and project understanding | 20 min | Runnable Laravel/Sail environment |
| Data model and persistence | 40 min | Migrations, Eloquent relationships, demo data |
| Planning and capacity | 35 min | Five ideas, three priorities, two reserves, capacity warnings |
| Briefing and content generation | 35 min | One complete briefing and structured channel variants |
| Versioning and human approval | 40 min | Immutable versions, approval snapshot and hash |
| Simulated handoff and report | 25 min | Idempotent handoff payload and weekly text report |
| Tests, evidence and documentation | 45 min | Four mandatory scenarios, evidence and final notes |
| **Total** | **240 min** | **Time-boxed MVP** |

The timeboxes are planning guidance, not permission to exceed the four-hour limit.

## 2. End-to-end workflow

1. Read structured, synthetic weekly input and at least three simulated inspiration references.
2. Create exactly five ideas: three priorities and two reserves, with a short selection rationale.
3. Allocate production, channel-variant, coordination and approval work to Person A (90 min/week) and Person B (150 min/week).
4. Display a warning and a reasoned alternative when capacity is exceeded. Never silently increase capacity.
5. Create one complete production briefing for a priority idea.
6. Generate channel-specific content variants and preserve each content version immutably.
7. Require explicit human approval of the exact version, text, media reference, channels and schedule.
8. Simulate the handoff only for complete, approved content. Repeated requests must not create duplicates.
9. Generate a weekly text report from persisted application data.

The exact data model and rationale are documented in [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md).

## 3. Scope and transparency

### Included in the MVP

- Laravel monolith, MySQL and Blade.
- Five-idea weekly planning with three priorities and two reserves.
- Synthetic inspiration references with provenance and evidence status.
- Capacity calculation and visible warnings.
- One detailed production briefing.
- Structured channel variants.
- Immutable content versions.
- Human approval bound to an exact snapshot and SHA-256 hash.
- Simulated, idempotent handoff.
- Weekly text report.
- Automated tests for the critical business rules.

### Explicitly not implemented as real integrations

- No real publishing to Instagram, Facebook, TikTok or LinkedIn.
- No social-network OAuth or publishing API.
- No real image/video creation or media upload.
- No live trend research or scraping.
- No real email delivery.
- No production hosting or distributed microservice architecture.

Analytics/attribution, economics, comment moderation and production operations are documented as future extensions only. See the bilingual architecture document for details.

All demonstration people, source IDs, content and appointment links must be fictional. Use `example.com` for example links. Missing metrics remain unknown; they must not be invented or represented as zero.

## 4. Technical approach

- PHP 8.4
- Laravel
- Laravel Sail / Docker Compose
- MySQL 8.4
- Eloquent ORM and Laravel migrations
- Blade for a small server-rendered interface
- Laravel service container for dependency injection
- PHPUnit for automated tests

Laravel is selected because it provides routing, validation, migrations, ORM and dependency injection with little setup. A monolith is appropriate for a small, time-boxed workflow. Business rules remain in focused services; controllers should stay thin.

MySQL is used because it is the database configured for this project. The application should be tested against the same database engine used by the local Docker environment rather than silently switching to SQLite.

A deterministic mock AI provider keeps the demo reproducible and independent of API credentials. A real provider is a possible future adapter, not a requirement for the MVP.

## 5. Local development

Run commands from the repository root:

```bash
# Start Docker/Sail services
./vendor/bin/sail up -d

# Inspect the Laravel environment
./vendor/bin/sail artisan about

# Apply migrations
./vendor/bin/sail artisan migrate

# Run automated tests
./vendor/bin/sail artisan test

# Stop services
./vendor/bin/sail down
```

The local environment uses MySQL. Verify the values in the local `.env` without committing credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
```

Use the project's Sail executable for PHP, Artisan and Composer commands. Do not commit `.env`, credentials or local database data.

## 6. Mandatory acceptance tests

| ID | Scenario | Expected result |
|---|---|---|
| T1 | A complete approved version is handed off repeatedly | Exactly one handoff record exists |
| T2 | Approval or media reference is missing | Handoff is blocked |
| T3 | Approved content is changed | A new version and new approval are required |
| T4 | Person's estimated workload exceeds capacity | A visible warning or reasoned rescheduling is produced |

Additional tests should cover the five-idea composition, allowed state transitions, failed handoff behavior and consistency of the weekly report.

## 7. Definition of done

- [ ] The application starts locally with Docker/Sail and MySQL.
- [ ] The weekly plan contains exactly three priorities and two reserves.
- [ ] Inspiration provenance and unknown metrics are represented honestly.
- [ ] Capacity is calculated against 90 minutes for A and 150 minutes for B.
- [ ] One priority has a complete production briefing and channel variants.
- [ ] Versions are immutable.
- [ ] Approval is tied to the exact content snapshot.
- [ ] Handoff is blocked without valid approval or a media reference.
- [ ] Repeated handoff does not create duplicates.
- [ ] The weekly report is generated from persisted data.
- [ ] The four mandatory test scenarios are demonstrated.
- [ ] No real social-media publication or email is sent.
- [ ] README and architecture documentation reflect what was actually implemented.

## 8. Final delivery notes

At the end of the timebox, document:

- Actual time spent.
- Features implemented.
- Features simulated.
- Features only designed/documented.
- Commands used to run the application and tests.
- Test results and evidence (logs or screenshots).
- Known limitations and sensible next steps.
- AI tool/provider use, including whether generation was mocked.

**Guiding principle:** deliver one reliable, traceable end-to-end workflow within four hours. A small working application is better than a large unfinished architecture.
