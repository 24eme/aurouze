FROM php:7.0.33-apache

RUN sed -i s/deb.debian.org/archive.debian.org/g /etc/apt/sources.list
RUN sed -i s/security.debian.org/archive.debian.org/g /etc/apt/sources.list
RUN sed -i s/stretch-updates/stretch/g /etc/apt/sources.list

RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    iputils-ping \
    libicu-dev \
    libmemcached-dev \
    libz-dev \
    libpq-dev \
    libjpeg-dev \
    libpng-dev \
    libfreetype6-dev \
    libssl-dev \
    libmcrypt-dev \
    libxml2-dev \
    libbz2-dev \
    libjpeg62-turbo-dev \
    librabbitmq-dev \
    libzip-dev \
    curl \
    git \
    subversion \
    unzip \
    ca-certificates \
  && rm -rf /var/lib/apt/lists/*

RUN cd /tmp/ && curl -fsSLk 'https://pecl.php.net/get/mongodb-1.9.2.tgz' -o mongodb.tar.gz \
    && mkdir -p mongodb \
    && tar -xf mongodb.tar.gz -C mongodb --strip-components=1 \
    && rm mongodb.tar.gz \
    && ( \
        cd mongodb \
        && phpize \
        && ./configure \
        && make -j "$(nproc)" \
        && make install \
    ) \
    && rm -r mongodb \
    && docker-php-ext-enable mongodb

ENV APACHE_DOCUMENT_ROOT /var/www/html/web/
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
