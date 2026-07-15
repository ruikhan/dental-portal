FROM php:8.2-apache

# ── Install MySQL PDO driver + enable Apache modules the app needs ──
RUN docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite headers expires deflate \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# ── Copy the whole project into the web root ──
COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

# ── Entrypoint dynamically binds Apache to Render's $PORT at runtime ──
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 10000
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
