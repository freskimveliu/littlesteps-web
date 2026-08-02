---
name: api
description: Conventions for the v1 mobile API — single-action controllers, the ApiResponse envelope, Resources, and the abilities pattern that keeps permission rules on the server. Use before adding or changing any endpoint under routes/api.php, any Http/Resource, or anything the app reads.
---

# The v1 API

The only consumer is the LittleSteps app in `~/Desktop/Projects/littlesteps` (`src/services/api/`,
types in `src/types/`). There is no versioning story beyond the `v1` prefix and no deprecation
window — **a payload change is a lockstep change**, so edit both repos in the same sitting and deploy
the server first.

## Shape

One class per endpoint, `__invoke` only, named `<Verb><Thing>Controller` under
`Http/Controllers/Api/V1/<Group>/`. Routes live in `routes/api.php`; keep the group order there.

Every response goes through `ApiResponse`:

```php
return ApiResponse::success(new ChildChapterResource($chapter), 'Saved.');
return ApiResponse::noContent();                       // 204, for deletes
```

which wraps as `{ data, message, code }` with nulls stripped. Never `response()->json()` directly.

Payload keys are **camelCase** (`monthsFrom`, `milestonesRecorded`); request keys are **snake_case**
(`child_chapter_id`, `move_milestones_to`). That asymmetry is deliberate — requests mirror the
columns, responses mirror the app's types.

## Permissions belong here, not in the app

Every chapter and milestone carries an `abilities` object, computed in
`ChildChapter::abilities()` / `ChildMilestone::abilities()`:

```php
'abilities' => ['rename' => true, 'reorder' => true, 'delete' => $this->isDeletable(), …]
```

The app renders the buttons it is handed and decides nothing. **Adding a capability is one key in
that array** — never a new top-level boolean, and never a rule reimplemented client-side. Keep pure
state (`isUnlocked`, `isCompleted`, `isHidden`, `isLocked`, `isRecorded`) separate: state describes
the row, abilities grant an action.

`is_editable` records only *where a row came from* — catalogue or parent. It must never gate what may
be done to it: a guided chapter or milestone can still be renamed, moved and deleted, because the map
belongs to the parent.

## Where logic goes

- **Action** (`app/Actions/<Group>/`) — anything that writes across more than one table or awards
  something. Constructor-injected collaborators, one `handle()`, a docblock saying *why*. See
  `Actions/Chapters/CompleteChapter.php`.
- **Support** (`app/Support/`) — cross-cutting rules with no side effects: `Limits` (daily caps, what
  may be completed), `Progress/Metrics` (trophy counters).
- **Controller** — authorize, validate, delegate, respond. Nothing else.
- **Policy** — `ChildPolicy` draws the line between `contribute` (add memories) and `update` (own the
  child). Start every child-scoped controller with `$this->authorize(...)` plus
  `abort_unless($model->child_id === $child->id, 404)`.

Enums are backed string enums in `app/Enums/` and validated with `Rule::enum(...)`. Adding a case is
additive; removing one breaks stored rows, so check with
`Model::select('col')->distinct()->pluck('col')` first.

## Before you finish

Run the suite (see the `testing` skill) — a payload rename will break `ProvisioningTest`, which
asserts the key set on purpose. Then update the app's `src/types/` to match, or it silently reads
`undefined`.
