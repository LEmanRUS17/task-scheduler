# Task scheduler

A SaaS for task management with customizable status workflows. Teams define the lifecycle of their tasks (statuses, transitions, conditions), assign owners, and receive notifications.

## Task commands

Common commands are wrapped in [`Taskfile.yml`](Taskfile.yml) via the [go-task](https://taskfile.dev) runner. Install it once per machine ([install guide](https://taskfile.dev/installation/)), then run `task --list` to see all available tasks.

| Command | Description |
|---|---|
| `task up` | Start all containers in the background |
| `task migration:generate` | Generate a new Doctrine migration from entity changes |
| `task migration:migrate` | Apply pending Doctrine migrations (PostgreSQL) |
| `task clickhouse:migrate` | Apply ClickHouse table migrations |
| `task search:reindex` | Recreate Manticore indexes and reindex all search data |

## License

[MIT](LICENSE)
