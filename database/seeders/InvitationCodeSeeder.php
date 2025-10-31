<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\InvitationCode; // この行を追加
use Illuminate\Support\Str; // この行を追加


class InvitationCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // テスト用の招待コードを10個作成する
        for ($i = 0; $i < 10; $i++) {
            InvitationCode::create([
                'code' => Str::random(16), // 16桁のランダムな文字列を生成
            ]);
        }
    }
}
