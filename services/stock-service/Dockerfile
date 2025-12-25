FROM php:8.1-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libonig-dev libpng-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring bcmath gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --prefer-dist

RUN chown -R www-data:www-data /var/www

EXPOSE 8000

CMD php artisan serve --host=0.0.0.0 --port=8000
