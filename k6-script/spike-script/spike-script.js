/**
 * k6スパイクテストスクリプト - 読み取り専用API負荷試験
 *
 * このスクリプトは、読み取り専用API（/api/read-weight）に対するスパイクテストを実施します：
 * 
 * 【試験シナリオ】
 * - ターゲット: /api/read-weight エンドポイント（読み取り専用）
 * - パターン: 短時間で大量のリクエストを送信後、通常負荷に戻る
 * - 思考時間: 各操作間に1-3秒のランダム待機時間
 * - 負荷: 30秒で最大200ユーザーまで急激に増加、60秒間維持後急激に減少
 * 
 * 【合格基準 (SLO)】
 * - 通常時: 95パーセンタイル値 < 1秒
 * - スパイク時: 95パーセンタイル値 < 3秒
 * - エラーレート: < 1%
 *
 * 使用方法:
 *   k6 run -e ENV=frankenphp 04-spike-script/04-spike-script.js
 */
import http from 'k6/http';
import { sleep, check } from 'k6';
import { getBaseUrl, COMMON_HEADERS, COMMON_CHECKS } from '../common-config.js';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// --- 設定 ---
// 環境変数からテスト対象の環境を取得 (デフォルト: frankenphp)
const ENV = __ENV.ENV || 'frankenphp';
const BASE_URL = getBaseUrl(ENV);

// スパイクテストのシナリオ設定
const spikeScenario = {
  startVUs: 0,
  stages: [
    { duration: '10s', target: 10 },
    { duration: '20s', target: 200 },
    { duration: '10s', target: 0 }
  ],
  thresholds: {
    'http_req_duration': ['p(95)<2000'],
    'http_req_failed': ['rate<0.01']
  }
};

// --- k6 オプション ---
export const options = {
  scenarios: {
    spike_test: {
      executor: 'ramping-vus',
      startVUs: spikeScenario.startVUs,
      stages: spikeScenario.stages,
    },
  },
  thresholds: spikeScenario.thresholds,
  tags: {
    environment: ENV,
    test_type: 'spike',
  },
};

// --- メイン処理 ---
export default function() {
  const baseUrl = BASE_URL;
  const headers = COMMON_HEADERS.accept;

  // 100リクエスト毎にtrace=1を追加
  const shouldTrace = (__VU * __ITER) % 100 === 0;
  const traceParam = shouldTrace ? '?trace=1' : '';
  
  // Step 1: GET - 読み取り専用API（/api/read-weight）にアクセス
  const readUrl = `${baseUrl}/api/read-weight${traceParam}`;
  const readParams = {
    headers: headers,
    tags: {
      name: 'read_weight_spike',
    },
  };

  const readResponse = http.get(readUrl, readParams);

  // レスポンスの検証
  check(readResponse, {
    'GET status is 200': COMMON_CHECKS.status200,
    'GET response has data': COMMON_CHECKS.hasData,
    'GET response time < 5000ms': (r) => r.timings.duration < 5000,
  });

  // ユーザーの思考時間: 1-3秒のランダム待機
  sleep(randomIntBetween(1, 3));
}
