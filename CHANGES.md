# Odontogram Fix — What Changed

## Files in this drop
```
db_conn.php
migration_odontogram_v2.sql
assets/odontogram.js
assets/odontogram.css
customers/create.php
customers/edit.php
customers/view.php
```
Drop each into the matching path in your project, overwriting the old version.

## Install steps
1. Run `migration_odontogram_v2.sql` against your live database. It's
   idempotent (checks `information_schema` first), so it's safe even if
   `migration_odontogram.sql` was already partially applied.
2. Replace the 6 code files above.
3. Hard-refresh (or bump a cache-busting query string on the CSS/JS links)
   since browsers aggressively cache `.css`/`.js`.

## Bugs fixed
1. **Function name mismatch.** `create.php`/`edit.php`/`view.php` called
   `initOdontogram()` / `renderReadOnlyOdontogram()`, which don't exist —
   only `initOdontogramAdvanced()` / `renderReadOnlyOdontogramAdvanced()`
   are exported. The chart silently failed to render. Fixed by calling the
   real names with the right argument list.
2. **Data format mismatch.** `odontogram.js` writes `teeth_data` as JSON;
   `odonto_counts()` in `db_conn.php` was parsing it as CSV, so
   `tooth_upper`/`tooth_lower` were being computed as 0 almost every time.
   Fixed: `odonto_counts()` now `json_decode`s first, with CSV kept only as
   a fallback for any pre-existing legacy rows.
3. **Missing details container.** The chart markup in `create.php`/`edit.php`
   never included a details container element, so even with the right
   function name, the per-tooth status/shade/size/notes editor had nowhere
   to render. Added `#odontogramDetails` / `#viewOdontogramDetails`.
4. **Schema not applied.** `dental_portal_db.sql` doesn't have the
   `teeth_data` column yet — the migration file existed but was never run
   against the live DB.

## UI/UX additions
- **Status-colored teeth.** Previously every selected tooth looked
  identical regardless of status — the CSS classes JS was adding
  (`status-planned`/`status-inprogress`/`status-completed`) had no rules.
  Now: gold = planned, blue = in progress, green = completed, visible
  directly on the chart.
- **Legend** under the chart explaining those colors (plus a "tap to
  toggle" hint on the editable version).
- **Live count badges** (Upper / Lower / Total) next to the per-tooth
  editor, always in sync as teeth are added/removed — no more relying on
  scrolling back up to a separate summary line.
- **Clear All** button with a confirm step, for starting over without
  clicking every tooth again.
- **`.tooth-status-pill`** — used by the read-only per-tooth table on the
  patient view page but never actually styled before; now color-coded to
  match the chart.
- Slightly larger touch targets on the tooth buttons (44px min height) for
  mobile.

## One decision still open (not changed here)
`dental_services.tooth_shade` / `tooth_size` are top-level single fields,
while the odontogram now stores shade/size **per tooth** inside
`teeth_data`. I kept the top-level fields in the form (relabeled "Overall
Shade/Size — optional quick reference") rather than removing them, since
dropping them would silently lose data entry capability that existed
before. Worth deciding later whether:
- they stay as an optional summary label, or
- they get removed entirely in favor of always reading per-tooth detail.

No other pages need changes — `appointments/list.php`, `customers/list.php`,
and the analytics dashboard all read `tooth_upper`/`tooth_lower` off
`dental_services`, so they'll just start showing correct numbers once the
migration and `odonto_counts()` fix are live.
