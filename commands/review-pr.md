---
description: Review a pull request and say whether it is ready to merge
argument-hint: "[PR number or URL — defaults to the current branch's PR]"
---

Use the **laravel-code-review** skill and its Engineering principles. This is **review only** — do not edit files, push, or merge; the report goes in chat, not `CODE_REVIEW.md`.

## Scope

1. Identify the PR: "$ARGUMENTS". If empty, use the PR for the current branch (`gh pr view`).
2. Collect context:
   - `gh pr view <pr> --json title,body,baseRefName,headRefName,files,commits,reviews,statusCheckRollup`
   - `gh pr diff <pr>`
   - `gh pr checks <pr>` for CI status.
   - If `gh` isn't available or authenticated, fall back to `git fetch` and `git diff origin/<base>...origin/<head>`, and say so.
3. Read the PR description to understand the intent, and any linked issue.

## Wizard

Follow the skill's **Wizard** section. Ask in one AskUserQuestion call:

- **PR** — only if none was given and there's no PR for the current branch: offer the open PRs from `gh pr list` (number + title, up to 4).
- **Run locally** — Check out and run Pint, PHPStan, tests (Recommended) / Review the diff only.
- **Focus** (multiSelect) — Security / Performance / Migrations & data / Tests / Everything (Recommended).
- **After review** — Show the review here only (Recommended) / Also post it as PR comments with `gh pr review`.

## Review

1. Follow Steps 1–4 of the skill (review-only mode) on the changed code. Read the full files and related code, not only the diff hunks.
2. Check the PR does what its description says — no missing pieces, no unrelated changes slipped in.
3. Check merge blockers:
   - Failing or missing CI checks.
   - Migrations: reversible `down()`, safe on large tables, no edits to already-merged migrations, indexes for new foreign keys / lookups.
   - Breaking changes to routes, API responses, config keys, or DB columns without a migration path.
   - Missing tests for new behaviour, authorization, and validation.
   - Secrets, debug leftovers, conflict markers.
   - New code that reimplements an existing Laravel or project helper.
4. If the user chose to run locally: note the current branch, stash uncommitted work including untracked files (`git stash push -u -m "xd:review-pr"`) only if `git status --porcelain` is not empty, check out the PR (`gh pr checkout <pr>`), and run Pint, PHPStan, and the related tests. If `composer.lock` differs from the original branch, say so — results may need `composer install`. Afterwards switch back to the original branch and `git stash pop` only the stash you created. If anything fails, tell the user exactly which branch they're on and that their work is in the stash.

## Output

Start with the verdict on its own line:

- ✅ **Ready to merge**
- ⚠️ **Approve with comments** — only 🟡 suggestions
- ❌ **Changes requested** — any 🔴 / 🟠 finding, failing CI, or merge blocker

Then:
- PR summary in 2–3 lines (what it does, files touched).
- CI and check results.
- Findings ranked by severity with `file:line`, why it matters, and the suggested fix.
- **What you did well**.

Posting is visible to the whole team, so post to GitHub only if the user chose that in the wizard: use `gh pr review` with `--approve`, `--comment`, or `--request-changes` matching the verdict, and inline comments for findings. Otherwise, offer it at the end.
