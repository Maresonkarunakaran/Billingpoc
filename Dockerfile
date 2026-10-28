FROM php:8.3-cli-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    curl \
    git \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    nodejs \
    npm \
    sqlite-dev \
    sqlite \
    oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_sqlite bcmath gd intl mbstring zip opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy application files
COPY . .

# Install dependencies and build assets
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm install && npm run build

# Prepare database and cache folders
RUN touch database/database.sqlite
RUN chown -R www-data:www-data storage bootstrap/cache database
RUN chmod -R 775 storage bootstrap/cache database

EXPOSE 8000

CMD ["sh", "-c", "php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}"]
