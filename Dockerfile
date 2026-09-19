FROM php:5.6-fpm-alpine

RUN apk --update upgrade \
    && apk add \
        libpng-dev \
        libjpeg-turbo-dev \
        nginx \
        ca-certificates \
    && update-ca-certificates

RUN docker-php-ext-configure gd --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install gd

RUN rm -rf /var/cache/apk && mkdir -p /var/cache/apk

COPY www /var/www
RUN chown -R www-data:www-data /var/www

COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN install -o www-data -g www-data \
    -d /run/nginx \
       /var/lib/nginx \
       /var/log/nginx \
       /var/tmp/nginx \
    && sed -i "/^user/d" /etc/nginx/nginx.conf

COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

WORKDIR /var/www

EXPOSE 8080
CMD ["/entrypoint.sh"]
