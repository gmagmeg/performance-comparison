# 最新の安定版ベースイメージに更新
FROM php:8.3.20-fpm

# ユーザーとグループの設定
ARG USER_ID=1000
ARG GROUP_ID=1000

# レイヤーキャッシュ効率化のため、依存関係インストールを分離
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# PHP拡張機能インストーラーの取得
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

# PHP拡張機能のインストール
RUN install-php-extensions \
    opentelemetry \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    opcache

# Composerをインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ユーザーとグループを作成
RUN groupadd -g ${GROUP_ID} appuser && \
    useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash appuser

# 作業ディレクトリを設定
WORKDIR /var/www/html

# Composerファイルを先にコピー（依存関係キャッシュ効率化）
COPY ./composer.json ./composer.lock ./

# 依存関係のインストール（開発環境用）
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer suggest open-telemetry/sdk open-telemetry/exporter-otlp open-telemetry/opentelemetry-auto-laravel
RUN composer install --no-autoloader

# アプリケーションコードをコピー
COPY ./ /var/www/html/

# Composerの最終処理
RUN composer dump-autoload --optimize --no-scripts

# ディレクトリ権限の設定
COPY --chown=appuser:appuser --chmod=775 ./storage ./storage
COPY --chown=appuser:appuser --chmod=775 ./bootstrap/cache ./bootstrap/cache

# PHP-FPM設定ファイルをコピー
COPY ./docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# PHP設定ファイルをコピー
COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

# エントリーポイントスクリプトをコピー
COPY --chmod=755 ./docker/php/docker-entrypoint.sh /usr/local/bin/

# appuserに切り替え
USER appuser

# コンテナ起動時にPHP-FPMを起動
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

EXPOSE 9000 