#!/usr/bin/env bash
# Actualiza la instancia de evaluación a una rama de GitHub (sin sudo).
# Uso: bash update.sh [rama]      (por defecto: la rama actual del servidor)
# Hace respaldo de la BD antes de migrar. Para volver atrás:
#   git checkout <commit anterior, guardado en ~/backups/head_pre-update.txt>
#   mysql stockia < ~/backups/<respaldo>.sql   (si hubo migraciones)
set -euo pipefail
APP=/var/www/stockia
cd "$APP"
BRANCH="${1:-$(git branch --show-current)}"

mkdir -p ~/backups
TS=$(date +%Y%m%d_%H%M)
git rev-parse HEAD > ~/backups/head_pre-update.txt
MYSQL_PWD="$(cat ~/.stockia-db-pass)" mysqldump -u stockia --single-transaction --routines --triggers \
  --no-tablespaces stockia > ~/backups/stockia_pre-update_$TS.sql
echo "== Respaldo: ~/backups/stockia_pre-update_$TS.sql (HEAD previo: $(cat ~/backups/head_pre-update.txt | cut -c1-8)) =="

git fetch -q origin
git checkout -q "$BRANCH"
git pull -q --ff-only origin "$BRANCH"
echo "== $(git log --oneline -1) =="

composer install --no-dev --optimize-autoloader --no-interaction 2>&1 | tail -1
npm ci --no-audit --no-fund >/dev/null 2>&1 && npm run build 2>&1 | tail -1
php artisan migrate --force
php artisan optimize
echo "HTTP /login: $(curl -s -o /dev/null -w '%{http_code}' http://localhost/login)"
