# syntax=docker/dockerfile:1
# =============================================================================
# Little Steps - Unified Dockerfile
# =============================================================================
# Usage:
#   Development: docker build --target development -t littlesteps-dev .
#   Production:  docker build --target production  -t littlesteps-prod .
# =============================================================================

# =============================================================================
# Stage 1: Base - Shared PHP setup for all environments
# =============================================================================
FROM php:8.4-fpm-alpine AS base

# Build dependencies (removed again after extension compilation)
RUN apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    linux-headers \
    libjpeg-turbo-dev \
    libpng-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev

# Runtime dependencies (kept in the final image)
RUN apk add --no-cache \
    curl \
    git \
    zip \
    unzip \
    icu-libs \
    freetype \
    libjpeg-turbo \
    libpng \
    libwebp \
    libzip \
    mysql-client \
    # Image optimizers used by spatie/laravel-medialibrary; without these the
    # optimize() step on a conversion silently does nothing.
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    libwebp-tools

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype && \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        gd \
        bcmath \
        exif \
        zip \
        pcntl \
        intl \
        opcache

RUN pecl install redis && docker-php-ext-enable redis

RUN apk del .build-deps

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Base PHP configuration
RUN echo '[PHP]' > $PHP_INI_DIR/conf.d/app.ini && \
    echo 'memory_limit = 1024M' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'upload_max_filesize = 100M' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'post_max_size = 100M' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'max_execution_time = 300' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'max_input_time = 300' >> $PHP_INI_DIR/conf.d/app.ini

# PHP-FPM timeout configuration (5 minutes)
RUN echo 'request_terminate_timeout = 300s' >> /usr/local/etc/php-fpm.d/www.conf && \
    echo 'clear_env = no' >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www


# =============================================================================
# Stage 2: Development - Xdebug and dev tooling
# =============================================================================
FROM base AS development

# Xdebug for step debugging
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del .build-deps

RUN echo '[xdebug]' > $PHP_INI_DIR/conf.d/xdebug.ini && \
    echo 'xdebug.mode=debug,develop' >> $PHP_INI_DIR/conf.d/xdebug.ini && \
    echo 'xdebug.start_with_request=no' >> $PHP_INI_DIR/conf.d/xdebug.ini && \
    echo 'xdebug.client_host=host.docker.internal' >> $PHP_INI_DIR/conf.d/xdebug.ini && \
    echo 'xdebug.client_port=9003' >> $PHP_INI_DIR/conf.d/xdebug.ini && \
    echo 'xdebug.cli_color=1' >> $PHP_INI_DIR/conf.d/xdebug.ini

# Development PHP settings
RUN echo 'error_reporting = E_ALL' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'display_errors = On' >> $PHP_INI_DIR/conf.d/app.ini

# Node.js for ad-hoc asset builds from the PHP container
RUN apk add --no-cache nodejs npm bash openssh-client rsync

CMD ["php-fpm"]


# =============================================================================
# Stage 3: Builder - Compile frontend assets and production dependencies
# =============================================================================
FROM base AS builder

RUN apk add --no-cache nodejs npm

WORKDIR /build

# Install PHP dependencies first for better layer caching
COPY laravel/composer.json laravel/composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs

# Install Node dependencies
COPY laravel/package.json laravel/package-lock.json ./
RUN npm ci

# Application code
COPY laravel/ .

# Artisan needs an .env to boot during the build
RUN cp .env.example .env

# Build frontend assets
RUN npm run build

# Cleanup and production optimisation
RUN rm -rf node_modules && \
    rm -rf bootstrap/cache/*.php && \
    rm -rf vendor && \
    composer install --no-dev --optimize-autoloader --prefer-dist --ignore-platform-reqs && \
    rm -rf storage/framework/cache/data/* && \
    rm -rf storage/framework/views/* && \
    rm -rf storage/framework/sessions/* && \
    php artisan package:discover --ansi && \
    rm -f .env


# =============================================================================
# Stage 4: Production - nginx + php-fpm under supervisor
# =============================================================================
FROM base AS production

RUN apk add --no-cache \
    nginx \
    supervisor \
    rsync \
    su-exec

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# Opcache tuning for production
RUN echo '' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo '[opcache]' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.enable = 1' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.memory_consumption = 128' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.interned_strings_buffer = 16' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.max_accelerated_files = 10000' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.validate_timestamps = 0' >> $PHP_INI_DIR/conf.d/app.ini && \
    echo 'opcache.save_comments = 1' >> $PHP_INI_DIR/conf.d/app.ini

RUN cat > /etc/nginx/nginx.conf <<'EOF'
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    log_format main '$remote_addr - $remote_user [$time_local] "$request" $status $body_bytes_sent "$http_referer" "$http_user_agent"';
    access_log /var/log/nginx/access.log main;
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    client_max_body_size 100M;
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml application/json application/javascript application/xml;

    server {
        listen 80;
        server_name _;
        root /var/www/public;
        index index.php;

        location / {
            try_files $uri $uri/ /index.php?$query_string;
        }

        location = /favicon.ico { access_log off; log_not_found off; }
        location = /robots.txt  { access_log off; log_not_found off; }

        location /build/ {
            expires 1y;
            access_log off;
            add_header Cache-Control "public, immutable";
        }

        error_page 404 /index.php;

        location ~ \.php$ {
            fastcgi_pass 127.0.0.1:9000;
            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_hide_header X-Powered-By;
            fastcgi_connect_timeout 300s;
            fastcgi_send_timeout 300s;
            fastcgi_read_timeout 300s;
        }

        location ~ /\.(?!well-known).* {
            deny all;
        }
    }
}
EOF

RUN mkdir -p /etc/supervisor/conf.d /var/log/supervisor && \
    cat > /etc/supervisor/conf.d/supervisord.conf <<'EOF'
[supervisord]
nodaemon=true
user=root
logfile=/var/log/supervisor/supervisord.log
pidfile=/var/run/supervisord.pid

[program:php-fpm]
command=php-fpm -F
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
autorestart=true
EOF

# Built application from the builder stage
COPY --from=builder /build /var/www

RUN mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/app/public \
    /var/www/bootstrap/cache \
    /var/log/nginx && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Entrypoint fixes permissions on mounted volumes before handing off
RUN cat > /entrypoint.sh <<'EOF'
#!/bin/sh
set -e

mkdir -p /var/www/storage/logs \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/app/public

chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/storage /var/www/bootstrap/cache 2>/dev/null || true

[ -L /var/www/public/storage ] || php /var/www/artisan storage:link 2>/dev/null || true

case "$*" in
    *supervisord*)
        exec "$@"
        ;;
    *)
        exec su-exec www-data "$@"
        ;;
esac
EOF
RUN chmod +x /entrypoint.sh

HEALTHCHECK --interval=10s --timeout=5s --start-period=60s --retries=5 \
    CMD curl -f http://localhost/up || exit 1

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
