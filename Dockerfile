# PHP 8.2 + Laravel用の開発環境
# Composerとnpmを同梱し、ローカル開発に必要なツールを一式揃える
FROM php:8.2-fpm

# 作業ユーザー設定（コンテナ内のファイル権限をMacのユーザーと合わせる）
ARG UID=1000
ARG GID=1000

# システムパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# PHPの拡張機能インストール（Laravelの動作に必要）
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Composerのインストール（公式イメージからコピー、Docker Hubの認証なしで取得可能）
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node.jsのインストール（フロントエンドのビルドに必要）
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# www-dataユーザーのUID/GIDをMacのユーザーに合わせる（ファイル権限の問題を防ぐ）
RUN usermod -u ${UID} www-data && groupmod -g ${GID} www-data

USER www-data

EXPOSE 8000

# php artisan serveでLaravelの開発サーバーを起動
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
