FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    wget \
    libssl-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    json \
    curl \
    zip

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Set working directory
WORKDIR /var/www/html

# Enable Apache modules
RUN a2enmod rewrite
RUN a2enmod headers

# Copy Apache configuration
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html/storage

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
