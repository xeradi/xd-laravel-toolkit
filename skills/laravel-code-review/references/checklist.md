# Laravel Review Checklist

Use as a lens, not a script. Check each item that applies to the code under review.

## 1. Security 🔴

- [ ] **Mass assignment**: `Model::create($request->all())` or `->update($request->all())`. Use `$request->validated()` and a correct `$fillable` (never `$guarded = []` on models touched by user input).
- [ ] **Validation**: every user input validated, ideally in a `FormRequest`. Watch for missing `exists:`, `in:`, `max:`, file `mimes:`/`max:` rules.
- [ ] **Authorization**: does the action check the user may act on *this* record? Look for IDOR — `Order::find($id)` with no ownership check. Use Policies + `$this->authorize()` / `Gate` / `can` middleware, or scope queries to the user (`$request->user()->orders()->findOrFail($id)`).
- [ ] **SQL injection**: string interpolation in `DB::raw`, `whereRaw`, `orderByRaw`, `selectRaw`, `DB::select`. Use bindings. User-controlled column names in `orderBy` must be whitelisted.
- [ ] **XSS**: `{!! $var !!}` in Blade with user content. Use `{{ }}` or purify.
- [ ] **Secrets**: API keys, passwords, tokens in code. Must live in `.env` and be read through `config()`.
- [ ] **`env()` outside config files**: returns null once config is cached. Move to `config/*.php`.
- [ ] **File uploads**: stored with user-controlled names/paths? Use `store()` / `hashName()`; validate type and size; never store in `public/` directly when private.
- [ ] **CSRF**: forms missing `@csrf`; routes wrongly excluded from CSRF.
- [ ] **Sensitive data in responses/logs**: returning whole models (password hashes, tokens) in JSON — use API Resources or `$hidden`. `Log::info($request->all())` with passwords.
- [ ] **Rate limiting** on login, OTP, password reset, public API endpoints.
- [ ] **Unsafe functions**: `unserialize` on user input, `eval`, `exec`/`shell_exec` with user input, `extract()`.
- [ ] **Hashing**: passwords via `Hash::make` / `hashed` cast, never `md5`/`sha1`.

## 2. Correctness & data integrity 🔴/🟠

- [ ] Multi-step writes wrapped in `DB::transaction()`.
- [ ] Race conditions: check-then-insert without unique index; counters updated with read-modify-write instead of `increment()`; stock/balance updates without `lockForUpdate()`.
- [ ] `find()` result used without null check → use `findOrFail()` or handle null.
- [ ] `first()` vs `firstOrFail()`; `->get()` when one row expected.
- [ ] Off-by-one, inverted conditions, `==` vs `===` with strings/ints (`"abc" == 0` pitfalls on older PHP), `empty("0")` is true.
- [ ] Dates: timezone assumptions, `now()` vs `today()`, comparing Carbon instances with `==`, mutating a shared Carbon (use `CarbonImmutable` or `->copy()`).
- [ ] Money as float → use integer cents or a decimal/money type.
- [ ] Migrations: `down()` that works; dropping columns with data; `->change()` on large tables; missing foreign keys / `cascadeOnDelete` choices thought through; editing an already-run migration.
- [ ] Silent failures: empty `catch` blocks, `catch (\Exception $e) { return false; }` losing the error. Log or rethrow.
- [ ] Jobs: idempotent if retried? `$tries`/`$backoff` set? Serializing models that may be deleted before the job runs?
- [ ] External HTTP calls: timeout set (`Http::timeout()`), failures handled (`->throw()` or status checks), retries where sensible.

## 3. Performance 🟠

- [ ] **N+1**: relationship accessed inside a loop/Blade `@foreach` without `with()`. Suggest `Model::preventLazyLoading()` in `AppServiceProvider` for non-production.
- [ ] `Model::all()` / `->get()` on large tables → paginate, `chunkById()`, `lazy()`, or `cursor()`.
- [ ] Counting with `->get()->count()` → `->count()`; existence with `->count() > 0` → `->exists()`.
- [ ] Filtering collections in PHP that should be filtered in SQL (`->get()->where(...)`).
- [ ] Queries inside loops → batch with `whereIn`, `upsert`, `insert`.
- [ ] Missing DB indexes on columns used in `where`, `orderBy`, foreign keys, unique lookups.
- [ ] `select *` when few columns needed on wide tables.
- [ ] Slow work (emails, PDFs, API calls, image processing) done in the request → queue a job.
- [ ] Repeated expensive computations → `Cache::remember()` with sensible key and TTL.

## 4. Laravel idioms 🟡

- [ ] Validation in `FormRequest` classes, not inline in long controllers.
- [ ] Route model binding instead of manual `find($id)`.
- [ ] Eloquent relationships instead of manual joins / `where('user_id', ...)` everywhere.
- [ ] Query scopes for repeated `where` chains.
- [ ] Casts (`$casts` / `casts()` method) for dates, booleans, JSON, enums. Native PHP enums for status fields instead of magic strings.
- [ ] API Resources for JSON output.
- [ ] `config()` / constants / enums instead of magic numbers and strings.
- [ ] Named routes + `route()` helper instead of hard-coded URLs.
- [ ] Events/Listeners, Jobs, Notifications, Mailables used where they fit instead of inline side effects.
- [ ] Helpers: `optional()`/nullsafe `?->`, `Str::`, `Arr::`, collection methods instead of manual loops where clearer.
- [ ] Dependency injection over `new SomeService()` / facades inside deep domain logic when testability matters.
- [ ] `response()->json()` with proper status codes (201 for created, 204 no content, 422 validation, 404, 403).

## 5. Design & readability 🟡

- [ ] Fat controllers: business logic belongs in a service/action class, model method, or job. Controller should read like a table of contents.
- [ ] Methods longer than ~30 lines or nested > 3 levels → extract, use early returns / guard clauses.
- [ ] **Reinvented helpers**: custom functions that duplicate Laravel built-ins or existing project helpers. Common ones: manual slug/random string → `Str::slug()`, `Str::random()`, `Str::uuid()`; nested `isset($a['x']['y'])` → `data_get()` / `Arr::get()`; `empty()`/`trim()` checks → `blank()` / `filled()`; manual loops building arrays → `collect()->map()/pluck()/keyBy()/groupBy()`; hand-written retry loops → `retry()`; manual number/currency formatting → `Number::format()`, `Number::currency()`; manual date math → Carbon; curl → `Http`. Also grep `app/Helpers`, `app/Support`, `app/Services`, traits and scopes for an existing equivalent.
- [ ] **DRY**: logic duplicated across controllers/services (validation rules, pricing, permission checks, query chains). Extract to a scope, FormRequest, policy, or service — rule of three, unless the duplication is a bug risk.
- [ ] **Premature abstraction**: an interface, repository, base class, or "generic" helper with only one user, or code merged together that changes for different reasons. Inline it (KISS).
- [ ] **YAGNI**: unused parameters, config flags, options, branches, or methods built "for later". Remove them.
- [ ] **Separation of Concerns**: queries or business logic in Blade; validation or HTTP calls in models; authorization scattered in controllers instead of policies; controllers doing calculations, emails, or file processing directly.
- [ ] **Convention drift**: naming, folder placement, or patterns that differ from the rest of the codebase without a reason.
- [ ] Naming: descriptive variables (`$activeSubscriptions`, not `$data`, `$arr`, `$x`); methods are verbs; booleans read as questions (`isActive`, `hasPaid`).
- [ ] Type declarations on parameters, return types, and properties (`declare(strict_types=1);` if the project uses it).
- [ ] Dead code, commented-out blocks, `dd()`, `dump()`, `var_dump`, `ray()`, `Log::debug` leftovers.
- [ ] Comments that explain *what* (delete) vs *why* (keep).
- [ ] Modern PHP where the version allows: constructor property promotion, `readonly`, `match`, enums, named arguments, first-class callables, nullsafe operator.

## 6. Tests 🟠

- [ ] Changed behaviour covered by a Feature test (HTTP level) at minimum.
- [ ] Authorization tested: the "other user can't do this" case.
- [ ] Validation failures tested.
- [ ] Factories used instead of manual inserts; `RefreshDatabase` / `LazilyRefreshDatabase`.
- [ ] External services faked (`Http::fake()`, `Queue::fake()`, `Mail::fake()`, `Storage::fake()`, `Event::fake()`).
- [ ] Tests assert outcomes (DB state, response), not implementation details.

## 7. Style (only beyond Pint)

- [ ] PSR-12 / Laravel Pint preset — run Pint rather than commenting by hand.
- [ ] Consistent import ordering, no unused imports.
- [ ] Blade: logic-free templates; heavy logic moved to view models, components, or accessors.
