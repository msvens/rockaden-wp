# rockaden-wp

WordPress plugin + block theme for SK Rockaden chess club (Stockholm).

## Project Overview

Monorepo with two packages:
- **`plugin/`** (`rockaden-chess`) — WordPress plugin. PHP backend (CPTs, REST API, admin pages) + React frontend (wp-scripts/webpack). Shared TypeScript utilities (roundRobin, translations, expandRecurringEvents, calendar types) live in `plugin/js/shared/`.
- **`theme/`** (`rockaden-theme`) — WordPress block theme. theme.json design tokens, templates, dark mode.

Package manager: **pnpm** (workspace monorepo).

## Origin

Ported from `/Users/msvens/projects/github.com/msvens/rockaden2` (PayloadCMS + Next.js POC). The `plugin/js/shared/` directory contains framework-agnostic logic copied from that project. The plugin reimplements the backend in PHP and will port the React components to use `@wordpress/components` instead of Tailwind.

## Architecture

### Plugin (rockaden-chess)
- **PSR-4 autoloader** in `rockaden-chess.php`, namespace `Rockaden\`
- **Custom Post Types**: `rc_training_group`, `rc_training_session`, `rc_event` — all data stored as post meta (JSON strings for arrays)
- **REST API** (`/wp-json/rockaden/v1/`):
  - `ssf/{path}` — Proxy to member.schack.se (solves CORS)
  - `training-groups`, `training-groups/{id}`, participants, sessions CRUD
  - `training-sessions/{id}/attendance`, `/games/{idx}`, `/notes`
  - `events?month=YYYY-MM` — with server-side recurring event expansion
- **Admin pages**: React training manager (mount point in TrainingAdmin.php), Settings page (SSF club ID)
- **Gutenberg blocks**: calendar, standings, training-group (skeleton editors, block.json registered)
- **wp-scripts** build with custom webpack.config.js (5 entry points)
- **Shared TypeScript** (`plugin/js/shared/`):
  - `roundRobin.ts` — Berger method pairing generator + standings computation
  - `expandRecurringEvents.ts` — Expands recurring events (weekly/biweekly) into individual occurrences
  - `translations.ts` — en/sv translations for training + calendar UI
  - `types.ts` — CalendarEvent, EventCategory, Language

### Theme (rockaden-theme)
- Block theme (FSE) with theme.json v3
- Colors: blue-600 (#2563eb) primary, gray palette (matching rockaden2 design)
- Typography: Geist Sans variable font, weight 300 headings, letter-spacing 0.025em
- Templates: index, single, page, archive
- Parts: header (white bg, fixed, uppercase nav, dark toggle), footer (minimal centered links)
- Patterns: hero, news-grid, page-with-sidebar, page-three-column
- Dark mode: `html.dark` class, localStorage `theme` key, system preference default, flicker-free inline script

## Development

```bash
pnpm install
npx wp-env start          # WordPress at http://localhost:8888 (admin/password)
pnpm dev                  # wp-scripts watch for JS hot-reload
# PHP changes are instant (symlinked by wp-env)
```

Requires Docker running.

## Build

```bash
pnpm build                # Builds plugin JS (wp-scripts)
pnpm package              # Creates dist/rockaden-chess.zip + dist/rockaden-theme.zip
```

## Implementation Status

### Phase 1: Project Setup — DONE
- Monorepo structure, pnpm workspace, .wp-env.json
- Shared TypeScript utilities in plugin/js/shared/
- Plugin skeleton: PHP entry point, autoloader, 3 CPTs with meta fields, full REST API (SSF proxy, training CRUD, events with recurring expansion), admin pages, 3 Gutenberg block definitions
- Theme skeleton: theme.json, templates, parts, patterns, dark mode
- Full build verified (`pnpm build` succeeds)

### Phase 2: Plugin Backend Testing — DONE
- All 3 CPTs register correctly (rc_training_group, rc_training_session, rc_event)
- All REST endpoints tested: training groups CRUD, participants, sessions, attendance, games, notes, events
- SSF proxy works against live member.schack.se (federation + district/clubs)

### Phase 3: Admin Training UI — DONE
- Training manager SPA with group list, group detail, session detail views
- CreateGroupModal with WP DateTimePicker, inline date pickers
- @wordpress/api-fetch for REST calls, @wordpress/components UI

### Phase 4: Theme — DONE
- Restyled to match rockaden2 design (Geist font, gray/blue palette, light weights)
- Fixed header with uppercase nav, sun/moon dark toggle
- Dark mode with system preference default, flicker-free inline script
- Card styles, sidebar nav, page layout patterns (2-col, 3-col)
- Minimal footer with centered links

### Phase 5: Gutenberg Blocks — DONE
- Calendar block (server render + React hydration)
- Standings block (server render)
- Training Group block

### Phase 6: Polish — DONE
- Define per-heading-level font sizes in theme.json (H1→xxx-large, H2→xx-large, H3→x-large, etc.) so headings are consistent without manual sizing
- Dark-mode-safe color strategy: consider disabling custom colors (`"color": { "custom": false }`) to force palette-only; document "always use palette colors" for content editors
- Evaluate adding page templates (e.g., page-with-sidebar.html) vs relying on patterns
- Swedish .po/.mo translations
- WP-CLI seed command (training groups, participants, sessions, events — needed for quick setup after wp-env destroy)
- Edit Group Modal: allow editing the linked event inline (change schedule, location, recurrence) — currently only group fields are editable
- Add Participant Modal: keep search box open after adding a participant so multiple can be added without reopening
- Define per-heading-level spacing/margins in theme.json or CSS so page content doesn't have excessive gap below the page heading
- Add block theme support for hiding page headings (useful for pages where the content starts with its own heading or doesn't need the default page title)
- Training group visibility controls: per-group toggles for showing/hiding participants list and tournament standings in the public view (privacy for junior groups, secret standings). Part of a larger effort around user roles and public vs. authenticated content.
- Documentation

### Phase 7: Polish Round 2
- Audit link styles for consistency: define clear rules for inline content links (blue, hover behavior), navigational links (post titles, nav, footer — muted/text color), and ensure all link contexts follow the same pattern across light and dark mode

## Key Files
- `plugin/rockaden-chess.php` — Plugin entry point + autoloader
- `plugin/src/Api/TrainingApi.php` — Main REST API (training CRUD)
- `plugin/src/Api/SsfProxy.php` — SSF proxy endpoint
- `plugin/src/Api/EventApi.php` — Calendar events with recurring expansion
- `plugin/webpack.config.js` — Custom wp-scripts entry points
- `plugin/js/admin/training-manager.tsx` — React admin app (placeholder)
- `plugin/js/shared/roundRobin.ts` — Tournament pairing logic
- `plugin/js/shared/index.ts` — Shared module barrel export
- `theme/theme.json` — Design tokens

## Conventions
- PHP: PSR-4 namespacing under `Rockaden\`, meta keys prefixed `rc_`
- REST: namespace `rockaden/v1`, editor capability for writes, public reads
- JSON meta: participants, attendance, games stored as JSON strings in post_meta
- TypeScript: wp-scripts handles compilation via Babel
