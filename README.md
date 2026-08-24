# Task Scheduler

A SaaS platform for task management with customizable status workflows. Teams define the lifecycle of their own tasks — statuses, transitions, conditions — assign owners, and get notified about changes.

Backend built on Symfony 8 / PHP 8.4, following hexagonal architecture (Ports & Adapters) with a feature-module layout.

## Stack

- **PHP 8.4**, **Symfony 8** (Messenger, Workflow, Security, Mercure Bundle)
- **PostgreSQL** — primary data storage (Doctrine ORM)
- **RabbitMQ** — asynchronous message bus
- **Mercure** — real-time notifications via SSE
- **ClickHouse** — storage and analytics for task events
- **ManticoreSearch** — full-text search over tasks
- **Redis** — cache
- **JWT** (lexik/jwt-authentication-bundle + refresh-token) — authentication

## Architecture

Each feature lives in two sibling directories inside `src/`:

- `SomethingFeature/` — the implementation:
  - `Domain/` — entities, value objects, interactors, ports (interfaces), domain events
  - `Application/` — application services (API), DTO mappers, validators
  - `Infrastructure/` — Doctrine repositories, Messenger handlers/subscribers, external clients (ClickHouse, Manticore, file storage)
  - `Presentation/` — Symfony controllers
- `SomethingFeatureApi/` — the feature's public contract for other modules (interfaces and DTO interfaces only)

Calls between features go **only** through `*FeatureApi` interfaces. Direct use of another feature's internal classes is not allowed.

### Modules

| Module | Purpose |
|---|---|
| `UserFeature` | Registration, JWT authentication (`SecurityUser` wraps the domain `User`) |
| `ProfileFeature` | User profile, created on the `UserRegistered` event |
| `TeamFeature` | Teams and team membership |
| `WorkflowFeature` | Customizable status workflows (statuses + transitions) |
| `TaskFeature` | Tasks: Symfony Workflow integration, assignees, status transitions |
| `CommentFeature` | Comments |
| `DescriptionFeature` | Entity descriptions |
| `TagFeature` | Tags |
| `FileFeature` | File upload and storage (avatars, attachments) |
| `SubscriptionFeature` | Notification subscriptions |
| `NotificationFeature` | Email and Mercure notifications driven by task domain events |
| `SearchFeature` | Full-text search via ManticoreSearch |
| `AnalyticsFeature` | Task event/action analytics via ClickHouse |
| `AuditLogFeature` | Automatic audit trail for all entities implementing `AuditableInterface` |

### Domain events

Entities accumulate events via `recordEvent()`. After `persist()`, the interactor calls `pullDomainEvents()` and dispatches them through `DomainEventDispatcherInterface` → `MessengerDomainEventDispatcher` → the Symfony `event.bus`.

Subscribers on `event.bus` (e.g. `TaskNotificationSubscriber`, `TaskAnalyticsSubscriber`) translate domain events into commands on the `messenger.bus.default` bus (asynchronously, via RabbitMQ).

### Messenger buses

- `event.bus` — domain events; handlers are marked `#[AsMessageHandler(bus: 'event.bus')]`; `allow_no_handlers: true`
- `messenger.bus.default` — asynchronous commands, dispatched to the RabbitMQ transports `async` and `notifications`

In tests both transports are replaced with `in-memory://` (see `config/packages/messenger.yaml`).

### Audit log

Any entity implementing the marker interface `AuditLogFeatureApi\Contract\AuditableInterface` is automatically tracked by `AuditDoctrineSubscriber` on the Doctrine `onFlush` event. It records `create`, `update`, and `delete` actions with change sets in the `AuditEntry` table.

### Infrastructure services

| Service | Role |
|---|---|
| PostgreSQL | Primary data storage |
| RabbitMQ | Asynchronous message transport |
| Mercure | Server-Sent Events for real-time notifications |
| ClickHouse | Analytics storage and queries |
| ManticoreSearch | Full-text search index for tasks |
| Redis | Cache |
| Mailpit | Local SMTP server for catching emails during development |

## Getting started

1. Copy `.env.dist` to `.env` and fill in the values for your environment (ports, DB/RabbitMQ passwords, JWT secrets, etc.).
2. Start the containers:

   ```bash
   task up
   # or
   docker compose up -d
   ```
3. Apply migrations:

   ```bash
   task migration:migrate
   task clickhouse:migrate
   ```
4. Create indexes and reindex search data:

   ```bash
   task search:reindex
   ```

The `consumer` container listens to the `async` transport (`messenger:consume async`) — this is where notifications, analytics, and other background commands are processed.

## Task commands

Common commands are wrapped in [`Taskfile.yml`](Taskfile.yml) via the [go-task](https://taskfile.dev) runner. Install it once per machine ([install guide](https://taskfile.dev/installation/)), then run `task --list` to see all available tasks.

| Command | Description |
|---|---|
| `task up` | Start all containers in the background |
| `task migration:generate` | Generate a new Doctrine migration from entity changes |
| `task migration:migrate` | Apply pending Doctrine migrations (PostgreSQL) |
| `task clickhouse:migrate` | Apply ClickHouse table migrations |
| `task search:reindex` | Recreate Manticore indexes and reindex all search data |
| `task test` | Run the PHPUnit test suite |
| `task phpcbf` | Auto-fix PSR-12 code style issues |
| `task phpstan` | Run static analysis with an increased memory limit |
| `task check` | Run code style fix, static analysis, and tests in sequence |

## Tests and code quality

All commands run inside the `php` Docker container (`docker compose exec php ...`) or locally if PHP 8.4 is installed.

```bash
# Run all checks (style + static analysis + tests)
composer check

# Individual checks
composer phpcs          # PSR-12 code style check
composer phpcbf         # Auto-fix code style
composer phpstan        # Static analysis (level 6)
composer phpunit        # Run all tests

# Run a single test file
./vendor/bin/phpunit tests/Unit/TaskFeature/Domain/Entity/TaskTest.php

# Run a single test method
./vendor/bin/phpunit --filter testMethodName tests/Unit/...
```

PHPStan requires a dev cache (`bin/console cache:warmup`) to resolve the Symfony container.

Test structure:

- `tests/Unit/` — plain PHPUnit `TestCase`, no Symfony kernel; dependencies stubbed with `createStub()`
- `tests/Integration/` — `WebTestCase` (Symfony test client); uses SQLite in-memory and the `in-memory://` transport

The `.env.dist` file shows the CI/test defaults (SQLite, null mailer, in-memory transports).

Code style is PSR-12, enforced by PHP_CodeSniffer. `declare(strict_types=1)` is required in every file.

## License

[MIT](LICENSE)
