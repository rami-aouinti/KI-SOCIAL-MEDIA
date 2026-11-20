# KI Social Media

A strictly time-boxed, **5-hour MVP** for preparing AI-assisted social-media content with a human approval step.

The goal is not to build a complete social-media management platform. The goal is to demonstrate one small, understandable end-to-end workflow: create an idea, generate a draft, review it, approve it, and simulate handing it off.

## 1. Time budget and delivery rule

**Total available implementation time: 5 hours (300 minutes), including setup, tests and documentation.**

| Timebox | Duration | Deliverable |
|---|---:|---|
| 00:00–00:30 | 30 min | Symfony project setup, environment and basic application structure |
| 00:30–01:15 | 45 min | Idea/content entity, persistence and basic CRUD |
| 01:15–02:15 | 60 min | AI provider interface and content generation for selected channels |
| 02:15–03:15 | 60 min | Review/approval workflow and business rules |
| 03:15–04:15 | 60 min | Minimal usable web interface |
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

## 4. Proposed technical approach

Keep the application as a **single Symfony monolith** to minimize setup and integration overhead.

- PHP 8.4
- Symfony 7.4
- Doctrine ORM
- SQLite for a lightweight local development database (unless the existing environment makes another database simpler)
- Twig for server-rendered pages
- Symfony Validator and forms where useful
- PHPUnit for automated tests
- An `AiContentGeneratorInterface` with a deterministic mock implementation; a real API adapter is optional

The exact dependency versions may be adjusted to what is available in the development environment. Avoid adding a dependency unless it directly supports an MVP requirement.

## 5. Minimal domain model

### ContentIdea

- `id`
- `topic`
- `targetAudience`
- `tone`
- `channels`
- `status`
- `createdAt`
- `updatedAt`

### ContentDraft

- `id`
- `contentIdea`
- `hook`
- `body`
- `callToAction`
- `channelVariants`
- `visualSuggestion`
- `version`
- `approvedVersionHash` (nullable)
- `createdAt`
- `updatedAt`

### HandoffRecord

- `id`
- `contentDraft`
- `versionHash`
- `idempotencyKey` (unique)
- `payload`
- `createdAt`

Keep the model smaller if implementation time requires it. Do not build a generic workflow engine.

## 6. Critical business rules

1. A draft must contain its required text and channel variants before it can enter review.
2. Only a draft in `in_review` can be approved.
3. Approval is bound to the exact content version (for example, using a SHA-256 hash of the approved fields).
4. Any edit to approved content invalidates the approval and returns the idea to `draft`.
5. Handoff is allowed only if the current content hash matches the explicitly approved hash.
6. Handoff creates a local simulation record; it never calls a social-media publishing API.
7. Repeating a handoff request with the same idempotency key must not create a second record.

## 7. Acceptance criteria

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

## 8. Suggested project structure

The structure should follow the Symfony conventions and stay intentionally small. One possible organization:

```text
src/
  Controller/
  Entity/
  Enum/
  Repository/
  Service/
    AI/
    Workflow/
templates/
  content/
tests/
  Functional/
  Unit/
```

Do not create empty layers or interfaces without a concrete use.

## 9. Local setup and run

The exact commands will be added once the Symfony skeleton and dependencies are created. The intended developer experience is:

1. Clone the repository.
2. Install dependencies.
3. Configure the local database and optional AI credentials.
4. Run Doctrine migrations.
5. Start the Symfony development server.
6. Open the application in a browser and follow the demo workflow.

The mock AI provider must work without an API key so the demo remains reproducible.

## 10. Demo scenario

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

## 11. Evidence and implementation notes

At the end of the timebox, document:

- Actual time spent (do not claim exactly five hours unless that is what was actually spent).
- Features completed and features not completed.
- Commands used to run the application and tests.
- Test results.
- A few screenshots or a short demo recording, if available.
- Known limitations and sensible next steps.

## 12. Definition of done

- [ ] Application starts locally from documented instructions.
- [ ] A user can complete the idea-to-handoff demo.
- [ ] Approval and handoff rules are enforced on the server side.
- [ ] Duplicate handoffs are prevented.
- [ ] Critical business rules have automated tests.
- [ ] No real social-media post is sent.
- [ ] README reflects the actual implementation, including any deviations from this plan.

---

**Guiding principle:** deliver one reliable end-to-end workflow in five hours. A small working application is better than a large unfinished architecture.
