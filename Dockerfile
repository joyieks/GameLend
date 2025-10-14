FROM php:8.2-apache

# Install system deps and PHP extensions (Postgres only)
RUN apt-get update \
 && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libzip-dev zip unzip curl libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && rm -rf /var/lib/apt/lists/*

# Enable Apache mods
RUN a2enmod rewrite headers expires

# Configure Apache: allow .htaccess overrides and set DocumentRoot
RUN sed -ri 's!/var/www/html!/var/www/html!g' /etc/apache2/sites-available/000-default.conf \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy app
WORKDIR /var/www/html
COPY . /var/www/html

# Set recommended PHP settings for production
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_MEMORY_LIMIT=256M

# Expose HTTP
EXPOSE 80

# Healthcheck (simple)
# Simple healthcheck
HEALTHCHECK --interval=30s --timeout=3s CMD curl -fsS http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]


