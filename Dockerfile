FROM php:8.3-apache

# SQLite kurulumu
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Apache rewrite module
RUN a2enmod rewrite

# Apache yapılandırması
COPY docker/apache-config.conf /etc/apache2/sites-available/000-default.conf

# Çalışma dizini
WORKDIR /var/www/html

# İzinleri düzelt
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Port
EXPOSE 80

# Apache'yi başlat
CMD ["apache2-foreground"]
