#!/usr/bin/env bash
# Entrypoint service "web" di Railway (juga bisa dipakai lokal: PORT=8000 bash scripts/railway-start.sh)
set -euo pipefail

# SQLite: file database harus ada sebelum migrate (Railway tanpa volume = ephemeral)
if [ "${DB_CONNECTION:-}" = "sqlite" ]; then
    db="${DB_DATABASE:-database/database.sqlite}"
    mkdir -p "$(dirname "$db")"
    [ -f "$db" ] || touch "$db"
fi

php artisan config:cache
php artisan route:cache

# --force wajib: di production Laravel menolak migrate tanpa konfirmasi
php artisan migrate --force

# Jangan pakai APP_DEBUG=true di Railway (menampilkan stack trace ke publik)
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
