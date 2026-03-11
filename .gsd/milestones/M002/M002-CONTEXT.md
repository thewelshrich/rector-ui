# M002: Selective apply engine — Context

**Gathered:** 2026-03-11
**Status:** Ready for future planning

## Project Description

This milestone turns Rector UI from a review-only tool into a product-owned change application workflow. The core capability is selecting whole files or individual hunks from a reviewed Rector diff set and applying only those chosen changes back to the current local project.

## Why This Milestone

The product’s main differentiator is not merely prettier review; it is controlled application of upgrade changes beyond Rector’s coarse all-or-nothing execution model. This milestone proves that Rector UI can safely write only reviewed selections to disk and become the practical bridge from analysis to code change.

## User-Visible Outcome

### When this milestone is complete, the user can:

- Select complete files or individual hunks from a Rector analysis result.
- Apply only those selected changes back to the current local project.
- See clear diagnostics when apply operations cannot be completed cleanly.

### Entry point / environment

- Entry point: local Rector UI browser flow running against the current project.
- Environment: local browser + local PHP process + local filesystem.
- Live dependencies involved: local analysis output, patch generation/application logic, project files on disk.

## Completion Class

- Contract complete means: selection state, patch representation, and apply APIs are defined and verifiable.
- Integration complete means: chosen files/hunks are written correctly to real project files without applying unselected changes.
- Operational complete means: failed apply attempts expose enough diagnostics to recover safely.

## Final Integrated Acceptance

To call this milestone complete, we must prove:

- A developer can select a subset of analyzed changes and apply exactly those changes to disk.
- File-level selection and hunk-level selection both work against real project files.
- Failed or conflicting apply attempts are visible and do not silently corrupt unrelated code.

## Risks and Unknowns

- Partial patch application may be harder than raw diff display if Rector output lacks enough stable patch metadata.
- Safe apply behavior must be exact; over-applying or under-applying changes would destroy trust.
- UX for selection state must stay understandable with large change sets.

## Existing Codebase / Prior Art

- `frontend/src/lib/rector-analysis.js` — existing file and block parsing foundation that likely informs selection granularity.
- `src/RectorAnalysisService.php` — existing source of raw Rector output and likely future patch metadata source.

> See `.gsd/DECISIONS.md` for all architectural and pattern decisions — it is an append-only register; read it during planning, append to it during execution.

## Relevant Requirements

- R002 — completes the apply half of the primary user loop.
- R003 — delivers the key differentiator of file- and hunk-level selective apply.
- R006 — extends structured diagnostics to apply failures.
- R007 — preserves current-project local-first constraints.

## Scope

### In Scope

- File selection and hunk selection.
- Product-owned patch application.
- Safety and diagnostics around writeback.

### Out of Scope / Non-Goals

- Commit batching.
- In-app code editing.
- Multi-project workflows.
- Long-term session/history preservation.

## Technical Constraints

- Apply semantics should write only chosen hunks/files.
- The product must stay local-first and current-project scoped.
- No general-purpose editor surface.

## Integration Points

- Diff model from M001.
- Local filesystem writes.
- Future batching workflow in M003.

## Open Questions

- Whether patch application should be based on unified diffs, reconstructed file content, or another internal representation.
- What conflict detection and rollback model is necessary for trustworthy local apply behavior.
