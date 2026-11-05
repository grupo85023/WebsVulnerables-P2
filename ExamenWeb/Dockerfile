FROM php:8.2-apache
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli
# Hardening: PHP sin versión expuesta ni errores en pantalla; Apache sin versión y con cabeceras de seguridad
COPY hardening/php-security.ini /usr/local/etc/php/conf.d/zz-security.ini
COPY hardening/apache-security.conf /etc/apache2/conf-available/zz-security.conf
RUN a2enmod headers && a2enconf zz-security
