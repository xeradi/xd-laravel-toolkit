---
description: List every TODO comment in the project's .php files (Blade included)
argument-hint: "[optional: folder to scan, defaults to the project root]"
---

Use the **laravel-code-review** skill. This command is read-only unless the user picks an option in the wizard that writes a file.

## 1. Wizard

Follow the skill's **Wizard** section. Before scanning, ask in one AskUserQuestion call (skip any question already answered by "$ARGUMENTS"):

- **Scope** — Whole project (Recommended) / `app/` only / `app/` + `resources/views` / Other folder.
- **Markers** (multiSelect) — TODO (Recommended) / FIXME / HACK / XXX.
- **Details** — Location + text only (Recommended) / Also author and age from `git blame`.
- **Then** — Just list them (Recommended) / Group by theme and priority / Save the list to `TODOS.md`.

## 2. Scan

Run the skill's scanner — it uses PHP's tokenizer, so only real comments match (not `Todo::class`, `'todo'` strings, or a `todos` table). Markers match in UPPERCASE or as a phpDoc `@todo` tag, so prose like "the user's todo items" is ignored; add `--ignore-case` only if the user wants every casing:

```bash
php "${CLAUDE_PLUGIN_ROOT}/skills/laravel-code-review/scripts/find-todos.php" <scope> --markers=<chosen markers, e.g. TODO,FIXME> --format=json
```

Run it from the project root so reported paths work with `git blame`. It needs PHP 8.0+ and already skips `vendor`, `node_modules`, `storage`, `bootstrap/cache`, `.git`, and `public/build`.

If PHP isn't available (or is older than 8.0), fall back to the Grep tool on `*.php` files with a pattern like `\b(TODO|FIXME)\b|@todo`, then read each hit and keep only those inside a comment.

If the user asked for author and age: `git blame -L <line>,<line> --porcelain <file>` for each hit (author, author-time).

## 3. Output

- Total count, and count per marker.
- Grouped by file, in a table: `Line | Marker | TODO | (Author | Age)`.
- Flag TODOs with no description, and ones that look urgent (mention security, bug, broken, hack, temporary, remove before prod).
- If the user chose **Group by theme and priority**: group them (security, bugs, performance, refactor, features, cleanup) and order by priority, using the skill's severity levels.
- If the user chose **Save to `TODOS.md`**: write the same content there (this is the only file this command may write).

End by offering `/xd:plan` to plan the most important ones, or `/xd:refactor` to resolve specific TODOs.
