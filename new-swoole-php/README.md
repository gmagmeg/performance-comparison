# Nginx + PHP-FPM + Laravel 環境

## 構成

- PHP: 8.3.21RC1-fpm-bullseye
- Nginx: 最新の公式イメージ
- Laravel: フレームワーク

## セットアップ手順

1. リポジトリをクローン
   ```
   git clone <リポジトリURL>
   cd <プロジェクト名>
   ```

2. Dockerコンテナを起動
   ```
   docker compose up -d
   ```

3. Laravelプロジェクトを新規作成
   ```
   docker compose exec app composer create-project laravel/laravel .
   ```

4. 権限を設定
   ```
   docker compose exec app chmod -R 777 storage bootstrap/cache
   ```

5. ブラウザでアクセス
   ```
   http://localhost
   ```

## コマンド

- コンテナ起動: `docker compose up -d`
- コンテナ停止: `docker compose down`
- アプリケーションシェルへのアクセス: `docker compose exec app bash`
- Laravelコマンド実行例: `docker compose exec app php artisan migrate` 