FROM vaultke/php8.4-fpm-nginx

COPY . /var/www/html
COPY ./queue.conf /etc/supervisord.conf
WORKDIR /var/www/html
RUN chmod -R 777 /var/www/html
