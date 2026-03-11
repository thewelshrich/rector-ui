# Decisions Register

<!-- Append-only. Never edit or remove existing rows.
     To reverse a decision, add a new row that supersedes it.
     Read this file at the start of any planning or research phase. -->

| # | When | Scope | Decision | Choice | Rationale | Revisable? |
|---|------|-------|----------|--------|-----------|------------|
| D001 | M001 | scope | Product strategy | Multi-milestone local-first product plan | The vision spans review, selective apply, batching, config control, and continuity, which is broader than a single milestone. | No |
| D002 | M001 | constraint | Initial project targeting | Current detected project only | The user wants local-first behavior without project selection in the first planned milestone. | Yes — if later local workspace management becomes valuable |
| D003 | M001 | pattern | Primary user loop | Review then apply | The product should optimize for running analysis, reviewing diffs, choosing wanted changes, then applying them. | No |
| D004 | M001 | arch | Future apply semantics | Product-owned selective patch application at file and hunk level | The differentiator is applying only chosen changes, not relying on Rector’s all-or-nothing write path. | Yes — if implementation research proves a safer equivalent representation |
| D005 | M001 | convention | Initial review UX shape | Sidebar/tree workspace | The first release should feel like a review tool, not a wizard. | Yes — if usability evidence later suggests a hybrid flow |
| D006 | M001 | operability | Failure visibility standard | Structured diagnostics in-product | Local upgrade workflows need visible command/phase/error context rather than minimal banner errors. | No |
| D007 | M001 | anti-feature | Editing boundary | No in-app general-purpose source editor | The product should present clean diffs and controlled actions without becoming a browser IDE. | No |
