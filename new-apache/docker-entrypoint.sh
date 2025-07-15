#!/bin/bash
set -e

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force


# 引数として渡されたコマンドを実行
exec "$@" 