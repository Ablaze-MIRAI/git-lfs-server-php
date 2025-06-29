FROM php:7.2-apache
RUN  a2enmod rewrite
COPY ./*.php /var/www/html/
COPY ./.htaccess /var/www/html/
RUN mkdir /var/www/html/objects
