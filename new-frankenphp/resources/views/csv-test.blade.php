<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Upload Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
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
        .section {
            margin-bottom: 40px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .csv-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .csv-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .btn-small { background-color: #28a745; }
        .btn-medium { background-color: #17a2b8; }
        .btn-large { background-color: #ffc107; color: #333; }
        .btn-error { background-color: #dc3545; }
        .csv-buttons button:hover {
            opacity: 0.8;
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
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 4px;
            background: #fafafa;
        }
        .upload-form {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        .upload-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        button {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #0056b3;
        }
        button:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .result {
            margin-top: 20px;
            padding: 20px;
            border-radius: 4px;
            white-space: pre-wrap;
            font-family: monospace;
            font-size: 14px;
            max-height: 400px;
            overflow-y: auto;
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
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            text-align: center;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-item {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #007bff;
            border-radius: 4px;
        }
        .stat-label {
            font-weight: bold;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .trace-option {
            margin: 10px 0;
        }
        .trace-option label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }
        .trace-option input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
        }
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin-top: 0;
            color: #0066cc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>CSV Upload Performance Test</h1>
        
        <div class="section">
            <h2>テスト用CSVファイル生成</h2>
            <div class="info-box">
                <h3>ファイルサイズ目安</h3>
                <ul>
                    <li><strong>Small (10行):</strong> 約1KB - 基本動作確認用</li>
                    <li><strong>Medium (100行):</strong> 約10KB - 中規模データテスト用</li>
                    <li><strong>Large (1000行):</strong> 約100KB - パフォーマンステスト用</li>
                    <li><strong>Error Test:</strong> 約1KB - バリデーションエラーテスト用</li>
                </ul>
            </div>
            <div class="csv-buttons">
                <button class="btn-small" onclick="downloadCsv('small')">Small CSV (10行)</button>
                <button class="btn-medium" onclick="downloadCsv('medium')">Medium CSV (100行)</button>
                <button class="btn-large" onclick="downloadCsv('large')">Large CSV (1000行)</button>
                <button class="btn-error" onclick="downloadCsv('error')">Error Test CSV</button>
            </div>
        </div>
        
        <div class="section">
            <h2>CSV Upload Test</h2>
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="upload-form">
                    <div class="form-group">
                        <label for="csvFile">CSVファイルを選択</label>
                        <input type="file" id="csvFile" name="csv_file" accept=".csv,.txt" required>
                    </div>
                    <button type="submit">アップロード実行</button>
                </div>
                <div class="trace-option">
                    <label>
                        <input type="checkbox" id="enableTrace"> トレーシングを有効化
                    </label>
                </div>
            </form>
        </div>
        
        <div class="section">
            <h2>処理結果</h2>
            <div id="stats" class="stats" style="display: none;">
                <div class="stat-item">
                    <div class="stat-label">処理成功</div>
                    <div class="stat-value" id="successCount">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">エラー数</div>
                    <div class="stat-value" id="errorCount">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">処理時間</div>
                    <div class="stat-value" id="processingTime">-</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">ファイルサイズ</div>
                    <div class="stat-value" id="fileSize">-</div>
                </div>
            </div>
            <div id="result"></div>
        </div>
    </div>

    <script>
        let startTime;
        
        function downloadCsv(type) {
            const url = `/csv-test/generate?type=${type}`;
            const a = document.createElement('a');
            a.href = url;
            a.download = `test_${type}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
        
        document.getElementById('uploadForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('csvFile');
            const file = fileInput.files[0];
            
            if (!file) {
                alert('ファイルを選択してください');
                return;
            }
            
            const formData = new FormData();
            formData.append('csv_file', file);
            
            const enableTrace = document.getElementById('enableTrace').checked;
            if (enableTrace) {
                formData.append('trace', '1');
            }
            
            startTime = performance.now();
            showResult('CSVファイルを処理中...', 'loading');
            updateStats(null, file.size);
            
            try {
                const response = await fetch('/api/post-weight/csv', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const endTime = performance.now();
                const processingTime = ((endTime - startTime) / 1000).toFixed(2);
                
                const result = await response.json();
                
                if (response.ok) {
                    updateStats(result, file.size, processingTime);
                    
                    let message = `✅ 処理完了\n\n`;
                    message += `📊 統計情報:\n`;
                    message += `  - 成功: ${result.processed_count}件\n`;
                    message += `  - エラー: ${result.error_count}件\n`;
                    message += `  - 処理時間: ${processingTime}秒\n`;
                    message += `  - ファイルサイズ: ${formatFileSize(file.size)}\n\n`;
                    
                    if (result.error_count > 0 && result.errors.length > 0) {
                        message += `❌ エラー詳細:\n`;
                        result.errors.forEach(error => {
                            message += `  行${error.row}: ${error.error || JSON.stringify(error.errors)}\n`;
                        });
                    }
                    
                    if (enableTrace) {
                        message += `\n🔍 トレーシング有効 - Jaegerで詳細を確認できます`;
                    }
                    
                    showResult(message, result.error_count > 0 ? 'error' : 'success');
                } else {
                    updateStats(null, file.size, processingTime);
                    showResult(`❌ 処理失敗\n\n${JSON.stringify(result, null, 2)}`, 'error');
                }
            } catch (error) {
                const endTime = performance.now();
                const processingTime = ((endTime - startTime) / 1000).toFixed(2);
                updateStats(null, file.size, processingTime);
                showResult(`❌ ネットワークエラー\n\n${error.message}`, 'error');
            }
        });
        
        function showResult(message, type) {
            const resultDiv = document.getElementById('result');
            resultDiv.className = `result ${type}`;
            resultDiv.textContent = message;
        }
        
        function updateStats(result, fileSize, processingTime = null) {
            const statsDiv = document.getElementById('stats');
            statsDiv.style.display = 'grid';
            
            document.getElementById('successCount').textContent = result ? result.processed_count : '-';
            document.getElementById('errorCount').textContent = result ? result.error_count : '-';
            document.getElementById('processingTime').textContent = processingTime ? `${processingTime}s` : 'Processing...';
            document.getElementById('fileSize').textContent = formatFileSize(fileSize);
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
    </script>
</body>
</html>