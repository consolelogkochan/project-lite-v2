<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;

class BoardPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser; // ボードオーナー（管理者）
    private User $memberUser; // 一般メンバー
    private User $guestUser;  // ゲスト
    private User $strangerUser; // 無関係なユーザー
    private Board $board;

    /**
     * テストのセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. ユーザーを作成
        $this->adminUser = User::factory()->create();
        $this->memberUser = User::factory()->create();
        $this->guestUser = User::factory()->create();
        $this->strangerUser = User::factory()->create(); // 招待されていない人

        // 2. ボードを作成
        $this->board = Board::factory()->create([
            'owner_id' => $this->adminUser->id
        ]);

        // 3. メンバーをボードにアタッチ (withPivot で 'role' を設定)
        $this->board->users()->attach([
            $this->adminUser->id => ['role' => 'admin'],
            $this->memberUser->id => ['role' => 'member'],
            $this->guestUser->id => ['role' => 'guest'],
        ]);
    }

    /**
     * Phase 4: Test 1
     * 招待されていないユーザーはボードにアクセスできない (403 Forbidden)
     */
    public function test_stranger_user_cannot_view_board()
    {
        // $this->strangerUser (無関係な人) として振る舞う
        $response = $this->actingAs($this->strangerUser)->getJson(
            route('boards.show', $this->board)
        );

        // BoardPolicy@view が 'false' を返し、403 Forbidden が返る
        $response->assertStatus(403);
    }

    /**
     * Phase 4: Test 2
     * 「ゲスト」権限のユーザーが、閲覧はできるが、招待や編集はできない
     */
    public function test_guest_user_can_view_but_cannot_manage()
    {
        $this->actingAs($this->guestUser); // 「ゲスト」として振る舞う

        // A. 閲覧はできる (200 OK)
        $this->getJson(route('boards.show', $this->board))
             ->assertStatus(200);
             
        // B. メンバー一覧の閲覧 (API) もできる (200 OK)
        $this->getJson(route('boards.getMembers', $this->board))
             ->assertStatus(200);

        // C. ボード削除はできない (403 Forbidden)
        $this->deleteJson(route('boards.destroy', $this->board))
             ->assertStatus(403);
             
        // D. メンバー招待 (検索) もできない (403 Forbidden)
        $this->getJson(route('boards.searchUsers', $this->board) . '?q=test')
             ->assertStatus(403);
    }

    /**
     * Phase 4: Test 3
     * 「メンバー」権限のユーザーが、ボード設定（削除）を変更できない
     */
    public function test_member_user_cannot_delete_or_invite()
    {
        $this->actingAs($this->memberUser); // 「メンバー」として振る舞う

        // A. 閲覧はできる (200 OK)
        $this->getJson(route('boards.show', $this->board))
             ->assertStatus(200);

        // B. ボード削除はできない (403 Forbidden)
        $this->deleteJson(route('boards.destroy', $this->board))
             ->assertStatus(403);
             
        // C. メンバー招待 (検索) もできない (403 Forbidden)
        $this->getJson(route('boards.searchUsers', $this->board) . '?q=test')
             ->assertStatus(403);
    }
    
    /**
     * Phase 4: Test 4
     * 「管理者」権限のユーザーが、全ての操作を行える
     */
    public function test_admin_user_can_do_everything()
    {
        $this->actingAs($this->adminUser); // 「管理者」として振る舞う

        // A. 閲覧 (200 OK)
        $this->getJson(route('boards.show', $this->board))
             ->assertStatus(200);

        // B. メンバー招待 (検索) (200 OK)
        $this->getJson(route('boards.searchUsers', $this->board) . '?q=test')
             ->assertStatus(200);
             
        // C. メンバーの役割変更 (200 OK)
        $this->patchJson(route('boards.updateRole', ['board' => $this->board, 'user' => $this->memberUser]), [
            'role' => 'guest'
        ])->assertStatus(200);

        // D. ボード削除 (302 Found - ダッシュボードへのリダイレクト)
        // ※
        // $this->deleteJson() ではなく $this->delete() を使う
        // (BoardController@destroy が JSON ではなくリダイレクトを返すため)
        $response = $this->delete(route('boards.destroy', $this->board));
        $response->assertStatus(302); // 302 Found (Redirect)
        $response->assertRedirectToRoute('dashboard');
    }
}