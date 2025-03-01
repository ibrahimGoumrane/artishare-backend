FROM webdevops/php-nginx:8.3-alpine

# Install necessary dependencies
RUN apk add --no-cache oniguruma-dev libxml2-dev mysql-client nodejs npm

# Install PHP extensions
RUN docker-php-ext-install \
        bcmath \
        ctype \
        fileinfo \
        mbstring \
        pdo_mysql \
        xml

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set up working directory
ENV WEB_DOCUMENT_ROOT /app/public
ENV APP_ENV production
WORKDIR /app
COPY . .

# Copy the entrypoint script from the root folder where docker-compose is
COPY ./entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Ensure .env file exists
RUN cp -n .env.example .env

# Install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev


# Optimize Laravel configuration
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache
RUN php artisan storage:link
# Ensure storage and bootstrap/cache are writable
RUN chmod -R 775 storage bootstrap/cache
RUN chown -R application:application .

# Expose the necessary ports
EXPOSE 80

# Set entrypoint
# ENTRYPOINT ["/entrypoint.sh"]
