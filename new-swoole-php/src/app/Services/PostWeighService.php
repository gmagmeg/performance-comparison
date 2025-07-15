<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Comment;
use App\Models\PostView;
use App\Models\User;
use App\Tracer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PostWeighService
{
    public function store(Request $request): JsonResponse
    {
        $isTrace = $request->boolean('trace', false);   
        $tracer = new Tracer($isTrace);

        // バリデーション
        $validated = $this->validateRequest($request);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

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
            $preparedData = $this->prepareData($validated, $existingUser);

            // データ保存
            $post = $this->saveData($preparedData);

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

    public function validateRequest(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:50',
            'status' => 'required|string|in:draft,published,archived',
            'tags' => 'required|array|max:5',
            'tags.*' => 'required_with:tags|string|max:50',
            'categories' => 'required|array|max:3',
            'categories.*' => 'required_with:categories|integer|min:1',
            'published_at' => 'nullable|date|after_or_equal:today',
        ]);
        if ($validator->fails()) {
            
            return response()->json($validator->errors(), 422);
        }
        $validated = $validator->validated();
        return $validated;
    }

    public function prepareData(array $validated, User $existingUser): array
    {
        // ダミーのユーザーデータを生成
        $users = [];
        for ($i = 1; $i <= 5; $i++) {
            $users[] = [
                'id' => $existingUser->id + $i - 1,
                'name' => 'Fake User ' . $i,
            ];
        }

        // カテゴリーデータの準備
        $categories = [];
        foreach ($validated['categories'] as $categoryId) {
            $categories[] = [
                'category_id' => $categoryId,
                'name' => 'Category ' . $categoryId,
                'slug' => 'category-' . $categoryId,
                'description' => 'Auto-generated category',
                'sort_order' => $categoryId,
            ];
        }

        // タグデータの準備
        $tags = [];
        foreach ($validated['tags'] as $tagName) {
            $tags[] = [
                'name' => $tagName,
                'slug' => Str::slug($tagName),
                'description' => 'Auto-generated tag',
            ];
        }

        // コメントデータの準備
        $commentData = [];
        foreach ($users as $user) {
            $commentData[] = [
                'user_id' => $user['id'],
                'author_name' => $user['name'],
                'content' => 'コンテンツのコメント ' . $user['name'],
                'status' => 'approved',
                'created_at' => Carbon::now()->toIso8601String(),
                'updated_at' => Carbon::now()->toIso8601String(),
            ];
        }
        
        $postViewData = [];
        foreach ($users as $user) {
            $postViewData[] = [
                'user_id' => $user['id'],
                'ip_address' => '127.0.0.1',
                'viewed_at' => Carbon::now()->toIso8601String(),
                'created_at' => Carbon::now()->toIso8601String(),
                'updated_at' => Carbon::now()->toIso8601String(),
            ];
        }
        
        // 投稿データの準備
        $postData = [
            'title' => $validated['title'],
            'slug' => Str::slug(title: $validated['title']) . Carbon::now()->timestamp . rand(900000, max: 999999),
            'content' => $validated['content'],
            'user_id' => $existingUser->id,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'] ?? Carbon::now(),
        ];
        
        return [
            'post_data' => $postData,
            'categories' => $categories,
            'tags' => $tags,
            'comment_data' => $commentData,
            'post_view_data' => $postViewData,
        ];
    }

    public function saveData(array $preparedData): Post
    {
        DB::beginTransaction();
        
        // 投稿の保存処理
        $post = Post::create($preparedData['post_data']);

        // カテゴリーの保存処理
        $categoryIds = [];
        foreach ($preparedData['categories'] as $categoryData) {
            $category = Category::firstOrCreate(
                ['category_id' => $categoryData['category_id']],
                $categoryData
            );
            $categoryIds[] = $category->category_id;
        }

        $post->categories()->attach($categoryIds, ['created_at' => Carbon::now()]);

        // タグの保存処理
        $tagIds = [];
        foreach ($preparedData['tags'] as $tagData) {
            $tag = Tag::firstOrCreate(
                ['slug' => $tagData['slug']],
                $tagData
            );
            $tagIds[] = $tag->tag_id;
        }
        $tagIds = array_unique($tagIds);
        $post->tags()->attach($tagIds, ['created_at' => Carbon::now()]);

        // コメントの保存処理
        foreach ($preparedData['comment_data'] as $comment) {
            Comment::create([
                'post_id' => $post->post_id,
                'user_id' => $comment['user_id'],
                'author_name' => $comment['author_name'],
                'content' => $comment['content'],
                'status' => $comment['status'],
            ]);
        }

        // ポストビューの保存処理
        foreach ($preparedData['post_view_data'] as $view) {
            PostView::create([
                'post_id' => $post->post_id,
                'user_id' => $view['user_id'],
                'ip_address' => $view['ip_address'],
                'viewed_at' => Carbon::parse($view['viewed_at']),
            ]);
        }

        DB::commit();
        
        return $post;
    }
}