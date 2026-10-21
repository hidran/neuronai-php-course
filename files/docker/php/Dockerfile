# PHP 8.5 CLI with Composer and the extensions the course uses:
# pdo_mysql (MySQLToolkit, MariaDB vector store), pcntl and posix (parallel tool calls), intl, zip, redis.
FROM php:8.5-cli

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pcntl intl zip redis \
    && apt-get update && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
