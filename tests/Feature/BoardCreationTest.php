<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardCreationTest extends TestCase
{
    use RefreshDatabase; // テスト実行のたびにDBをリセットする

    /**
     * A basic feature test example.
     */
    public function test_authenticated_user_can_create_board(): void
    {
        // 1. 準備 (Arrange)
        // テスト用のユーザーを作成し、そのユーザーとしてログインする
        $user = User::factory()->create();
        $this->actingAs($user);

        // 送信するデータ
        $boardData = [
            'title' => 'My New Test Board',
            'background_color' => '#6366F1',
        ];

        // 2. 実行 (Act)
        // ボード作成ルートにPOSTリクエストを送信する
        $response = $this->post(route('boards.store'), $boardData);

        // 3. 検証 (Assert)
        // リダイレクトが成功したか
        $response->assertRedirect(route('dashboard'));

        // データベースの`boards`テーブルにデータが正しく保存されたか
        $this->assertDatabaseHas('boards', [
            'title' => 'My New Test Board',
            'owner_id' => $user->id,
            'background_color' => '#6366F1',
        ]);

        // データベースの`board_user`（中間テーブル）に関連付けが保存されたか
        $this->assertDatabaseHas('board_user', [
            'user_id' => $user->id,
            'board_id' => 1, // (このテストでは最初のボードIDは1になるため)
        ]);
    }

    public function test_guest_cannot_create_board(): void
    {
        // 1. 準備 (Arrange)
        // ログインしていない状態（ゲスト）

        // 送信するデータ
        $boardData = [
            'title' => 'Guest Board',
        ];

        // 2. 実行 (Act)
        $response = $this->post(route('boards.store'), $boardData);

        // 3. 検証 (Assert)
        // ログインページにリダイレクトされたか
        $response->assertRedirect(route('login'));
    }

    public function test_board_requires_a_title(): void
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $this->actingAs($user);

        // タイトルがないデータ
        $boardData = [
            'background_color' => '#6366F1',
        ];

        // 2. 実行 (Act)
        $response = $this->post(route('boards.store'), $boardData);

        // 3. 検証 (Assert)
        // 'title'フィールドのエラーでセッションに戻されたか
        $response->assertSessionHasErrors('title');
    }
}