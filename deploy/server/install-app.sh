#!/usr/bin/env bash
# Configura Stockia en /var/www/stockia (sin sudo), después de setup-root.sh y del git clone + composer + npm build.
# Uso: bash install-app.sh <dump.sql> <env-origen>
#   <env-origen>: .env de otra instancia de la misma BD (se reutiliza su APP_KEY)
set -euo pipefail
APP=/var/www/stockia
DUMP="$1"; SRC_ENV="$2"
P=$(cat ~/.stockia-db-pass)
[ -n "$P" ] && [ -f "$DUMP" ] && [ -f "$SRC_ENV" ] || { echo "faltan datos"; exit 1; }
cd "$APP"

echo "== .env =="
cp "$SRC_ENV" .env
sed -i -e "s|^APP_ENV=.*|APP_ENV=production|" -e "s|^APP_DEBUG=.*|APP_DEBUG=false|" \
  -e "s|^APP_URL=.*|APP_URL=http://localhost|" -e "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" \
  -e "s|^DB_PORT=.*|DB_PORT=3306|" -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$P|" .env
chgrp www-data .env; chmod 640 .env
grep -E '^(APP_ENV|APP_DEBUG|DB_HOST|DB_DATABASE|DTE_MODE|DTE_AMBIENTE|MAIL_MAILER)=' .env

echo "== Importar BD =="
export MYSQL_PWD="$P"
sed -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' -e 's/utf8mb4_0900_bin/utf8mb4_bin/g' "$DUMP" \
  | mysql -u stockia stockia
mysql -u stockia stockia -e "
select count(*) tablas from information_schema.tables where table_schema='stockia';
select (select count(*) from users) users, (select count(*) from companies) companies,
       (select count(*) from products) products, (select count(*) from sales) sales;"

echo "== Módulos: todos activos (DTE sigue en simulación por .env) =="
mysql -u stockia stockia -e "update modules set enabled=1; select \`key\`, enabled from modules;"

echo "== Laravel =="
chgrp -R www-data storage bootstrap/cache
chmod -R g+rwX storage bootstrap/cache
php artisan storage:link 2>&1 | tail -1
php artisan migrate --force 2>&1 | tail -2
php artisan optimize 2>&1 | tail -2

echo "== HTTP local =="
for u in / /login /register; do
  printf "%-10s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' -H 'X-Forwarded-Proto: https' http://localhost$u)"
done
