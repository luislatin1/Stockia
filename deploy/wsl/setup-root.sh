#!/usr/bin/env bash
# Stockia en WSL Ubuntu-22.04 — parte que requiere sudo (correr UNA vez).
# No toca el sitio existente en :80 (mendoza-landscaping) ni el PHP 8.1 por defecto.
set -euo pipefail
U=luisl
APP_DIR=/home/$U/proyectos/stockia

echo "== Paquetes base + PHP 8.3 (PPA ondrej) =="
apt-get update
apt-get install -y software-properties-common ca-certificates curl gnupg unzip
# PPA ondrej/php a mano (sin la API de Launchpad, que puede dar 503)
rm -f /etc/apt/sources.list.d/ondrej-ubuntu-php-jammy.list
curl -fsSL "https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14AA40EC0831756756D7F66C4F4EA0AAE5267A6C" \
  | gpg --dearmor --yes -o /usr/share/keyrings/ondrej-php.gpg
echo "deb [signed-by=/usr/share/keyrings/ondrej-php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" \
  > /etc/apt/sources.list.d/ondrej-php.list
apt-get update
apt-get install -y php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-soap php8.3-sqlite3

echo "== MariaDB =="
apt-get install -y mariadb-server
systemctl enable --now mariadb
PASS_FILE=/home/$U/.stockia-db-pass
if [ ! -f "$PASS_FILE" ]; then
  openssl rand -hex 16 > "$PASS_FILE"
  chown $U:$U "$PASS_FILE"; chmod 600 "$PASS_FILE"
fi
DBPASS=$(cat "$PASS_FILE")
mysql -e "CREATE DATABASE IF NOT EXISTS stockia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'stockia'@'localhost' IDENTIFIED BY '$DBPASS';
ALTER USER 'stockia'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON stockia.* TO 'stockia'@'localhost'; FLUSH PRIVILEGES;"

echo "== Node 20 =="
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo "== cloudflared =="
mkdir -p --mode=0755 /usr/share/keyrings
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg -o /usr/share/keyrings/cloudflare-main.gpg
echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared jammy main" > /etc/apt/sources.list.d/cloudflared.list
apt-get update && apt-get install -y cloudflared

echo "== Carpeta de la app =="
mkdir -p "$APP_DIR"; chown $U:$U "$APP_DIR"
usermod -aG $U www-data   # Apache/FPM lee los archivos del usuario
chmod 750 /home/$U

echo "== Apache vhost :8081 -> php8.3-fpm =="
a2enmod proxy_fcgi setenvif rewrite headers
grep -q '^Listen 8081' /etc/apache2/ports.conf || echo 'Listen 8081' >> /etc/apache2/ports.conf
cat > /etc/apache2/sites-available/stockia.conf <<EOF
<VirtualHost *:8081>
    DocumentRoot $APP_DIR/public
    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
    ErrorLog \${APACHE_LOG_DIR}/stockia_error.log
    CustomLog \${APACHE_LOG_DIR}/stockia_access.log combined
</VirtualHost>
EOF
a2ensite stockia
systemctl enable --now php8.3-fpm
systemctl restart php8.3-fpm apache2

echo; echo "LISTO. Avísale a Claude para que siga con la instalación de la app."
