<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\PostWeighService;
use App\Tracer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostWeightController extends Controller
{
    /**
     * 新しい投稿をpostsテーブルに保存
     */
    public function store(Request $request): JsonResponse
    {
        headers_send(103);
        $isTrace = $request->boolean('trace', false);
        $tracer = new Tracer(enabled: $isTrace, rootSpanName: "store.post-weight");
        $postWeighService = new PostWeighService();

        // バリデーション
        $tracer->startSpan('store.validation');
        $validated = $postWeighService->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }
        $tracer->endSpan('store.validation');

        try {
            // データベースから既存のユーザーを取得
            $existingUser = User::first();
            if (!$existingUser) {
                DB::rollBack();
                return response()->json([
                    'message' => 'ユーザーが見つかりません。',
                    'error' => 'データベースにユーザーが存在しません。'
                ], 422);
            }

            // データ準備
            $tracer->startSpan('store.format-data');
            $preparedData = $postWeighService->prepareData($validated, $existingUser);
            $tracer->endSpan('store.format-data');

            // データ保存
            $tracer->startSpan('store.data-save');
            $post = $postWeighService->saveData($preparedData);
            $tracer->endSpan('store.data-save');

            $tracer->startSpan('store.response');
            $response = response()->json([
                'message' => 'ok',
                'post-id' => $post->post_id,
            ], 200);
            $tracer->endSpan('store.response');

            return $response;

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('投稿作成エラー', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            $response = response()->json([
                'message' => '投稿の作成に失敗しました。',
                'error' => '内部エラーが発生しました。'
            ], 500);

            return $response;
        }
    }

    /**
     * 保存した投稿をJSONとして返す
     */
    public function show(Request $request, $id): JsonResponse
    {
        headers_send(103);
        $isTrace = $request->boolean('trace', false);
        $tracer = new Tracer(enabled: $isTrace, rootSpanName: "show.post-weight");

        try {
            $tracer->startSpan(spanName: 'show.fetch-post');
            $post = Post::with(['categories', 'tags', 'comments', 'views'])
                        ->findOrFail($id);
            $tracer->endSpan('show.fetch-post');

            $tracer->startSpan('show.format-data');
            $data = $post->toArray();
            $tracer->endSpan('show.format-data');

            $tracer->startSpan('show.response');
            $response = response()->json([
                'post' => $data
            ], 200);
            $tracer->endSpan('show.response');

            return $response;

        } catch (\Exception $e) {
            Log::error('投稿取得エラー', [
                'error' => $e->getMessage(),
                'post_id' => $id
            ]);

            return response()->json([
                'message' => '投稿が見つかりませんでした。',
                'error' => '指定された投稿が存在しません。'
            ], 404);
        }
    }

}
