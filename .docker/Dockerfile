FROM php:8.1-fpm

WORKDIR /tmp/

RUN apt update && \
    apt upgrade -y && \
    apt install -y \
      git \
      libzip-dev \
      zip \
      tcpdump

RUN docker-php-ext-install \
      bcmath \
      zip

RUN curl -sS https://getcomposer.org/installer -o composer-setup.php
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer


WORKDIR /usr/dev/
