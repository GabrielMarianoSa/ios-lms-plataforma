FROM php:8.3-apache

# Extensions: mysqli + curl (Groq API)
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libcurl4-openssl-dev \
	&& docker-php-ext-install mysqli curl \
	&& rm -rf /var/lib/apt/lists/*

# Enable rewrite for simple access rules (.htaccess)
RUN a2enmod rewrite headers \
	&& echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
	&& a2enconf servername

WORKDIR /var/www/html
COPY . /var/www/html

# Railway fornece PORT via env
EXPOSE 8080

# Make Apache listen on Railway PORT
CMD ["bash", "-lc", "p=${PORT:-8080}; sed -i \"s/Listen 80/Listen ${p}/\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost \\*:${p}>/\" /etc/apache2/sites-available/000-default.conf; apache2-foreground"]
