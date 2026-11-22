# Image untuk aplikasi Laravel (service "web" di Railway).
# PHP + Node: Node dipakai untuk menandatangani transaksi anchor (chain/scripts/anchor.js).

FROM php:8.3-cli-bookworm

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    APP_ENV=production \
    APP_DEBUG=false

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libonig-dev libpq-dev libsqlite3-dev nodejs npm ca-certificates \
    && docker-php-ext-install pdo_pgsql pdo_sqlite mbstring bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# dependency PHP dulu supaya layer cache tidak rusak saat kode berubah
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

# hanya ethers yang dibutuhkan saat runtime (hardhat tidak perlu di image web)
RUN composer dump-autoload --optimize --no-dev \
    && npm ci --omit=dev --prefix chain \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
       storage/logs bootstrap/cache database \
    && chmod -R ug+rwX storage bootstrap/cache database

EXPOSE 8080

CMD ["bash", "scripts/railway-start.sh"]
