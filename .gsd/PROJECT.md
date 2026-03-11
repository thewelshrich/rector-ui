# Project

## What This Is

Rector UI is a local-first web interface for Rector that makes PHP upgrades and large-scale refactors easier to review than raw CLI diff output. It is currently a Composer-installable PHP package with a React frontend that can run Rector dry-runs against the current project, detect project context, and present analysis results in a browser-based review workspace.

## Core Value

A single developer can understand and review large Rector-driven code changes in the browser faster and more safely than scanning one massive terminal diff.

## Current State

A working local application exists with a ReactPHP backend and React frontend. The backend exposes `/api/health`, `/api/meta`, `/api/project`, and `/api/analysis`; the frontend fetches project and health information, runs a Rector dry-run, and builds a file tree plus diff-block review model from Rector JSON output. The product does not yet own patch application, commit batching, config editing, or continuity across runs.

## Architecture / Key Patterns

The backend is a local PHP server with request routing through `src/HttpApplication.php` and JSON endpoint handling in `src/ApiController.php`. Rector execution is orchestrated through `src/RectorAnalysisService.php`, which shells out to `vendor/bin/rector process --dry-run --output-format=json` in the detected current project. The frontend is a Vite + React app rooted at `frontend/src/main.jsx`, with review state modeled by `frontend/src/lib/rector-analysis.js`, which parses Rector JSON into file, tree, rule, and diff-block structures for a sidebar/workspace UI.

## Capability Contract

See `.gsd/REQUIREMENTS.md` for the explicit capability contract, requirement status, and coverage mapping.

## Milestone Sequence

- [ ] M001: Credible local review workflow — Make Rector analysis understandable and navigable in a browser for the current local project.
- [ ] M002: Selective apply engine — Let the product apply only the user’s chosen files and hunks back to disk.
- [ ] M003: Change batching and commit preparation — Organize accepted changes into logical review groups and commit-ready upgrade batches.
- [ ] M004: Config and upgrade control surface — Inspect and manage Rector configuration and rerun upgrades from inside the app.
- [ ] M005: Continuity and operational polish — Preserve local upgrade progress, history, and diagnostics across longer-running workflows.
