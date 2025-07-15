<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Comment;
use App\Models\PostView;
use App\Models\Like;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fakerのシード値を固定して全環境で同じデータを生成
        fake()->seed(12345);
        
        $this->command->info('Starting blog seeding process...');
        
        // Create 100 users
        $this->command->info('Creating 100 users...');
        $users = User::factory(100)->create();
        $userIds = $users->pluck('id')->toArray();
        
        // Create categories (including some with parent-child relationships)
        $this->command->info('Creating categories...');
        $categories = Category::factory(20)->create();
        
        // Create some child categories
        $childCategories = Category::factory(10)->create([
            'parent_id' => $categories->random()->category_id
        ]);
        $allCategories = $categories->concat($childCategories);
        $categoryIds = $allCategories->pluck('category_id')->toArray();
        
        // Create tags
        $this->command->info('Creating tags...');
        $tags = Tag::factory(100)->create();
        $tagIds = $tags->pluck('tag_id')->toArray();
        
        // Create posts (10 per user = 1,000 total)
        $this->command->info('Creating 1,000 posts (10 per user)...');
        $postData = [];
        $batchSize = 100;
        
        foreach ($users->chunk($batchSize) as $userChunk) {
            foreach ($userChunk as $user) {
                for ($i = 0; $i < 10; $i++) {
                    $title = fake()->sentence(rand(3, 8));
                    $postData[] = [
                        'user_id' => $user->id,
                        'title' => $title,
                        'slug' => \Illuminate\Support\Str::slug($title) . '-' . \Illuminate\Support\Str::random(8),
                        'content' => fake()->paragraphs(rand(5, 10), true),
                        'excerpt' => fake()->paragraph(rand(2, 4)),
                        'featured_image' => rand(0, 1) ? fake()->imageUrl(800, 600, 'articles') : null,
                        'status' => fake()->randomElement(['draft', 'published', 'archived']),
                        'published_at' => rand(0, 1) ? fake()->dateTimeBetween('-1 year', 'now') : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Insert batch
            Post::insert($postData);
            $postData = [];
            $this->command->info('Inserted posts for ' . $userChunk->count() . ' users');
        }
        
        // Get all created posts
        $posts = Post::all();
        $postIds = $posts->pluck('post_id')->toArray();
        
        // Attach tags to posts (5 tags per post)
        $this->command->info('Attaching tags to posts (5 per post)...');
        $postTagData = [];
        foreach ($posts->chunk(500) as $postChunk) {
            foreach ($postChunk as $post) {
                $selectedTags = array_rand(array_flip($tagIds), 5);
                foreach ($selectedTags as $tagId) {
                    $postTagData[] = [
                        'post_id' => $post->post_id,
                        'tag_id' => $tagId,
                        'created_at' => now(),
                    ];
                }
            }
            
            // Insert batch
            \DB::table('post_tags')->insert($postTagData);
            $postTagData = [];
            $this->command->info('Attached tags for ' . $postChunk->count() . ' posts');
        }
        
        // Attach categories to posts (5 categories per post)
        $this->command->info('Attaching categories to posts (5 per post)...');
        $postCategoryData = [];
        foreach ($posts->chunk(500) as $postChunk) {
            foreach ($postChunk as $post) {
                $selectedCategories = array_rand(array_flip($categoryIds), min(5, count($categoryIds)));
                foreach ($selectedCategories as $categoryId) {
                    $postCategoryData[] = [
                        'post_id' => $post->post_id,
                        'category_id' => $categoryId,
                        'created_at' => now(),
                    ];
                }
            }
            
            // Insert batch
            \DB::table('post_categories')->insert($postCategoryData);
            $postCategoryData = [];
            $this->command->info('Attached categories for ' . $postChunk->count() . ' posts');
        }
        
        // Create comments (5 per post = 5,000 total)
        $this->command->info('Creating comments (5 per post)...');
        $commentData = [];
        foreach ($posts->chunk(500) as $postChunk) {
            foreach ($postChunk as $post) {
                for ($i = 0; $i < 5; $i++) {
                    $hasUser = rand(0, 1);
                    $commentData[] = [
                        'post_id' => $post->post_id,
                        'user_id' => $hasUser ? $userIds[array_rand($userIds)] : null,
                        'parent_id' => null,
                        'author_name' => $hasUser ? null : fake()->name(),
                        'author_email' => $hasUser ? null : fake()->safeEmail(),
                        'content' => fake()->paragraphs(rand(1, 3), true),
                        'status' => fake()->randomElement(['pending', 'approved', 'spam', 'rejected']),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
            
            // Insert batch
            Comment::insert($commentData);
            $commentData = [];
            $this->command->info('Created comments for ' . $postChunk->count() . ' posts');
        }
        
        // Create post views (5 per post = 5,000 total)
        $this->command->info('Creating post views (5 per post)...');
        $postViewData = [];
        foreach ($posts->chunk(500) as $postChunk) {
            foreach ($postChunk as $post) {
                for ($i = 0; $i < 5; $i++) {
                    $postViewData[] = [
                        'post_id' => $post->post_id,
                        'user_id' => rand(0, 1) ? $userIds[array_rand($userIds)] : null,
                        'ip_address' => fake()->ipv4(),
                        'user_agent' => fake()->userAgent(),
                        'viewed_at' => fake()->dateTimeBetween('-6 months', 'now'),
                    ];
                }
            }
            
            // Insert batch
            PostView::insert($postViewData);
            $postViewData = [];
            $this->command->info('Created views for ' . $postChunk->count() . ' posts');
        }
        
        // Create likes for posts (5 per post = 5,000 total)
        $this->command->info('Creating likes for posts (5 per post)...');
        $likeData = [];
        foreach ($posts->chunk(500) as $postChunk) {
            foreach ($postChunk as $post) {
                $selectedUsers = array_rand(array_flip($userIds), 5);
                foreach ($selectedUsers as $userId) {
                    $likeData[] = [
                        'user_id' => $userId,
                        'likeable_type' => Post::class,
                        'likeable_id' => $post->post_id,
                        'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
                    ];
                }
            }
            
            // Insert batch
            Like::insert($likeData);
            $likeData = [];
            $this->command->info('Created likes for ' . $postChunk->count() . ' posts');
        }
        
        $this->command->info('Blog seeding completed successfully!');
        $this->command->info('Created:');
        $this->command->info('- 100 users');
        $this->command->info('- 1,000 posts (10 per user)');
        $this->command->info('- 5,000 comments (5 per post)');
        $this->command->info('- 5,000 post views (5 per post)');
        $this->command->info('- 5,000 likes (5 per post)');
        $this->command->info('- Post-tag relationships (5 tags per post)');
        $this->command->info('- Post-category relationships (5 categories per post)');
    }
} 