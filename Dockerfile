FROM php:8.3-cli

# MySQL (mysqli)
RUN docker-php-ext-install mysqli

WORKDIR /app
COPY . /app

# Railway fornece a porta via $PORT
CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
