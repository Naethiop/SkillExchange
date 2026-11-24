FROM php:8.3-apache

RUN docker-php-ext-install mysqli \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html
COPY railway-entrypoint.sh /usr/local/bin/railway-entrypoint

RUN chmod +x /usr/local/bin/railway-entrypoint \
    && chown -R www-data:www-data /var/www/html

EXPOSE 8080
CMD ["/usr/local/bin/railway-entrypoint"]
