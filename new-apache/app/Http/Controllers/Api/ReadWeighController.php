<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Tracer;
use Illuminate\Support\Collection;

class ReadWeighController extends Controller
{
    /**
     * 閲覧テスト用: 20件ずつページングしてpostsを取得
     */
    public function index(Request $request): JsonResponse
    {
        $isTrace = $request->boolean('trace', false);
        $tracer = new Tracer(enabled: $isTrace, rootSpanName: "read.weight");
        
        $tracer->startSpan('pdo.processed');
        $postCollection = $this->fetchPosts();
        $tracer->endSpan('pdo.processed');

        $tracer->startSpan('foreach.processed');

        $data = [];
        $totalViews = 0;
        $totalLikes = 0;
        /** @var Post $post */
        foreach ($postCollection as $post) {
            $response = $this->formatPostResponse($post, $data, $totalViews, $totalLikes);
            $data[] = $response;
        }
        $tracer->endSpan('foreach.processed');

        $tracer->startSpan('response.json');
        $response = response()->json([
            'data' => $data,
            'user_id' => 10,
            'posts_count' => count($data),
            'message' => 'Retrieved all posts and related data for user ID 10',
        ]);
        $tracer->endSpan('response.json');

        return $response;
    }

    // users.id=10のユーザーの記事と紐づくデータをすべて取得
    private function fetchPosts(): Collection
    {
        return Post::with([
            'user:id,name,email',
            'tags:tag_id,name,slug,color',
            'categories:category_id,name,slug',
            'comments:comment_id,post_id,user_id,author_name,content,status,created_at',
            'comments.user:id,name',
            'views:post_view_id,post_id,user_id,ip_address,viewed_at',
            'views.user:id,name',
            'likes:like_id,user_id,created_at',
            'likes.user:id,name'
        ])->where('user_id', 10)
            ->orderBy('post_id', 'desc')
            ->get();
    }

    /**
     * 投稿データをレスポンス形式にフォーマット
     */
    private function formatPostResponse(Post $post, array $data, int $totalViews, int $totalLikes): array
    {
        // ユーザー情報
        $user = [
            'id' => $post->user->id,
            'name' => $post->user->name,
            'email' => $post->user->email,
        ];
        // タグ情報
        $tags = $post->tags->map(function ($tag) {
            return [
                'tag_id' => $tag->tag_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'color' => $tag->color,
            ];
        });
        $categories = $post->categories->map(function ($category) {
            return [
                'category_id' => $category->category_id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        });
        $comments = $post->comments->map(function ($comment) {
            return [
                'comment_id' => $comment->comment_id,
                'content' => $comment->content,
                'status' => $comment->status,
                'created_at' => Carbon::create($comment->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        $response = [
            'post_id' => (int)$post->post_id,
            'title' => trim($post->title),
            'slug' => $post->slug,
            'content' => nl2br($post->content),
            'excerpt' => $post->excerpt,
            'featured_image' => $post->featured_image,
            'status' => $post->status,
            'published_at' => Carbon::create($post->published_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::create($post->created_at)->format('Y-m-d H:i:s'),
            'user' => $user,
            'tags' => $tags,
            'categories' => $categories,
            'comments' => $comments,                
        ];

        // いいね情報と閲覧数情報
        $viewCount = count($post->views);
        $likesCount = count($post->likes);

        $response['views_count'] = $viewCount;
        $response['likes_count'] = $likesCount;

        $dataCount = count($data) < 1 ? 1 : count($data);
        $response['average_views'] = bcdiv($viewCount, $dataCount, 3);
        $response['average_likes'] = bcdiv($likesCount, $dataCount, 3);
        $response['total_views'] = bcadd($viewCount, $totalViews, 0);
        $response['total_likes'] = bcadd($likesCount, $totalLikes, 0);

        return $response;
    }

} 