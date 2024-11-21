# PHP 8.3 and Imagick extension inside Docker

Adding `ext-imagick` to a PHP `8.3` Docker image, from source.

## The situation

I recently found myself setting up a new PHP project, with its own simple Docker image.

What I already knew:

- Apache 2 will likely be the stage and production sever.
- The project should use PHP `8.3` (the latest version at the time).
- The unit-tests will require `ext-imagick` for image comparison.
  - In this case, utilizing [`machinateur/imagickompare`](https://github.com/machinateur/imagickompare)
    for comparing two images (or documents).
- I will be the only developer for the time being
  and will be programming on Windows and Mac over the course of the project.
  - This is why Docker was something I wanted  to get right before proceeding with development:
    I didn't want to bother with local setup stuff later on.

So far so good...

## The `Dockerfile`

I came up with an easy `Dockerfile`,
 based on the one used for [this website](https://github.com/machinateur/website/blob/main/Dockerfile) in development:

```Dockerfile
# last update 2024-11-21
FROM php:8.3-apache

# enable apache mod_rewrite
RUN a2enmod rewrite ssl expires

# install system dependencies
RUN apt-get update \
    && apt-get install -y pkg-config \
    git wget zip openssl curl sqlite3 \
    imagemagick libmagickwand-dev\
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install imagick

# enable required extensions
RUN docker-php-ext-install zip pdo_pgsql imagick

# create self-signed certificate for https
RUN mkdir -p /etc/apache2/ssl \
    && openssl req -new -newkey rsa:4096 -days 365 -nodes -x509 -subj \
    "/C=DE/ST=Hesse/L=Frankfurt/O=machinateur/OU=private/CN=the-project.local" \
    -keyout /etc/apache2/ssl/ssl.key -out /etc/apache2/ssl/ssl.crt

# download composer latest 2.2.x lts version
RUN wget https://getcomposer.org/download/latest-2.2.x/composer.phar \
    && mv composer.phar /usr/bin/composer \
    && chmod +x /usr/bin/composer

# add apache configuration and the entrypoint script
COPY ./docker/apache.conf /etc/apache2/sites-enabled/000-default.conf

# add the source
COPY ./ /var/www/app/

# set working directory
WORKDIR /var/www/app/

# install dependencies
RUN composer install -n

# get apache to run in foreground
CMD ["apache2-foreground"]
```

Easy, right? *At least that's what I though...*

---

First some comments on the above `Dockerfile`:

Since I already knew the project will later likely run on apache 2, this was a good starting point for the image.
It already came with PHP and the server, as well as a way to reliably install most common PHP extensions, as well as
 some ways to manage apache 2 extensions as well.

The `./docker/apache.conf` file used above is mostly similar to the default apache configuration
 from [this website](https://github.com/machinateur/website/blob/main/docker/apache.conf) as well.
Make sure to change the domain name (from `the-project.local`) for the SSL certificate and inside the webserver configuration.

Back to the story:

## Now here is the problem

As it quickly turned out, as of now (November 2024) the imagick PHP extension from `pecl` is (still) not working for PHP `8.3`.
 According to reports online, it works fine with `8.2`, but I didn't bother to test this. That was not an alternative for me.

To be correct, actually, the version currently available over `pecl` is broken, see [_Solution_](#solution) below.

Thankfully with Linux and Docker, we always have the option to build from source.
I also found a blog article that briefly explained the essential commands involved in that process.
 You can read it here: https://orkhan.dev/2024/02/07/using-imagick-with-php-83-on-docker/

## Solution

**So the solution is pretty much this**: Replace the call to `pecl` with one to git and the build scripts.

```Dockerfile

# manually build PHP imagick extension, as no 8.3 version is available
RUN git clone https://github.com/Imagick/imagick.git --depth 1 /tmp/imagick \
    && cd /tmp/imagick \
    && git fetch origin master \
    && git switch master \
    && phpize && ./configure \
    && make && make install \
    && docker-php-ext-enable imagick \
    && rm -rf /tmp/*
```

The problem is, as I understand it from the [original issue over at `imagick/imagick` GitHub](https://github.com/Imagick/imagick/issues/640),
 caused by a missing closing parenthesis (i.e. `}`) in a stub file. Feel free to track the progress of releasing a fix there.

It also mentions some other workarounds and links different threads.
 In general a good starting point for researching the problem.
