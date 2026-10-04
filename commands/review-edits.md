---
description: Review uncommitted changes and say whether they are ready to push
argument-hint: "[optional: specific files to limit the review]"
---

Use the **laravel-code-review** skill and its Engineering principles. This is **review only** — do not edit any project file; the report goes in chat, not `CODE_REVIEW.md`.

## Scope

1. Run `git status --porcelain` and `git diff HEAD` to collect all uncommitted changes (staged, unstaged, and untracked files — read untracked files in full). In a repository with no commits yet, use `git diff --cached` plus the untracked files instead.
2. If "$ARGUMENTS" names files, limit the review to those.
3. If there are no changes, say so and stop.

## Wizard

Follow the skill's **Wizard** section. Ask in one AskUserQuestion call, using the real changed files you found:

- **Scope** — All uncommitted changes, N files (Recommended) / Staged only / Pick files (list them).
- **Checks** (multiSelect) — Lint + Pint (Recommended) / PHPStan (Recommended) / Related tests (Recommended) / Full test suite.
- **Strictness** — Junior mentoring, explain every finding (Recommended) / Blockers only, short.

## Review

1. Follow Steps 1–4 of the skill (review-only mode) on the changed code. Read the surrounding code each change depends on, not just the diff lines.
2. Run the available checks on the changed files only: `php -l`, `./vendor/bin/pint --test`, `./vendor/bin/phpstan analyse`, and the related tests.
3. Also check for push blockers:
   - Debug leftovers: `dd(`, `dump(`, `var_dump(`, `ray(`, `console.log(`, `Log::debug`.
   - Secrets or `.env` values in code; `.env` or large/binary files staged by mistake.
   - Edited migrations that may have already run.
   - Merge-conflict markers, commented-out blocks, TODOs added in this change.
   - New code that reimplements an existing Laravel or project helper.

## Output

Start with the verdict on its own line:

- ✅ **Ready to push**
- ⚠️ **Ready after small fixes** — only 🟡 suggestions, nothing blocking
- ❌ **Not ready** — any 🔴 Critical or 🟠 Important finding, failing tests, or a push blocker

Then:
- Check results (lint / Pint / PHPStan / tests).
- Findings ranked by severity with `file:line`, why it matters, and the suggested fix.
- A short **What you did well**.
- Offer to apply the fixes (via `/xd:refactor`) — do not apply them yourself.
