#!/usr/bin/env bash
# Uso: wsl-modules.sh [enable]  — sin argumento solo muestra el estado
cd ~/proyectos/stockia
export MYSQL_PWD=$(cat ~/.stockia-db-pass)
if [ "${1:-}" = enable ]; then
  mysql -u stockia stockia -e "update modules set enabled=1;"
  php8.3 artisan optimize:clear >/dev/null && php8.3 artisan optimize >/dev/null
  echo "cache regenerada"
fi
mysql -u stockia stockia -e "select * from modules;"
mysql -u stockia stockia -e "select u.id, u.name, u.email from users u;"
