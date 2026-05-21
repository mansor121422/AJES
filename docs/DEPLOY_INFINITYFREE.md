# Deploy AJES to InfinityFree

## Common 404 cause

- Files uploaded to `htdocs` but `.htaccess` still has `RewriteBase /AJES/` (localhost only).
- Full Git project uploaded instead of the deploy zip.
- `AJES-infinityfree.zip` uploaded but **not extracted**.

## Correct steps

1. InfinityFree → **File Manager** → open **`htdocs`**.
2. **Delete everything** inside `htdocs` (or move to a backup folder).
3. Upload **`AJES-infinityfree.zip`** from your PC.
4. **Extract** the zip inside `htdocs`.
5. Copy **`.htaccess.infinityfree`** → rename to **`.htaccess`** (overwrite).
6. Create **`.env`** in `htdocs` with InfinityFree MySQL + URL (see below).
7. **phpMyAdmin** → Import **`AjesDB-export.sql`**.
8. Open `https://yoursite.infinityfreeapp.com/auth/login`

## `htdocs` should look like

```
htdocs/
  index.php
  ci-route-bootstrap.php
  chat/index.php
  chatlogs/index.php
  .htaccess
  app/
  system/
  vendor/
  writable/
  .env
```

Do **not** need: `.git`, `frontend/`, `docs/`, `tests/` (dev repo only).

## Chat / Chat Logs still 404 after `.htaccess` fix?

InfinityFree often does not rewrite `/chat` to `index.php`. Upload these from your PC:

- `ci-route-bootstrap.php` (site root)
- `chat/index.php` (folder `chat/`)
- `chatlogs/index.php` (folder `chatlogs/`)

Add to **`.env`**:

```ini
app.indexPage = index.php
FORCE_INDEX_PAGE = true
```

Test both:

- `https://yoursite.infinityfreeapp.com/index.php/chat`
- `https://yoursite.infinityfreeapp.com/chat/`

## API / Android app (AJESCHAT)

Web chat polls `api/chat/users`. Android uses `api/login`. These URLs **do not** use `index.php` unless you configure it.

1. Upload folder **`api/`** with **`api/.htaccess`** (routes `/api/*` → `index.php`).
2. Or set Android `ajes.baseUrl` to include `index.php`:
   - `https://yoursite.infinityfreeapp.com/index.php/`
3. Test in browser:
   - `https://yoursite.infinityfreeapp.com/api/login` (POST only — use app or Postman)
   - After login in browser, open Chat and check browser DevTools → Network for `api/chat/users` (should be 200, not 404).

## `.env` example

```ini
CI_ENVIRONMENT = production

app.baseURL = https://ajescrier.infinityfreeapp.com/
app.extraHosts = ajescrier.infinityfreeapp.com
app.indexPage = index.php
FORCE_INDEX_PAGE = true

database.default.hostname = sql123.infinityfree.com
database.default.database = if0_12345678_ajesdb
database.default.username = if0_12345678
database.default.password = YOUR_MYSQL_PASSWORD
database.default.DBDriver = MySQLi

ENCRYPTION_KEY = hex2bin:paste_same_key_from_local_env
```

Get MySQL values from InfinityFree → **MySQL Databases**.

## Fix without re-upload

If `index.php` is already in `htdocs`, edit `.htaccess` and change:

```
RewriteBase /AJES/
```

to:

```
RewriteBase /
```

Save, then reload the site.
