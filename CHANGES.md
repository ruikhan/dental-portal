# Odontogram Fix — What Changed

## ⚠️ NEW: Why the home screen icon showed a gray box with "0"

That placeholder (screenshot you sent) is what Android shows when it can't
find a real app icon to display. Tracing through the project, there were
**three separate reasons** this was happening, not one:

1. **The icon files never existed.** `manifest.json` pointed at
   `assets/icons/icon-192.png` and `icon-512.png` — but `DEPLOYMENT_GUIDE.md`
   itself flags these with `← CREATE THIS`, meaning they were placeholders
   that were never actually generated. The browser had nowhere to load an
   icon from.
2. **`partials/pwa-head.php` was never included anywhere.** The file exists
   and correctly links `manifest.json`, sets the theme color, and sets up
   the iOS home-screen meta tags — but no page's `<head>` actually
   `include`s it (I checked every page provided: `index.php`, `login.php`,
   `settings.php`, the customer/appointment/message pages — none of them
   reference it). Without that link tag, the browser doesn't know this is a
   PWA with a custom name/icon/display mode at all, so "Add to Home
   Screen" falls back to a generic bookmark shortcut — which is exactly
   what produced the plain gray "0" box in your screenshot.
3. **The service worker was never registered.** `sw.js` exists and is
   correctly written, but its registration code was sitting in a separate,
   unused file (`sw-registration-snippet.js`) with a comment saying to
   paste it into `app.js` — which never happened. Without an active service
   worker, most browsers won't treat the site as a fully installable app
   (custom icon + standalone window + offline support).

### What's fixed in this drop
- Generated real icon files at `assets/icons/`: `icon-192.png`, `icon-512.png`
  (rounded, "any" purpose), `icon-192-maskable.png`, `icon-512-maskable.png`
  (full-bleed, "maskable" purpose — safe from being cropped by Android's
  circular/squircle adaptive icon masks), `apple-touch-icon.png` (180×180,
  flat, for iOS), and `favicon-16.png` / `favicon-32.png` / `favicon.ico`
  for the browser tab. All navy→teal gradient with a white tooth glyph,
  matching the sidebar brand icon and the odontogram's own tooth silhouette.
- `manifest.json` — icon entries now point at real files, split into
  proper `any` vs `maskable` purposes (absolute `/assets/...` paths).
- `partials/pwa-head.php` — now also links the favicon; `apple-touch-icon`
  now points at the dedicated flat icon instead of the rounded 192 one.
- `assets/app.js` — service worker registration is now actually in here
  (merged in from the unused snippet file), so `sw.js` actually runs.
- `sw.js` — cache list updated to include the new icon files and
  `odontogram.css`/`.js`; cache version bumped so any stale cache from a
  half-working previous attempt gets cleared out.
- Added the `pwa-head.php` include to the three customer pages I maintain
  in this bundle (`create.php`, `edit.php`, `view.php`).

### What you still need to do
**Add one line to every other page's `<head>`** (I can't safely regenerate
pages I haven't been given full content for without risking silently
dropping something):
```php
<?php include 'partials/pwa-head.php'; ?>        <!-- from a root page, e.g. index.php, login.php -->
<?php include '../partials/pwa-head.php'; ?>     <!-- from customers/, appointments/, messages/, etc. -->
```
Put it right after the `<meta name="viewport">` line, and remove any
existing standalone `<meta name="theme-color" ...>` line on that page
(pwa-head.php already sets it — no need to duplicate).

**Remove the old broken shortcut and reinstall.** The gray "0" icon on your
phone is already saved locally — deploying this fix won't retroactively
repair it. Long-press → remove/uninstall it, then reopen the site in
Chrome and use "Add to Home Screen" / "Install app" again once the fix is
live.

**Confirm HTTPS.** PWA install (manifest + service worker) only works over
HTTPS or `localhost`. If you're on the Render deployment from
`DEPLOY_TO_RENDER.md`, that's already HTTPS by default — just flagging it
in case you're testing on a plain HTTP host somewhere.

---



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

## Responsive / mobile pass
- **Fixed a real mobile bug:** the read-only per-tooth table on the patient
  view page was built with the app's global `.dp-table` class, and
  `style.css` has a blanket rule that hides every `.dp-table` below 768px
  (it expects a paired `.mobile-card-list`, which this table never had).
  Net effect: on any phone or tablet, the whole table would silently
  disappear. Fixed by giving it its own class (`.tooth-detail-table`) with
  self-contained styling, wrapped in `.table-wrap` so it scrolls
  horizontally instead of vanishing.
- **Chart sizing** now has explicit breakpoints instead of one `clamp()`:
  tablets (≤768px) shrink tooth width; phones (≤480px) tighten row/legend
  spacing and stack the toolbar (badges above, full-width Clear All button
  below — easier to tap than a squeezed inline button); small phones
  (≤400px) shrink teeth to a fixed 20px and drop the per-tooth editor to a
  single column.
- **Swipe hint:** below 400px the full 16-tooth arch still needs horizontal
  scroll even at minimum size — a small "Swipe to see all teeth" hint now
  appears under the chart on those screens only (hidden everywhere else).
- **iOS momentum scrolling** (`-webkit-overflow-scrolling: touch`) on the
  chart's scroll container.
- **Landscape phones** (short viewport height) get reduced vertical padding
  so the chart doesn't force excessive scrolling in landscape mode.
- No changes were needed to the surrounding page chrome (sidebar, topbar,
  forms, patient hero, messages panel) — `style.css` already had solid
  breakpoints for those; this pass focused on the odontogram component
  itself and the one table it introduced.

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
