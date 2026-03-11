# M001: Credible local review workflow — Context

**Gathered:** 2026-03-11
**Status:** Ready for planning

## Project Description

Rector UI is a local-first browser interface for reviewing Rector-driven PHP upgrades and refactors against the current project. The existing codebase already has a PHP backend, project detection, a Rector dry-run integration, and a React frontend that can parse Rector JSON into review-oriented file and diff-block models.

## Why This Milestone

This milestone establishes the product’s first credible value: replacing painful terminal diff review with a browser-based review workflow that a single PHP developer would actually choose to use locally. It must prove the product can run against the current project, present large Rector output clearly, and surface meaningful failure diagnostics when analysis does not succeed.

## User-Visible Outcome

### When this milestone is complete, the user can:

- Launch Rector UI against the current project and see project/runtime context in a browser.
- Run a Rector dry-run and review changed files and diff hunks in a sidebar/workspace flow that is materially better than one massive CLI diff.
- Understand when analysis failed or output is incomplete through structured diagnostics instead of vague errors.

### Entry point / environment

- Entry point: `vendor/bin/rector-ui` in local development, with optional Vite frontend during development.
- Environment: local browser + local PHP process against the current working project.
- Live dependencies involved: local Rector binary/config, local filesystem, React frontend, ReactPHP backend.

## Completion Class

- Contract complete means: health/project/analysis flows return structured data that the frontend can render into project context, file tree, and diff-block review models.
- Integration complete means: a real local run against a project with Rector configured can execute analysis and render reviewable output end-to-end in the browser.
- Operational complete means: analysis unavailability or failure states are surfaced clearly enough for a developer to diagnose what went wrong locally.

## Final Integrated Acceptance

To call this milestone complete, we must prove:

- A developer can launch the app for the current project, run Rector analysis, and browse changed files plus hunks in the UI.
- The UI remains useful when Rector returns many file diffs by providing navigable file-tree/workspace review instead of raw output dumping.
- Real failure states such as unavailable Rector/config or failing analysis produce structured, inspectable diagnostics in the app rather than only silent or generic errors.

## Risks and Unknowns

- Rector JSON shape may vary across projects and versions — the review model must be robust enough to handle incomplete or variant fields.
- Large change sets may overwhelm the current UI — M001 has to prove the sidebar/tree workspace remains usable when many files and hunks exist.
- Current backend error handling is thin — richer diagnostics may require extending result models and API contracts without breaking the simple local flow.

## Existing Codebase / Prior Art

- `src/ApiController.php` — existing JSON API surface for health, project, and analysis endpoints.
- `src/RectorAnalysisService.php` — existing Rector dry-run orchestration and result construction.
- `frontend/src/main.jsx` — app bootstrap and fetch lifecycle for project + analysis state.
- `frontend/src/lib/rector-analysis.js` — parses Rector output into files, trees, rules, and diff blocks.
- `frontend/src/components/analysis-shell.jsx` — current review workspace shell and likely primary UI surface for M001.

> See `.gsd/DECISIONS.md` for all architectural and pattern decisions — it is an append-only register; read it during planning, append to it during execution.

## Relevant Requirements

- R001 — establishes the core browser-based review capability for the current project.
- R006 — introduces structured failure visibility rather than minimal banner errors.
- R007 — keeps the milestone local-first and scoped to the current detected project.

## Scope

### In Scope

- Current-project local execution and review flow.
- Sidebar/tree-oriented diff review UX.
- Reviewable file-level and hunk-level presentation of Rector output.
- Structured diagnostics for unavailable or failed analysis states.

### Out of Scope / Non-Goals

- Writing selected changes back to disk.
- Commit batching or multi-step upgrade campaign management.
- In-app Rector config editing.
- Session persistence or history across runs.
- Collaboration or remote/shared workflows.

## Technical Constraints

- The milestone must work against the current detected project only.
- The product remains local-first and Composer-installable.
- No in-app source editor; the review surface works from clean diff presentation.
- Existing backend/frontend architecture should be extended rather than replaced.

## Integration Points

- Rector CLI — invoked as a local subprocess for analysis.
- Project context detector — determines current project path, Rector binary, and config availability.
- Frontend review model — consumes backend analysis output and must remain resilient to variant Rector payloads.
- Local filesystem/project runtime — source of truth for current project state and analysis execution.

## Open Questions

- How much of the existing review shell already satisfies the “credible” UX bar versus needing slice-level redesign — to be answered in planning/execution.
- What structured diagnostic shape best balances simplicity with future reuse for apply/history milestones — likely decided during implementation.
