# Code Review — <scope, e.g. "OrderController + checkout flow" or "branch feature/invoices">

**Reviewed:** <date> · **Laravel** <x> · **PHP** <x> · **Tests:** <Pest/PHPUnit, pass/fail before review>

## Summary

| 🔴 Critical | 🟠 Important | 🟡 Suggestion | 💡 Learning |
|---|---|---|---|
| n | n | n | n |

<2–3 sentences: overall state of the code and the single most important thing to fix.>

## What you did well

- <Specific praise with a location, e.g. "Good use of route model binding in `InvoiceController@show` — keeps lookups and 404s consistent.">
- <…>

## Findings

### 1. 🔴 <Short title, e.g. "Any user can delete any invoice">

**Where:** `app/Http/Controllers/InvoiceController.php:42` (also `:88`)

**What's wrong:** <one or two sentences describing the problem concretely.>

**Why it matters:** <the lesson — what can go wrong in production and the principle behind it.>

**Before**
```php
public function destroy($id)
{
    Invoice::find($id)->delete();
    return back();
}
```

**After**
```php
public function destroy(Invoice $invoice)
{
    $this->authorize('delete', $invoice);

    $invoice->delete();

    return back()->with('status', 'Invoice deleted.');
}
```

---

### 2. 🟠 <title>

…

## 💡 Learning notes

- **<Concept>** — <short explanation and a link to the relevant Laravel docs page if useful.>

## Left for the developer

- <Anything not changed because it needs a product/team decision, e.g. renaming a public route, a schema change on a large table.>

---

## Changes applied

| # | Finding | Change | Verified |
|---|---|---|---|
| 1 | Any user can delete any invoice | Added `InvoicePolicy@delete`, route model binding, test `it_forbids_deleting_other_users_invoices` | ✅ tests, ✅ phpstan, ✅ pint |
| 2 | … | … | … |
