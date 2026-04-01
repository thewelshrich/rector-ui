# rector-ui

A visual IDE for [Rector](https://getrector.com/) — the PHP automated refactoring tool. Browse rules, preview changes, apply selectively, and generate atomic per-rule git commits, all from your browser.

> **replaces** Rector's paid consulting service ($6K-$8K analysis + $140-$160/hr manual upgrades) with free, open-source software.

## Features

- **Visual diff viewer** — side-by-side and unified views with PHP syntax highlighting
- **Rule explorer** — browse, search, and filter 200+ Rector rules by category
- **Selective application** — accept or reject individual file changes before applying
- **Atomic per-rule commits** — one git commit per Rector rule (the killer feature)
- **File tree navigation** — browse all changed files with change indicators
- **Project dashboard** — overview of project health, recent activity, quick actions
- **Commit queue** — review, configure, and batch-create commits
- **History & rollback** — view rector-ui commit history and revert changes
- **Zero dependencies** — runs entirely locally, no external services needed

## How It Works

```
composer require thewelshrich/rector-ui --dev
vendor/bin/rector-ui serve
# → Opens browser at http://localhost:8199
```

rector-ui starts a local PHP server that wraps the Rector CLI binary. A React frontend provides the visual interface. Everything runs on your machine — no code is sent anywhere.

**Core workflow:**
1. Browse and select Rector rules
2. Run a dry-run analysis
3. Preview diffs in the visual diff viewer
4. Accept or reject individual changes
5. Create atomic git commits (one per rule)

## Planned Installation

```bash
# Add to your project
composer require thewelshrich/rector-ui --dev
composer require rector/rector --dev

# Start the server
vendor/bin/rector-ui serve

# Or use standalone (for legacy projects)
mkdir rector-tools && cd rector-tools
composer require thewelshrich/rector-ui rector/rector
vendor/bin/rector-ui serve /path/to/your/project
```

## Requirements

- **PHP 8.1+** (Rector requires 8.1+, but can process projects targeting PHP 5.3+)
- **Rector** (`composer require rector/rector --dev`)
- **Git** (for atomic commits and rollback)
- **Node.js 18+** (for frontend development only; built assets are committed)

## Architecture

rector-ui follows a local HTTP server + browser UI architecture, similar to PHPStan Pro:

- **Backend**: PHP built-in development server + nikic/fast-route router
- **Rector integration**: CLI wrapper via symfony/process (no public PHP API exists)
- **Frontend**: Vite + React 18 + TypeScript + shadcn/ui
- **State**: Zustand + TanStack Query
- **Config**: `rector-ui.json` in project root

```
Browser (React UI)
    ↕ HTTP REST API
PHP Server (localhost:8199)
    ↕ symfony/process
Rector CLI (vendor/bin/rector)
    ↕ git
Working Tree
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for the full technical specification.

## Development Roadmap

| Phase | Description | Status |
|-------|-------------|--------|
| 0 | Project setup (composer, CI, frontend scaffold) | Planned |
| 1 | Core backend (server, CLI wrapper, API) | Planned |
| 2 | Analysis engine (rules, dry-run, diff parsing) | Planned |
| 3 | Frontend MVP (React UI, all core views) | Planned |
| 4 | Commit engine (atomic per-rule commits) | Planned |
| 5 | Polish (history, watch mode, CI, IDE, UX) | Planned |

See [ROADMAP.md](ROADMAP.md) for the full development plan with issue links.

## Contributing

See the [open issues](https://github.com/thewelshrich/rector-ui/issues) for work items. Each issue includes detailed acceptance criteria and references to related work.

## License

MIT
