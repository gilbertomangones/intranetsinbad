FROM nginx:1.20

COPY docker/nginx/conf/site.conf /etc/nginx/conf.d/default.conf
COPY docker/nginx/conf/server.conf /etc/nginx/nginx.conf

COPY ./docroot /var/www/app

RUN userdel -f www-data &&\
    if getent group www-data ; then groupdel www-data; fi &&\
    groupadd -g 1000 www-data &&\
    useradd -l -u 1000 -g www-data www-data &&\
    install -d -m 0755 -o www-data -g www-data /var/www && \
    apt-get update -y && apt-get upgrade -y && apt-get install -y php php-cli php-xml php-mbstring php-gd \
    php-zip php-curl php-json php-pdo php-opcache zip git curl

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN chown -R www-data:www-data $HOME/.composer/ && \
    mkdir -p /var/www/.composer && \
    chown www-data /var/www/.composer && \
    bash -c "cd /var/www/app && composer install --no-scripts --no-autoloader" && \
    mkdir -p /var/www/app/sites/default/files/tmp && \
    mkdir -p /var/www/app/sites/default/files/private && \
    chown www-data:www-data -R /var/www/app/ && \
    chmod -R 755 /var/www/app/sites/default/files

# Set working directory
WORKDIR /var/www/app
