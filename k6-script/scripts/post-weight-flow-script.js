/**
 * k6負荷計測スクリプト - BtoCシナリオ対応 Webフロントエンド性能試験
 *
 * このスクリプトは、BtoCシナリオに基づいたWebアプリケーションの性能試験を実施します：
 * 
 * 【試験シナリオ】
 * - ユーザー行動: 1回の記事投稿に対し10回の記事閲覧（1:10の比率）
 * - 思考時間: 各操作間に2-5秒のランダム待機時間
 * - 負荷: 60秒で最大30ユーザーまで段階的増加、その後3分間維持
 * 
 * 【合格基準 (SLO)】
 * - 記事投稿: 90パーセンタイル値 < 3秒
 * - 記事閲覧: 90パーセンタイル値 < 1秒
 * - エラーレート: < 0.1%
 *
 * 使用方法:
 *   k6 run -e ENV=frankenphp scripts/post-weight-flow-script.js
 */
import http from 'k6/http';
import { sleep, check } from 'k6';
import { getBaseUrl, COMMON_HEADERS, COMMON_CHECKS } from '../common-config.js';
import { randomIntBetween } from 'https://jslib.k6.io/k6-utils/1.2.0/index.js';

// --- 設定 ---
// 環境変数からテスト対象の環境を取得 (デフォルト: frankenphp)
const ENV = __ENV.ENV || 'frankenphp';
const BASE_URL = getBaseUrl(ENV);

// POSTするデータ
const payload = JSON.parse(open('./post-weight-payload.json'));

// BtoCシナリオ専用の設定
const btocOptions = {
  scenarios: {
    btoc_scenario: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '60s', target: 80 }, // 60秒で30ユーザーまで段階的増加
        { duration: '120s', target: 80 }, // 3分間30ユーザーを維持
        { duration: '60s', target: 0 },  // 30秒で0ユーザーまで段階的減少
      ],
    },
  },
  thresholds: {
    // 記事投稿のSLO: 90パーセンタイル値 < 3秒
    'http_req_duration{name:post_weight_create}': ['p(90)<3000'],
    // 記事閲覧のSLO: 90パーセンタイル値 < 1秒
    'http_req_duration{name:post_weight_get}': ['p(90)<1000'],
    // エラーレート: < 0.1%
    'http_req_failed': ['rate<0.001'],
  },
};

// --- k6 オプション ---
export const options = btocOptions;

// --- メイン処理 ---
export default function() {
  const baseUrl = BASE_URL;
  const headers = COMMON_HEADERS.json;

  // 100リクエスト毎にtrace=1を追加
  const shouldTrace = (__VU * __ITER) % 100 === 0;
  const traceParam = shouldTrace ? '?trace=1' : '';
  
  // BtoCシナリオ: 1回の投稿に対し10回の閲覧（1:10の比率）
  // 11回に1回投稿、10回は閲覧のみ
  const actionType = (__VU + __ITER) % 11 === 0 ? 'post' : 'get';
  
  if (actionType === 'post') {
    // Step 1: POST - 投稿データを作成
    const postUrl = `${baseUrl}/api/post-weight${traceParam}`;
    const postParams = {
      headers: headers,
      tags: {
        name: 'post_weight_create',
      },
    };

    const postResponse = http.post(postUrl, JSON.stringify(payload), postParams);

    // POST レスポンスの検証
    const postCheck = check(postResponse, {
      'POST status is 200': COMMON_CHECKS.status200,
    });

    let postId = null;
    if (postCheck && postResponse.status === 200) {
      try {
        const postBody = JSON.parse(postResponse.body);
        postId = postBody['post-id'];
      } catch (e) {
        console.error('POST レスポンスの解析エラー:', e);
      }
    }

    // ユーザーの思考時間: 2秒待機
    sleep(2);

    // Step 2: GET - 作成した投稿データを取得
    if (postId) {
      const getUrl = `${baseUrl}/api/post-weight/${postId}${traceParam}`;
      const getParams = {
        headers: COMMON_HEADERS.accept,
        tags: {
          name: 'post_weight_get',
        },
      };

      const getResponse = http.get(getUrl, getParams);

      // GET レスポンスの検証
      check(getResponse, {
        'GET status is 200': COMMON_CHECKS.status200,
        'GET post has correct ID': (r) => {
          try {
            const body = JSON.parse(r.body);
            return body.post && body.post.post_id == postId;
          } catch (e) {
            return false;
          }
        },
      });

      sleep(1);
    } else {
      console.error('POST-IDが取得できませんでした。GETテストをスキップします。');
    }
  } else {
    // 閲覧のみ: ランダムなIDで記事を取得
    const randomId = randomIntBetween(1, 100); // 1-100の範囲でランダムID
    const getUrl = `${baseUrl}/api/post-weight/${randomId}${traceParam}`;
    const getParams = {
      headers: COMMON_HEADERS.accept,
      tags: {
        name: 'post_weight_get',
      },
    };

    const getResponse = http.get(getUrl, getParams);

    // GET レスポンスの検証
    check(getResponse, {
      'GET status is 200': COMMON_CHECKS.status200,
    });
  }

  sleep(2);
}