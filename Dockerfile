FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    zip \
    unzip \
    libpq-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Configure GD with freetype and jpeg support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock symfony.lock ./

# Install dependencies (allow platform checks to be bypassed if needed)
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-progress --prefer-dist

# Copy application files
COPY . .

# Run post-install scripts
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative

# Create var directory structure
RUN mkdir -p var/cache var/log var/sessions \
    && chown -R www-data:www-data var \
    && chmod -R 775 var

# Configure Apache
RUN a2enmod rewrite
COPY docker-apache.conf /etc/apache2/sites-available/000-default.conf

# Set APP_ENV for cache commands
ENV APP_ENV=prod

# Clear and warm up cache (skip if fails)
RUN php bin/console cache:clear --no-warmup || true
RUN php bin/console cache:warmup || true

EXPOSE 80

CMD ["apache2-foreground"]
