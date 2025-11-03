<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\User;
use App\Models\BoardList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListCrudTest extends TestCase
{
    use RefreshDatabase;

    // ユーザーとボードをセットアップする共通メソッド
    private function setupBoard(): array
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['owner_id' => $user->id]);
        $user->boards()->attach($board->id);
        
        $this->actingAs($user);

        return ['user' => $user, 'board' => $board];
    }

    /**
     * 1. リスト作成（C）のテスト
     */
    public function test_user_can_create_list(): void
    {
        $data = $this->setupBoard();

        $response = $this->postJson(route('lists.store', $data['board']), [
            'title' => 'New List Title',
        ]);

        $response->assertStatus(200); // 201 (Created) or 200 (OK)
        $response->assertJson(['title' => 'New List Title']);
        $this->assertDatabaseHas('lists', [
            'board_id' => $data['board']->id,
            'title' => 'New List Title',
            'order' => 1, // 最初のリスト
        ]);
    }

    /**
     * 2. リスト更新（U）のテスト
     */
    public function test_user_can_update_list_title(): void
    {
        $data = $this->setupBoard();
        $list = BoardList::factory()->create(['board_id' => $data['board']->id]);

        $response = $this->patchJson(route('lists.update', $list), [
            'title' => 'Updated Title',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['title' => 'Updated Title']);
        $this->assertDatabaseHas('lists', [
            'id' => $list->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * 3. リスト削除（D）のテスト
     */
    public function test_user_can_delete_list(): void
    {
        $data = $this->setupBoard();
        $list = BoardList::factory()->create(['board_id' => $data['board']->id]);

        $response = $this->deleteJson(route('lists.destroy', $list));

        $response->assertStatus(204); // No Content
        $this->assertDatabaseMissing('lists', [
            'id' => $list->id,
        ]);
    }

    /**
     * 4. リスト並び替え（Sort）のテスト
     */
    public function test_user_can_update_list_order(): void
    {
        $data = $this->setupBoard();
        $list1 = BoardList::factory()->create(['board_id' => $data['board']->id, 'order' => 0]);
        $list2 = BoardList::factory()->create(['board_id' => $data['board']->id, 'order' => 1]);

        // [1, 2] の順序を [2, 1] に変更する
        $newOrderIds = [$list2->id, $list1->id];

        $response = $this->patchJson(route('lists.updateOrder'), [
            'orderedListIds' => $newOrderIds,
        ]);

        $response->assertStatus(204);
        
        // データベースで順序が変更されたかを確認
        $this->assertDatabaseHas('lists', ['id' => $list1->id, 'order' => 1]);
        $this->assertDatabaseHas('lists', ['id' => $list2->id, 'order' => 0]);
    }
}