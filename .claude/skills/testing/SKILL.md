---
name: testing
description: How to run and write the Pest suite — it runs inside docker against MySQL, and it is the only gate on deploying to production. Use before changing app code, when a test fails, or when adding coverage.
---

# Testing

The suite is the **only** gate on production. PHPStan and Pint are deliberately not gates
(`composer phpstan` reports ~300 level-6 errors today, mostly Pest higher-order syntax). So a green
suite is what "safe to ship" means here — run it before every push.

## Running it

Tests need MySQL, so they run in docker, never on the host:

```bash
cd ~/Desktop/Projects/littlesteps-web
docker compose up -d database php          # if not already running
docker compose exec -T php php artisan test
```

`phpunit.xml` pins `DB_CONNECTION=mysql` and `DB_DATABASE=testing`; SQLite will not do, because
`Progress/Metrics` uses `DATE_ADD(... INTERVAL ... MONTH)` in raw SQL. Create the schema once with
`docker compose exec -T database mysql -uroot -psecret -e "CREATE DATABASE IF NOT EXISTS testing;"`.

Filter while iterating: `… artisan test --filter=ChapterRules`, and `--stop-on-failure` is what CI
uses.

## Writing it

Pest, `RefreshDatabase`, feature tests only — `tests/Unit` is empty by design; this codebase is
tested through its HTTP surface. Helpers in `tests/Pest.php`:

| Helper | Gives you |
|---|---|
| `family(ageMonths: 6)` | `[$user, $child]` with the whole catalogue provisioned, signed in as the parent |
| `viewer($child)` | a member who may read but not write |
| `editor($child)` | a second parent who may add memories but does not own the child |
| `console()` | signs in at `/admin` — call it **after** `family()`, which switches the guard |

`family()` provisions the real catalogue, so a child has genuine chapters and milestones. Reach for
known rows by name (`$child->milestones()->where('name', 'Birth Day')->first()`) rather than by id.

Name tests as sentences about the rule, matching the house style — *"it lets a guided chapter the
child has grown past be deleted"*, *"it refuses to delete a chapter that has already been finished"*.
The name is the specification; if you cannot write it as a sentence, the rule is not clear yet.

Assert the rule, not the plumbing: `assertJsonPath('data.abilities.rename', true)` says what a parent
may do; `assertOk()` alone says almost nothing.

## When a payload changes

`ProvisioningTest` asserts the **key set** of a chapter and a milestone on purpose, so renaming a
field fails it loudly. That is the point — update it, and check the app's `src/types/` in the same
change. Tests asserting an old rule should be rewritten to assert the new one rather than deleted; a
disappearing test is a rule nobody is watching any more.

## Touching production data

Don't, unless asked. If you probe live to verify a deploy, create a guest through `/auth/guest` and
remove it afterwards with `DELETE /auth/me` — and say in your report that you did.
