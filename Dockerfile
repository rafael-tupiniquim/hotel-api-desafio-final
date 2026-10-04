FROM php:8.2-cli

# Extensões do PHP necessárias: pdo_mysql (banco da aplicação) e pdo_sqlite
# (os testes usam SQLite em memória, conforme phpunit.xml).
RUN apt-get update && apt-get install -y \
        git unzip libsqlite3-dev libzip-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

# Composer, copiado da imagem oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./
RUN composer install --no-scripts --no-interaction --prefer-dist || true

COPY . .
# Dependências de desenvolvimento ficam instaladas para permitir `php artisan test` no container.
RUN composer install --no-interaction --prefer-dist \
    && cp -n .env.example .env \
    && php artisan key:generate --force

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["docker-entrypoint.sh"]
