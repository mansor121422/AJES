#!/usr/bin/env bash
# Restores a mysqldump .sql file into the database from .env (Lab 4). DESTRUCTIVE — requires typing YES.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="$ROOT/.env"

if [[ $# -lt 1 ]]; then
  echo "Usage: $0 <path-to-dump.sql>" >&2
  echo "Example: $0 $ROOT/writable/backups/ajesdb_20260101_120000.sql" >&2
  exit 1
fi

DUMP_FILE="$1"
if [[ ! "$DUMP_FILE" =~ ^/ ]]; then
  DUMP_FILE="$ROOT/${DUMP_FILE#./}"
fi

if [[ ! -f "$DUMP_FILE" ]]; then
  echo "Dump file not found: $DUMP_FILE" >&2
  exit 1
fi

if [[ "$(wc -c <"$DUMP_FILE")" -lt 64 ]] || ! head -n 80 "$DUMP_FILE" | grep -qiE 'CREATE TABLE|INSERT INTO|DROP TABLE|mysqldump'; then
  echo "Dump file failed validation (too small or missing SQL markers). Refusing to restore." >&2
  exit 1
fi

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

echo "WARNING: This will import SQL into database '$DB_NAME' on '$HOST_DB'."
echo "Dump file: $DUMP_FILE"
read -r -p "Type YES to continue: " CONFIRM
if [[ "$CONFIRM" != "YES" ]]; then
  echo "Aborted."
  exit 1
fi

MYSQL="mysql"
if [[ -n "${MYSQL_BIN:-}" ]]; then
  MYSQL="${MYSQL_BIN%/}/mysql"
fi

if ! command -v "$MYSQL" >/dev/null 2>&1; then
  echo "mysql client not found. Install MySQL client tools or set MYSQL_BIN." >&2
  exit 1
fi

if [[ -n "$PASS_DB" ]]; then
  MYSQL_PWD="$PASS_DB" "$MYSQL" --host="$HOST_DB" --user="$USER_DB" "$DB_NAME" <"$DUMP_FILE"
else
  "$MYSQL" --host="$HOST_DB" --user="$USER_DB" "$DB_NAME" <"$DUMP_FILE"
fi

echo "Restore completed for database '$DB_NAME'."
