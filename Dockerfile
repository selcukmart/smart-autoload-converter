# Smart Autoload Converter - PHP 8.5 + Symfony 7.4 LTS
FROM php:8.5-cli-alpine AS base

RUN apk add --no-cache \
    git \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && docker-php-ext-enable zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# --- Dependencies layer (cached) ---
FROM base AS deps

COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# --- Dev dependencies layer ---
FROM base AS deps-dev

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# --- Production image ---
FROM base AS prod

COPY --from=deps /app/vendor /app/vendor
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative

ENTRYPOINT ["php", "bin/smart-autoload-converter"]
CMD ["list"]

# --- Development / Test image ---
FROM base AS dev

COPY --from=deps-dev /app/vendor /app/vendor
COPY . .
RUN composer dump-autoload --optimize

# Prepare workspace with sample project
RUN mkdir -p /workspace/input /workspace/output /workspace/backups /workspace/reports \
    && cp -r fixtures/legacy-sample-project/* /workspace/input/

# Smoke test
RUN php bin/smart-autoload-converter list
RUN php vendor/bin/phpunit --version
