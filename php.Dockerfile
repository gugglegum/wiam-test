FROM php:8.2-fpm

# Creates a new request based on an incoming POST request.
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql

# Remove apt caches to reduce the image size
RUN apt-get clean && rm -rf /var/lib/apt/lists/*
