# Paid Offering & Competitor Analysis for rector-ui

> Research conducted for the rector-ui project — an open-source visual IDE for Rector.
> Goal: Fully understand what the Rector team sells commercially and what features
> we need to replicate to provide a free, open-source alternative.

---

## Table of Contents

1. [Rector Paid Offering — What They Sell](#1-rector-paid-offering--what-they-sell)
2. [PHPStan Pro Server Model](#2-phpstan-pro-server-model)
3. [Competitor Analysis](#3-competitor-analysis)
4. [Key Features to Replicate (Prioritized)](#4-key-features-to-replicate-prioritized)
5. [UX Patterns for Code Review & Refactoring UIs](#5-ux-patterns-for-code-review--refactoring-uis)
6. [Architecture Recommendations](#6-architecture-recommendations)
7. [Summary & Strategic Recommendations](#7-summary--strategic-recommendations)

---

## 1. Rector Paid Offering — What They Sell

### 1.1 Two Distinct Revenue Streams

The Rector team (company: Edukai, s.r.o., Prague, Czech Republic, led by Tomas Votruba as CEO and Abdul Malik Ikhsan as CTO) has **two completely separate commercial offerings**:

#### A. Codebase Renovation / Hire-Team Service (Consulting)

This is a **manual, hands-on upgrade service** where the Rector team directly works on client codebases. It is NOT a software product — it's professional services.

**Phase 1: Intro Analysis**
- One-time charge: **$6,000–$8,000**
- Client shares Git repository under NDA
- Rector team does a 3-week deep-dive analysis of the codebase
- Deliverable: PDF report with timeline and step-by-step upgrade plan
- Identifies weak spots, upgrade paths, and risk areas
- Uses automated tools to identify upgrade spots

**Phase 2: Hands-on Upgrade**
- Hourly billing at **$140–$160/hour**
- 20–80 hours/week of direct developer help (depending on pace)
- Dedicated developer with Rector experience assigned to the project
- Typically runs **6–12 months**
- Key promise: "work is always in a finished state — no weeks-ongoing in-the-air changes"
- Small, safe, gradual steps
- Works in parallel with client's feature development team
- Monthly progress meetings
- Knowledge transfer goal: make the client's team self-sufficient by the end

**What they promise over DIY:**
- Expert guidance on complex legacy code (PHP 5.3+)
- Prioritization and sequencing of upgrades (what to tackle first)
- CI/CD setup and optimization alongside upgrades
- Framework migration expertise (custom/in-house → Symfony/Laravel)
- Risk mitigation through small, atomic changes

#### B. Free CLI Tool (Rector itself)

The open-source Rector CLI provides:
- PHP version upgrades (5.3 → 8.5)
- Framework migrations (Symfony, Laravel, Doctrine, PHPUnit, etc.)
- Code quality improvements
- `--dry-run` for previewing changes
- Rule sets and individual rule execution
- JSON/text output formats
- `setup-ci` command for GitHub Actions integration

**Key gap the free CLI doesn't fill:**
- No visual preview of changes (diff viewer)
- No per-rule commit generation
- No interactive rule selection/exploration
- No project-wide overview of impact
- No guided upgrade paths
- No automated sequencing of complex upgrades
- No file tree navigation for changes

### 1.2 What the Free CLI Provides (for context)

- `rector process` — run all configured rules
- `rector process --dry-run` — preview without applying
- `rector process <file>` — single file
- `--config` flag for configuration
- JSON output format for machine consumption
- `setup-ci` command generates GitHub Actions workflow
- `find-rule` web interface for searching rules
- AST visualization tool (web-based)
- Interactive demo on getrector.com

### 1.3 The Codebase Renovation Transformation Targets

From their marketing page (getrector.com/codebase-renovation), they promise to transform projects across 5 dimensions:

| Area | Before | After |
|------|--------|-------|
| **PHP Version** | Ambiguous, multiple places, upper bracket | Single exact PHP version (latest stable) |
| **Static Analysis** | Multiple overlapping tools, 1000+ ignored errors | Single PHPStan with zero ignores |
| **Autoloading** | classmap/PSR-0/files autoloading conflicts | Clean PSR-4 only |
| **Coding Standard** | Mix of IDE setup, PHP-CS-Fixer, pre-commit hooks, PSR-2 | Single ECS with prepared sets beyond PSR-12 |
| **CI Feedback** | Scattered knowledge, manual testing | Automated CI with up-to-date verification steps |

### 1.4 Atomic Commit Generation

The Rector team emphasizes **small, atomic commits** as a core value. From the GitHub issue tracker (#8898), users have requested:
- One commit per Rector rule
- Listing all rules that would apply before running
- Running a single rule at a time (currently requires modifying `rector.php`)
- Creating commits per-rule with external scripts

Currently, Rector CLI does NOT support:
- `--single-rule` flag (was rejected as out of scope in issue #8505)
- A command to list full class names of rules that would apply
- Automatic git commit generation

This is a **key opportunity for rector-ui**: we can orchestrate running rules one at a time and creating atomic git commits, which the free CLI explicitly doesn't support.

### 1.5 CI Integration (Automated PR Workflow)

Rector promotes an "active code review" philosophy:
- Tools should **contribute fixes**, not just report errors
- GitHub Actions workflow that runs Rector on PRs, commits changes, and pushes back
- `setup-ci` command generates the workflow YAML
- Combined with ECS for post-Rector coding standard cleanup
- 300+ open-source projects use this pattern

---

## 2. PHPStan Pro Server Model

### 2.1 Architecture Overview

PHPStan Pro is a **paid add-on** for the free PHPStan CLI static analyzer, created by Ondrej Mirtes. It uses a local HTTP server + WebSocket architecture.

**How it works:**
1. User runs `vendor/bin/phpstan analyse --pro` (or `--watch`)
2. PHPStan downloads the Pro PHAR file
3. A **local HTTP server** is started on a random port (configurable via `PHPSTAN_PRO_WEB_PORT` env var)
4. A browser tab opens automatically pointing to `localhost:<port>`
5. The UI communicates with the server via **HTTP + WebSockets**
6. The server runs PHPStan analysis in the background and pushes results to the UI in real-time

**Key technical details:**
- Runs **entirely locally** — no code is sent to external servers
- Uses WebSockets for real-time updates (file watcher → re-analysis → push to UI)
- Default random port, configurable with `PHPSTAN_PRO_WEB_PORT`
- Can be proxied behind Nginx for remote access (requires WebSocket support: `proxy_http_version 1.1`)
- SSL termination is a known issue (ws:// vs wss://)
- Memory management can be problematic over long sessions (recommend using Supervisor for auto-restart)

### 2.2 Key Features of the Pro UI

1. **Interactive Web UI for Errors**
   - Browse all reported errors with surrounding code context
   - Click to open file at exact line in IDE
   - View suppressed/ignored errors in their original context
   - Beautiful, organized interface vs scrolling terminal output

2. **Continuous Analysis (Watch Mode)**
   - Monitors filesystem for changes
   - Re-runs analysis automatically on file save
   - Real-time "to-do list" while refactoring
   - Loading indicator when background analysis is active

3. **Migration Wizards**
   - Automated fixes for typehints
   - "1-Click Fix" button for individual errors
   - Updates PHPDocs automatically
   - Improves codebase metadata so PHPStan finds more bugs

4. **IDE Integration**
   - Click icon to open file at line in VS Code / PhpStorm
   - Protocol: likely uses custom URL schemes or LSP-like file/line references

### 2.3 Pricing

| Plan | Monthly | Annual (2 months free) |
|------|---------|----------------------|
| Individual | €7/month | €70/year |
| Team (up to 25 users) | €70/month | €700/year |
| 30-day free trial for all plans |
| No limit on number of projects |

### 2.4 Known Issues (from GitHub Discussions)

- "1-Click Fix" has ~20 second latency per fix
- Concurrent fixes can cause failures
- High CPU usage from spawned workers (100% CPU)
- Memory exhaustion (4GB RAM exhausted on large codebases)
- UI notification bugs (non-dismissable error popups)
- Server crashes during normal navigation

**Takeaway for rector-ui:** PHPStan Pro's architecture is a good reference model. We should use the same local HTTP server + WebSocket pattern but avoid their performance issues by:
- Running analysis asynchronously with proper queueing
- Using incremental analysis (only re-analyze changed files)
- Implementing proper memory management
- Building a more responsive UI

### 2.5 Communication Protocol (Inferred)

Based on the Nginx proxy configuration and behavior:
- **HTTP**: For initial page load, error listing, and configuration
- **WebSocket**: For real-time file watching, analysis progress, and live result updates
- **Custom URL schemes**: For IDE integration (e.g., `phpstorm://open?file=...&line=...`)
- No REST API documentation is publicly available (closed-source Pro PHAR)

---

## 3. Competitor Analysis

### 3.1 JetBrains PhpStorm

**Refactoring capabilities:**
- Rename, Extract Method, Inline, Change Signature, Pull Members Up/Down
- Safe delete with usage search
- Type migration (change type across codebase)
- Code cleanup with configurable inspections
- Integration with PHPStan, PHP CS Fixer, Psalm via external tools
- Built-in diff viewer with merge capabilities
- Batch refactoring across projects
- PHP 8 attribute migrations

**Strengths:**
- Mature, well-tested refactoring engine
- Deep PHP language understanding
- Excellent IDE integration (obviously)
- Visual refactoring previews

**Weaknesses (vs Rector):**
- No bulk automated refactoring (rule-based)
- No PHP version upgrade automation
- No framework migration tools
- Paid software ($99/year for individuals)
- No CI/CD integration for automated fixes
- Cannot process entire codebase with 100+ rules at once

### 3.2 PHP CS Fixer

**What it does:**
- Automated coding standard fixing (PSR-1, PSR-2, PSR-12, Symfony, etc.)
- CLI tool with `--dry-run` support
- Configurable rules in `.php-cs-fixer.dist.php`
- Can be integrated in CI/CD

**GUI tools:**
- No official GUI
- Some community browser-based tools exist but are basic
- Often paired with pre-commit hooks

**Weaknesses:**
- Only handles coding style, not structural refactoring
- No PHP version upgrades
- No framework migrations
- No AST-based transformations

### 3.3 PHP Refactoring Browser (refactor.phar)

**What it does:**
- CLI tool that outputs diffs for refactoring operations
- Supports: Extract Method, Rename Variable, Convert Local to Instance Variable, Fix Class Names, Optimize Use Statements
- Pipes output to `patch -p1`

**Strengths:**
- Generates diffs (safe, reviewable)
- Focused on safe refactoring operations

**Weaknesses:**
- Alpha state, limited refactorings
- No GUI
- No bulk processing
- No CI integration
- Limited compared to Rector

### 3.4 ast-grep

**What it does:**
- Tree-sitter based structural search and replace
- Supports multiple languages (not PHP-specific)
- Interactive mode (`sg scan -i`)
- YAML-based rule definitions
- CLI tool with AST pattern matching

**Strengths:**
- Language-agnostic (Rust-based, very fast)
- Pattern matching on AST (more powerful than regex)
- Interactive scanning mode

**Weaknesses:**
- No PHP-specific rules (tree-sitter-php exists but rules need to be written)
- No GUI
- No CI workflow integration
- No migration/wizard capabilities
- Much smaller PHP ecosystem than Rector

### 3.5 Summary Comparison Matrix

| Feature | Rector CLI | PHPStan Pro | PhpStorm | PHP CS Fixer | ast-grep | **rector-ui (target)** |
|---------|-----------|-------------|----------|-------------|----------|----------------------|
| Visual diff preview | No | Yes | Yes | No | No | **Yes** |
| Web UI | No | Yes | No | No | No | **Yes** |
| Bulk refactoring | Yes | Limited | No | Style only | Yes | **Yes** |
| PHP version upgrades | Yes | No | No | No | No | **Yes** |
| Framework migrations | Yes | No | No | No | No | **Yes** |
| Per-rule atomic commits | No | N/A | No | No | No | **Yes** |
| File tree navigation | No | No | Yes | No | No | **Yes** |
| Rule exploration/search | Web only | N/A | N/A | N/A | N/A | **Yes** |
| CI integration | YAML only | N/A | N/A | Yes | No | **Yes** |
| IDE integration | No | Yes | Native | Plugin | No | **Yes** |
| Watch mode | No | Yes | Yes | No | No | **Yes** |
| Free & open source | Yes | No | No | Yes | Yes | **Yes** |

---

## 4. Key Features to Replicate (Prioritized)

Based on research, here is the prioritized feature list that would make rector-ui a full replacement for Rector's paid offering:

### P0 — Must Have (Core Value Proposition)

| # | Feature | What It Replaces | Why Critical |
|---|---------|-----------------|--------------|
| 1 | **Visual diff viewer** | Manual `--dry-run` terminal output | Core UX — developers need to see what will change |
| 2 | **File tree with change indicators** | No equivalent in free CLI | Navigate changes across large codebases |
| 3 | **Rule selection & configuration UI** | Editing `rector.php` manually | Lowers barrier to entry, enables exploration |
| 4 | **Run analysis and preview changes** | `rector process --dry-run` | Core workflow — see before applying |
| 5 | **Apply/revert individual file changes** | All-or-nothing `rector process` | Safety — apply selectively |
| 6 | **Per-rule atomic git commits** | Not available in CLI (requested in #8898) | Key differentiator — clean git history |

### P1 — Should Have (Competitive Parity with PHPStan Pro)

| # | Feature | What It Replaces | Why Important |
|---|---------|-----------------|---------------|
| 7 | **Watch mode (file watcher)** | PHPStan Pro's core feature | Live feedback while coding |
| 8 | **Incremental analysis** | Full re-run each time | Performance — only analyze changed files |
| 9 | **Rule search/exploration** | getrector.com/find-rule (web) | Discover what Rector can do |
| 10 | **One-click apply for individual changes** | PHPStan Pro's "1-Click Fix" | Quick workflow for small fixes |
| 11 | **Error/issue summary dashboard** | Terminal output parsing | Overview of project health |
| 12 | **Undo/redo stack** | Not available | Safety net for experimentation |

### P2 — Nice to Have (Differentiation)

| # | Feature | What It Replaces | Why Valuable |
|---|---------|-----------------|--------------|
| 13 | **Guided upgrade wizard** | Codebase Renovation consulting ($6K+) | Automates what they charge $6K for |
| 14 | **Upgrade path visualization** | PDF report from Intro Analysis | See the full journey before starting |
| 15 | **Per-rule impact analysis** | Not available | Understand risk before applying |
| 16 | **Batch rule execution with progress** | Running rules sequentially in CLI | Visibility into long-running operations |
| 17 | **CI workflow generator** | `rector setup-ci` command | Enhanced version with UI |
| 18 | **IDE open integration** | PHPStan Pro's IDE click | Open files in VS Code/PhpStorm |
| 19 | **Custom rule testing UI** | Manual PHPUnit tests for rules | Lower barrier to writing custom rules |
| 20 | **AST visualization** | getrector.com/ast | Understand what rules do to code |

### P3 — Future / Stretch Goals

| # | Feature | Description |
|---|---------|-------------|
| 21 | **Multi-project support** | Manage multiple codebases from one UI |
| 22 | **Team collaboration** | Share rule configurations, review changes together |
| 23 | **Plugin/extension system** | Community-contributed rules and UI extensions |
| 24 | **Migration templates** | Pre-built upgrade paths (e.g., "CakePHP 2 → Laravel 11") |
| 25 | **ECS integration** | Combined Rector + Easy Coding Standard workflow |

---

## 5. UX Patterns for Code Review & Refactoring UIs

### 5.1 Diff Viewer Patterns

**Split view (side-by-side):**
- Gold standard for code review (GitHub, GitLab, Gerrit)
- Shows old code on left, new code on right
- Color-coded additions (green) and deletions (red)
- Line-by-line correspondence
- Best for: understanding what changed

**Unified view:**
- Shows changes inline with context
- Better for narrow screens
- Common in: VS Code, PhpStorm
- Best for: reading through changes sequentially

**Inline diff (word-level):**
- Highlights exact words changed within a line
- GitHub and GitLab support this
- Best for: spotting subtle changes

**Recommended components:**
- `react-diff-viewer-continued` (565K weekly npm downloads) — simple, beautiful, supports split/unified
- `git-diff-view` — GitHub-style, supports React/Vue/Solid/Svelte, virtual scrolling, syntax highlighting
- Monaco Editor's built-in diff editor — powerful but heavier

**Key UX principles:**
- Syntax highlighting is essential (PHP code)
- Line numbers on both sides
- Ability to expand collapsed unchanged context
- Minimap or scroll sync between sides
- Click to toggle between split and unified view

### 5.2 File Tree Navigation Patterns

**GitHub PR changed files pattern:**
- List of all changed files with summary indicators
- Filter by: all, added, modified, deleted
- Search/filter by filename
- Show change statistics (additions/deletions per file)
- Click to jump to file's diff
- Batch actions: "Approve all", "Apply all"

**VS Code Explorer pattern:**
- Hierarchical tree with folder expansion
- Change indicators (colored dots/badges)
- File type icons
- Context menu per file
- Collapse all / expand all

**Recommended approach:**
- Flat list view by default (like GitHub PR files)
- Group by directory as an option
- Show per-file change summary (+5 -3 lines)
- Color-coded status indicators
- Search/filter bar at top
- Checkbox per file for selective application
- "Apply all" / "Revert all" bulk actions

### 5.3 Refactoring Workflow Patterns

**The "Review → Select → Apply" pattern:**
1. Run analysis → show results
2. Review changes in diff viewer
3. Select/deselect individual changes or files
4. Apply selected changes
5. See confirmation summary

**The "Wizard" pattern (for guided upgrades):**
1. Analyze current codebase state
2. Present recommended upgrade path with options
3. Step through each phase
4. Preview changes at each step
5. Apply with confirmation
6. Track progress across sessions

**The "Dashboard" pattern (for ongoing maintenance):**
1. Overview: X issues found, Y auto-fixable
2. Categorized by: severity, rule, file, directory
3. Quick actions: fix all, fix selected, ignore
4. History: what was fixed, when, by which rule
5. Trends: code quality over time

### 5.4 Responsive Design Considerations

- Main workspace: file tree (sidebar) + diff viewer (main area)
- Mobile: stacked layout (tree → file → diff)
- Keyboard shortcuts essential (j/k navigation, enter to open, space to select)
- Dark mode (developers prefer it)
- Font: monospace, configurable size
- Tab-based navigation for multiple files

### 5.5 Status & Feedback Patterns

- Progress bar for long-running analysis
- Per-rule progress in batch operations
- Real-time file count / change count
- Toast notifications for actions (applied, reverted, error)
- Loading skeletons for content
- Error boundaries with recovery options

---

## 6. Architecture Recommendations

### 6.1 Server Architecture (following PHPStan Pro model)

```
┌──────────────────────────────────────────────┐
│  rector-ui (Electron or browser)             │
│                                              │
│  ┌─────────┐  ┌──────────┐  ┌────────────┐  │
│  │ React   │  │ Monaco   │  │ Diff       │  │
│  │ UI      │  │ Editor   │  │ Viewer     │  │
│  └────┬────┘  └──────────┘  └────────────┘  │
│       │                                      │
│  ┌────▼────────────────────────────────────┐  │
│  │ WebSocket Client                        │  │
│  │ (real-time updates, progress, results)  │  │
│  └────┬────────────────────────────────────┘  │
│       │ ws://localhost:PORT                   │
└───────┼──────────────────────────────────────┘
        │
┌───────▼──────────────────────────────────────┐
│  rector-ui-server (Node.js / PHP)            │
│                                              │
│  ┌──────────┐  ┌───────────┐  ┌───────────┐  │
│  │ HTTP     │  │ WebSocket │  │ Analysis  │  │
│  │ API      │  │ Server    │  │ Queue     │  │
│  └────┬─────┘  └─────┬─────┘  └─────┬─────┘  │
│       │              │              │         │
│  ┌────▼──────────────▼──────────────▼─────┐   │
│  │ Rector CLI Wrapper                     │   │
│  │ (spawns rector processes, parses       │   │
│  │  JSON output, manages git operations)  │   │
│  └────────────────────────────────────────┘   │
│                                              │
│  ┌────────────────────────────────────────┐   │
│  │ File Watcher (chokidar / fs.watch)     │   │
│  └────────────────────────────────────────┘   │
└──────────────────────────────────────────────┘
```

### 6.2 Communication Protocol

**HTTP REST API:**
- `GET /api/status` — server status, analysis state
- `POST /api/analyze` — trigger analysis run
- `GET /api/rules` — list available rules
- `GET /api/rules/search?q=...` — search rules
- `POST /api/apply` — apply selected changes
- `POST /api/revert` — revert changes
- `GET /api/files` — list project files with change status
- `GET /api/diff/:file` — get diff for specific file
- `POST /api/config` — update rector configuration
- `GET /api/history` — git history of rector changes

**WebSocket events:**
- `analysis:start` — analysis began
- `analysis:progress` — { current, total, file, rule }
- `analysis:complete` — full results
- `file:changed` — file system watcher event
- `error` — analysis error

### 6.3 Key Technical Decisions

**Why not Electron:**
- Browser-based is more accessible (no install)
- PHPStan Pro proved the local-server + browser model works
- Easier to develop and iterate
- Can be packaged as Electron later if needed

**Why Node.js server:**
- Better WebSocket support than PHP
- Can wrap PHP Rector CLI as child processes
- File watching with chokidar is mature
- npm ecosystem for diff/editor components

**Alternative: Pure PHP server:**
- Could use ReactPHP or Swoole for async
- Better PHP ecosystem integration
- But WebSocket support is less mature
- Would need to handle Rector process spawning differently

---

## 7. Summary & Strategic Recommendations

### 7.1 What We're Replacing

The Rector team sells **expertise and guidance**, not software. Their commercial offering is:
1. A consulting service ($6K–$8K intro + $140–$160/hr ongoing)
2. The free CLI tool (which does the actual work)

**rector-ui's opportunity:** Build the **software layer that makes their consulting unnecessary** by providing:
- Visual guidance that replaces the expert analysis
- Guided upgrade workflows that replace the manual sequencing
- Diff review tools that replace the careful manual verification
- Atomic commit generation that replaces the careful git management

### 7.2 PHPStan Pro as Architecture Blueprint

PHPStan Pro proves the model works:
- Local HTTP server + browser UI
- Real-time file watching
- WebSocket for live updates
- IDE integration via URL schemes
- Paid add-on to free CLI tool

**We should replicate this architecture but fix their problems:**
- Better performance (async, incremental)
- Better memory management
- More responsive UI
- More features (diff viewer, file tree, atomic commits)

### 7.3 Key Differentiators

What makes rector-ui unique vs all competitors:
1. **Only tool combining visual UI + bulk refactoring + PHP upgrades**
2. **Atomic per-rule commits** (requested but rejected by Rector core)
3. **Guided upgrade wizard** (replaces $6K consulting)
4. **Free and open source** (vs PHPStan Pro at €7/month, PhpStorm at $99/year)
5. **Rule exploration UI** (better than web-based find-rule)

### 7.4 Development Priority Recommendation

**Phase 1 — MVP (Weeks 1–4):**
- Local server with HTTP API
- Basic React UI with project loading
- Run Rector and display results
- Simple diff viewer (react-diff-viewer-continued)
- File list with change indicators
- Apply/revert all changes

**Phase 2 — Core Experience (Weeks 5–8):**
- Rule selection UI (search, filter, enable/disable)
- Per-file apply/revert
- Atomic per-rule git commits
- File tree navigation
- Monaco editor integration for code viewing

**Phase 3 — Power Features (Weeks 9–12):**
- Watch mode with file watcher
- Incremental analysis
- Guided upgrade wizard
- IDE integration (open in VS Code/PhpStorm)
- CI workflow generator

**Phase 4 — Polish (Weeks 13–16):**
- Custom rule testing UI
- AST visualization
- Dashboard with project health metrics
- Dark/light theme
- Keyboard shortcuts
- Performance optimization

---

## Sources

- getrector.com — official website
- getrector.com/hire-team — consulting service page
- getrector.com/codebase-renovation — transformation examples
- getrector.com/blog — official blog
- phpstan.org/blog/introducing-phpstan-pro — PHPStan Pro announcement
- github.com/phpstan/phpstan/discussions/7982 — PHPStan Pro DX report
- github.com/phpstan/phpstan/discussions/4543 — PHPStan Pro port/proxy config
- github.com/rectorphp/rector/issues/8898 — atomic commit feature request
- github.com/rectorphp/rector/issues/8505 — git integration rejected
- github.com/ShahinSorkh/refactor.phar — PHP Refactoring Browser
- github.com/Aeolun/react-diff-viewer-continued — diff viewer component
- github.com/mrwangjusttodo/git-diff-view — GitHub-style diff component
