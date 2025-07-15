# 概要
PHPカンファレンス関西（https://2025.kphpug.jp） の発表に用いた検証環境郡です。  
ご自由にお使いください。  
各環境の簡易な構築手順と負荷試験の実行手順を載せています。

# 開発環境構築手順

各ディレクトリに移動する
```shell
docker compose build
docker compose up -d
```

# 共通環境変数

- apache-php
- nginx-fpm
- swoole-php
- frankenphp

# ロードテストの実行

- CPU、メモリ計測
```shell
timeout 260s ./monitor-cpu-memory.sh ■■■コンテナID■■■ 10s ./results/apache-memory.csv
```

- 負荷シナリオ実行
```shell
k6 run -e ENV=apache-php --out influxdb=http://localhost:8086/k6 ./k6-script/scripts/post-weight-flow-script.js
```

# スパイクテストの実行

- CPU、メモリ計測
```shell
timeout 50s ./monitor-cpu-memory.sh ■■■コンテナID■■■ 5s ./results/spkie-memory.csv
```

- 負荷シナリオ実行
```shell
k6 run -e ENV=環境変数 --out influxdb=http://localhost:8086/k6 ./k6-script/spike-script/spike-script.js
```
