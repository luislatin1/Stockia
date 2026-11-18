#!/usr/bin/env bash
# Ajusta el vhost de Stockia (:8081) para exponerlo por Cloudflare Tunnel:
#  - HTTPS=on cuando llega X-Forwarded-Proto: https (Laravel genera enlaces https)
#  - /register bloqueado en esta instancia
set -euo pipefail
APP_DIR=/home/luisl/proyectos/stockia
cat > /etc/apache2/sites-available/stockia.conf <<EOF
<VirtualHost *:8081>
    DocumentRoot $APP_DIR/public
    SetEnvIf X-Forwarded-Proto "^https\$" HTTPS=on
    <Directory $APP_DIR/public>
        AllowOverride All
        Require all granted
    </Directory>
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
apache2ctl configtest
systemctl reload apache2
echo "LISTO."
