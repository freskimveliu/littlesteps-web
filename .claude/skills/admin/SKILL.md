---
name: admin
description: The Inertia + Vue admin panel — the Ui* component kit, page layout conventions, and how catalogue screens are wired. Use before building or changing anything under resources/js/pages/Admin or resources/js/components.
---

# Admin panel

Laravel + Inertia + Vue 3 `<script setup>` + Tailwind. Pages in `resources/js/pages/Admin/`, one
directory per resource (`Chapters/`, `Milestones/`, `Categories/`, `Levels/`, `Prompts/`, `Trophies/`,
`Users/`, `Children/`, `Settings/`), served by single-action controllers under
`app/Http/Controllers/Admin/`.

This is the **catalogue** editor — the templates every new child is provisioned from. It is not the
app's data: editing a `Chapter` here does not touch any `ChildChapter` already copied onto a child.
Say so when a change looks like it should ripple and will not.

## Reuse the kit first

Everything in `resources/js/components/ui/` is prefixed `Ui`. Check there before writing a component:

| Need | Use |
|---|---|
| Page title + actions | `UiPageHeader` |
| A panel with a title | `UiCard` |
| Tables | `UiTable`, `UiTableHeader`, `UiTableRow`, `UiTableCell`, `UiSortableTableHeader`, `UiSortIcon` |
| Form fields | `UiFormControl` wrapping `UiInput` / `UiSelect` / `UiTextarea` / `UiSwitch` |
| Save/cancel row | `UiFormActions` |
| Buttons | `UiButton`, `UiActionButton` (icon-only, in table rows) |
| Destructive confirm | `UiConfirmationModal` — never a bare `confirm()` |
| Anything modal | `UiModal` |
| Nothing yet | `UiEmptyState` |
| Search / filter | `UiSearchInput`, `UiFilterPopover` |
| Paged lists | `UiPagination` |
| Feedback | `UiToast`, `UiSpinner` |
| Small stats | `UiBadge`, `UiSplitBar`, `UiBarChart` |

Pages sit inside `AdminLayout`; its sidebar entries are the map of what exists.

## Conventions

- `<script setup lang="ts">` with typed `defineProps`. Index pages take a typed row interface — see
  `Admin/Chapters/Index.vue` (`milestones_count`, `sort-key`).
- Write through Inertia (`router.post/patch/delete`), not `fetch` — the API in `routes/api.php` is the
  app's, not the panel's.
- Counts come from the controller as `withCount`, exposed as `<relation>_count`, and are what
  `sort-key` sorts on.
- Tailwind only, matching the app's restraint: the brand is `primary` with opacity modifiers, `ink`
  for text. No new shades.
- Copy is plain and parent-facing even here — *"A chapter needs at least ten visible milestones before
  a parent can finish it"* explains the rule rather than naming the setting.

## Settings

`Admin/Settings` edits `AppSetting` rows keyed by `AppSettingKey`, which is what `Support/Limits`
reads at runtime — daily caps, `max_custom_milestones_per_chapter`,
`min_milestones_to_complete_chapter`. Changing one takes effect immediately for every child, with no
deploy. Treat it as production data, not configuration.

## Landing page

`resources/js/pages/Landing.vue` plus `components/landing/` is public marketing, not admin. It
mirrors app vocabulary — **chapters** hold **milestones** — and the phone mockups there
(`ScreenHome`, `ScreenMap`, `PhoneTabBar`) should match the real app's tabs. Keep them in step when
the app's navigation changes.
