<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Fakerのシード値を固定して全環境で同じデータを生成
        fake()->seed(12345);
        
        $this->command->info('Creating 100 users...');
        
        $users = [];
        $currentTime = Carbon::now();
        
        // Generate 100 users data
        for ($i = 1; $i <= 100; $i++) {
            $users[] = [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'email_verified_at' => $currentTime,
                'password' => Hash::make('password'),
                'remember_token' => null,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ];
        }
        
        // Bulk insert users in chunks
        $chunkSize = 50;
        for ($i = 0; $i < count($users); $i += $chunkSize) {
            $chunk = array_slice($users, $i, $chunkSize);
            DB::table('users')->insert($chunk);
            unset($chunk);
        }
        
        $this->command->info('User seeding completed successfully!');
        $this->command->info('Created: 100 users');
    }
}