# M003: Change batching and commit preparation — Context

**Gathered:** 2026-03-11
**Status:** Ready for future planning

## Project Description

This milestone adds organizational workflow on top of selective apply. Users can group accepted changes into logical batches that make large upgrade efforts easier to review, apply, and turn into clean commits.

## Why This Milestone

Once selective apply exists, large upgrade efforts still need structure. Batching keeps unrelated or differently scoped changes from being mixed together and helps developers produce understandable upgrade commits.

## User-Visible Outcome

### When this milestone is complete, the user can:

- Organize accepted changes into named or otherwise distinct batches.
- Review each batch as a coherent unit before application.
- Prepare cleaner commit-ready upgrade work instead of one large monolithic change set.

### Entry point / environment

- Entry point: local Rector UI review/apply flow.
- Environment: local browser + local PHP process + local git-backed project.
- Live dependencies involved: selection state from M002, local filesystem, likely local git workflows.

## Completion Class

- Contract complete means: batch model, assignment rules, and batch review/apply surfaces are defined.
- Integration complete means: batches can drive real selective application and clean commit preparation.
- Operational complete means: the system makes batch state understandable and recoverable during longer local workflows.

## Final Integrated Acceptance

To call this milestone complete, we must prove:

- A developer can separate accepted changes into logical batches.
- Batches can be reviewed and applied without losing the underlying selective-apply guarantees.
- The workflow materially improves commit preparation for large upgrade efforts.

## Risks and Unknowns

- Batch semantics may become confusing if selections can belong to overlapping or shifting groups.
- Git-aware ergonomics may be valuable but could expand scope sharply.

## Existing Codebase / Prior Art

- M001 review model and M002 selective apply outputs are the main prior art this milestone must build on.

> See `.gsd/DECISIONS.md` for all architectural and pattern decisions — it is an append-only register; read it during planning, append to it during execution.

## Relevant Requirements

- R002 — strengthens the review→apply loop.
- R003 — depends on selective apply semantics.
- R004 — primary requirement delivered by this milestone.

## Scope

### In Scope

- Logical grouping of accepted changes.
- Batch review and batch-oriented application/commit preparation.

### Out of Scope / Non-Goals

- Collaborative review workflows.
- Full release or campaign management.

## Technical Constraints

- Must preserve file/hunk-level accuracy from M002.
- Should remain local-first.

## Integration Points

- Selection/apply engine from M002.
- Local git workflows if commit preparation is included directly.

## Open Questions

- Whether commit preparation should stop at filesystem state organization or integrate directly with git metadata/actions.
