# Lean, evaluation-friendly image: no MySQL/Redis (this app needs none),
# just PHP + Composer, dependencies installed, ready to test, demo or serve.
FROM php:8.3-cli

# Only the extensions Laravel actually needs here. SQLite + array cache mean
# no external services, so the image stays small and starts instantly.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libonig-dev libxml2-dev \
    && docker-php-ext-install mbstring pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN cp .env.example .env \
    && composer install --no-interaction --prefer-dist --no-progress \
    && php artisan key:generate

EXPOSE 8000

# Default: serve the API. Override to run tests or the demo (see README).
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
