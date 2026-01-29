FROM php:8.3-cli

# Extensions: mysqli + curl (Groq API)
RUN apt-get update \
	&& apt-get install -y --no-install-recommends libcurl4-openssl-dev \
	&& docker-php-ext-install mysqli curl \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

# Railway fornece PORT via env
EXPOSE 8080

# Use a router to prevent accidental exposure of repo files (sql/docs/config)
CMD php -S 0.0.0.0:${PORT:-8080} -t /app /app/router.php
