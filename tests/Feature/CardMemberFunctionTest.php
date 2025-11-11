<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;

class CardMemberFunctionTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $memberUser;
    private Board $board;
    private Card $card;

    /**
     * テストのセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. ユーザーを作成
        $this->adminUser = User::factory()->create();
        $this->memberUser = User::factory()->create();

        // 2. ボードを作成
        $this->board = Board::factory()->create([
            'owner_id' => $this->adminUser->id
        ]);

        // 3. メンバーをボードにアタッチ (withPivot で 'role' を設定)
        $this->board->users()->attach([
            $this->adminUser->id => ['role' => 'admin'],
            $this->memberUser->id => ['role' => 'member'],
        ]);
        
        // 4. リストとカードを作成
        $list = BoardList::factory()->create(['board_id' => $this->board->id]);
        $this->card = Card::factory()->create(['board_list_id' => $list->id]);
        
        // 5. 認証済みユーザー（管理者）として振る舞う
        $this->actingAs($this->adminUser);
    }

    /**
     * 1. メンバー割り当て (POST /cards/{card}/assign-user/{user})
     */
    public function test_an_authenticated_user_can_assign_a_member_to_a_card()
    {
        // 1. 準備 (Arrange)
        // カードにメンバーが割り当てられていないことを確認
        $this->assertCount(0, $this->card->assignedUsers);

        // 2. 実行 (Act)
        // $memberUser (id: 2) を $this->card に割り当てる
        $response = $this->postJson(
            route('cards.assignUser', ['card' => $this->card, 'user' => $this->memberUser])
        );

        // 3. 検証 (Assert)
        $response->assertStatus(200);
        
        // 中間テーブル 'card_user' にレコードが作成されたか
        $this->assertDatabaseHas('card_user', [
            'card_id' => $this->card->id,
            'user_id' => $this->memberUser->id
        ]);

        // (参考) カードのリレーションを再読み込みして確認
        $this->assertCount(1, $this->card->fresh()->assignedUsers);
    }
    
    /**
     * 2. メンバー割り当て解除 (DELETE /cards/{card}/assign-user/{user})
     */
    public function test_an_authenticated_user_can_unassign_a_member_from_a_card()
    {
        // 1. 準備 (Arrange)
        // 最初に $memberUser を割り当てておく
        $this->card->assignedUsers()->attach($this->memberUser->id);
        $this->assertDatabaseHas('card_user', [
            'card_id' => $this->card->id,
            'user_id' => $this->memberUser->id
        ]);

        // 2. 実行 (Act)
        $response = $this->deleteJson(
            route('cards.unassignUser', ['card' => $this->card, 'user' => $this->memberUser])
        );

        // 3. 検証 (Assert)
        $response->assertStatus(200);
        
        // 中間テーブル 'card_user' からレコードが削除されたか
        $this->assertDatabaseMissing('card_user', [
            'card_id' => $this->card->id,
            'user_id' => $this->memberUser->id
        ]);
    }
}