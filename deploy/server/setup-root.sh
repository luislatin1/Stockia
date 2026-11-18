#!/usr/bin/env bash
# Stockia en servidor Ubuntu 24.04 (instancia de evaluación) — parte que requiere sudo.
# Uso: sudo bash setup-root.sh [usuario]   (por defecto el que corre sudo)
# Se puede correr más de una vez.
set -euo pipefail
U=${1:-${SUDO_USER:-soportecorp}}
APP_DIR=/var/www/stockia

echo "== Paquetes: Apache, PHP 8.3 (nativo de 24.04), MariaDB, Composer =="
apt-get update
apt-get install -y ca-certificates curl gnupg unzip git apache2 mariadb-server composer \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl php8.3-soap php8.3-sqlite3

echo "== Node 20 =="
if ! command -v node >/dev/null || ! node -v | grep -q '^v20'; then
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
fi

echo "== cloudflared =="
mkdir -p --mode=0755 /usr/share/keyrings
curl -fsSL https://pkg.cloudflare.com/cloudflare-main.gpg -o /usr/share/keyrings/cloudflare-main.gpg
echo "deb [signed-by=/usr/share/keyrings/cloudflare-main.gpg] https://pkg.cloudflare.com/cloudflared noble main" \
  > /etc/apt/sources.list.d/cloudflared.list
apt-get update && apt-get install -y cloudflared

echo "== BD stockia =="
systemctl enable --now mariadb
PASS_FILE=/home/$U/.stockia-db-pass
if [ ! -s "$PASS_FILE" ]; then
  openssl rand -hex 16 > "$PASS_FILE"
fi
chown $U:$U "$PASS_FILE"; chmod 600 "$PASS_FILE"
DBPASS=$(cat "$PASS_FILE")
mysql -e "CREATE DATABASE IF NOT EXISTS stockia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'stockia'@'localhost' IDENTIFIED BY '$DBPASS';
ALTER USER 'stockia'@'localhost' IDENTIFIED BY '$DBPASS';
GRANT ALL PRIVILEGES ON stockia.* TO 'stockia'@'localhost'; FLUSH PRIVILEGES;"

echo "== Carpeta de la app =="
mkdir -p "$APP_DIR"
chown $U:www-data "$APP_DIR"; chmod 2775 "$APP_DIR"
usermod -aG www-data "$U"
usermod -aG systemd-journal "$U"   # para leer la URL del túnel sin sudo

echo "== Apache :80 -> php8.3-fpm =="
a2enmod proxy_fcgi setenvif rewrite headers
a2dismod -f php8.3 2>/dev/null || true
cat > /etc/apache2/sites-available/stockia.conf <<EOF
<VirtualHost *:80>
    DocumentRoot $APP_DIR/public
    # Detrás de Cloudflare Tunnel: la app no tiene trustProxies
    SetEnvIf X-Forwarded-Proto "^https\$" HTTPS=on
    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
    # Instancia de evaluación: los usuarios se crean desde Usuarios → Crear
    <Location "/register">
        Require all denied
    </Location>
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
    ErrorLog \${APACHE_LOG_DIR}/stockia_error.log
    CustomLog \${APACHE_LOG_DIR}/stockia_access.log combined
</VirtualHost>
EOF
a2dissite 000-default >/dev/null 2>&1 || true
a2ensite stockia
systemctl enable --now php8.3-fpm apache2

echo "== Servicio del túnel (quick tunnel, URL en journalctl) =="
cat > /etc/systemd/system/stockia-tunnel.service <<EOF
[Unit]
Description=Cloudflare quick tunnel para Stockia
After=network-online.target apache2.service
Wants=network-online.target

[Service]
ExecStart=/usr/bin/cloudflared tunnel --no-autoupdate --url http://localhost:80
Restart=on-failure
RestartSec=10
DynamicUser=yes

[Install]
WantedBy=multi-user.target
EOF
systemctl daemon-reload
systemctl enable stockia-tunnel   # se arranca al final, cuando la app ya esté lista

apache2ctl configtest
systemctl restart php8.3-fpm apache2
echo; echo "LISTO. Avísale a Claude para que siga con la instalación de la app."
