# Dockerfile for PHP + Apache
FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy source code
COPY ./src /var/www/html/
COPY ./public /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
