---
description: Plan a refactor or a new feature from the codebase — read-only, no file edits
argument-hint: <what to refactor or build>
allowed-tools: Read, Grep, Glob, AskUserQuestion, Bash(git status:*), Bash(git log:*), Bash(git diff:*), Bash(git merge-base:*), Bash(php artisan route:list:*), Bash(php artisan model:show:*)
---

Use the **laravel-code-review** skill and its Engineering principles.

**Goal:** $ARGUMENTS

## Wizard

After a quick read-only look at the relevant code, follow the skill's **Wizard** section. Ask in one AskUserQuestion call (skip what the goal already answers):

- **Type** — Refactor existing code / New feature / Both. If no goal was given, offer the likely candidates you found instead.
- **Detail** — Step-by-step plan with files (Recommended) / Short outline / Detailed with code sketches.
- **Limits** (multiSelect) — No database changes / No new packages / Keep public routes and API unchanged / No limits.
- **Priority** — Security & performance first (Recommended) / Smallest change first / Clean architecture first.

For a new feature, ask a second round for the business rules you can't infer from the code (e.g. who can access it, what happens on failure).

## Hard rule

**Read-only.** Do not create, edit, move, or delete any file. Do not run migrations, Pint with fixes, or anything that writes. Output the plan in chat only.

## Steps

1. Read `composer.json` (PHP/Laravel versions, packages, Pest vs PHPUnit).
2. Explore the relevant code: routes, controllers, models, migrations, FormRequests, policies, services/actions, jobs, views, tests. Learn the project's conventions and folder structure.
3. Search for what already exists before planning anything new: Laravel built-ins, project helpers (`app/Helpers`, `app/Support`, `app/Services`, traits, scopes), and installed packages.
4. For a refactor, review the target against `${CLAUDE_PLUGIN_ROOT}/skills/laravel-code-review/references/checklist.md` to find what needs changing and why.

## Output

1. **Context** — what exists today (2–5 lines, with file paths).
2. **Reuse** — existing helpers, methods, packages, and Laravel features the work should use.
3. **Plan** — numbered steps. For each: files to create or change, what changes, and which principle or finding it serves. Security and performance items first.
4. **Database** — new migrations, indexes, data backfills (if any).
5. **Tests** — which tests to add or update.
6. **Risks & decisions** — breaking changes, things the developer must decide, trade-offs (e.g. DRY vs KISS).
7. **Out of scope** — what you're deliberately not doing (YAGNI).

Keep it proportional: a small change gets a short plan.
