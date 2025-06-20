FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libonig-dev \
    libzip-dev \
    zip \
    unzip \
    git \
  && docker-php-ext-configure gd --with-jpeg \
  && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli mbstring zip xml intl opcache \
  && a2enmod rewrite headers

# Configure Apache
RUN sed -i 's!/var/www/html!/var/www/html/htdocs!g' /etc/apache2/sites-available/000-default.conf

# Copy Dolibarr source
COPY . /var/www/html/htdocs/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/htdocs

# Expose web port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"] 