# rockaden-wp

WordPress plugin and block theme for **SK Rockaden** chess club (Stockholm).

Manages training groups, sessions with round-robin tournaments, a calendar with recurring events, and integrates with the Swedish Chess Federation (SSF) member database.

## Project structure

Monorepo with two packages managed by **pnpm workspaces**:

```
rockaden-wp/
├── plugin/          rockaden-chess   — WordPress plugin (PHP + React)
│   └── js/shared/   Shared TypeScript utilities (roundRobin, translations, etc.)
└── theme/           rockaden-theme   — WordPress block theme (FSE)
```

### plugin/ (`rockaden-chess`)

WordPress plugin with a PHP backend and React frontend:

- **Custom Post Types**: `rc_training_group`, `rc_training_session`, `rc_event`
- **REST API** (`/wp-json/rockaden/v1/`):
  - `GET /training-groups` — list all groups
  - `GET /training-groups/{id}` — single group with participants
  - `POST /training-groups/{id}/participants` — add participant
  - `DELETE /training-groups/{id}/participants/{pid}` — deactivate participant
  - `GET|POST /training-groups/{id}/sessions` — list/create sessions
  - `PUT /training-sessions/{id}/attendance` — save attendance
  - `PUT /training-sessions/{id}/games/{idx}` — save game result
  - `PUT /training-sessions/{id}/notes` — save session notes
  - `GET /events?month=YYYY-MM` — calendar events (with recurring expansion)
  - `GET|POST /ssf/{path}` — proxy to member.schack.se (solves CORS)
- **Admin pages**: React training manager, settings page (SSF club ID)
- **Gutenberg blocks**: calendar, standings, training-group (skeletons)
- **Shared TypeScript** (`plugin/js/shared/`):
  - **roundRobin** — Berger method pairing generator and standings computation
  - **expandRecurringEvents** — Expands weekly/biweekly events into individual occurrences
  - **translations** — English/Swedish translations for training and calendar UI
  - **types** — CalendarEvent, EventCategory, Language

### theme/ (`rockaden-theme`)

Block theme (Full Site Editing) with theme.json v3:

- Colors: dark blue (#1e3a5f) primary, gold (#c9a84c) accent
- Templates: index, single, page, archive
- Parts: header, footer
- Patterns: hero, news-grid
- Dark mode via CSS custom properties + localStorage toggle

## Prerequisites

- **Node.js** 22+
- **pnpm** 10+
- **PHP** 8.1+ with Composer
- **Docker** with the `docker compose` plugin
  - If using Colima: `brew install docker-compose` and ensure `cliPluginsExtraDirs` in `~/.docker/config.json` points to `/opt/homebrew/lib/docker/cli-plugins`

## Getting started

```bash
# Install JS dependencies
pnpm install

# Install PHP dev dependencies (linting/analysis)
composer -d plugin install

# Build plugin JS
pnpm build

# Start the local WordPress environment
npx @wordpress/env start
```

WordPress will be available at **http://localhost:8888**.

### First-time setup

After starting wp-env for the first time you need to enable pretty permalinks for the REST API to work:

```bash
npx @wordpress/env run cli wp rewrite structure '/%postname%/'
npx @wordpress/env run cli wp rewrite flush --hard
```

### Admin login

| URL | Username | Password |
|-----|----------|----------|
| http://localhost:8888/wp-admin/ | `admin` | `password` |

### Authenticated API access

Write endpoints require authentication. Create an application password:

```bash
npx @wordpress/env run cli wp user application-password create 1 myclient --porcelain
```

Then use it with curl:

```bash
curl -u "admin:<app-password>" \
  -H "Content-Type: application/json" \
  -X POST http://localhost:8888/wp-json/rockaden/v1/training-groups/5/participants \
  -d '{"id":"p1","name":"Anna Andersson"}'
```

## Development

```bash
# Watch mode — rebuilds plugin JS on file changes
pnpm dev

# PHP changes take effect immediately (symlinked by wp-env)
```

### Code quality

```bash
# JS/TS: typecheck + lint + build
pnpm run check

# PHP: PHPStan (level 6) + PHP_CodeSniffer (WordPress standard)
composer -d plugin lint:php

# Auto-fix PHP formatting
cd plugin && vendor/bin/phpcbf
```

CI runs both checks on every push/PR to `main` via GitHub Actions.

### Useful wp-env commands

```bash
# Stop the environment
npx @wordpress/env stop

# Destroy and recreate (loses data)
npx @wordpress/env destroy

# Run WP-CLI commands
npx @wordpress/env run cli wp plugin list
npx @wordpress/env run cli wp post-type list

# Enable debug logging
npx @wordpress/env run cli wp config set WP_DEBUG_LOG true --raw

# View debug log
npx @wordpress/env run cli cat /var/www/html/wp-content/debug.log
```

### Testing the REST API

```bash
# List training groups
curl http://localhost:8888/wp-json/rockaden/v1/training-groups

# Get events for a month
curl http://localhost:8888/wp-json/rockaden/v1/events?month=2026-03

# SSF proxy (Swedish Chess Federation API)
curl http://localhost:8888/wp-json/rockaden/v1/ssf/organisation/federation
curl http://localhost:8888/wp-json/rockaden/v1/ssf/organisation/district/clubs/5821
```

### Creating test data

```bash
# Create a training group
npx @wordpress/env run cli wp post create \
  --post_type=rc_training_group \
  --post_title="Nybörjargruppen" \
  --post_status=publish

# Create an event
npx @wordpress/env run cli wp post create \
  --post_type=rc_event \
  --post_title="Klubbkväll" \
  --post_status=publish
```

## Build and package

```bash
# Build plugin JS
pnpm build

# Create distribution zip files
pnpm package
# Output: dist/rockaden-chess.zip, dist/rockaden-theme.zip
```

## Conventions

- **PHP**: PSR-4 namespacing under `Rockaden\`, WordPress coding standards (snake_case, Yoda conditions, doc comments), meta keys prefixed `rc_`
- **REST API**: namespace `rockaden/v1`, `edit_posts` capability for writes, public reads
- **Data storage**: participants, attendance, games stored as JSON strings in `post_meta`
- **TypeScript**: compiled via wp-scripts (Babel), WordPress ESLint rules

## SSF integration

The plugin proxies requests to the Swedish Chess Federation API at `member.schack.se` to avoid CORS issues in the browser. SK Rockaden's club ID is **38464** (district: Stockholms SF, 5821).
