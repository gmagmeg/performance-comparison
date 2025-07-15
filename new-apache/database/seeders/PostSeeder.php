<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Post;
use App\Models\User;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fakerのシード値を固定して全環境で同じデータを生成
        fake()->seed(12345);
        
        // テーブルをクリア
        Post::truncate();

        // userに縛られない1000件のpostsデータを作成
        // 負荷テスト用のため、user_idはnullで作成
        $batchSize = 100; // バッチ処理でメモリ効率を向上
        $totalPosts = 1000;
        
        for ($i = 0; $i < $totalPosts; $i += $batchSize) {
            $posts = [];
            $currentBatchSize = min($batchSize, $totalPosts - $i);
            
            for ($j = 0; $j < $currentBatchSize; $j++) {
                $posts[] = [
                    'title' => 'パフォーマンステスト用投稿 ' . ($i + $j + 1),
                    'content' => 'これは負荷テスト用のコンテンツです。投稿番号: ' . ($i + $j + 1) . '。',
                    'user_id' => null, // userに縛られない
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            
            Post::insert($posts);
        }
        
        $this->command->info("1000件のpostsデータを正常に作成しました。");
    }
}
