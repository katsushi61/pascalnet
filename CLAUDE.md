# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

**PascalNet** (repo name `venonet-app`, host `app.venonet.jp`) is a tiny PHP app
portal: a thin PHP auth/routing shell serves a directory of independent,
single-file HTML/JS apps under `apps/`. There is no build system, package
manager, or test suite — PHP files run as-is and each app is a static HTML
file opened directly in the browser or served through the auth gate.

## Commands

There is no build/lint/test tooling in this repo (no `package.json`,
composer, or CI config). Workflow is:

- **Local check of an app**: open `apps/<app-name>/index.html` directly in a
  browser to verify it works standalone (no server required for the app
  itself).
- **Local check of the PHP shell**: run `php -S localhost:8000` from the repo
  root (requires a `config.local.php`, see below) and browse
  `http://localhost:8000/`.
- **Deploy**: `git push` to `main`. A webhook on the `app.venonet.jp` server
  (WADAX) auto-pulls and deploys within seconds — there is no separate
  build/deploy command. See "Branch & deploy rules" below.

## Architecture

### Two layers that don't mix

1. **Auth/portal shell** (PHP, repo root) — `index.php`, `login.php`,
   `logout.php`, `admin.php`, `setup.php`, `gate.php`, `auth.php`, `db.php`,
   `apps_lib.php`, `.htaccess`, `schema.sql`, `config.local.php.example`.
   This is infrastructure, not an "app" — it's exempt from the one-app-one-file
   rule below and should rarely need changes when just adding/updating apps.
2. **Apps** (`apps/<app-name>/index.html`) — self-contained single-file HTML
   apps with inline CSS/JS. Each is independent; they never reference each
   other or share server-side code. Persistence is client-side only
   (`localStorage` via app-specific `saveState`/`loadState` helpers), since
   there is no per-app backend.

### Request flow for an app

`app.venonet.jp/apps/<app-name>/` → Apache `.htaccess` rewrite
(`^apps/([a-z0-9-]+)/?$` → `gate.php?app=$1`) → `gate.php` validates the slug,
calls `require_login()` from `auth.php`, then `readfile()`s
`apps/<app-name>/index.html` and streams it out. `apps/.htaccess` separately
denies **direct** `.html` access (`Require all denied`), so an app's HTML can
only ever be reached through `gate.php`'s login check — there is no way to
view an app's source without authenticating first.

### Portal listing (`index.php` + `apps_lib.php`)

`index.php` calls `list_all_apps()`, which globs `apps/*/index.html`, and for
each file whose parent directory name matches `^[a-z0-9-]+$`, parses the
leading `<!-- App: ... Created: ... Description: ... -->` HTML comment (see
app-file header format below) to build the card grid. An app with a
malformed slug or missing/malformed header comment is silently skipped or
shown with fallback title — so the header comment format is load-bearing,
not decorative.

### Auth layer (`auth.php`, `db.php`, `schema.sql`)

Single flat `users` table (`id`, `username`, `password_hash`, `display_name`,
`is_admin`). No per-app permissions model: any logged-in user can reach every
app in `apps/`. Sessions are PHP native sessions with `httponly`/`SameSite=Lax`
cookies; `csrf_token()`/`csrf_verify()` guard the one state-changing form
(`admin.php`'s add-user action). `setup.php` is a one-time bootstrap that
only works while the `users` table is empty (creates the first admin, then
403s on subsequent visits). `db.php` reads DB credentials from
`config.local.php` (gitignored, copy from `config.local.php.example` and
deploy it directly on the server — never commit it).

## App authoring & deployment conventions

These rules govern any work that creates or updates a file under `apps/`.
They are strict because a `git push` to `main` deploys to production within
seconds (see "Branch & deploy rules") — there is no staging environment.

### 1. Building an app

- Output is a **single HTML file** with all CSS/JS inlined. Do not split into
  multiple files.
- External resources may be loaded **only** from `cdnjs.cloudflare.com`.
- Never use Claude-artifact-only APIs (`window.storage`, `sendPrompt()`,
  etc.) — this is a plain static file served to a real browser, not an
  artifact sandbox. For persistence, implement your own `saveState(data)` /
  `loadState()` backed by `localStorage`.
- **`localStorage` keys must be namespaced** `<app-name>:key-name` (e.g.
  `expense-tracker:records`) so apps never collide with each other's storage.
- File must start with a config block:
  ```html
  <script>const CONFIG = { apiBase: "", basePath: "" };</script>
  ```
- File must start with a metadata comment in this exact key format (parsed by
  `apps_lib.php::extract_app_meta()` for the portal listing —
  `Description:` may wrap across multiple lines):
  ```html
  <!--
    App: <app-name>
    Created: YYYY-MM-DD
    Description: 簡単な説明文。
  -->
  ```
- The app name (used as the directory name) must be **kebab-case**
  (lowercase alphanumeric + hyphens only) — no Japanese, spaces, or
  uppercase. `apps_lib.php` filters out any directory that doesn't match
  `^[a-z0-9-]+$`.
- No build step: the file must work as-is when opened in a browser.

### 2. File placement

- Path is always `apps/<app-name>/index.html` — **the filename must be
  `index.html`**, never `<app-name>.html`. (Apache serves
  `apps/<app-name>/` by folder name; without `index.html` present it 403s.)
- One app = one file. Don't add sibling files to an app's directory.
- Public URL after deploy: `app.venonet.jp/apps/<app-name>/`.
- **Before creating a new app, check `apps/` for an existing directory with
  the same name.** If one exists, read its header comment (App/Created/
  Description) and compare it against the current request:
  - Same purpose → treat as an update, overwrite freely.
  - Different purpose → do **not** silently overwrite; ask the user how to
    proceed (e.g. "`<app-name>` is already used for a different app (...).
    How would you like to proceed?").

### 3. Branch & deploy rules (fully automatic)

- The server only watches `main`. There are no per-app branches — always
  work directly on `main` for app changes (commit and push straight to
  `main`).
- A GitHub webhook on `main` triggers an immediate auto pull+deploy on the
  WADAX server — no manual "pull now"/"deploy now" step exists or is needed.
  A push is live on `app.venonet.jp` within seconds to tens of seconds.
- Because push == near-instant production release, **all verification must
  happen before pushing** (see self-check list below).
- Commit messages: concise Japanese, describing what changed (e.g. `add:
  expense-tracker 初版`, `fix: 合計計算のバグ修正`).
- After pushing, tell the user only: "pushしました。数十秒後に
  `app.venonet.jp/apps/<app-name>/` で確認できます。" Don't tell them to open
  WADAX/GitHub admin screens.

### 4. Pre-deploy self-check

Run through this before every push, since push is effectively instant
production release with no other safety net:

- [ ] File path is `apps/<app-name>/index.html` (filename is exactly
      `index.html`)
- [ ] `<app-name>` is kebab-case (alphanumeric + hyphens only)
- [ ] No external resources loaded from anywhere other than a CDN
- [ ] No forbidden APIs (`window.storage`, `sendPrompt()`, etc.)
- [ ] Opens and works standalone in a browser (as far as can be verified)
- [ ] Layout holds up at mobile width (use `@media (max-width: 600px)` etc.)
- [ ] Matches the folder-only access pattern
      `app.venonet.jp/apps/<app-name>/`

### 5. Login/auth layer

`app.venonet.jp` has required login since 2026-07-29. There is no per-app
permission system by design — any authenticated user can use every app.

- The auth-infrastructure files listed under "Two layers that don't mix"
  above are exempt from the one-app-one-file rule; don't add to or modify
  them when just creating/updating an app.
- Once a new app is pushed and deployed, every existing logged-in user can
  use it immediately — no extra permission step. After pushing, just give
  the user the same one-line status message as in section 3.
- Adding login accounts (who can log in at all) is done by an admin via
  `app.venonet.jp/admin.php`, not something apps or Claude need to handle.
- Anyone with write access to this repo can open it in Claude Code and
  ship an app following these rules — this isn't Claude-specific tooling.
- DB credentials live only in `config.local.php` (gitignored, deployed
  directly on the server). Never commit its contents or paste plaintext
  passwords into chat.

### 6. Secrets & hygiene

- Never put server credentials (SSH keys, tokens, webhook secrets,
  `config.local.php` DB credentials) in this file or in commits. Auth relies
  on the existing git remote config, the WADAX deploy key, GitHub webhook
  config, and config files placed directly on the server.
- Watch for duplicate webhooks — periodically check the repo's
  Settings → Webhooks for more than one hook on the same payload URL.
- Never push a broken file, since push is immediate production release. For
  risky/large changes, confirm with the user before pushing.
