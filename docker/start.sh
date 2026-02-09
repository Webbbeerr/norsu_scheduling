#!/bin/bash
set -e

cd /var/www/html

# ===== Supplement .env with Railway env vars (if available) =====
# The .env file is committed to the repo with production defaults.
# Only override specific values if Railway provides them as env vars.
if [ ! -z "$DATABASE_URL" ]; then
    sed -i "s|^DATABASE_URL=.*|DATABASE_URL=\"${DATABASE_URL}\"|" /var/www/html/.env
fi
if [ ! -z "$APP_SECRET" ]; then
    sed -i "s|^APP_SECRET=.*|APP_SECRET=${APP_SECRET}|" /var/www/html/.env
fi
if [ ! -z "$APP_ENV" ]; then
    sed -i "s|^APP_ENV=.*|APP_ENV=${APP_ENV}|" /var/www/html/.env
fi
if [ ! -z "$DEFAULT_URI" ]; then
    sed -i "s|^DEFAULT_URI=.*|DEFAULT_URI=${DEFAULT_URI}|" /var/www/html/.env
fi
if [ ! -z "$MAILER_DSN" ]; then
    sed -i "s|^MAILER_DSN=.*|MAILER_DSN=${MAILER_DSN}|" /var/www/html/.env
fi
if [ ! -z "$MESSENGER_TRANSPORT_DSN" ]; then
    sed -i "s|^MESSENGER_TRANSPORT_DSN=.*|MESSENGER_TRANSPORT_DSN=${MESSENGER_TRANSPORT_DSN}|" /var/www/html/.env
fi
chown www-data:www-data /var/www/html/.env
chmod 644 /var/www/html/.env
echo "Environment configured (APP_ENV=$(grep ^APP_ENV .env | cut -d= -f2))"

# ===== Ensure PHP-FPM passes environment variables =====
# This is critical for Railway - without this, PHP-FPM workers won't see env vars
PHP_FPM_ENV_CONF="/usr/local/etc/php-fpm.d/zz-env.conf"
echo "[www]" > "$PHP_FPM_ENV_CONF"
echo "clear_env = no" >> "$PHP_FPM_ENV_CONF"
# Also explicitly pass key env vars to PHP-FPM
for var in APP_ENV APP_SECRET DATABASE_URL MAILER_DSN MESSENGER_TRANSPORT_DSN DEFAULT_URI PORT; do
    if [ ! -z "${!var}" ]; then
        echo "env[$var] = ${!var}" >> "$PHP_FPM_ENV_CONF"
    fi
done
echo "PHP-FPM environment configured"

# Use PORT env variable or default to 80
PORT=${PORT:-80}

echo "Starting Smart Scheduling System on port ${PORT}..."

# Generate nginx config with correct port
cat > /etc/nginx/sites-available/default << EOF
server {
    listen ${PORT};
    server_name localhost;
    root /var/www/html/public;

    index index.php;

    # Gzip compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript image/svg+xml;
    gzip_min_length 256;

    location / {
        try_files \$uri /index.php\$is_args\$args;
    }

    location ~ ^/index\.php(/|\$) {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
        fastcgi_read_timeout 60s;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }

    # Cache static assets
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
        try_files \$uri =404;
    }

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    # Deny access to sensitive files
    location ~ /(\.env|composer\.|config|src|var|vendor|migrations|tests) {
        deny all;
        return 404;
    }

    # Increase upload size
    client_max_body_size 50M;

    error_log /var/log/nginx/error.log;
    access_log /var/log/nginx/access.log;
}
EOF

# Ensure symlink exists
ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# Create required directories
mkdir -p /var/www/html/var/cache /var/www/html/var/log /var/www/html/var/sessions
chown -R www-data:www-data /var/www/html/var
chmod -R 775 /var/www/html/var

# Run Symfony cache warmup
if [ -f /var/www/html/.env ] || [ ! -z "$APP_ENV" ]; then
    echo "Warming up Symfony cache..."
    php bin/console cache:clear --env=${APP_ENV:-prod} --no-debug 2>&1 || true
    php bin/console cache:warmup --env=${APP_ENV:-prod} --no-debug 2>&1 || true
    
    # Run database migrations automatically
    echo "Running database migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration 2>&1 || true
fi

echo "Starting PHP-FPM and Nginx via Supervisor..."

# Start supervisor (manages both PHP-FPM and Nginx)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
