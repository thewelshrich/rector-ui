# M001: Credible local review workflow

**Vision:** Deliver a local-first browser workflow that makes Rector analysis for the current project understandable, navigable, and diagnosable enough that a PHP developer would prefer it to reviewing one massive CLI diff.

## Success Criteria

- A developer can launch Rector UI against the current project, run analysis, and review changed files plus individual diff hunks through a sidebar/workspace browser UI.
- The review experience remains usable for larger change sets by presenting structured file navigation and hunk-level detail instead of raw output dumping.
- When Rector is unavailable, misconfigured, or fails during analysis, the UI surfaces structured diagnostics that help the developer understand what happened locally.

## Key Risks / Unknowns

- Rector JSON payloads may vary across projects or versions, which could break the review model or hide key details.
- Large change sets may overwhelm the current UI if navigation and selection primitives are too shallow.
- Current error handling may be too generic to support credible local troubleshooting.

## Proof Strategy

- Rector JSON variability and review-model robustness → retire in S01 by proving real analysis payloads can be normalized into stable file and hunk review structures.
- Large change-set usability in the browser → retire in S02 by proving a sidebar/tree + workspace flow stays navigable with multi-file Rector output.
- Thin diagnostic surfaces → retire in S03 by proving unavailable/failing analysis states render structured, actionable diagnostics in the UI.

## Verification Classes

- Contract verification: PHP tests for analysis/result contracts, frontend model checks for parsed file/hunk structures, artifact checks for wired API/UI surfaces.
- Integration verification: run the local app against a real current project with Rector configured and exercise health/project/analysis flows in the browser.
- Operational verification: confirm unavailable or failing Rector/config states surface inspectable diagnostics through the app lifecycle.
- UAT / human verification: assess whether the review workspace is materially better than terminal diff review for scanning a real change set.

## Milestone Definition of Done

This milestone is complete only when all are true:

- All slice deliverables are complete.
- Backend analysis and frontend review surfaces are actually wired together.
- The real local entrypoint is exercised against a project with Rector context.
- Success criteria are re-checked against live browser behavior, not just code artifacts.
- Final integrated acceptance scenarios pass for success and failure paths.

## Requirement Coverage

- Covers: R001, R006, R007
- Partially covers: R002
- Leaves for later: R003, R004, R005, R020
- Orphan risks: none

## Slices

- [ ] **S01: Stable analysis contract and review model** `risk:high` `depends:[]`
  > After this: a real Rector dry-run for the current project can be normalized into stable file-level and hunk-level review data with current-project context.
- [ ] **S02: Credible sidebar/workspace review experience** `risk:medium` `depends:[S01]`
  > After this: a developer can browse changed files and hunks in a sidebar/tree review workspace that is clearly more usable than raw CLI diff output.
- [ ] **S03: Analysis diagnostics and failure visibility** `risk:medium` `depends:[S01,S02]`
  > After this: the app clearly explains unavailable, failed, or malformed analysis states instead of leaving the user with vague errors.
- [ ] **S04: End-to-end local review proof** `risk:low` `depends:[S01,S02,S03]`
  > After this: the assembled local app is proven end-to-end against a real project and the milestone’s live review workflow is demonstrably complete.

## Boundary Map

### S01 → S02

Produces:
- Analysis payload normalization for changed files, diff hunks, counts, and parse-failure state.
- Stable current-project context shape for frontend consumption.
- File-tree and hunk-level review model primitives derived from Rector output.

Consumes:
- nothing (first slice)

### S01 → S03

Produces:
- Structured analysis result states for success, unavailable, failure, and malformed-output scenarios.
- Backend response fields that preserve command, exit code, stdout/stderr, and changed-file counts when available.

Consumes:
- nothing (first slice)

### S02 → S04

Produces:
- Sidebar/tree review workspace wired to normalized file and hunk data.
- User-visible project/review flow that can be exercised in a real browser session.

Consumes from S01:
- Normalized file, hunk, and project-context data structures.

### S03 → S04

Produces:
- Diagnostic UI states for unavailable Rector/config, failed analysis execution, and malformed or incomplete output.
- Observable failure surfaces that can be checked in browser verification.

Consumes from S01:
- Structured analysis state and diagnostic payload fields.
