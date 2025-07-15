<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PostWeighController 動作確認</title>
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
            background: white;
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
        input[type="text"], input[type="number"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        .tag-input {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
        .tag-input input {
            flex: 1;
            min-width: 100px;
        }
        .category-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }
        .category-checkboxes label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
        }
        .category-checkboxes input[type="checkbox"] {
            width: auto;
            margin-right: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 30px;
            padding: 20px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .loading {
            text-align: center;
            color: #666;
        }
        .form-section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .form-section h2 {
            margin-top: 0;
            color: #333;
        }
        small {
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>PostWeighController 動作確認</h1>
        
        <div class="form-section">
            <h2>単一投稿作成</h2>
            <form id="postForm">
            <div class="form-group">
                <label for="title">タイトル (必須)</label>
                <input type="text" id="title" name="title" required>
            </div>
            
            <div class="form-group">
                <label for="content">コンテンツ (50文字以上必須)</label>
                <textarea id="content" name="content" required placeholder="50文字以上入力してください"></textarea>
            </div>
            
            <div class="form-group">
                <label for="user_id">ユーザーID (必須)</label>
                <input type="number" id="user_id" name="user_id" value="1" required min="1">
            </div>
            
            <div class="form-group">
                <label for="status">ステータス</label>
                <select id="status" name="status" required>
                    <option value="draft">下書き</option>
                    <option value="published" selected>公開</option>
                    <option value="archived">アーカイブ</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>タグ (最大5個)</label>
                <div class="tag-input">
                    <input type="text" name="tags[]" placeholder="タグ1">
                    <input type="text" name="tags[]" placeholder="タグ2">
                    <input type="text" name="tags[]" placeholder="タグ3">
                    <input type="text" name="tags[]" placeholder="タグ4">
                    <input type="text" name="tags[]" placeholder="タグ5">
                </div>
            </div>
            
            <div class="form-group">
                <label>カテゴリ (最大3個)</label>
                <div class="category-checkboxes">
                    <label><input type="checkbox" name="categories[]" value="1"> カテゴリ1</label>
                    <label><input type="checkbox" name="categories[]" value="2"> カテゴリ2</label>
                    <label><input type="checkbox" name="categories[]" value="3"> カテゴリ3</label>
                    <label><input type="checkbox" name="categories[]" value="4"> カテゴリ4</label>
                    <label><input type="checkbox" name="categories[]" value="5"> カテゴリ5</label>
                </div>
            </div>
            
            <button type="submit">投稿作成</button>
            <button type="button" id="showPostBtn" disabled>投稿を表示</button>
        </form>
        </div>
        
        <div class="form-section">
            <h2>CSV一括投稿</h2>
            <form id="csvForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="csvFile">CSVファイル (最大10MB)</label>
                    <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" required>
                    <small>必要なヘッダー: title, content, user_id, status, tags, categories</small>
                    <br><small>tags/categoriesは"|"で区切って複数指定可能</small>
                </div>
                <button type="submit">CSV処理実行</button>
                <button type="button" id="downloadSampleBtn">サンプルCSVダウンロード</button>
            </form>
        </div>
        
        <div id="result"></div>
    </div>

    <script>
        let lastCreatedPostId = null;
        
        document.getElementById('postForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {};
            
            // フォームデータを整理
            for (let [key, value] of formData.entries()) {
                if (key.endsWith('[]')) {
                    const keyName = key.slice(0, -2);
                    if (!data[keyName]) data[keyName] = [];
                    if (value.trim()) data[keyName].push(value.trim());
                } else {
                    data[key] = value;
                }
            }
            
            // 必須フィールドの確認
            if (!data.tags || data.tags.length === 0) {
                alert('少なくとも1つのタグを入力してください');
                return;
            }
            
            if (!data.categories || data.categories.length === 0) {
                alert('少なくとも1つのカテゴリを選択してください');
                return;
            }
            
            // カテゴリを数値に変換
            data.categories = data.categories.map(cat => parseInt(cat));
            
            showResult('投稿を作成中...', 'loading');
            
            try {
                const response = await fetch('/api/post-weight', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    lastCreatedPostId = result['post-id'];
                    document.getElementById('showPostBtn').disabled = false;
                    showResult(JSON.stringify(result, null, 2), 'success');
                } else {
                    showResult(JSON.stringify(result, null, 2), 'error');
                }
            } catch (error) {
                showResult('エラー: ' + error.message, 'error');
            }
        });
        
        document.getElementById('showPostBtn').addEventListener('click', async function() {
            if (!lastCreatedPostId) return;
            
            showResult('投稿を取得中...', 'loading');
            
            try {
                const response = await fetch(`/api/post-weight/${lastCreatedPostId}`);
                const result = await response.json();
                
                if (response.ok) {
                    showResult(JSON.stringify(result, null, 2), 'success');
                } else {
                    showResult(JSON.stringify(result, null, 2), 'error');
                }
            } catch (error) {
                showResult('エラー: ' + error.message, 'error');
            }
        });
        
        document.getElementById('csvForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            showResult('CSVファイルを処理中...', 'loading');
            
            try {
                const response = await fetch('/api/post-weight/csv', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (response.ok) {
                    let message = `処理完了: ${result.processed_count}件成功`;
                    if (result.error_count > 0) {
                        message += `, ${result.error_count}件エラー`;
                        if (result.errors.length > 0) {
                            message += '\n\nエラー詳細:\n' + result.errors.map(e => 
                                `行${e.row}: ${e.error || JSON.stringify(e.errors)}`
                            ).join('\n');
                        }
                    }
                    showResult(message, result.error_count > 0 ? 'error' : 'success');
                } else {
                    showResult(JSON.stringify(result, null, 2), 'error');
                }
            } catch (error) {
                showResult('エラー: ' + error.message, 'error');
            }
        });
        
        document.getElementById('downloadSampleBtn').addEventListener('click', function() {
            const csvContent = 'title,content,user_id,status,tags,categories\n' +
                '"サンプル投稿1","これは50文字以上のサンプルコンテンツです。テスト用のデータとして使用します。",1,published,"タグ1|タグ2","1|2"\n' +
                '"サンプル投稿2","もう一つのサンプル投稿です。このコンテンツも50文字以上になるように調整しています。",2,draft,"タグ3|タグ4|タグ5","2|3"';
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'sample_posts.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
        
        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.className = `result ${type}`;
            resultDiv.textContent = message;
        }
    </script>
</body>
</html>