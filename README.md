# Rector UI

Rector UI is a local web interface for Rector.

It is being built to make PHP upgrades and large-scale refactors easier to review in the browser instead of as one massive CLI diff.

## Installation

```bash
composer require --dev rector-ui/rector-ui
```

## Usage

Start the local server from your project:

```bash
vendor/bin/rector-ui
```

You can also pass host and port options:

```bash
vendor/bin/rector-ui --host=127.0.0.1 --port=8080 --no-open
```

## Local Development

For normal package usage, Rector UI serves the compiled frontend bundle.

For local frontend development with hot reload:

1. In the consuming project, run Rector UI in dev mode:

```bash
RECTOR_UI_DEV=1 vendor/bin/rector-ui --no-open --port=8080
```

2. In the `rector-ui` repository, start the Vite dev server:

```bash
cd frontend
npm run dev
```

3. Open the Vite URL in your browser:

```bash
http://127.0.0.1:5173
```

The Vite app will hot reload frontend changes and proxy `/api/*` requests to the PHP server running inside the real project.

For realistic testing, this is the preferred development workflow. Run Rector UI from the consuming application so the backend is operating against the actual project you want to inspect.

If you are developing Rector UI in this repository directly, you can also run:

```bash
composer dev
```

## Compatibility

Rector UI is currently targeting PHP `^7.3 || ^8.0` for the package itself.

## Vision

Rector UI is intended to become a local-first review layer for PHP upgrades and framework migrations:

- run Rector in dry-run mode
- inspect results in the browser
- group changes by rule or area
- review incrementally instead of all at once
- add commit and progress workflows on top

## Contributing

This project will be open source, but pull requests are not being accepted right now.
