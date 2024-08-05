FROM php:8.2-fpm-alpine

# Workdir
ENV WORKDIR=/var/www/app
WORKDIR ${WORKDIR}

# Updates and installs system dependencies
RUN apk add --update --no-cache \
    bash \
    nano \
    tzdata \
    libxml2-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    postgresql-dev \
    libzip-dev \
    libxslt-dev \
    oniguruma-dev \
    openrc \
    libzip-dev \
    zip \
    icu-dev \
    libxml2-dev \
    shadow \
    autoconf \
    g++ \
    make \
    linux-headers \
    $PHPIZE_DEPS && \
    rm -rf /var/cache/apk/*

# Configure php dependency
RUN docker-php-ext-configure soap --enable-soap
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install php dependencies
RUN docker-php-ext-install \
  -j$(nproc) gd \
  # iconv \
  bcmath \
  xml \
  soap \
  mbstring \
  pdo \
  pdo_mysql \
  pdo_pgsql \
  mysqli \
  zip \
  opcache \
  intl \
  xsl \
  exif \
  soap

# Install xdebug
RUN pecl install xdebug \
 && docker-php-ext-enable xdebug \
 && echo 'xdebug.start_with_request=no' >> ${PHP_INI_DIR}/conf.d/xdebug.ini \
 && echo 'xdebug.mode=debug' >> ${PHP_INI_DIR}/conf.d/xdebug.ini \
 && echo 'xdebug.client_host=host.docker.internal' >> ${PHP_INI_DIR}/conf.d/xdebug.ini \
 && echo 'xdebug.client_port=9000' >>  ${PHP_INI_DIR}/conf.d/xdebug.ini \
 && echo 'xdebug.remote_cookie_expire_time=36000' >>  ${PHP_INI_DIR}/conf.d/xdebug.ini

# Install extension redis
RUN pecl -v install redis
RUN docker-php-ext-enable redis

#RUN apt update
#RUN apt install inkscape

# Copy php settings
COPY php.ini ${PHP_INI_DIR}/conf.d/99-php.ini

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

#Install nodejs
ENV NODE_VERSION 8.15
RUN apk add --no-cache --repository=http://dl-cdn.alpinelinux.org/alpine/v3.8/main/ nodejs=8.14.0-r0 npm

RUN node --version

# Expose port 9000 and start php-fpm server
EXPOSE 9000

CMD ["php-fpm"]
