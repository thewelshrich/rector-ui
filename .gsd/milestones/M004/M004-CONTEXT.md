# M004: Config and upgrade control surface — Context

**Gathered:** 2026-03-11
**Status:** Ready for future planning

## Project Description

This milestone adds an in-product control surface for Rector configuration and upgrade targeting so users can iterate on upgrade strategy without leaving the application.

## Why This Milestone

Review and apply are powerful, but upgrade work also involves steering Rector itself. Bringing configuration visibility and control into the product reduces context switching and makes the upgrade loop tighter.

## User-Visible Outcome

### When this milestone is complete, the user can:

- Inspect the current project’s Rector configuration from inside the app.
- Make bounded configuration changes or choose upgrade controls in the UI.
- Rerun analysis based on those updated settings.

### Entry point / environment

- Entry point: local Rector UI running against the current project.
- Environment: local browser + local PHP process + project config files.
- Live dependencies involved: Rector config files, local analysis flow, possibly project-specific Rector conventions.

## Completion Class

- Contract complete means: config inspection/edit contracts and rerun controls are defined.
- Integration complete means: user changes in the product affect real subsequent analysis runs.
- Operational complete means: invalid or risky config states are visible and recoverable.

## Final Integrated Acceptance

To call this milestone complete, we must prove:

- A developer can inspect meaningful Rector configuration state in the UI.
- A user action in the product can influence a subsequent analysis run.
- Misconfiguration or invalid config changes are surfaced safely.

## Risks and Unknowns

- Rector config formats may vary across PHP projects and conventions.
- Safe editing boundaries may be hard to define if config is highly custom PHP.

## Existing Codebase / Prior Art

- `ProjectContextDetector` and analysis orchestration provide the current starting point for discovering config paths and rerunning analysis.

> See `.gsd/DECISIONS.md` for all architectural and pattern decisions — it is an append-only register; read it during planning, append to it during execution.

## Relevant Requirements

- R005 — primary requirement delivered by this milestone.
- R002 — supports a tighter review/apply loop through reruns.

## Scope

### In Scope

- Config inspection.
- Bounded config management or control surfaces.
- Rerun integration.

### Out of Scope / Non-Goals

- Arbitrary general-purpose code editing.
- Remote/shared config management.

## Technical Constraints

- Must respect the no-editor product boundary.
- Must work against current-project local config only.

## Integration Points

- Project context detection.
- Rector config files.
- Analysis rerun API.

## Open Questions

- Whether the safest first version is structured controls over known Rector options, config inspection only, or a constrained raw-file editing approach.
