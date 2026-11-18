#!/usr/bin/env bash
set -uo pipefail
cd ~/proyectos/stockia
PHP=php8.3
chmod -R g+rwX storage bootstrap/cache
chmod g+r .env
$PHP artisan storage:link 2>&1 | tail -1
echo "== migraciones pendientes =="
$PHP artisan migrate:status --pending 2>&1 | tail -5
$PHP artisan migrate --force 2>&1 | tail -3
$PHP artisan optimize 2>&1 | tail -3
echo "== scramble instalado? =="
[ -d vendor/dedoc/scramble ] && echo si || echo "NO (está en require-dev)"
echo "== rutas register/setup =="
$PHP artisan route:list --path=register 2>&1 | grep -v '^$' | head -5
echo "== HTTP =="
for u in / /login /register /docs/api; do
  printf "%-12s %s\n" "$u" "$(curl -s -o /dev/null -w '%{http_code}' http://localhost:8081$u)"
done
tail -3 storage/logs/laravel.log 2>/dev/null | cut -c1-300
