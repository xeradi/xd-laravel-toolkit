# Changelog

## 1.1.0 — 2026-10-04

### Fixed
- `find-todos.php`: JSON output was empty when a comment contained an em dash or other multibyte character (UTF-8 bytes were split by the marker regex).
- `find-todos.php`: crashed when given a single file instead of a folder.
- `find-todos.php`: prose such as "the user's todo items" was reported as a TODO; markers now match in UPPERCASE or as a phpDoc `@todo` tag (`--ignore-case` restores the old behaviour).
- `find-todos.php`: paths are now relative to the working directory, so they work with `git blame`; `vendor`/`node_modules` are pruned instead of walked; inline `@php(...)` no longer swallows a later `@php ... @endphp` block.
- `/xd:review-edits` and `/xd:review-pr` no longer conflict with the skill writing `CODE_REVIEW.md` — read-only reviews stay in chat.
- Commands now reference the checklist via `${CLAUDE_PLUGIN_ROOT}` instead of a path that only resolved inside the skill folder.

### Changed
- Skill description broadened and made more specific so it triggers on everyday phrasing ("is this controller OK?"), not only on reviews of junior code.
- The wizard now asks for **Mode** (Review + fix / Review only) when the skill runs directly, and is skipped when the request already answers everything.
- Unattended (CI/scheduled) runs never apply edits.
- `/xd:review-pr` stashes untracked files too, pops only its own stash, and warns when `composer.lock` differs.
- `find-todos.php`: `--help`, validation of `--format` and unknown options, PHP 8.0 version check.

## 1.0.0 — 2026-10-04

First public release.

- `laravel-code-review` skill: mentoring-style review ranked by severity, safe enhancement, engineering principles (security & performance first, reuse helpers, KISS, DRY, YAGNI, SoC), follow-up wizard
- Commands: `/xd:refactor`, `/xd:plan`, `/xd:review-edits`, `/xd:review-pr`, `/xd:todo`
- `find-todos.php`: tokenizer-based TODO scanner for PHP and Blade
