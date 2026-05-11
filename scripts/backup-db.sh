#!/usr/bin/env bash
# MySQL logical backup using mysqldump (Lab 4). Reads database.default.* from .env in project root.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT/.env"
OUT_DIR="$ROOT/writable/backups"

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Missing .env at $ENV_FILE" >&2
  exit 1
fi

get_env() {
  local key="$1"
  grep -E "^[[:space:]]*${key}[[:space:]]*=" "$ENV_FILE" | head -1 | cut -d= -f2- | sed 's/^[[:space:]]*//;s/[[:space:]]*$//;s/^"//;s/"$//;s/^'"'"'//;s/'"'"'$//'
}

HOST_DB="$(get_env database.default.hostname)"
DB_NAME="$(get_env database.default.database)"
USER_DB="$(get_env database.default.username)"
PASS_DB="$(get_env database.default.password)"

HOST_DB="${HOST_DB:-localhost}"

if [[ -z "$DB_NAME" || -z "$USER_DB" ]]; then
  echo "database.default.database and database.default.username are required in .env" >&2
  exit 1
fi

mkdir -p "$OUT_DIR"

MYSQLDUMP="mysqldump"
if [[ -n "${MYSQL_BIN:-}" ]]; then
  MYSQLDUMP="${MYSQL_BIN%/}/mysqldump"
fi

if ! command -v "$MYSQLDUMP" >/dev/null 2>&1; then
  echo "mysqldump not found. Install MySQL client tools or set MYSQL_BIN to the bin directory." >&2
  exit 1
fi

STAMP="$(date +%Y%m%d_%H%M%S)"
OUT_FILE="$OUT_DIR/ajesdb_${STAMP}.sql"

echo "Backing up '$DB_NAME' on '$HOST_DB' -> $OUT_FILE"

if [[ -n "$PASS_DB" ]]; then
  MYSQL_PWD="$PASS_DB" "$MYSQLDUMP" \
    --host="$HOST_DB" \
    --user="$USER_DB" \
    --single-transaction \
    --routines \
    --events \
    --add-drop-table \
    --default-character-set=utf8mb4 \
    "$DB_NAME" >"$OUT_FILE"
else
  "$MYSQLDUMP" \
    --host="$HOST_DB" \
    --user="$USER_DB" \
    --single-transaction \
    --routines \
    --events \
    --add-drop-table \
    --default-character-set=utf8mb4 \
    "$DB_NAME" >"$OUT_FILE"
fi

BYTES=$(wc -c <"$OUT_FILE")
if [[ "$BYTES" -lt 64 ]]; then
  echo "Backup too small ($BYTES bytes); removing." >&2
  rm -f "$OUT_FILE"
  exit 1
fi
if ! head -n 80 "$OUT_FILE" | grep -qiE 'CREATE TABLE|INSERT INTO|DROP TABLE|mysqldump'; then
  echo "Backup verification failed: file does not look like a SQL dump." >&2
  rm -f "$OUT_FILE"
  exit 1
fi
echo "Done (verified): $OUT_FILE ($BYTES bytes)"
