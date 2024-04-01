FROM php:8.3-apache

# Set environment variables

ENV PHP_MEMORY_LIMIT="512M"
ENV PHP_UPLOAD_LIMIT="2G"

ENV APACHE_BODY_LIMIT=2147483648

# Install dependencies

RUN set -ex; \
    \
    apt-get update && \
    apt-get install -y --no-install-recommends \
      bash \
      cron \
      git \
      libcurl4-openssl-dev \
      libpq-dev \
      libxml2-dev \
      libzip-dev \
      sudo \
      supervisor \
      wait-for-it \
      zip; \
    \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -; \
    apt-get install -y nodejs

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Install PHP extensions

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN set -ex; \
    \
    { \
        echo ""; \
        echo ";see https://sabre.io/dav/webservers/"; \
        echo "output_buffering=off"; \
        echo "always_populate_raw_post_data=off"; \
        echo "magic_quotes_gpc=off"; \
        echo "mbstring.func_overload=off"; \
    } >> $PHP_INI_DIR/php.ini;

RUN set -ex; \
    \
    savedAptMark="$(apt-mark showmanual)"; \
    \
    docker-php-ext-install -j$(nproc) \
      bcmath \
      curl \
      dom \
      exif \
      fileinfo \
      intl \
      mysqli \
      pdo \
      pdo_mysql \
      pdo_pgsql \
      pgsql \
      zip; \
    \
    pecl install redis; \
    \
    docker-php-ext-enable redis; \
    \
    # reset apt-mark's "manual" list so that "purge --auto-remove" will remove all build dependencies
    apt-mark auto '.*' > /dev/null; \
    apt-mark manual $savedAptMark; \
        ldd "$(php -r 'echo ini_get("extension_dir");')"/*.so \
        | awk '/=>/ { print $3 }' \
        | sort -u \
        | xargs -r dpkg-query -S \
        | cut -d: -f1 \
        | sort -u \
        | xargs -rt apt-mark manual; \
        \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    rm -rf /var/lib/apt/lists/*

# Setup cronjob for schedules

COPY docker/cron.conf /etc/cron.d/securedav

# Setup Apache

RUN set -ex; \
    \
    { \
        echo "memory_limit=$PHP_MEMORY_LIMIT"; \
        echo "upload_max_filesize=$PHP_UPLOAD_LIMIT"; \
        echo "post_max_size=$PHP_UPLOAD_LIMIT"; \
    } > $PHP_INI_DIR/conf.d/limits.ini;

RUN set -ex; \
    \
    { \
        echo "LimitRequestBody $APACHE_BODY_LIMIT"; \
    } > $APACHE_CONFDIR/conf-available/apache-limits.conf; \
    a2enconf apache-limits

RUN set -ex; \
    APACHE_DOCUMENT_ROOT=/var/www/html/public; \
    sed -ri -e "s!/var/www/html!$APACHE_DOCUMENT_ROOT!g" $APACHE_CONFDIR/sites-available/*.conf; \
    sed -ri -e "s!/var/www/!$APACHE_DOCUMENT_ROOT!g" $APACHE_CONFDIR/apache2.conf $APACHE_CONFDIR/conf-available/*.conf

RUN set -ex; a2enmod headers rewrite;

# Copy application

WORKDIR /var/www/html

COPY --chown=www-data:www-data  . ./

RUN set -ex; \
    \
    mkdir -p storage/app/files; \
    chown -R www-data:www-data storage; \
    chmod -R g+w bootstrap/cache storage

COPY --chown=www-data:www-data docker/.env.docker .env

# Install composer dependencies

RUN set -ex; \
    \
    composer install --no-interaction --no-progress --no-dev; \
    composer clear-cache; \
    chown -R www-data:www-data vendor

# Install node dependencies

RUN set -ex; \
    \
    npm ci; \
    npm run build; \
    rm -rf node_modules

# Run application

VOLUME ["/var/www/html/storage/app/files", "/var/www/html/storage/logs"]

ENTRYPOINT ["/var/www/html/docker/entrypoint.sh"]
CMD ["apache2-foreground"]
