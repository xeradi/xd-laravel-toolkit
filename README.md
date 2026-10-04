# XD Laravel Toolkit

A [Claude Code](https://code.claude.com) plugin that turns Claude into a senior Laravel engineer: it reviews code written by junior developers, explains *why* each issue matters, and refactors safely — **security and performance first**.

- 🔍 Mentoring-style reviews ranked by severity (🔴 Critical · 🟠 Important · 🟡 Suggestion · 💡 Learning note)
- 🛠️ Safe refactoring with characterization tests, Pint, PHPStan and your test suite as guardrails
- ♻️ Reuses Laravel built-ins and your existing helpers before writing anything new
- 🧭 Asks follow-up questions in a clickable wizard before it changes anything
- ✅ Clear verdicts: ready to push? ready to merge?

## Install

In Claude Code:

```text
/plugin marketplace add xeradi/xd-laravel-toolkit
/plugin install xd@xeradi
```

Or from your shell:

```bash
claude plugin marketplace add xeradi/xd-laravel-toolkit
claude plugin install xd@xeradi
```

Update later with `/plugin marketplace update xeradi`.

## Commands

| Command | What it does | Edits files? |
|---|---|---|
| `/xd:refactor <target>` | Reviews the target, adds characterization tests if needed, refactors in small verified steps, writes `CODE_REVIEW.md` | Yes |
| `/xd:plan <goal>` | Plans a refactor or new feature from your codebase: what exists, what to reuse, steps, migrations, tests, risks | No |
| `/xd:review-edits` | Reviews uncommitted changes and gives a verdict: ✅ ready to push · ⚠️ small fixes · ❌ not ready | No |
| `/xd:review-pr [number]` | Reviews a GitHub PR (diff, CI, description) and gives a verdict: ✅ merge · ⚠️ approve with comments · ❌ changes requested | No* |
| `/xd:todo [folder]` | Lists every TODO / FIXME / HACK in PHP and Blade comments, optionally with author and age | No* |
| `/xd:laravel-code-review [target]` | Runs the underlying skill directly (it also triggers automatically when you ask Claude to review Laravel code) | Only if you pick **Review + fix** in the wizard |

\* Only if you choose it in the wizard: posting the review to GitHub, or saving `TODOS.md`.

Every command starts with a quick read-only scan, then asks its questions (scope, depth, checks…) as multiple-choice cards — with real file and class names from your project — before doing the work.

## What it checks

Ranked in this order:

1. **Security** — mass assignment, IDOR / missing authorization, raw SQL injection, XSS via `{!! !!}`, secrets in code, `env()` outside config, unsafe uploads, missing rate limits
2. **Correctness** — missing transactions, race conditions, null handling, money as float, unsafe migrations, swallowed exceptions, non-idempotent jobs
3. **Performance** — N+1 queries, `all()` on big tables, queries in loops, missing indexes, slow work that belongs in a queue
4. **Laravel idioms** — FormRequests, Policies, route model binding, relationships and scopes, casts and enums, API Resources
5. **Design** — KISS, DRY (rule of three), YAGNI, Separation of Concerns, reinvented helpers, convention drift
6. **Tests** — feature coverage, authorization and validation cases, fakes for external services

The full list is in [`skills/laravel-code-review/references/checklist.md`](skills/laravel-code-review/references/checklist.md).

## Engineering principles

The skill holds every change — the junior's and its own — to these rules:

- Security and performance first
- Check Laravel helpers and existing project helpers before creating any function
- Follow Laravel best practices and the project's existing conventions
- Readable and clear for anyone
- KISS · DRY (without premature abstraction) · YAGNI · Separation of Concerns

It also won't rename public routes, columns or config keys, add packages, or edit migrations that may have already run without asking.

## Requirements

- Claude Code
- PHP 8.0+ in your `PATH` (for `/xd:todo`; it falls back to text search without it)
- Optional, used when installed in your project: Laravel Pint, PHPStan / Larastan, Pest or PHPUnit
- Optional: [GitHub CLI](https://cli.github.com) (`gh`) for `/xd:review-pr`

## Structure

```text
.claude-plugin/
  plugin.json          # plugin manifest
  marketplace.json     # lets users add this repo as a marketplace
skills/laravel-code-review/
  SKILL.md             # review process, principles, wizard
  references/          # checklist + report template
  scripts/find-todos.php
commands/              # refactor, plan, review-edits, review-pr, todo
```

## Contributing

Issues and pull requests are welcome. To test changes locally:

```bash
claude --plugin-dir ./
claude plugin validate ./
```

## Author

**Xeradi** — [github.com/xeradi](https://github.com/xeradi)

## License

[MIT](LICENSE)
