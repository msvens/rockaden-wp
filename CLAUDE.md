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
- Colors: dark blue (#1e3a5f) primary, gold (#c9a84c) accent
- Templates: index, single, page, archive
- Parts: header (dark blue nav bar), footer
- Patterns: hero, news-grid
- Dark mode: CSS custom properties + localStorage toggle in assets/js/dark-mode.js

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

### Phase 3: Admin Training UI — TODO
- Port training React components from rockaden2 to @wordpress/components
- Build training manager SPA (group list → group detail → session detail)
- Wire up @wordpress/api-fetch for REST calls

### Phase 4: Theme — TODO
- Complete theme.json, header/footer refinement
- Dark mode toggle integration
- Block patterns styling

### Phase 5: Gutenberg Blocks — TODO
- Calendar block (server render + React hydration)
- Standings block (server render)
- Training Group block

### Phase 6: Polish — TODO
- Swedish .po/.mo translations
- WP-CLI seed command (training groups, participants, sessions, events — needed for quick setup after wp-env destroy)
- Documentation

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
