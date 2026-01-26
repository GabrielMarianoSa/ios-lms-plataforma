FROM php:8.3-apache

# MySQL (mysqli)
RUN docker-php-ext-install mysqli

# Habilita mod_rewrite
RUN a2enmod rewrite

# Copia arquivos para pasta do Apache
COPY . /var/www/html/

# Permissões
RUN chown -R www-data:www-data /var/www/html

# Configura Apache para usar variável PORT do Railway
ENV PORT=8080
RUN sed -i 's/Listen 80/Listen ${PORT}/g' /etc/apache2/ports.conf && \
    sed -i 's/:80/:${PORT}/g' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["sh", "-c", "sed -i \"s/\\${PORT}/$PORT/g\" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
