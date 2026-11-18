#!/usr/bin/env bash
# Importa el dump de la BD de Windows a MariaDB en WSL y corrige DB_PASSWORD en .env
set -euo pipefail
APP=~/proyectos/stockia
DUMP="$1"
P=$(cat ~/.stockia-db-pass)
[ -n "$P" ] || { echo "sin password"; exit 1; }
[ -f "$DUMP" ] || { echo "no existe $DUMP"; exit 1; }

sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=$P|" "$APP/.env"
echo "DB_PASSWORD en .env: $(grep -c "^DB_PASSWORD=$P\$" "$APP/.env") coincidencia(s)"

echo "Collations MySQL 8 en el dump:"
grep -o "utf8mb4_0900_[a-z_]*" "$DUMP" | sort | uniq -c || true

export MYSQL_PWD="$P"
sed -e 's/utf8mb4_0900_ai_ci/utf8mb4_unicode_ci/g' -e 's/utf8mb4_0900_bin/utf8mb4_bin/g' "$DUMP" \
  | mysql -u stockia stockia
echo IMPORT_OK
mysql -u stockia stockia -e "
select count(*) tablas from information_schema.tables where table_schema='stockia';
select (select count(*) from users) users, (select count(*) from companies) companies,
       (select count(*) from products) products, (select count(*) from sales) sales;"
