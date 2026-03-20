---
name: laravel-simplifier
description: Simplifies and refines PHP/Laravel code for clarity, consistency, and maintainability while preserving all functionality. Focuses on recently modified code unless instructed otherwise.
---

# Laravel Simplifier

## When to use this skill

Use this skill when:

- simplifying, refactoring, or polishing PHP or Laravel code;
- reducing complexity, nesting, or duplication without changing behavior;
- aligning recent changes with project conventions;
- performing a cleanup pass after implementing a feature or bug fix;
- reviewing touched files for readability, consistency, and maintainability.

Default scope is the code most recently modified in the current session, current diff, or working tree. Only widen scope when the user asks for it or when a nearby dependency must be adjusted to preserve behavior.

## Primary objective

Improve clarity, consistency, and maintainability while preserving exact functionality.

This skill optimizes for explicit, readable Laravel code. It does not optimize for minimum line count, cleverness, or stylistic churn.

## Non-negotiable rules

- Never change behavior intentionally.
- Do not alter outputs, side effects, validation rules, authorization, persistence, events, or public APIs unless the user explicitly asks for a functional change.
- Prefer explicit code over dense one-liners.
- Avoid nested ternary operators. Use `if` / `elseif`, `match`, or `switch` when multiple conditions are involved.
- Remove only comments that describe obvious code. Keep comments that explain intent, constraints, or non-obvious decisions.
- Respect existing architecture boundaries. Do not move domain logic into UI layers or vice versa.
- Limit edits to recently touched code unless expansion is necessary and justified.

## Project standards

Before refactoring, read the project instructions that govern the repo, especially `AGENTS.md`, `CLAUDE.md`, and nearby sibling files in the area being edited.

Apply project standards consistently:

- use clear namespaces and organized imports;
- prefer explicit parameter and return types;
- follow PSR-12 and established local naming conventions;
- use Laravel conventions for actions, services, policies, requests, models, and tests;
- keep error handling idiomatic and explicit;
- preserve framework patterns already established in the module.

## Refinement workflow

1. Identify the active scope.
2. Determine what code was recently modified using the current request, `git diff`, `git status`, or nearby touched files.
3. Read adjacent files to match local conventions before editing.
4. Simplify structure without changing behavior.
5. Remove redundancy and reduce unnecessary branching or nesting.
6. Improve naming only when it increases clarity and does not widen the change surface excessively.
7. Run the minimum relevant tests or checks required to verify no regression.
8. Report only meaningful refinements and any residual risk.

## Preferred refactoring patterns

- Extract small private methods when they reveal intent or remove repeated logic.
- Inline pointless abstractions when they hide simple behavior and add no reuse value.
- Replace condition pyramids with guard clauses when it improves readability.
- Consolidate duplicated query fragments or repeated transformations when the result stays obvious.
- Normalize inconsistent naming in a local area when it materially improves comprehension.
- Prefer Laravel helpers and framework conventions when they make the code clearer, not merely shorter.

## Patterns to avoid

- large opportunistic rewrites unrelated to the touched code;
- introducing new base classes, interfaces, or traits without a real need;
- compressing logic into chained expressions that are harder to debug;
- replacing readable conditionals with nested ternaries;
- rewriting code only for personal style preference;
- changing test intent while “fixing” implementation style.

## Verification

Every refinement must be programmatically verified with the minimum relevant test coverage for the touched area.

Prefer:

- targeted `php artisan test --compact` runs;
- existing feature or unit tests near the changed code;
- repo-standard static analysis only when already part of the local workflow.

If no relevant automated test exists and the change is non-trivial, add or update the smallest test that proves behavior is unchanged.

## Output expectations

When reporting results:

- summarize the meaningful simplifications;
- state what was verified;
- call out any assumptions or residual risks;
- avoid changelog-style noise for purely cosmetic edits.
