FROM phpswoole/swoole:php8.3-zts

# ユーザーとグループの設定
ARG USER_ID=1000
ARG GROUP_ID=1000

# システムの依存関係をインストール
RUN apt-get update && apt-get install -y \
    libssl-dev \
    libcurl4-openssl-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# ユーザーとグループを作成
RUN groupadd -g ${GROUP_ID} appuser && \
    useradd -u ${USER_ID} -g ${GROUP_ID} -m -s /bin/bash appuser && \
    echo 'appuser ALL=(ALL) NOPASSWD:ALL' >> /etc/sudoers

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions \
    opentelemetry \
    pcntl \
    pdo \
    pdo_mysql \
    bcmath \
    opcache

COPY ./src /app

WORKDIR /app

# .envファイルの準備 - Laravelプロジェクトのルートにある.env.exampleから.envをコピー
RUN if [ -f /app/.env.example ]; then \
        cp /app/.env.example /app/.env; \
    fi && \
    if [ -f /app/.env ]; then \
        chmod 666 /app/.env; \
    fi

RUN php artisan octane:install --server=swoole

COPY ./docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install
RUN composer dump-autoload  --optimize --no-scripts

# ストレージとキャッシュディレクトリのパーミッション設定
COPY --chown=appuser:appuser --chmod=775 ./storage ./storage
COPY --chown=appuser:appuser --chmod=775 ./bootstrap ./bootstrap

# appuserに切り替え
USER appuser

CMD [ "php", "artisan", "octane:start", "--host=0.0.0.0"]