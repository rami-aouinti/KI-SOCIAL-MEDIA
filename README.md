# KI Social Media

A strictly time-boxed, **5-hour MVP** for preparing AI-assisted social-media content with a human approval step.

The goal is not to build a complete social-media management platform. The goal is to demonstrate one small, understandable end-to-end workflow: create an idea, generate a draft, review it, approve it, and simulate handing it off.

## 1. Time budget and delivery rule

**Total available implementation time: 5 hours (300 minutes), including setup, tests and documentation.**

| Timebox | Duration | Deliverable |
|---|---:|---|
| 00:00–00:30 | 30 min | Laravel project setup, Docker/Sail environment, database and basic application structure |
| 00:30–01:15 | 45 min | Eloquent models, migrations, persistence and basic CRUD |
| 01:15–02:15 | 60 min | AI provider interface and content generation for selected channels |
| 02:15–03:15 | 60 min | Review/approval workflow and business rules |
| 03:15–04:15 | 60 min | Minimal usable web interface with Blade |
| 04:15–04:45 | 30 min | Essential automated tests |
| 04:45–05:00 | 15 min | README, demo data and final verification |
| **Total** | **300 min** | **Runnable MVP** |

The timeboxes are limits, not a reason to silently extend the project. If a task takes longer, reduce optional polish and keep the core workflow working. Record the actual time spent honestly in the final implementation notes.

## 2. MVP user story

As a content editor, I want to:
1. Create a social-media content idea with a topic, target audience, tone and selected channels.
2. Ask an AI provider to generate a first draft.
3. Review and manually edit the generated text.
4. Submit the content for human review.
5. Approve the current version.
6. Simulate a handoff of the approved content without publishing it to a real social network.

## 3. Functional scope

### Included

- Create, list, view, edit and delete content ideas.
- Store the topic, audience, tone and selected channels.
- Generate a content draft containing:
  - A short hook.
  - Main post text.
  - A call to action.
  - A channel-specific text variant for each selected channel.
  - A short visual/creative suggestion (text only).
- Use a small AI provider abstraction:
  - A deterministic mock provider so the application works without API credentials.
  - An optional real AI provider if time permits and an API key is configured.
- Human workflow with these states:
  - `draft`
  - `in_review`
  - `approved`
  - `handed_off`
- Only a complete, explicitly approved current version can be handed off.
- Editing content after approval invalidates the approval and returns it to draft.
- Simulated handoff with an idempotency key to prevent duplicate handoff records.
- A simple web interface for the end-to-end workflow.
- A small set of automated tests for the critical business rules.
- Synthetic demo data and setup/demo instructions.

### Explicitly out of scope

To protect the 5-hour limit, this MVP will not implement:

- Real publishing to Instagram, Facebook, LinkedIn or other platforms.
- OAuth, social-network account connections or external publishing APIs.
- Real image/video generation, uploads or media storage.
- n8n or another external workflow orchestrator.
- RAG, vector databases, embeddings or a separate Python AI service.
- Microservices, message brokers, Kubernetes or cloud deployment.
- Analytics, ROI calculations or performance dashboards.
- Automated comment moderation or automatic replies.
- Complex team permissions, multi-tenancy or advanced capacity planning.
- Email delivery or calendar integrations.
- A complex SPA frontend or extensive visual design.

These are possible future improvements, not promises for the 5-hour delivery.

## 4. Technical approach and framework choice

Use **Laravel as a single PHP monolith**. Run the application and its development tools in Docker so the project does not depend on PHP, Composer or Node.js versions installed on the host machine.

### Stack

- PHP 8.4
- Latest stable Laravel release compatible with PHP 8.4 (Laravel 13 is the target)
- Laravel Sail / Docker Compose for the local development environment
- Composer inside the PHP container
- Node.js/npm inside the Sail environment only if frontend assets require it
- Eloquent ORM
- MySQL 8.4 (dedicated database container managed by Docker Compose)
- Blade for server-rendered pages
- Laravel routing and Form Request validation
- Laravel service container for dependency injection
- PHPUnit or Pest for automated tests

### Why Laravel and Docker?

- Laravel provides routing, validation, database migrations and dependency injection out of the box.
- Eloquent makes the small CRUD and persistence requirements quick to implement.
- Blade provides a straightforward server-rendered interface without introducing a separate frontend application.
- Laravel's testing tools make it practical to cover the critical workflow rules.
- Docker/Sail provides a repeatable environment for the whole team and avoids host-machine version differences.
- SQLite keeps the infrastructure small: we do not need a separate database service for this MVP.

A framework is a pragmatic choice: it supplies established infrastructure and conventions, while the application-specific value remains in the business logic. Symfony would also be technically suitable; Laravel is selected for this implementation to gain practical experience with its ecosystem.

### Keep the implementation deliberately small

- Use Eloquent models and migrations for persistence.
- Use controllers for HTTP orchestration, not for complex business logic.
- Put content generation behind an `AiContentGeneratorInterface` and bind a deterministic mock implementation through Laravel's service container.
- Keep approval and handoff rules in a focused application service (or a small number of focused services).
- Use Form Requests for input validation where useful.
- Do not add repositories, interfaces, packages or architectural layers without a concrete need.
- Do not add Livewire or Inertia to the mandatory scope; Blade is sufficient for this MVP.
- Do not add MySQL/PostgreSQL, Redis or extra containers unless a real requirement appears.

## 5. Docker-first local development

Docker Desktop with the WSL2 backend is the expected local runtime on Windows/WSL. Docker Engine and the Docker Compose plugin must be available inside the terminal.

Laravel Sail is the project's Docker-based development environment. After the Laravel skeleton is created, install Sail as a development dependency and generate its Compose configuration. Keep the Compose setup minimal: the Laravel application container only; use SQLite for persistence.

### Expected daily commands

Run these commands from the repository root after Sail has been installed:

```bash
# Start the application container
./vendor/bin/sail up -d

# Run Artisan commands
./vendor/bin/sail artisan about
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test

# Run Composer inside the container
./vendor/bin/sail composer install
./vendor/bin/sail composer require vendor/package

# Stop the environment
./vendor/bin/sail down
```

Use `./vendor/bin/sail` rather than relying on globally installed PHP, Composer or Node. Do not commit secrets or a populated local SQLite database. Keep `.env.example` as the reproducible configuration template.

## 6. Minimal domain model

### ContentIdea

- `id`
- `topic`
- `target_audience`
- `tone`
- `channels` (JSON)
- `status`
- `created_at`
- `updated_at`

### ContentDraft

- `id`
- `content_idea_id`
- `hook`
- `body`
- `call_to_action`
- `channel_variants` (JSON)
- `visual_suggestion`
- `version`
- `approved_version_hash` (nullable)
- `created_at`
- `updated_at`

### HandoffRecord

- `id`
- `content_draft_id`
- `version_hash`
- `idempotency_key` (unique)
- `payload` (JSON)
- `created_at`

Keep the model smaller if implementation time requires it. Do not build a generic workflow engine.

## 7. Critical business rules

1. A draft must contain its required text and channel variants before it can enter review.
2. Only a draft in `in_review` can be approved.
3. Approval is bound to the exact content version (for example, using a SHA-256 hash of the approved fields).
4. Any edit to approved content invalidates the approval and returns the idea to `draft`.
5. Handoff is allowed only if the current content hash matches the explicitly approved hash.
6. Handoff creates a local simulation record; it never calls a social-media publishing API.
7. Repeating a handoff request with the same idempotency key must not create a second record.

## 8. Acceptance criteria

The MVP is considered complete when the following scenarios work:

| ID | Scenario | Expected result |
|---|---|---|
| AT-01 | Create an idea and generate content | Idea and generated draft are persisted |
| AT-02 | Submit an incomplete draft for review | Request is rejected with a useful validation message |
| AT-03 | Approve content that is not in review | Approval is rejected |
| AT-04 | Approve a complete draft in review | Current version is marked approved |
| AT-05 | Edit content after approval | Approval is invalidated and status returns to draft |
| AT-06 | Handoff without valid approval | Handoff is rejected |
| AT-07 | Handoff approved content twice with the same key | Only one handoff record exists |
| AT-08 | Handoff approved content | A simulated handoff record is created; no external post is published |

At least the most important business rules (AT-03, AT-05, AT-06 and AT-07) must have automated tests. If time allows, cover all acceptance criteria.

## 9. Suggested Laravel project structure

Follow Laravel conventions and keep the structure intentionally small:

```text
app/
  Http/
    Controllers/
    Requests/
  Models/
  Services/
    AI/
    Workflow/
database/
  migrations/
  seeders/
resources/
  views/
    content/
routes/
  web.php
tests/
  Feature/
  Unit/
```

Do not create empty layers or interfaces without a concrete use.

## 10. Clone and bootstrap

Clone the `master` branch:

```bash
git clone -b master https://github.com/rami-aouinti/KI-SOCIAL-MEDIA.git
cd KI-SOCIAL-MEDIA
```

The repository currently contains the project plan. The Laravel application skeleton and Sail configuration are created during the first 30-minute setup timebox; they are not pre-generated in this documentation-only commit.

Before starting, verify Docker is available:

```bash
docker --version
docker compose version
docker info
```

Bootstrap the Laravel skeleton using the PHP 8.4 Composer image, without requiring PHP or Composer on the host:

```bash
mkdir -p .bootstrap
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD/.bootstrap":/app -w /app \
  laravelsail/php84-composer:latest \
  composer create-project laravel/laravel .

cp -a .bootstrap/. .
rm -rf .bootstrap
```

Then install Sail and generate the Docker Compose configuration. Select `mysql` as the database service:

```bash
docker run --rm -u "$(id -u):$(id -g)" \
  -v "$PWD":/var/www/html -w /var/www/html \
  laravelsail/php84-composer:latest \
  composer require laravel/sail --dev

docker run --rm -it -u "$(id -u):$(id -g)" \
  -v "$PWD":/var/www/html -w /var/www/html \
  laravelsail/php84-composer:latest \
  php artisan sail:install
```

After Sail generates its Compose file, ensure its PHP runtime path and image use PHP 8.4 (`vendor/laravel/sail/runtimes/8.4` and `sail-8.4/app`). Keep the MySQL service and the `depends_on: mysql` dependency. Then use the project-local Sail executable:

```bash
cp .env.example .env
# Configure DB_CONNECTION=mysql, DB_HOST=mysql, DB_PORT=3306,
# DB_DATABASE=laravel, DB_USERNAME=sail and DB_PASSWORD=password in .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test
```

If a bootstrap command fails, stop and fix that step before continuing; do not spend the rest of the five-hour budget debugging optional infrastructure. Check the generated Compose file and `.env` before the first startup. The application container must use PHP 8.4 and the database container must use MySQL 8.4. Never commit `.env`, credentials, or the local SQLite database.

## 11. Demo scenario

Use fictional data for a local optician:

- Topic: Summer sunglasses collection.
- Audience: Adults looking for everyday sunglasses.
- Tone: Friendly and informative.
- Channels: Instagram and Facebook.

Demo sequence:

1. Create the idea.
2. Generate the draft with the mock AI provider (or configured real provider).
3. Inspect and edit the hook, copy and channel variants.
4. Submit the draft for review.
5. Approve it.
6. Trigger the simulated handoff.
7. Trigger the same handoff again and show that no duplicate is created.
8. Edit the content and show that the old approval is no longer valid.

## 12. Evidence and implementation notes

At the end of the timebox, document:

- Actual time spent (do not claim exactly five hours unless that is what was actually spent).
- Features completed and features not completed.
- Commands used to run the application and tests.
- Test results.
- A few screenshots or a short demo recording, if available.
- Known limitations and sensible next steps.

## 13. Definition of done

- [ ] Application starts locally using Docker/Sail from documented instructions.
- [ ] A user can complete the idea-to-handoff demo.
- [ ] Approval and handoff rules are enforced on the server side.
- [ ] Duplicate handoffs are prevented.
- [ ] Critical business rules have automated tests.
- [ ] No real social-media post is sent.
- [ ] README reflects the actual implementation, including any deviations from this plan.

---

**Guiding principle:** deliver one reliable end-to-end workflow in five hours. A small working application is better than a large unfinished architecture.
