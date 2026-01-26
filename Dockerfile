FROM php:8.3-apache

# MySQL (mysqli)
RUN docker-php-ext-install mysqli

# Habilita mod_rewrite
RUN a2enmod rewrite

# Copia arquivos para pasta do Apache
COPY . /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Railway usa $PORT, Apache precisa escutar nessa porta
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

EXPOSE ${PORT}

CMD ["apache2-foreground"]
