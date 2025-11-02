<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use App\Models\BoardList;
use App\Models\Card;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardShowTest extends TestCase
{
    use RefreshDatabase; // テスト実行のたびにDBをリセット

    /**
     * 認証済みのユーザーは、自分が所属するボードを閲覧できる
     */
    public function test_authenticated_user_can_view_their_board(): void
    {
        // 1. 準備 (Arrange)
        $user = User::factory()->create();
        $board = Board::factory()->create(['owner_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $card = Card::factory()->create(['board_list_id' => $list->id]);
        $user->boards()->attach($board->id); // ユーザーをボードのメンバーにする

        // 2. 実行 (Act)
        // そのユーザーとしてログインし、ボード表示ページにアクセス
        $response = $this->actingAs($user)->get(route('boards.show', $board));

        // 3. 検証 (Assert)
        $response->assertStatus(200); // ページが正常に表示されたか
        $response->assertViewIs('boards.show'); // 正しいビューが使われているか
        $response->assertSee($board->title); // ボードのタイトルが表示されているか
        $response->assertSee($list->title); // リストのタイトルが表示されているか
        $response->assertSee($card->title); // カードのタイトルが表示されているか
    }

    /**
     * ゲスト（未認証ユーザー）は、ボードを閲覧できず、ログインページにリダイレクトされる
     */
    public function test_guest_cannot_view_board(): void
    {
        // 1. 準備 (Arrange)
        $board = Board::factory()->create(); // 閲覧しようとするボード

        // 2. 実行 (Act)
        // ログインせずにボード表示ページにアクセス
        $response = $this->get(route('boards.show', $board));

        // 3. 検証 (Assert)
        $response->assertRedirect(route('login')); // ログインページにリダイレクトされたか
    }
}