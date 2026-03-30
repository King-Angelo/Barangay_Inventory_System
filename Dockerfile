# PHP app — document root is the legacy inventory project folder.
# Build from repository root: docker build -t barangay-app .
# Render: Docker runtime, root directory = repo root, Dockerfile path = Dockerfile

FROM php:8.2-apache

RUN docker-php-ext-install mysqli > /dev/null \
	&& docker-php-ext-enable mysqli

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www/html

COPY inventoryProjBrgy/inventoryProjBrgy/ .

EXPOSE 80
