FROM php:8.4.2-apache

COPY /apache/default.conf /etc/apache2/sites-enabled/000-default.conf

RUN apt-get update && apt-get install -y \
    curl \
    gcc \
    make \
    autoconf \
    libprotobuf-dev \
    protobuf-compiler \
    libpng-dev \
    libjpeg-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    librdkafka-dev\
    zlib1g-dev \
    supervisor \
    libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        sockets \
        zip \
        mysqli
# Install MongoDB extension
RUN pecl install mongodb \
    && echo "extension=mongodb.so" > $PHP_INI_DIR/conf.d/mongodb.ini

WORKDIR /var/www/html

RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
RUN docker-php-ext-enable pdo_mysql

RUN a2enmod rewrite

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy only composer files first
COPY composer.json composer.lock ./

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . ./

COPY /docker/docker-entrypoint.sh /usr/local/bin/


RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && chown -R www-data:www-data . \
    && chmod -R 755 /var/www \
    && chmod -R 775 storage \
    && chmod -R 775 bootstrap/cache


ENTRYPOINT ["docker-entrypoint.sh"]

CMD ["apache2-foreground"]