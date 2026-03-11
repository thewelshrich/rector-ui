# Requirements

This file is the explicit capability and coverage contract for the project.

Use it to track what is actively in scope, what has been validated by completed work, what is intentionally deferred, and what is explicitly out of scope.

Guidelines:
- Keep requirements capability-oriented, not a giant feature wishlist.
- Requirements should be atomic, testable, and stated in plain language.
- Every **Active** requirement should be mapped to a slice, deferred, blocked with reason, or moved out of scope.
- Each requirement should have one accountable primary owner and may have supporting slices.
- Research may suggest requirements, but research does not silently make them binding.
- Validation means the requirement was actually proven by completed work and verification, not just discussed.

## Active

### R001 — Browser-based Rector review for the current project
- Class: core-capability
- Status: active
- Description: A developer can run Rector analysis against the current local project and review the resulting change set in a browser instead of raw CLI diff output.
- Why it matters: This is the product’s foundational value and the first reason to use Rector UI at all.
- Source: user
- Primary owning slice: M001/S01
- Supporting slices: M001/S02, M001/S03
- Validation: mapped
- Notes: M001 must beat terminal-only review credibly for a single developer.

### R002 — Fast primary loop of review then apply
- Class: primary-user-loop
- Status: active
- Description: The product should optimize for the workflow of run analysis, inspect diffs, choose wanted changes, and then apply them.
- Why it matters: The product is not just an inspector; it is intended to become the practical upgrade workflow for local refactor work.
- Source: user
- Primary owning slice: M002/S01
- Supporting slices: M001/S01, M001/S02, M003/S01
- Validation: mapped
- Notes: M001 establishes the review half of the loop; apply arrives in M002.

### R003 — File and hunk level selective apply
- Class: differentiator
- Status: active
- Description: Users can select whole files or individual diff hunks for application, and the product writes only those reviewed selections back to disk.
- Why it matters: This is the key product differentiator versus Rector’s coarse all-or-nothing CLI flow.
- Source: user
- Primary owning slice: M002/S01
- Supporting slices: M002/S02, M003/S01
- Validation: mapped
- Notes: Explicitly excludes a full in-app code editor; the UI works from clean diffs and patch application.

### R004 — Change batching into logical review/apply groups
- Class: core-capability
- Status: active
- Description: Users can group accepted changes into meaningful batches that support cleaner review and commit preparation.
- Why it matters: Large upgrade campaigns become manageable when unrelated changes can be separated into deliberate units.
- Source: user
- Primary owning slice: M003/S01
- Supporting slices: M003/S02
- Validation: mapped
- Notes: Batches should build on selective apply rather than replace it.

### R005 — Rector config management from the UI
- Class: core-capability
- Status: active
- Description: Users can inspect and manage Rector configuration from inside the product to guide reruns and upgrade targeting.
- Why it matters: The product should reduce context-switching between config files and review output during upgrade work.
- Source: user
- Primary owning slice: M004/S01
- Supporting slices: M004/S02
- Validation: mapped
- Notes: The first version may emphasize inspection and safe editing boundaries rather than full config authoring freedom.

### R006 — Structured failure diagnostics for analysis and apply flows
- Class: failure-visibility
- Status: active
- Description: When Rector analysis or later apply flows fail, the UI surfaces command context, phase, summarized stderr/stdout, and actionable failure states.
- Why it matters: Local upgrade tools are only trustworthy if failure causes are visible and debuggable without dropping straight to guesswork.
- Source: user
- Primary owning slice: M001/S03
- Supporting slices: M002/S02, M005/S02
- Validation: mapped
- Notes: Minimal banner-only error handling is insufficient for the intended product.

### R007 — Local-first boundary
- Class: constraint
- Status: active
- Description: The product is designed for local-first use against the current project rather than collaborative cloud workflows.
- Why it matters: This constrains architecture, scope, and safety assumptions across all early milestones.
- Source: user
- Primary owning slice: M001/S01
- Supporting slices: M001/S02, M002/S01, M005/S01
- Validation: mapped
- Notes: Team or agency usage may happen through local workflows, but collaboration features are not an active requirement.

## Validated

## Deferred

### R020 — Session and history continuity across runs
- Class: continuity
- Status: deferred
- Description: Users can return to prior analyses or preserve progress across longer upgrade efforts.
- Why it matters: This will matter once the core review/apply workflow is solid and users need to continue work over time.
- Source: user
- Primary owning slice: none
- Supporting slices: none
- Validation: unmapped
- Notes: Explicitly useful later, not an early launch bar.

## Out of Scope

### R030 — In-app source editor
- Class: anti-feature
- Status: out-of-scope
- Description: The product does not become a full editor for arbitrary code editing inside the browser.
- Why it matters: This prevents scope drift away from diff review and controlled patch application.
- Source: user
- Primary owning slice: none
- Supporting slices: none
- Validation: n/a
- Notes: The product may show rich diffs and support selective application, but not general-purpose editing.

### R031 — Multi-project or arbitrary path selection in the first planned milestone
- Class: constraint
- Status: out-of-scope
- Description: M001 targets the current detected project only, not a project picker or workspace manager.
- Why it matters: It keeps the first milestone aligned with the current architecture and the user’s stated boundary.
- Source: user
- Primary owning slice: none
- Supporting slices: none
- Validation: n/a
- Notes: Could be revisited in a later milestone if the local-first model expands.

### R032 — Collaborative team workflows
- Class: anti-feature
- Status: out-of-scope
- Description: Shared review state, remote collaboration, and multi-user coordination are not in scope for the current product plan.
- Why it matters: It preserves the local-first strategy and prevents premature platform expansion.
- Source: user
- Primary owning slice: none
- Supporting slices: none
- Validation: n/a
- Notes: Team usage is supported indirectly via local outputs and commits, not live collaboration.

## Traceability

| ID | Class | Status | Primary owner | Supporting | Proof |
|---|---|---|---|---|---|
| R001 | core-capability | active | M001/S01 | M001/S02, M001/S03 | mapped |
| R002 | primary-user-loop | active | M002/S01 | M001/S01, M001/S02, M003/S01 | mapped |
| R003 | differentiator | active | M002/S01 | M002/S02, M003/S01 | mapped |
| R004 | core-capability | active | M003/S01 | M003/S02 | mapped |
| R005 | core-capability | active | M004/S01 | M004/S02 | mapped |
| R006 | failure-visibility | active | M001/S03 | M002/S02, M005/S02 | mapped |
| R007 | constraint | active | M001/S01 | M001/S02, M002/S01, M005/S01 | mapped |
| R020 | continuity | deferred | none | none | unmapped |
| R030 | anti-feature | out-of-scope | none | none | n/a |
| R031 | constraint | out-of-scope | none | none | n/a |
| R032 | anti-feature | out-of-scope | none | none | n/a |

## Coverage Summary

- Active requirements: 7
- Mapped to slices: 7
- Validated: 0
- Unmapped active requirements: 0
