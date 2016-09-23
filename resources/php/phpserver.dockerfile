FROM php:fpm

RUN docker-php-ext-install bcmath mbstring
