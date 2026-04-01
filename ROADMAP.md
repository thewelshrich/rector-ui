# rector-ui Roadmap

> Development roadmap for rector-ui — a visual IDE for the PHP refactoring tool Rector.
> Issues are tracked on GitHub: [thewelshrich/rector-ui/issues](https://github.com/thewelshrich/rector-ui/issues)

---

## Overview

rector-ui replaces Rector's paid consulting service ($6K-$8K analysis + $140-$160/hr manual upgrades) with a free, open-source software product. The primary differentiator is **atomic per-rule git commits** — a feature Rector core explicitly refuses to build.

**Estimated timeline**: 10 weeks to production-ready MVP.

---

## Phase 0: Project Setup (Week 1)

Foundation: composer package, PSR-4 structure, CI pipeline, frontend scaffolding.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 1 | [#1](https://github.com/thewelshrich/rector-ui/issues/1) | Initialize composer.json with PSR-4 structure and dependencies | Open |
| 2 | [#2](https://github.com/thewelshrich/rector-ui/issues/2) | Set up PHPUnit testing framework | Open |
| 3 | [#3](https://github.com/thewelshrich/rector-ui/issues/3) | Set up PHP coding standards with PHP-CS-Fixer | Open |
| 4 | [#4](https://github.com/thewelshrich/rector-ui/issues/4) | Create CLI entry point (bin/rector-ui serve command) | Open |
| 5 | [#5](https://github.com/thewelshrich/rector-ui/issues/5) | Scaffold Vite + React + TypeScript + shadcn/ui frontend | Open |

**Milestone**: `composer install` works, `vendor/bin/rector-ui serve` starts a server, `npm run dev` shows a React page.

---

## Phase 1: Core Backend (Weeks 2-3)

The PHP server that wraps Rector CLI via symfony/process and exposes a REST API.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 6 | [#6](https://github.com/thewelshrich/rector-ui/issues/6) | Build PHP HTTP server with fast-route router | Open |
| 7 | [#7](https://github.com/thewelshrich/rector-ui/issues/7) | Implement RectorCliWrapper service (symfony/process) | Open |
| 8 | [#8](https://github.com/thewelshrich/rector-ui/issues/8) | Implement ConfigGenerator for dynamic rector.php files | Open |
| 9 | [#9](https://github.com/thewelshrich/rector-ui/issues/9) | Implement project management API endpoints | Open |
| 10 | [#10](https://github.com/thewelshrich/rector-ui/issues/10) | Implement static file serving for frontend assets | Open |

**Milestone**: `GET /api/status` returns JSON, `GET /api/rules` returns rules from Rector, frontend loads from PHP server.

---

## Phase 2: Analysis Engine (Weeks 3-4)

The brain: rule discovery, dry-run analysis, diff parsing, selective application.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 11 | [#11](https://github.com/thewelshrich/rector-ui/issues/11) | Implement rule discovery API (list-rules parsing) | Open |
| 12 | [#12](https://github.com/thewelshrich/rector-ui/issues/12) | Implement dry-run analysis orchestration | Open |
| 13 | [#13](https://github.com/thewelshrich/rector-ui/issues/13) | Implement unified diff parser | Open |
| 14 | [#14](https://github.com/thewelshrich/rector-ui/issues/14) | Implement apply endpoint (selective change application) | Open |

**Milestone**: Can run a full dry-run analysis, get structured diffs, and apply selected changes to disk.

---

## Phase 3: Frontend MVP (Weeks 4-6)

The face: React UI with all core views — dashboard, rules, diffs, analysis workflow.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 15 | [#15](https://github.com/thewelshrich/rector-ui/issues/15) | Create API client and shared state management | Open |
| 16 | [#16](https://github.com/thewelshrich/rector-ui/issues/16) | Create Dashboard and Project Setup pages | Open |
| 17 | [#17](https://github.com/thewelshrich/rector-ui/issues/17) | Create Rule Explorer page with search and filtering | Open |
| 18 | [#18](https://github.com/thewelshrich/rector-ui/issues/18) | Create Diff Viewer with split/unified views and PHP syntax highlighting | Open |
| 19 | [#19](https://github.com/thewelshrich/rector-ui/issues/19) | Create Analysis Results page with file tree and diff integration | Open |
| 20 | [#20](https://github.com/thewelshrich/rector-ui/issues/20) | Create analysis configuration and execution workflow | Open |

**Milestone**: Complete end-to-end workflow — select rules, run analysis, preview diffs, apply changes — all in the browser.

---

## Phase 4: Commit Engine (Weeks 6-7)

The killer feature: atomic per-rule git commits.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 21 | [#21](https://github.com/thewelshrich/rector-ui/issues/21) | Implement GitService for git operations | Open |
| 22 | [#22](https://github.com/thewelshrich/rector-ui/issues/22) | Implement atomic per-rule commit generation | Open |
| 23 | [#23](https://github.com/thewelshrich/rector-ui/issues/23) | Create Commit Queue UI page | Open |
| 24 | [#24](https://github.com/thewelshrich/rector-ui/issues/24) | Implement history API endpoint and rollback support | Open |

**Milestone**: One-click atomic commits — each Rector rule gets its own clean git commit. This replaces Rector's paid consulting service.

---

## Phase 5: Polish (Weeks 8-10)

Production quality: history, watch mode, CI integration, IDE support, UX polish.

| # | Issue | Description | Status |
|---|-------|-------------|--------|
| 25 | [#25](https://github.com/thewelshrich/rector-ui/issues/25) | Create History page with git log and rollback UI | Open |
| 26 | [#26](https://github.com/thewelshrich/rector-ui/issues/26) | Add watch mode with file watcher and incremental analysis | Open |
| 27 | [#27](https://github.com/thewelshrich/rector-ui/issues/27) | Generate GitHub Actions CI workflow for Rector | Open |
| 28 | [#28](https://github.com/thewelshrich/rector-ui/issues/28) | Add IDE integration (open files in VS Code and PhpStorm) | Open |
| 29 | [#29](https://github.com/thewelshrich/rector-ui/issues/29) | Add dark/light theme support and keyboard shortcuts | Open |
| 30 | [#30](https://github.com/thewelshrich/rector-ui/issues/30) | Add comprehensive error handling, recovery UX, and performance optimization | Open |

**Milestone**: Production-ready release. Watch mode, CI integration, IDE support, polished UX.

---

## Dependency Graph

```
Phase 0 (Foundation)
  #1 composer.json ──┬── #2 PHPUnit
  #4 CLI entry ──────┤── #5 Frontend scaffold ── #10 Static serving
                     └── #6 HTTP server
                          ├── #7 RectorCliWrapper
                          │    ├── #8 ConfigGenerator
                          │    └── #9 Project API
                          └── #11 Rule Discovery
                               └── #12 Analysis Engine
                                    ├── #13 Diff Parser
                                    └── #14 Apply Endpoint

Phase 3 (Frontend)
  #15 API Client ──┬── #16 Dashboard/Setup
                   ├── #17 Rule Explorer
                   ├── #18 Diff Viewer ── #19 Analysis Results
                   └── #20 Analysis Workflow

Phase 4 (Commits)
  #14 Apply ── #21 GitService ── #22 Atomic Commits
                                    ├── #23 Commit Queue UI
                                    └── #24 History API ── #25 History UI

Phase 5 (Polish)
  #26 Watch Mode (depends on #12)
  #27 CI Generator (depends on #12)
  #28 IDE Integration (depends on #18)
  #29 Theme/Shortcuts (depends on #5)
  #30 Error Handling (all issues)
```

---

## Key Milestones

| Milestone | Target | What Ships |
|-----------|--------|------------|
| **Alpha** | End of Week 3 | Server runs, API works, basic frontend loads |
| **MVP** | End of Week 6 | Full workflow: select rules → analyze → preview → apply |
| **v1.0** | End of Week 7 | + Atomic per-rule commits (the killer feature) |
| **v1.5** | End of Week 10 | + Watch mode, CI integration, IDE support, polish |
