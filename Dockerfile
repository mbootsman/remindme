FROM nginx
ADD default.conf /etc/nginx/conf.d

FROM php:8.1-cli
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions bcmath

USER root
RUN chown -R www-data:www-data /var/www/html
