# --- STAGE 1: PHP DEPENDENCIES BUILDER ---
FROM php:8.3-fpm-alpine AS php-builder

RUN apk add --no-cache git unzip libzip-dev curl oniguruma-dev libpng-dev libjpeg-turbo-dev freetype-dev && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql mbstring bcmath gd zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --optimize-autoloader --no-dev --no-scripts --no-progress --prefer-dist

COPY . .
RUN composer dump-autoload --optimize


# --- STAGE 2: NODE BUILDER (VITE/TW) ---
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package.json pnpm-lock.yaml ./
RUN npm install -g pnpm && pnpm install --frozen-lockfile

COPY --from=php-builder /app/vendor ./vendor

COPY resources ./resources
COPY vite.config.js postcss.config.js tailwind.config.js ./

RUN pnpm build


# --- STAGE 3: FINAL RUNTIME FOR LARAVEL ---
FROM php:8.3-fpm-alpine AS php-runtime

RUN apk add --no-cache \
        nginx \
        curl \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo_mysql \
        zip \
        gd
        
WORKDIR /var/www

# Copy vendor & app
COPY --from=php-builder /app /var/www

# Copy built assets
COPY --from=node-builder /app/public/build /var/www/public/build

# Permissions
RUN chown -R www-data:www-data /var/www && chmod -R 775 storage bootstrap/cache

# Copy Nginx configuration
COPY nginx.conf /etc/nginx/http.d/default.conf

# Copy entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/entrypoint.sh"]
