FROM php:8.3-fpm

# System deps needed for Composer + Postgres PHP extension
RUN apt-get update && apt-get install -y \
    git unzip zip curl \
    libpq-dev \
  && docker-php-ext-install pdo_pgsql pgsql \
  && rm -rf /var/lib/apt/lists/*

# Install Composer inside the image
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

# Add entrypoint script
COPY docker/scripts/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
