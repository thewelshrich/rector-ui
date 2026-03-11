# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Rector UI is a local web interface for reviewing Rector PHP upgrades and refactors in the browser instead of via CLI
diff output. It is a Composer-installable PHP package with a React frontend.

## Commands

### Backend (PHP)

```bash
composer install          # Install PHP dependencies
composer test             # Run all PHPUnit tests
composer smoke            # Run integration smoke test only
composer dev              # Start server in dev mode (proxies to Vite)
```

### Frontend (React + Vite)

```bash
cd frontend && npm install    # Install frontend dependencies
cd frontend && npm run dev    # Start Vite dev server (port 5173)
cd frontend && npm run build  # Build to ../public/
```

### Running the Application

```bash
vendor/bin/rector-ui                              # Production mode (serves built assets)
vendor/bin/rector-ui --host=127.0.0.1 --port=8080 # Custom host/port
RECTOR_UI_DEV=1 vendor/bin/rector-ui --no-open    # Dev mode (proxies to Vite)
```

## Architecture

### Backend (PHP 7.3+ / 8.0+)

- **`bin/rector-ui`** - CLI entry point; parses args and bootstraps server
- **`src/Server.php`** - ReactPHP async HTTP server
- **`src/HttpApplication.php`** - Request router: `/api/*` routes to ApiController, everything else to
  StaticAssetResponder
- **`src/ApiController.php`** - JSON endpoints: `/api/health`, `/api/meta`, `/api/project`, `/api/analysis`
- **`src/RectorAnalysisService.php`** - Runs `vendor/bin/rector process --dry-run --output-format=json` and parses
  output
- **`src/StaticAssetResponder.php`** - Serves compiled frontend from `public/` or proxies to Vite in dev mode
- **`src/ProjectContextDetector.php`** - Detects the consuming project's path, rector binary, and config

### Frontend (React + Vite + Tailwind v4 + shadcn/ui)

- **`frontend/src/main.jsx`** - App entry point; fetches `/api/health`, `/api/project`, manages analysis state
- **`frontend/src/components/analysis-shell.jsx`** - Root layout with sidebar + workspace
- **`frontend/src/lib/rector-analysis.js`** - `buildAnalysisModel()` parses Rector JSON into file tree with diff blocks
- **`frontend/src/components/ui/`** - shadcn/ui components (sidebar, button, card, etc.)
- **`frontend/vite.config.js`** - Proxies `/api/*` to `http://127.0.0.1:8080`, builds to `../public/`

### Development Workflow

For realistic testing with a real project's Rector setup:

1. Run `RECTOR_UI_DEV=1 vendor/bin/rector-ui --no-open --port=8080` from the consuming project
2. Run `cd frontend && npm run dev` from this repository
3. Open `http://127.0.0.1:5173` - Vite hot reloads frontend, proxies API to PHP server

## API Endpoints

| Endpoint        | Method | Description                                            |
|-----------------|--------|--------------------------------------------------------|
| `/api/health`   | GET    | Health check with PHP/app version                      |
| `/api/meta`     | GET    | Runtime info and Rector availability                   |
| `/api/project`  | GET    | Detected project context (path, rector binary, config) |
| `/api/analysis` | POST   | Run Rector dry-run, return JSON result                 |

## Node Version

Node 22 (see `.nvmrc`)
