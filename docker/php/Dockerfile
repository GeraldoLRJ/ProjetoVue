ARG PUID=1000
ARG PGID=1000

FROM php:8.0-fpm-alpine

# Re-declare build args for post-FROM usage and set ENV defaults
ARG PUID
ARG PGID
ENV PUID=${PUID} \
    PGID=${PGID}

# System deps
RUN apk add --no-cache \
        bash git curl zip unzip icu-dev libzip-dev oniguruma-dev \
        libpng libpng-dev freetype-dev libjpeg-turbo-dev \
        postgresql-dev autoconf make g++ \
    && docker-php-ext-configure intl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" intl pdo pdo_pgsql mbstring zip bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del autoconf make g++

# Create user to match host UID/GID for file permissions
RUN addgroup -g ${PGID:-1000} appgroup 2>/dev/null || addgroup -g ${PGID:-1000} appgroup \
    && adduser -D -G appgroup -u ${PUID:-1000} appuser 2>/dev/null || true

WORKDIR /var/www/html

USER appuser

CMD ["php-fpm"]
