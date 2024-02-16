FROM php:7.4-fpm

# Copy php-fpm configuration
COPY ./docker/app/conf/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf

# PHP configuration
COPY ./docker/app/conf/php/php.ini /usr/local/etc/php/conf.d/php.ini

# Copy app
COPY ./docroot /var/www/app

RUN userdel -f www-data &&\
    if getent group www-data ; then groupdel www-data; fi &&\
    groupadd -g 1000 www-data &&\
    useradd -l -u 1000 -g www-data www-data &&\
    install -d -m 0755 -o www-data -g www-data /var/www && \
    apt-get update && apt-get install -y --fix-missing \
    mariadb-client \
    imagemagick \
    graphviz \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libmcrypt-dev \
    libxml2-dev \
    libxslt1-dev \
    linux-libc-dev \
    libyaml-dev \
    libonig-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    libssl-dev && \
    rm -r /var/lib/apt/lists/* && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd \
    mysqli \
    pdo_mysql \
    gd \
    mbstring \
    xsl \
    opcache \
    intl \
    exif \
    bcmath \
    zip

# Install Composer, drush and drupal console
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    HOME=/ /usr/local/bin/composer global require drush/drush:~10  && \
    ln -s /.composer/vendor/drush/drush/drush /usr/local/bin/drush && \
#  && curl https://drupalconsole.com/installer -L -o /usr/local/bin/drupal \
#  && chmod +x /usr/local/bin/drupal \
#  && php --version; composer --version; drupal --version; drush --version
    chown -R www-data:www-data $HOME/.composer/ && \
    mkdir -p /var/www/.composer && \
    chown www-data /var/www/.composer && \
# Install composer dependencies
    bash -c "cd /var/www/app && composer install" && \
    mkdir -p /var/www/app/sites/default/files/tmp && \
    mkdir -p /var/www/app/sites/default/files/private && \
    mkdir -p /var/www/app/sites/default/files/private/report-interaction && \
    chown www-data:www-data -R /var/www/app && \
    chmod -R 755 /var/www/app/sites/default/files


# Set working directory
WORKDIR /var/www/app

EXPOSE 9000
CMD ["php-fpm"]
