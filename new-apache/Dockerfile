FROM php:8.3.20-apache

ARG USER_ID=1000
ARG GROUP_ID=1000

# システムの依存関係をインストール
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    unzip \
    zip \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions installer
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# PHP拡張機能をインストール
RUN install-php-extensions \
    gd \
    pdo \
    pdo_mysql \
    exif \
    pcntl \
    opcache \
    opentelemetry \
    bcmath

# Apacheの設定（開発用）
RUN a2enmod rewrite

# Apacheのドキュメントルートを設定
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# アプリケーションディレクトリを設定
WORKDIR /var/www/html

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# アプリケーションファイルをコピー
COPY . /var/www/html/

# PHP設定ファイルをコピー
COPY php.ini /usr/local/etc/php/conf.d/99-custom.ini

# Composer依存関係をインストール（開発用）
RUN composer suggest open-telemetry/sdk open-telemetry/exporter-otlp open-telemetry/opentelemetry-auto-laravel
RUN composer install

COPY ./docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

COPY --chown=1000:1000 --chmod=775 ./storage ./storage
COPY --chown=1000:1000 --chmod=775 ./bootstrap/cache ./bootstrap/cache

# 起動スクリプトを設定
COPY --chmod=755 docker-entrypoint.sh /usr/local/bin/

USER 1000:1000

# コンテナ起動時のコマンド
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"] 