# rector-ui Architecture Specification

> The definitive technical blueprint for rector-ui — a visual IDE for the PHP refactoring tool Rector.
> This document covers system design, API contracts, data flow, and phased delivery.

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Backend Architecture](#2-backend-architecture)
3. [API Design](#3-api-design)
4. [Frontend Architecture](#4-frontend-architecture)
5. [Data Flow: A Refactoring Session](#5-data-flow-a-refactoring-session)
6. [Configuration Format](#6-configuration-format)
7. [Installation Flow](#7-installation-flow)
8. [Phased Delivery Plan](#8-phased-delivery-plan)
9. [Technical Constraints & Decisions](#9-technical-constraints--decisions)

---

## 1. System Overview

rector-ui is a local development tool that provides a visual web interface for running Rector — the PHP automated refactoring tool. It replaces Rector's paid consulting service ($6K-$8K analysis + $140-$160/hr manual upgrades) with a free, open-source software product.

### How It Works End-to-End

```
Developer runs:  vendor/bin/rector-ui serve

  ┌─────────────────────────────────────────────────────────┐
  │  PHP CLI Server (localhost:8199)                         │
  │                                                          │
  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
  │  │ HTTP Router   │  │ REST API     │  │ Static File  │  │
  │  │ (fast-route)  │  │ Controllers  │  │ Server       │  │
  │  └──────┬───────┘  └──────┬───────┘  └──────────────┘  │
  │         │                 │                             │
  │  ┌──────▼─────────────────▼─────────────────────────┐   │
  │  │ Services                                         │   │
  │  │  • RectorCliWrapper (symfony/process)            │   │
  │  │  • ConfigGenerator (dynamic rector.php)          │   │
  │  │  • DiffParser (unified diff → structured data)   │   │
  │  │  • GitService (commit, rollback, log)            │   │
  │  │  • ProjectService (detect, validate, configure)  │   │
  │  └──────────────────────────────────────────────────┘   │
  └──────────────────────┬──────────────────────────────────┘
                         │
  ┌──────────────────────▼──────────────────────────────────┐
  │  Browser (http://localhost:8199)                         │
  │                                                          │
  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐  │
  │  │Dashboard │ │Rule      │ │Diff      │ │Commit    │  │
  │  │          │ │Explorer  │ │Viewer    │ │Queue     │  │
  │  └──────────┘ └──────────┘ └──────────┘ └──────────┘  │
  └─────────────────────────────────────────────────────────┘
```

**Key principle**: Everything runs locally. No code is sent to external servers. The PHP server wraps the Rector CLI binary via `symfony/process` and exposes a REST API that a React frontend consumes.

---

## 2. Backend Architecture

### 2.1 Server: PHP Built-in Development Server

rector-ui uses PHP's built-in development server (`php -S`) with a custom router script. This eliminates external server dependencies (no Nginx, no Apache) while being sufficient for local development tool usage.

```bash
php -S localhost:8199 bin/router.php
```

The router script (`bin/router.php`) handles:
- Serving static assets from `public/` (the built React frontend)
- Routing API requests to the PHP application
- Returning 404 for unmatched routes

### 2.2 Router: nikic/fast-route

We use `nikic/fast-route` for lightweight, performant URL routing. It supports:
- Static routes: `GET /api/status`
- Parameter routes: `GET /api/files/{path}`
- Route groups with middleware

### 2.3 Dependency Stack

| Package | Purpose |
|---------|---------|
| `nikic/fast-route` | HTTP routing |
| `symfony/process` | Spawning Rector CLI processes |
| `symfony/http-foundation` | Request/Response objects |
| `symfony/console` | CLI entry point (`serve` command) |
| `psr/log` | Logging interface |
| `monolog/monolog` | Logging implementation |
| `phpunit/phpunit` | Testing |

### 2.4 PSR-4 Namespace Structure

```
rector-ui/
├── bin/
│   ├── rector-ui              # CLI entry point (#!/usr/bin/env php)
│   └── router.php             # PHP built-in server router
├── config/
│   └── services.php           # DI service definitions (simple factory)
├── public/                    # Built frontend assets (committed to repo)
│   └── index.html
├── src/
│   ├── Application/           # Application bootstrap, container
│   │   ├── Kernel.php         # Bootstraps services, router
│   │   └── Container.php      # Simple PSR-11 container
│   ├── Console/               # CLI commands
│   │   └── ServeCommand.php   # `rector-ui serve` command
│   ├── Controller/            # HTTP controllers
│   │   ├── ProjectController.php
│   │   ├── RuleController.php
│   │   ├── AnalysisController.php
│   │   ├── DiffController.php
│   │   ├── CommitController.php
│   │   └── HistoryController.php
│   ├── Http/                  # HTTP layer
│   │   ├── Request.php        # Request wrapper
│   │   ├── JsonResponse.php   # JSON response helper
│   │   └── Middleware/        # CORS, auth, etc.
│   ├── Service/               # Business logic
│   │   ├── RectorCliWrapper.php    # Wraps Rector CLI via symfony/process
│   │   ├── ConfigGenerator.php     # Generates dynamic rector.php configs
│   │   ├── DiffParser.php          # Parses unified diff output
│   │   ├── GitService.php          # Git operations
│   │   ├── ProjectService.php      # Project detection & validation
│   │   └── AnalysisService.php     # Orchestrates analysis workflow
│   ├── Model/                 # Data models / Value Objects
│   │   ├── Project.php
│   │   ├── Rule.php
│   │   ├── RuleSet.php
│   │   ├── FileDiff.php
│   │   ├── AnalysisResult.php
│   │   ├── AnalysisRun.php
│   │   └── Commit.php
│   └── Exception/             # Custom exceptions
│       ├── RectorProcessException.php
│       ├── ProjectNotFoundException.php
│       └── InvalidConfigException.php
├── tests/
│   ├── Unit/
│   └── Integration/
├── frontend/                  # React source (builds to public/)
│   ├── src/
│   ├── public/
│   ├── package.json
│   ├── tsconfig.json
│   └── vite.config.ts
├── storage/                   # Runtime data (gitignored)
│   ├── sessions/              # Analysis session state
│   └── logs/
├── rector-ui.json             # rector-ui configuration
├── composer.json
└── phpunit.xml.dist
```

### 2.5 RectorCliWrapper: The Core Integration

This service wraps every Rector CLI interaction. It is the single point of contact with Rector.

```php
class RectorCliWrapper
{
    public function __construct(
        private string $projectPath,
        private ?string $rectorBinary = null,
    ) {
        $this->rectorBinary = $rectorBinary
            ?? $this->projectPath . '/vendor/bin/rector';
    }

    /**
     * List all configured rules as structured data.
     */
    public function listRules(?string $configPath = null): array
    {
        return $this->execute([
            'list-rules',
            '--output-format', 'json',
            ...($configPath ? ['--config', $configPath] : []),
        ]);
    }

    /**
     * Run a dry-run analysis. Returns FileDiff[] without modifying files.
     */
    public function dryRun(
        array $paths,
        ?string $configPath = null,
        ?string $onlyRule = null,
    ): AnalysisResult {
        $command = array_filter([
            'process',
            ...$paths,
            '--dry-run',
            '--output-format', 'json',
            '--no-parallel',
            '--clear-cache',
            $configPath ? '--config' : null,
            $configPath,
            $onlyRule ? '--only' : null,
            $onlyRule,
        ], fn($v) => $v !== null);

        $output = $this->execute($command);
        return $this->parseAnalysisOutput($output);
    }

    /**
     * Apply changes (no dry-run). Modifies files on disk.
     */
    public function apply(
        array $paths,
        ?string $configPath = null,
        ?string $onlyRule = null,
    ): AnalysisResult {
        // Same as dryRun but without --dry-run
    }

    /**
     * Execute a Rector CLI command via symfony/process.
     */
    private function execute(array $args): array
    {
        $process = new Process(
            [$this->rectorBinary, ...$args],
            $this->projectPath,
            timeout: 600,  // 10 min timeout for large codebases
        );
        $process->run();

        if (!$process->isSuccessful()) {
            throw new RectorProcessException(
                $process->getErrorOutput() ?: $process->getOutput()
            );
        }

        return json_decode($process->getOutput(), true) ?? [];
    }
}
```

### 2.6 ConfigGenerator: Dynamic Config Files

Since Rector has no public PHP API, we control behavior by generating temporary `rector.php` config files:

```php
class ConfigGenerator
{
    /**
     * Generate a config with a single rule enabled.
     */
    public function generateSingleRuleConfig(
        string $ruleClass,
        array $paths,
        string $projectPath,
    ): string {
        $content = sprintf(
            '<?php declare(strict_types=1);' . "\n\n"
            . 'use Rector\Config\RectorConfig;' . "\n\n"
            . 'return RectorConfig::configure()' . "\n"
            . '    ->withPaths(%s)' . "\n"
            . '    ->withRules([%s::class])' . "\n"
            . '    ->withoutParallel();' . "\n",
            var_export($paths, true),
            $ruleClass,
        );

        return $this->writeTempConfig($content);
    }

    /**
     * Generate a config with multiple selected rules.
     */
    public function generateMultiRuleConfig(
        array $ruleClasses,
        array $paths,
        string $projectPath,
        array $options = [],
    ): string { /* ... */ }

    private function writeTempConfig(string $content): string
    {
        $path = sys_get_temp_dir() . '/rector_ui_' . uniqid() . '.php';
        file_put_contents($path, $content);
        return $path;
    }
}
```

### 2.7 GitService: Atomic Commit Engine

```php
class GitService
{
    public function __construct(private string $projectPath) {}

    public function isCleanWorkingTree(): bool { /* git status --porcelain */ }
    public function getChangedFiles(): array { /* git diff --name-only */ }
    public function stageFiles(array $files): void { /* git add */ }
    public function commit(string $message): string { /* git commit, returns SHA */ }
    public function getCommitLog(int $limit = 50): array { /* git log --oneline */ }
    public function rollback(string $commitHash): void { /* git revert */ }
    public function getFileContentAtCommit(string $file, string $hash): string { /* git show */ }
}
```

---

## 3. API Design

All API endpoints are prefixed with `/api/`. Responses use JSON. The server uses the PHP built-in development server with fast-route for routing.

### 3.1 Project Management

#### `GET /api/project`
Returns information about the current project.

**Response:**
```json
{
    "path": "/home/user/my-project",
    "name": "my-project",
    "php_version": "8.2",
    "composer_json": { "require": { ... } },
    "has_rector_config": true,
    "rector_config_path": "/home/user/my-project/rector.php",
    "is_git_repo": true,
    "git_branch": "main",
    "git_clean": true
}
```

#### `POST /api/project/validate`
Validates that a project path is suitable for rector-ui.

**Request:**
```json
{ "path": "/home/user/my-project" }
```

**Response:**
```json
{
    "valid": true,
    "issues": [],
    "warnings": ["Rector is not installed. Run: composer require rector/rector --dev"]
}
```

### 3.2 Rule Discovery

#### `GET /api/rules`
List all available rules. Sources: Rector's `list-rules --output-format json`, plus cached rule metadata.

**Query Parameters:**
- `category` — Filter by category (php80, code-quality, dead-code, etc.)
- `search` — Search by rule name or class
- `configured` — `true` to show only rules in current config

**Response:**
```json
{
    "rules": [
        {
            "class": "Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector",
            "short_name": "ClassPropertyAssignToConstructorPromotionRector",
            "category": "php80",
            "set": "PHP 8.0",
            "configured": true,
            "description": "Change property assign to constructor promotion"
        }
    ],
    "categories": [
        { "id": "php80", "name": "PHP 8.0", "count": 15 },
        { "id": "code-quality", "name": "Code Quality", "count": 42 }
    ],
    "total": 187
}
```

#### `GET /api/rules/{class}`
Get detailed information about a single rule.

**Response:**
```json
{
    "class": "Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector",
    "short_name": "ClassPropertyAssignToConstructorPromotionRector",
    "category": "php80",
    "description": "Change property assign to constructor promotion",
    "node_types": ["Stmt_Class"],
    "configured": true,
    "risky": false,
    "sets": ["PHP 8.0"]
}
```

#### `GET /api/sets`
List available rule sets.

**Response:**
```json
{
    "sets": [
        { "name": "PHP_74", "label": "PHP 7.4", "rule_count": 12, "description": "Upgrade to PHP 7.4 features" },
        { "name": "PHP_80", "label": "PHP 8.0", "rule_count": 15, "description": "Upgrade to PHP 8.0 features" },
        { "name": "CODE_QUALITY", "label": "Code Quality", "rule_count": 42, "description": "Improve code quality" },
        { "name": "DEAD_CODE", "label": "Dead Code", "rule_count": 28, "description": "Remove unused code" }
    ]
}
```

### 3.3 Analysis (Scan)

#### `POST /api/analysis`
Start an analysis run (dry-run). This is a long-running operation.

**Request:**
```json
{
    "paths": ["src/", "tests/"],
    "rules": ["Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector"],
    "config_path": null,
    "mode": "dry-run"
}
```

If `rules` is empty, all configured rules run. If `config_path` is null, the project's default `rector.php` is used.

**Response (202 Accepted):**
```json
{
    "id": "run_abc123",
    "status": "running",
    "started_at": "2026-04-02T00:10:00Z"
}
```

#### `GET /api/analysis/{id}`
Poll for analysis results.

**Response (in progress):**
```json
{
    "id": "run_abc123",
    "status": "running",
    "progress": { "current": 45, "total": 120, "current_file": "src/Service/UserService.php" },
    "started_at": "2026-04-02T00:10:00Z"
}
```

**Response (completed):**
```json
{
    "id": "run_abc123",
    "status": "completed",
    "started_at": "2026-04-02T00:10:00Z",
    "completed_at": "2026-04-02T00:10:45Z",
    "summary": {
        "files_changed": 23,
        "total_additions": 156,
        "total_deletions": 89,
        "rules_applied": 12,
        "files_processed": 120
    },
    "changes": [
        {
            "file": "src/Service/UserService.php",
            "diff": "--- a/src/Service/UserService.php\n+++ b/src/Service/UserService.php\n@@ ...",
            "applied_rectors": [
                "Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector"
            ],
            "additions": 5,
            "deletions": 3
        }
    ]
}
```

#### `GET /api/analysis`
List past analysis runs for the current session.

**Response:**
```json
{
    "runs": [
        { "id": "run_abc123", "status": "completed", "started_at": "...", "files_changed": 23 },
        { "id": "run_def456", "status": "completed", "started_at": "...", "files_changed": 5 }
    ]
}
```

### 3.4 Diff Preview

#### `GET /api/diff/{runId}/file/{filePath}`
Get the full diff for a specific file from an analysis run.

**Response:**
```json
{
    "file": "src/Service/UserService.php",
    "original_content": "...",
    "new_content": "...",
    "diff": "--- a/src/Service/UserService.php\n+++ ...",
    "applied_rectors": ["Rector\\Php80\\..."],
    "additions": 5,
    "deletions": 3,
    "hunks": [
        { "old_start": 15, "old_count": 5, "new_start": 15, "new_count": 3, "content": "..." }
    ]
}
```

#### `GET /api/diff/{runId}/rule/{ruleClass}`
Get all diffs caused by a specific rule.

**Response:**
```json
{
    "rule": "Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector",
    "files_changed": 8,
    "changes": [ { "file": "...", "diff": "...", "additions": 3, "deletions": 1 } ]
}
```

### 3.5 Apply / Commit

#### `POST /api/apply`
Apply selected changes from an analysis run to the filesystem.

**Request:**
```json
{
    "run_id": "run_abc123",
    "mode": "selective",
    "files": ["src/Service/UserService.php", "src/Model/User.php"],
    "rules": null
}
```

`mode` can be:
- `"all"` — Apply all changes from the run
- `"selective"` — Apply only specified files
- `"by-rule"` — Apply only changes from specified rules

**Response:**
```json
{
    "status": "applied",
    "files_modified": 2,
    "git_dirty": true
}
```

#### `POST /api/commit`
Create atomic git commits.

**Request:**
```json
{
    "run_id": "run_abc123",
    "mode": "per-rule",
    "commit_message_template": "refactor: apply {rule_short_name}",
    "rules": [
        "Rector\\Php80\\Rector\\Class_\\ClassPropertyAssignToConstructorPromotionRector",
        "Rector\\DeadCode\\Rector\\ClassMethod\\RemoveUnusedPrivateMethodRector"
    ]
}
```

`mode` can be:
- `"per-rule"` — One commit per rule (atomic commits)
- `"per-file"` — One commit per file
- `"single"` — One commit for all changes

**Response:**
```json
{
    "commits": [
        {
            "hash": "a1b2c3d",
            "message": "refactor: apply ClassPropertyAssignToConstructorPromotionRector",
            "files": 5,
            "additions": 23,
            "deletions": 12
        },
        {
            "hash": "e4f5g6h",
            "message": "refactor: apply RemoveUnusedPrivateMethodRector",
            "files": 3,
            "additions": 0,
            "deletions": 8
        }
    ],
    "total_commits": 2
}
```

#### `POST /api/rollback`
Rollback the last N commits.

**Request:**
```json
{ "commits": 1 }
```

**Response:**
```json
{
    "status": "rolled_back",
    "reverted_commits": ["a1b2c3d"]
}
```

### 3.6 History

#### `GET /api/history`
Get git history of rector-ui commits.

**Query Parameters:**
- `limit` — Number of commits (default: 50)
- `offset` — Pagination offset
- `rule` — Filter by rule

**Response:**
```json
{
    "commits": [
        {
            "hash": "a1b2c3d",
            "message": "refactor: apply ClassPropertyAssignToConstructorPromotionRector",
            "date": "2026-04-02T00:15:00Z",
            "author": "rector-ui",
            "files_changed": 5,
            "additions": 23,
            "deletions": 12,
            "tags": ["php80", "constructor-promotion"]
        }
    ],
    "total": 42
}
```

### 3.7 Server

#### `GET /api/status`
Health check and server info.

**Response:**
```json
{
    "status": "ok",
    "version": "0.1.0",
    "php_version": "8.2",
    "rector_version": "1.2.0",
    "project": { "path": "...", "name": "..." },
    "analysis_running": false
}
```

---

## 4. Frontend Architecture

### 4.1 Technology Stack

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Build | Vite 5 | Fast HMR, ESBuild bundling |
| UI Framework | React 18 | Component-based UI |
| Language | TypeScript 5 | Type safety |
| Styling | Tailwind CSS 3 | Utility-first CSS |
| Components | shadcn/ui | Pre-built accessible components |
| State | Zustand | Lightweight state management |
| Data Fetching | TanStack Query | Server state, caching, polling |
| Routing | React Router 6 | Client-side routing |
| Diff Viewer | react-diff-viewer-continued | Split/unified diff display |
| Icons | Lucide React | Icon library |
| Syntax Highlighting | Prism.js / Shiki | PHP code highlighting |

### 4.2 Page Structure

```
/                           → Dashboard (project overview, recent activity)
/project                    → Project Setup (path, validation, config)
/rules                      → Rule Explorer (browse, search, filter rules)
/rules/{class}              → Rule Detail (description, affected node types)
/analysis                   → Analysis view (configure & run analysis)
/analysis/{runId}           → Analysis Results (file tree + diff viewer)
/analysis/{runId}/diff/{file} → Full Diff View (split/unified for one file)
/commits                    → Commit Queue (review & create commits)
/history                    → History (git log of rector-ui commits)
```

### 4.3 Key Views

#### Dashboard
- Project info card (name, PHP version, git status)
- Quick actions: "Run Analysis", "View Rules", "Recent Commits"
- Summary stats: rules available, recent analysis results
- Recent activity feed (last 5 analysis runs)

#### Project Setup
- Project path input with validation
- Rector installation check
- Config file detection (`rector.php`)
- Target PHP version display
- Source paths configuration

#### Rule Explorer
- Left sidebar: category tree (PHP versions, code quality, dead code, etc.)
- Main area: searchable/filterable rule list
- Each rule shows: name, category, configured status, description
- Checkbox to select/deselect rules for analysis
- Preset buttons: "Select All", "Select PHP 8.0", "Select Code Quality"
- Click rule for detail panel (description, node types, risk level)

#### Diff Viewer
- Split view (side-by-side) and unified view toggle
- PHP syntax highlighting on both sides
- Line-level change indicators (word-level diff)
- "Applied rules" badges showing which rules caused each change
- File navigation (previous/next changed file)
- Accept/reject per-file buttons
- Summary bar: additions/deletions count

#### Commit Queue
- List of pending changes grouped by rule
- Per-rule checkboxes for selective committing
- Commit message template preview
- "Create Atomic Commits" button (one per rule)
- "Create Single Commit" button (all in one)
- Confirmation dialog before committing

#### History
- Git log of all rector-ui commits
- Filter by rule, date range, file
- Click commit to see diff
- Rollback button (revert last N commits)

### 4.4 State Management

```typescript
// Zustand stores
interface ProjectStore {
    project: Project | null;
    loading: boolean;
    error: string | null;
    loadProject: () => Promise<void>;
}

interface RuleStore {
    rules: Rule[];
    selectedRules: string[];
    categories: RuleCategory[];
    loading: boolean;
    loadRules: () => Promise<void>;
    toggleRule: (ruleClass: string) => void;
    selectCategory: (categoryId: string) => void;
}

interface AnalysisStore {
    currentRun: AnalysisRun | null;
    pastRuns: AnalysisRun[];
    isRunning: boolean;
    startAnalysis: (config: AnalysisConfig) => Promise<void>;
    pollResults: (runId: string) => void;
}

interface CommitStore {
    pendingCommits: PendingCommit[];
    history: Commit[];
    createAtomicCommits: (runId: string) => Promise<void>;
    rollback: (count: number) => Promise<void>;
}
```

---

## 5. Data Flow: A Refactoring Session

This is the core workflow — the sequence of operations when a developer uses rector-ui.

### Step 1: Project Detection & Setup

```
User opens http://localhost:8199
    → Frontend calls GET /api/project
    → Backend detects project from CWD or rector-ui.json
    → Returns project info (path, PHP version, rector config status)
    → Frontend shows Dashboard
```

### Step 2: Rule Discovery & Selection

```
User navigates to /rules
    → Frontend calls GET /api/rules
    → Backend runs: rector list-rules --output-format json
    → Parses JSON into structured Rule objects
    → Frontend renders rule list with categories
    → User browses, searches, and selects rules
    → Selected rules stored in Zustand (client-side)
```

### Step 3: Dry-Run Analysis

```
User clicks "Run Analysis"
    → Frontend calls POST /api/analysis
    → Backend generates temporary rector.php with selected rules
    → Backend runs: rector process src/ --config /tmp/rector_ui_xxx.php --dry-run --output-format json --no-parallel
    → Backend parses JSON output into FileDiff objects
    → Backend stores analysis result in session storage
    → Frontend polls GET /api/analysis/{id} for progress
    → Results displayed: file tree with change indicators
```

### Step 4: Diff Preview

```
User clicks on a changed file
    → Frontend calls GET /api/diff/{runId}/file/{filePath}
    → Backend returns diff + original/new content
    → Frontend renders split-view diff with syntax highlighting
    → User reviews changes, sees which rules applied per-hunk
    → User can accept/reject individual files
```

### Step 5: Selective Application

```
User selects files/rules to apply
    → Frontend calls POST /api/apply with selected items
    → Backend generates config for only selected rules
    → Backend runs: rector process src/ --config /tmp/rector_ui_xxx.php --no-parallel (no --dry-run)
    → Files are modified on disk
    → Backend confirms applied changes
```

### Step 6: Atomic Commits

```
User clicks "Create Atomic Commits"
    → Frontend calls POST /api/commit with mode "per-rule"
    → Backend iterates over applied rules:
        For each rule:
            1. Revert all changes: git checkout .
            2. Apply only this rule: rector process ... (no --dry-run)
            3. Stage files: git add <affected files>
            4. Commit: git commit -m "refactor: apply {rule_short_name}"
    → Returns list of commit hashes
    → Frontend shows commit summary
```

### Step 7: Review & Rollback

```
User reviews history at /history
    → Frontend calls GET /api/history
    → Shows git log with rule metadata
    → User can click rollback to revert specific commits
    → Backend runs: git revert <hash>
```

### Sequence Diagram

```
Browser          Server           Rector CLI        Git
  │                │                 │               │
  │──GET /rules───→│                 │               │
  │                │──list-rules──→  │               │
  │                │←──JSON─────────  │               │
  │←──rules JSON──│                 │               │
  │                │                 │               │
  │──POST /analysis→│                │               │
  │                │──process──────→ │               │
  │                │   (dry-run)     │               │
  │                │←──diffs JSON─── │               │
  │←──202 + run id│                 │               │
  │                │                 │               │
  │──GET /analysis/{id}─→│          │               │
  │←──results──────│                 │               │
  │                │                 │               │
  │──POST /commit──→│                 │               │
  │                │──process──→     │               │
  │                │  (apply rule 1) │               │
  │                │─────────────────────────git add→│
  │                │─────────────────────────git cm─→│
  │                │──process──→     │               │
  │                │  (apply rule 2) │               │
  │                │─────────────────────────git add→│
  │                │─────────────────────────git cm─→│
  │←──commits─────│                 │               │
```

---

## 6. Configuration Format

### 6.1 rector-ui.json

This file lives in the project root (next to `composer.json`) and configures rector-ui behavior:

```json
{
    "$schema": "https://raw.githubusercontent.com/thewelshrich/rector-ui/main/schema/rector-ui.schema.json",

    "project": {
        "name": "my-project",
        "paths": ["src", "tests"]
    },

    "server": {
        "host": "localhost",
        "port": 8199,
        "open_browser": true
    },

    "rector": {
        "binary": null,
        "config": null,
        "php_version": "8.2",
        "timeout": 600
    },

    "analysis": {
        "default_mode": "dry-run",
        "clear_cache_before_run": true,
        "no_parallel": true
    },

    "commits": {
        "default_mode": "per-rule",
        "message_template": "refactor: apply {rule_short_name}",
        "author_name": "rector-ui",
        "author_email": "rector-ui@local"
    },

    "ui": {
        "theme": "dark",
        "diff_view": "split"
    }
}
```

### 6.2 Configuration Hierarchy

1. `rector-ui.json` in project root (highest priority)
2. `~/.rector-ui/config.json` (user-global defaults)
3. Built-in defaults

### 6.3 CLI Flags Override

CLI flags override config file values:

```bash
vendor/bin/rector-ui serve --port=3000 --no-open
```

---

## 7. Installation Flow

### 7.1 Standard Installation (In-Project)

```bash
# 1. Add rector-ui as a dev dependency
composer require thewelshrich/rector-ui --dev

# 2. Rector must also be present
composer require rector/rector --dev

# 3. Start the server
vendor/bin/rector-ui serve

# 4. Browser opens to http://localhost:8199
```

### 7.2 Standalone Installation (For Legacy Projects)

For projects that can't install modern PHP dependencies directly:

```bash
# 1. Install in a separate directory
mkdir rector-tools && cd rector-tools
composer require thewelshrich/rector-ui rector/rector

# 2. Point at your project
vendor/bin/rector-ui serve /path/to/legacy-project

# 3. Browser opens to http://localhost:8199
```

### 7.3 First-Run Experience

1. Server starts and opens browser
2. Dashboard detects project, shows validation status
3. If Rector is not installed: show install instructions
4. If no `rector.php` found: offer to create one
5. Quick start guide / onboarding overlay

---

## 8. Phased Delivery Plan

### Phase 0: Project Setup (Week 1)

Foundation: composer.json, PSR-4 structure, CI, frontend scaffolding.

- Initialize composer package with PSR-4 autoloading
- Set up PHPUnit with CI pipeline
- Create CLI entry point (`bin/rector-ui`)
- Scaffold Vite + React + TypeScript + shadcn/ui frontend
- Set up PHP coding standards (PHP-CS-Fixer)
- Update .gitignore for the full project

### Phase 1: Core Backend (Weeks 2-3)

The PHP server that wraps Rector CLI and serves API responses.

- PHP built-in dev server with custom router
- nikic/fast-route integration
- RectorCliWrapper service (symfony/process)
- Project detection and validation
- Health/status endpoint
- Static file serving for frontend assets
- ConfigGenerator for dynamic rector.php files

### Phase 2: Analysis Engine (Weeks 3-4)

The brain: rule discovery, dry-run analysis, diff parsing.

- Rule discovery via `list-rules --output-format json`
- Dry-run analysis orchestration
- JSON output parsing into structured FileDiff objects
- DiffParser for unified diff → hunks/line-level data
- Analysis session management (run tracking)
- Per-rule analysis (single rule execution)

### Phase 3: Frontend MVP (Weeks 4-6)

The face: React UI with all core views.

- Dashboard with project overview
- Project Setup page
- Rule Explorer with search, filter, categories
- Diff Viewer (split + unified, PHP syntax highlighting)
- File tree with change indicators
- Analysis workflow (select rules → run → preview)
- Accept/reject per-file changes

### Phase 4: Commit Engine (Weeks 6-7)

The killer feature: atomic per-rule git commits.

- GitService for git operations
- Per-rule commit generation
- Selective file/rule application
- Commit message templating
- Commit Queue UI
- Rollback support

### Phase 5: Polish (Weeks 8-10)

Production quality.

- History view with git log
- Watch mode / file watcher
- CI workflow generator (GitHub Actions)
- IDE integration (open in VS Code / PhpStorm)
- Dark/light theme
- Keyboard shortcuts
- Error handling & recovery UX
- Performance optimization (incremental analysis)
- Documentation

---

## 9. Technical Constraints & Decisions

### 9.1 Key Constraints

| Constraint | Impact |
|-----------|--------|
| Rector has NO public PHP API | Must wrap via CLI (symfony/process) |
| Rector requires PHP 8.1+ runtime | rector-ui server needs PHP 8.1+ |
| Target projects can be PHP 7.3+ | Use `withPhpVersion()` in config |
| `--only` requires rule in config | Must generate dynamic configs |
| `--no-parallel` needed for predictable output | No parallel processing in rector-ui |
| JSON output has diffs, not full content | Must read files separately for preview |
| Rector bootstrapping takes seconds | Show loading states, cache where possible |

### 9.2 Architectural Decisions

| Decision | Rationale |
|----------|-----------|
| PHP built-in server, not Nginx/Apache | Zero external dependencies for local dev tool |
| nikic/fast-route, not Symfony/Laravel | Lightweight, no framework overhead |
| No WebSocket in MVP | Polling is simpler; add WebSocket in Phase 5 |
| Built React app committed to `public/` | No build step required at install time |
| symfony/process for CLI wrapping | Stable, well-tested, handles timeouts/errors |
| Dynamic config files (not --only flag) | More reliable, works with any rule |
| Per-rule application via revert+apply | Clean git history without complex patching |
| Zustand over Redux | Simpler, less boilerplate for a focused app |

### 9.3 Security Considerations

- Server binds to localhost only (not 0.0.0.0)
- No authentication needed for local tool
- Input validation on all API endpoints
- File path sanitization (prevent directory traversal)
- Process timeout to prevent hanging
- No external network requests

### 9.4 Performance Considerations

- Rector caching: use `--clear-cache` between different configs
- Analysis polling: 2-second interval, exponential backoff
- Frontend: virtual scrolling for large file lists
- Lazy loading: load diff content only when file is opened
- Session storage: persist analysis results across page reloads
