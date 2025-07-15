<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostWeightController Store メソッドテスト</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"], textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        textarea {
            height: 120px;
            resize: vertical;
        }
        .tag-input {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .tag-input input {
            flex: 1;
            min-width: 150px;
        }
        .category-input {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }
        .category-input input {
            flex: 1;
            min-width: 100px;
        }
        .btn {
            padding: 12px 30px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-secondary {
            background-color: #6c757d;
        }
        .btn-secondary:hover {
            background-color: #545b62;
        }
        .response {
            margin-top: 30px;
            padding: 15px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .response.success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .response.error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .checkbox-group {
            margin-bottom: 20px;
        }
        .checkbox-group label {
            display: inline;
            margin-left: 5px;
            font-weight: normal;
        }
        .info {
            background-color: #e7f3ff;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .small-text {
            font-size: 14px;
            color: #666;
        }
        .loading {
            display: none;
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PostWeightController Store メソッドテスト</h1>
        
        <div class="info">
            <strong>API エンドポイント:</strong> POST /api/post-weight<br>
            <strong>説明:</strong> 投稿データを作成し、関連するカテゴリー、タグ、コメント、ビューを保存します。<br>
            <strong>OpenTelemetry:</strong> トレースを有効にすると、処理の詳細なパフォーマンスデータを取得できます。
        </div>

        <form id="postWeightForm">
            <div class="form-group">
                <label for="title">タイトル *</label>
                <input type="text" id="title" name="title" required maxlength="255" placeholder="投稿のタイトルを入力してください">
            </div>

            <div class="form-group">
                <label for="content">コンテンツ *</label>
                <textarea id="content" name="content" required placeholder="投稿の内容を入力してください（最低50文字）"></textarea>
                <div class="small-text">最低50文字必要です</div>
            </div>

            <div class="form-group">
                <label for="status">ステータス *</label>
                <select id="status" name="status" required>
                    <option value="draft">下書き</option>
                    <option value="published">公開</option>
                    <option value="archived">アーカイブ</option>
                </select>
            </div>

            <div class="form-group">
                <label>タグ * (最大5個)</label>
                <div class="tag-input">
                    <input type="text" name="tags[]" placeholder="タグ1" maxlength="50">
                    <input type="text" name="tags[]" placeholder="タグ2" maxlength="50">
                    <input type="text" name="tags[]" placeholder="タグ3" maxlength="50">
                    <input type="text" name="tags[]" placeholder="タグ4" maxlength="50">
                    <input type="text" name="tags[]" placeholder="タグ5" maxlength="50">
                </div>
                <div class="small-text">少なくとも1つのタグが必要です</div>
            </div>

            <div class="form-group">
                <label>カテゴリー ID * (最大3個)</label>
                <div class="category-input">
                    <input type="number" name="categories[]" placeholder="カテゴリー ID 1" min="1">
                    <input type="number" name="categories[]" placeholder="カテゴリー ID 2" min="1">
                    <input type="number" name="categories[]" placeholder="カテゴリー ID 3" min="1">
                </div>
                <div class="small-text">少なくとも1つのカテゴリーIDが必要です</div>
            </div>

            <div class="form-group">
                <label for="published_at">公開日時</label>
                <input type="datetime-local" id="published_at" name="published_at">
                <div class="small-text">未来の日時を指定してください（省略可）</div>
            </div>

            <div class="checkbox-group">
                <input type="checkbox" id="trace" name="trace" value="1">
                <label for="trace">OpenTelemetry トレースを有効にする</label>
            </div>

            <div class="form-group">
                <button type="submit" class="btn">投稿を作成</button>
                <button type="button" class="btn btn-secondary" onclick="clearForm()">フォームクリア</button>
                <div class="loading" id="loading">処理中...</div>
            </div>
        </form>

        <div id="response" class="response" style="display: none;"></div>
    </div>

    <script>
        // CSRFトークンを取得
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.getElementById('postWeightForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const loadingEl = document.getElementById('loading');
            const responseEl = document.getElementById('response');
            const submitBtn = e.target.querySelector('button[type="submit"]');
            
            // ローディング状態を表示
            loadingEl.style.display = 'block';
            submitBtn.disabled = true;
            responseEl.style.display = 'none';
            
            try {
                // フォームデータを収集
                const formData = new FormData(e.target);
                const data = {};
                
                // 通常のフィールド
                data.title = formData.get('title');
                data.content = formData.get('content');
                data.status = formData.get('status');
                data.published_at = formData.get('published_at');
                data.trace = formData.get('trace') === '1';
                
                // タグの処理（空でない値のみ）
                const tags = formData.getAll('tags[]').filter(tag => tag.trim() !== '');
                if (tags.length > 0) {
                    data.tags = tags;
                }
                
                // カテゴリーの処理（空でない値のみ）
                const categories = formData.getAll('categories[]')
                    .filter(cat => cat.trim() !== '')
                    .map(cat => parseInt(cat));
                if (categories.length > 0) {
                    data.categories = categories;
                }
                
                console.log('送信データ:', data);
                
                // API呼び出し
                const response = await fetch('/api/post-weight', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                // レスポンスを表示
                responseEl.style.display = 'block';
                responseEl.className = 'response ' + (response.ok ? 'success' : 'error');
                responseEl.textContent = JSON.stringify(result, null, 2);
                
                if (response.ok) {
                    console.log('投稿作成成功:', result);
                    
                    // 成功時は作成されたポストの詳細を取得
                    if (result['post-id']) {
                        const showResponse = await fetch(`/api/post-weight/${result['post-id']}?trace=${data.trace ? 1 : 0}`);
                        const postData = await showResponse.json();
                        
                        responseEl.textContent += '\n\n--- 作成された投稿の詳細 ---\n' + JSON.stringify(postData, null, 2);
                    }
                } else {
                    console.error('投稿作成エラー:', result);
                }
                
            } catch (error) {
                console.error('通信エラー:', error);
                responseEl.style.display = 'block';
                responseEl.className = 'response error';
                responseEl.textContent = 'エラーが発生しました: ' + error.message;
            } finally {
                // ローディング状態を解除
                loadingEl.style.display = 'none';
                submitBtn.disabled = false;
            }
        });

        // フォームクリア関数
        function clearForm() {
            document.getElementById('postWeightForm').reset();
            document.getElementById('response').style.display = 'none';
        }

        // フォームに初期値を設定
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('title').value = 'テスト投稿 - ' + new Date().toLocaleString();
            document.getElementById('content').value = 'これはPostWeightControllerのstoreメソッドをテストするためのサンプル投稿です。このコンテンツは最低50文字の制限を満たすために十分な長さで作成されています。';
            document.getElementsByName('tags[]')[0].value = 'テスト';
            document.getElementsByName('tags[]')[1].value = 'Laravel';
            document.getElementsByName('categories[]')[0].value = '1';
            document.getElementsByName('categories[]')[1].value = '2';
        });
    </script>
</body>
</html>