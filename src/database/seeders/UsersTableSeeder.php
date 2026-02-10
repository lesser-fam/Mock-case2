<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ===== 管理者 =====
        User::create([
            'name' => '管理者A',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => '管理者B',
            'email' => 'admin2@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // ===== 一般ユーザー =====
        $users = [
            ['name' => '山田 太郎', 'email' => 'user1@example.com'],
            ['name' => '佐藤 次郎', 'email' => 'user2@example.com'],
            ['name' => '鈴木 三郎', 'email' => 'user3@example.com'],
            ['name' => '高橋 四郎', 'email' => 'user4@example.com'],
            ['name' => '伊藤 五郎', 'email' => 'user5@example.com'],
            ['name' => '渡辺 六郎',   'email' => 'user6@example.com'],
        ];

        foreach ($users as $user) {
            User::create([
                'name' => $user['name'],
                'email' => $user['email'],
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
        }
    }
}
