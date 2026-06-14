# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 — PHP dependencies (Composer). No autoloader yet; regenerated later.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.3 AS vendor
RUN install-php-extensions @composer
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader 2>/dev/null \
 || composer update --no-dev --prefer-dist --no-interaction --no-scripts --no-autoloader

# ---------------------------------------------------------------------------
# Stage 2 — front-end assets (Tailwind + Vite). vendor/ is copied in so
# Tailwind can scan Livewire's pagination views and keep their classes.
# ---------------------------------------------------------------------------
FROM node:20-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm install
COPY vite.config.js postcss.config.js tailwind.config.js ./
COPY resources ./resources
COPY public ./public
COPY app ./app
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# Stage 3 — runtime: FrankenPHP (Caddy + embedded PHP) in ONE container.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:1-php8.3 AS runtime

RUN install-php-extensions \
        pdo_sqlite \
        intl \
        zip \
        gd \
        opcache \
        pcntl \
        @composer \
 && apt-get update \
 && apt-get install -y --no-install-recommends curl \
 && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Production PHP / OPcache tuning sized for a 1 GB VM.
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-app.ini

# Reuse the resolved vendor tree, then bring in source + built assets.
COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
             storage/logs storage/app/public storage/app/database bootstrap/cache \
 && rm -rf public/storage \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENV SERVER_NAME=":80"

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1:80/up || exit 1

EXPOSE 80 443 443/udp

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
