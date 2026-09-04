# AGENTS.md

## Build & Test Commands

### Backend (PHP/Laravel)
```bash
cd backend
export DB_CONNECTION=sqlite DB_DATABASE=:memory:
php artisan test
```

### Frontend (TypeScript)
```bash
cd frontend
npx tsc --noEmit
npx ts-node scripts/check-enum-sync.ts
```

## Environment Setup

This project runs in a sandboxed cloud container where the environment resets between sessions. After each reset, install dependencies:

### PHP 8.2
```bash
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq php8.2-cli php8.2-mbstring php8.2-xml php8.2-sqlite3 php8.2-curl php8.2-gd php8.2-bcmath

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f composer-setup.php
cd backend && composer install --no-interaction --prefer-dist --no-progress --no-scripts
```

### TypeScript
```bash
cd frontend && npm install
```

## Headless Browser (optional, for render/a11y verification)

Chrome/Chromium cannot execute pages in this sandbox (the renderer SIGTRAPs), so use Firefox (AArch64) driven by Puppeteer's WebDriver BiDi or standalone geckodriver. The tooling lives in `$HOME/headless-tools` and is reinstalled with:

```bash
bash $HOME/headless-tools/install-headless.sh
```

- `node $HOME/headless-tools/shot.mjs <url> 1440,768,430 /tmp/shots` — Puppeteer screenshots + overflow/console-error report (default engine Firefox; `HLT_BROWSER=chrome` opts into the Chrome for Testing build).
- `$HOME/headless-tools/fireshot.sh <url> [out.png] [width] [height]` — one-off Firefox CLI screenshot.
- `$HOME/headless-tools/geckodriver` — raw WebDriver driver for the repo's `/tmp/opencode/verify*.js`-style probes.
- Note: local API mocks (MSW) do not engage under headless Firefox — serve real/stub API responses separately (`/tmp/opencode/stub-api.js` mirrors the backend contracts).

## Skills

Five agent skills are vendored in `.agents/skills/` (pinned via `skills-lock.json`):

| Skill | Source | Use |
|---|---|---|
| `typescript-advanced-types` | `wshobson/agents` | Advanced TS type patterns, generics, conditional/mapped/template-literal types, utility types |
| `browser-testing-with-devtools` | `addyosmani/agent-skills` | Browser testing/DOM/console/network/performance inspection |
| `playwright-cli` | `microsoft/playwright-cli` | Driving the Playwright browser tooling via CLI commands |
| `frontend-design` | `anthropics/skills` | Frontend UI design guidance |
| `web-design-guidelines` | `vercel-labs/agent-skills` | UI review against Web Interface Guidelines |

- Invoke a skill for relevant agent-friendly tasks (e.g. `frontend-design` or `web-design-guidelines` for frontend work; `playwright-cli` / `browser-testing-with-devtools` for browser testing). The `skills` tool lists them as available skills.
- To refresh or reinstall from upstream: `npx skills add <owner>/<repo> --skill <name>` in project mode (writes `.agents/skills/` + `skills-lock.json`).
- `skills-lock.json` `computedHash` = SHA-256 over the sorted relative-file paths + contents of each skill folder (excludes `.git`, `node_modules`).

## Lint & Type Check Commands
- `php artisan test` — runs all 95 PHPUnit tests (175 assertions)
- `npx tsc --noEmit` — TypeScript type checking (exit 0 = pass)
- `node scripts/check-enum-sync.ts` — verifies PHP and TypeScript enums are in sync
