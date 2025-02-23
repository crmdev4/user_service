# Use an official PHP runtime as a parent image
FROM php:8.2-fpm

# Set working directory inside the container
WORKDIR /var/www/userServices

# Install dependencies
RUN apt-get update && \
    apt-get install -y \
        git \
        curl \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        zip \
        unzip \
        supervisor \
        libzip-dev \
        redis-server 

# Configure GD extension for PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Redis PHP extension via PECL
RUN pecl install -o -f redis \
    && docker-php-ext-enable redis

# Clean up unnecessary files
RUN apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy existing application directory contents
COPY . /var/www/userServices

# Copy composer.json and composer.lock
COPY composer.json composer.lock /var/www/userServices/

# Install Laravel dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy custom php.ini
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Set ownership and permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/userServices \
    && chmod -R 755 /var/www/userServices \
    && chmod -R 775 /var/www/userServices/storage \
    && chmod -R 775 /var/www/userServices/bootstrap/cache

# Copy supervisor configuration file
COPY .docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Expose port 4441
EXPOSE 9000

# Start Nginx and PHP-FPM through Supervisor
CMD ["/usr/bin/supervisord"]
