# PHP 8.4 FPM (última versión estable con soporte completo del ecosistema).
# Cuando Laravel/librerías soporten bien 8.5, basta cambiar esta línea.
FROM php:8.4-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    curl-dev \
    git \
    unzip

RUN apk add --no-cache nodejs npm
# Extensiones PHP necesarias:
# - pdo_pgsql / pgsql : conexión a PostgreSQL
# - intl              : formateo de números/monedas (ej: pesos chilenos)
# - bcmath            : aritmética de precisión (crítico en montos contables)
# - zip, opcache, mbstring, xml : requeridas por Laravel y Composer
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    bcmath \
    zip \
    opcache \
    mbstring \
    xml

# Composer (gestor de paquetes de PHP)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuración PHP recomendada para desarrollo
RUN { \
    echo "display_errors=On"; \
    echo "error_reporting=E_ALL"; \
    echo "memory_limit=512M"; \
    echo "upload_max_filesize=50M"; \
    echo "post_max_size=50M"; \
    echo "date.timezone=America/Santiago"; \
  } > /usr/local/etc/php/conf.d/dev.ini

# Alinear www-data con el usuario del host (UID 1000) para
# que archivos creados por el contenedor sean editables desde VS Code
# y archivos del host sean escribibles por PHP
RUN apk add --no-cache shadow \
    && usermod -u 1000 www-data \
    && groupmod -g 1000 www-data

USER www-data

WORKDIR /var/www/html
