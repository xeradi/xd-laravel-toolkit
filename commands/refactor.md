---
description: Refactor existing PHP/Laravel code following the laravel-code-review skill
argument-hint: <files, folder, class or feature to refactor>
---

Use the **laravel-code-review** skill in **Review + fix** mode and follow its Engineering principles strictly.

**Target:** $ARGUMENTS

## Wizard (before any edit)

Do a quick read-only scan of the target and its related files, then follow the skill's **Wizard** section. Ask in one AskUserQuestion call, using real file/class names in the options:

- **Scope** — the target only (Recommended) / target + closely related files you found (name them) / whole feature. If no target was given, offer the likely candidates (e.g. recently changed files, the largest controllers).
- **Depth** — Critical + Important (Recommended) / Everything incl. suggestions / Critical only.
- **Bugs** — Fix bugs found, even if behaviour changes (Recommended) / Report behaviour-changing fixes, don't apply them.
- **Tests** — Add characterization tests first (Recommended) / Use existing tests only / Skip tests.

If `git status` shows uncommitted changes in the target files, mention it in the confirmation line so the user can commit or stash first — mixing your edits with theirs makes both hard to review.

Ask a second round only if your scan found a real decision, e.g. moving logic to a new Service/Action when the project has none yet, or a schema change.

## Steps

1. Run Steps 1–3 of the skill on the target: read `composer.json`, learn the project's conventions, read related files (models, migrations, FormRequests, policies, routes, tests), run the available tools (Pint, PHPStan, tests), and review against `${CLAUDE_PLUGIN_ROOT}/skills/laravel-code-review/references/checklist.md`.
2. If the user chose characterization tests and the current behaviour isn't covered, write them first (Pest or PHPUnit, matching the project) and confirm they pass on the original code.
3. Refactor in small steps, severity order (Critical → Important → Suggestions):
   - Reuse Laravel built-ins and existing project helpers before writing anything new.
   - Preserve behaviour. If a fix changes behaviour because it is a bug fix, say so explicitly.
   - Don't rename public APIs, routes, DB columns, or config keys, and don't add packages or new architectural layers, without asking.
   - Schema changes go in a new migration.
4. After each group of changes, run `php -l`, Pint, PHPStan, and the tests. Revert any change that breaks them and can't be fixed.
5. Write `CODE_REVIEW.md` with the findings and the **Changes applied** table.

## Final message

- Counts by severity, and what was changed.
- Verification results (tests / PHPStan / Pint).
- Anything left for the developer to decide.
- The 2–3 most useful lessons for the junior.
