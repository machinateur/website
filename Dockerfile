# last update 2022-02-01
FROM php:8.1-apache

# enable apache mod_rewrite
RUN a2enmod rewrite ssl expires

# install system dependencies
RUN apt-get update \
    && apt-get install -y libzip-dev git wget --no-install-recommends \
    && apt-get install -y openssl --no-install-recommends \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# create self-signed certificate for https
RUN mkdir -p /etc/apache2/ssl \
    && openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj \
    "/C=DE/ST=Hesse/L=Frankfurt/O=machinateur/OU=private/CN=machinateur.dev" \
    -keyout /etc/apache2/ssl/ssl.key -out /etc/apache2/ssl/ssl.crt

# required extensions (for now)
#RUN docker-php-ext-install zip
RUN docker-php-ext-install intl

# download composer latest 2.2.x lts version
RUN wget https://getcomposer.org/download/latest-2.2.x/composer.phar \
    && mv composer.phar /usr/bin/composer \
    && chmod +x /usr/bin/composer

# add apache configuration and the entrypoint script
COPY ./docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

# add the source
COPY ./ /var/www/website/

# set working directory
WORKDIR /var/www/website/

# install dependencies
RUN composer install -n

# get apache to run in foreground
CMD ["apache2-foreground"]
