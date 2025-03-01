#!/bin/sh

# Wait for MySQL to be ready
until nc -z database 3306; do
  echo "Waiting for MySQL..."
  sleep 2
done

echo "MySQL is up! Running migrations..."


# Run migrations
php artisan migrate --force
