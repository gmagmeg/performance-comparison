/**
 * K6負荷テスト共通設定
 * 全ての負荷テストスクリプトで使用される共通設定
 */

// テスト対象環境のベースURL
export const BASE_URLS = {
  "apache-php": "http://localhost:9100",
  "nginx-fpm": "http://localhost:9200", 
  "frankenphp": "http://localhost:9400",
  "swoole-php": "http://localhost:9300"
};

// 環境名のバリデーションとベースURL取得
export function getBaseUrl(env = 'frankenphp') {
  const baseUrl = BASE_URLS[env];
  if (!baseUrl) {
    throw new Error(`無効な環境名: ${env}. 利用可能な環境: ${Object.keys(BASE_URLS).join(', ')}`);
  }
  return baseUrl;
}

// 共通HTTPヘッダー
export const COMMON_HEADERS = {
  json: {
    'Content-Type': 'application/json',
  },
  multipart: {
    'Content-Type': 'multipart/form-data',
  },
  accept: {
    'Accept': 'application/json',
  }
};

// 共通k6オプションのベース
export function createOptions(env, customOptions = {}) {
  return {
    tags: {
      environment: env,
    },
    ...customOptions
  };
}

// 共通チェック関数
export const COMMON_CHECKS = {
  status200: (r) => r.status === 200,
  status201: (r) => r.status === 201,
  status200Or201: (r) => r.status === 200 || r.status === 201,
  hasData: (r) => {
    try {
      const body = JSON.parse(r.body);
      return body.data !== undefined;
    } catch (e) {
      return false;
    }
  },
  hasSuccess: (r) => {
    try {
      const body = JSON.parse(r.body);
      return body.success !== undefined || body.message !== undefined;
    } catch (e) {
      return false;
    }
  }
};

// デフォルトのthresholds設定
export const DEFAULT_THRESHOLDS = {
  http_req_duration: ['p(95)<2000'],
  http_req_failed: ['rate<0.1'],
};