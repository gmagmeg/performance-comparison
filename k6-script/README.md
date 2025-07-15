# K6負荷計測スクリプト

このスクリプトは複数のPHP実行環境に対して負荷計測を行うためのK6スクリプトです。

## 🆕 新しい負荷計測スクリプト（推奨）

### `01-load-script.js` - 軽負荷〜高負荷テスト対応

軽負荷、中負荷、高負荷の3つのシナリオに対応した新しいスクリプトです。

#### 簡単実行（推奨）

```bash
# 01-load-scriptディレクトリに移動
cd 01-load-script

# ヘルプ表示
./run-load-test.sh --help

# 軽負荷テスト（デフォルト）
./run-load-test.sh frankenphp lightweight

# 中負荷テスト
./run-load-test.sh frankenphp medium

# 高負荷テスト  
./run-load-test.sh swoole-php heavy
```

#### 手動実行

```bash
# 01-load-scriptディレクトリに移動
cd 01-load-script

# 軽負荷テスト（1-10ユーザー、5分）
k6 run -e ENV=frankenphp -e SCENARIO=lightweight 01-load-script.js

# 中負荷テスト（50-100ユーザー、10分）
k6 run -e ENV=frankenphp -e SCENARIO=medium 01-load-script.js

# 高負荷テスト（200-500ユーザー、15分）
k6 run -e ENV=frankenphp -e SCENARIO=heavy 01-load-script.js
```

#### シナリオ設定のカスタマイズ

`load-scenarios.json`ファイルを編集することで、各シナリオの設定を変更できます：

```json
{
  "scenarios": {
    "lightweight": {
      "stages": [
        { "duration": "30s", "target": 1 },
        { "duration": "4m", "target": 10 },
        { "duration": "30s", "target": 0 }
      ]
    }
  }
}
```

## 対応環境

| 環境名 | ポート | 説明 |
|--------|--------|------|
| apache-php | 9100 | Apache + PHP環境 |
| nginx-fpm | 9200 | Nginx + PHP-FPM環境 |
| frankenphp | 9400 | FrankenPHP環境 |
| swoole-php | 9300 | Swoole + PHP環境 |

## 使用方法

### 基本的な実行方法

```bash
# デフォルト環境（apache-php）で実行
k6 run k6-script.js

# 特定の環境を指定して実行
k6 run -e ENV=frankenphp k6-script.js
k6 run -e ENV=nginx-fpm k6-script.js
k6 run -e ENV=swoole-php k6-script.js
```

### 結果をInfluxDBに送信する場合

```bash
k6 run -e ENV=frankenphp --out influxdb=http://localhost:8086/k6 k6-script.js
```

## 負荷設定

現在の設定:
- 5秒で1ユーザーまで上昇
- より高い負荷をかけたい場合は、`options.stages`の設定を変更してください

```javascript
stages: [
  { duration: '5s', target: 1 },   // 5秒で1ユーザーまで上昇
  { duration: '40s', target: 10 }, // 40秒間10ユーザー維持
  { duration: '10s', target: 0 },  // 10秒で0ユーザーまで下降
],
```

## 前提条件

- 各環境のDockerコンテナが起動していること
- 各環境で`/api/lightweight`エンドポイントが利用可能であること

## テストの実行

### 事前準備：環境確認

```bash
# 全環境の起動状況確認（プロジェクトルートで実行）
cd ..
chmod +x env-confirm.sh
./env-confirm.sh
```

### 簡単なテスト実行（推奨）

```bash
# k6-scriptディレクトリに移動
cd k6-script

# 特定環境のテスト実行
./run-test.sh apache      # Apache環境のみ
./run-test.sh nginx       # Nginx環境のみ
./run-test.sh swoole      # Swoole環境のみ
./run-test.sh frankenphp  # FrankenPHP環境のみ
./run-test.sh all         # 全環境を順次実行

# ヘルプ表示
./run-test.sh --help
```

### 手動テスト実行

```bash
# k6-scriptディレクトリに移動
cd k6-script

# TypeScript型定義のインストール（初回のみ）
npm install

# 特定環境のテスト実行
k6 run --env TARGET_ENV=apache light-load-test.ts
k6 run --env TARGET_ENV=nginx light-load-test.ts
k6 run --env TARGET_ENV=swoole light-load-test.ts
k6 run --env TARGET_ENV=frankenphp light-load-test.ts

# または npm scriptsを使用
npm run test:apache
npm run test:nginx
npm run test:swoole
npm run test:frankenphp
npm run test:all  # 全環境を順次実行
```

## テスト内容

### 軽負荷テスト仕様

- **段階的負荷**: 5ユーザー → 10ユーザー → 15ユーザー
- **テスト時間**: 合計7分間
- **テストパターン**:
  - 60%: 投稿一覧取得（ページネーション付き）
  - 25%: 単一投稿詳細取得
  - 15%: 全投稿一覧取得（高負荷処理）

### 性能基準

- **レスポンス時間**: 95%が500ms以内、99%が1秒以内
- **エラー率**: 1%未満
- **各種API**: 異なるレスポンス時間の基準

## 結果の確認

テスト結果は `results/` ディレクトリに以下の形式で保存されます：

```
results/
├── light-load-apache_mod_php-2024-01-XX...json
├── light-load-nginx_php_fpm-2024-01-XX...json
├── light-load-swoole-2024-01-XX...json
└── light-load-frankenphp-2024-01-XX...json
```

### コンソール出力例

```
=== 軽負荷テスト結果: Apache+mod_php ===
テスト時刻: 2024-01-15T10:30:00.000Z
平均レスポンス時間: 85.42ms
95%タイル: 156.78ms
99%タイル: 298.45ms
リクエスト総数: 1250
エラー率: 0.00%
平均RPS: 18.52
============================
```

## テストのカスタマイズ

### 負荷レベルの調整

`light-load-test.ts` の `stages` を修正：

```typescript
stages: [
  { duration: '2m', target: 20 },   // より高い負荷
  { duration: '5m', target: 50 },   // さらに高い負荷
  { duration: '2m', target: 0 },    // クールダウン
],
```

### エンドポイントの追加

新しいテスト関数を追加して、メインループに組み込み：

```typescript
function testNewEndpoint(): void {
  // 新しいAPIエンドポイントのテスト
}

// メイン関数内で呼び出し
export default function () {
  // ... 既存コード
  testNewEndpoint();
}
```

## トラブルシューティング

### 環境が応答しない場合

```bash
# 各環境の状態確認
curl http://localhost:9100/api/info  # Apache
curl http://localhost:9200/api/info  # Nginx
curl http://localhost:9300/api/info  # Swoole
curl http://localhost:9400/api/info  # FrankenPHP
```

### k6が見つからない場合

k6の公式ドキュメントを参照してインストール：
https://k6.io/docs/getting-started/installation/

### TypeScriptエラーの場合

```bash
npm install  # 型定義ファイルのインストール
```

## 高負荷テストへの発展

このスクリプトをベースに、より高い負荷のテストを作成可能：

1. **中負荷テスト**: 50-100ユーザー、10分間
2. **高負荷テスト**: 200-500ユーザー、15分間
3. **ストレステスト**: 段階的に負荷を増加して限界を測定 