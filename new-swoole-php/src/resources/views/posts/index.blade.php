<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>投稿一覧</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .posts {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .post {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post h2 {
            margin-top: 0;
            font-size: 1.4rem;
        }
        .post-meta {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 10px;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            background: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <h1>投稿一覧</h1>

    @if(count($posts) > 0)
        <div class="posts">
            @foreach($posts as $post)
                <div class="post">
                    <h2>{{ $post->title ?? 'タイトルなし' }}</h2>
                    <div class="post-meta">
                        @if(isset($post->user))
                            投稿者: {{ $post->user->name ?? '不明' }} &bull; 
                        @endif
                        @if(isset($post->created_at))
                            {{ $post->created_at->format('Y/m/d H:i') }}
                        @endif
                    </div>
                    <div class="post-content">
                        {{ Str::limit($post->content ?? '内容なし', 150) }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <p>まだ投稿がありません。</p>
        </div>
    @endif
</body>
</html> 