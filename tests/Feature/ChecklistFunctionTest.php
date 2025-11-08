<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Checklist;
use App\Models\ChecklistItem;
use Illuminate\Support\Carbon; // (日付操作用)

class ChecklistFunctionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Board $board;
    private Card $card;
    private Checklist $checklist;
    private ChecklistItem $item1;

    /**
     * テストのセットアップ（各テスト実行前に呼ばれる）
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. 認証済みユーザーを作成
        $this->user = User::factory()->create();
        
        // 2. ユーザーが所有するボードを作成
        $this->board = Board::factory()->create(['owner_id' => $this->user->id]);
        $this->board->users()->attach($this->user->id);

        // 3. ボードに属するリストを作成
        $list = BoardList::factory()->create(['board_id' => $this->board->id]);

        // 4. リストに属するカードを作成
        $this->card = Card::factory()->create(['board_list_id' => $list->id]);
        
        // 5. カードに属するチェックリストを作成
        $this->checklist = Checklist::factory()->create([
            'card_id' => $this->card->id,
            'title' => 'Test Checklist'
        ]);

        // 6. チェックリストに属するアイテムを作成
        $this->item1 = ChecklistItem::factory()->create([
            'checklist_id' => $this->checklist->id,
            'content' => 'Item 1',
            'position' => 0
        ]);
        
        // 7. 認証済みユーザーとして振る舞う
        $this->actingAs($this->user);
    }

    /**
     * 1. チェックリスト作成 (POST /cards/{card}/checklists)
     */
    public function test_an_authenticated_user_can_create_a_checklist()
    {
        // 準備 (Arrange)
        $newChecklistData = ['title' => 'New Checklist'];
        
        // 実行 (Act)
        $response = $this->postJson(
            route('checklists.store', $this->card),
            $newChecklistData
        );

        // 検証 (Assert)
        $response->assertStatus(201) // 201 Created
                 ->assertJson($newChecklistData)
                 ->assertJsonFragment(['items' => []]); // items: [] が含まれる

        $this->assertDatabaseHas('checklists', [
            'card_id' => $this->card->id,
            'title' => 'New Checklist'
        ]);
    }
    
    /**
     * 2. チェックリスト項目作成 (POST /checklists/{checklist}/items)
     */
    public function test_an_authenticated_user_can_create_a_checklist_item()
    {
        // 準備 (Arrange)
        $newItemData = ['content' => 'Item 2'];
        // (setUp で item1 (position: 0) が作成済み)

        // 実行 (Act)
        $response = $this->postJson(
            route('checklist_items.store', $this->checklist),
            $newItemData
        );

        // 検証 (Assert)
        $response->assertStatus(201)
                 ->assertJson($newItemData)
                 ->assertJsonFragment(['position' => 1]); // 2番目 (0 の次)

        $this->assertDatabaseHas('checklist_items', [
            'checklist_id' => $this->checklist->id,
            'content' => 'Item 2',
            'position' => 1
        ]);
    }

    /**
     * 3. チェックリスト項目更新 (PATCH /checklist-items/{item})
     */
    public function test_an_authenticated_user_can_update_a_checklist_item()
    {
        // 準備 (Arrange)
        $updatedData = [
            'content' => 'Updated Content',
            'is_completed' => true
        ];

        // 実行 (Act)
        $response = $this->patchJson(
            route('checklist_items.update', $this->item1),
            $updatedData
        );
        
        // 検証 (Assert)
        $response->assertStatus(200)
                 ->assertJson($updatedData);
        
        $this->assertDatabaseHas('checklist_items', [
            'id' => $this->item1->id,
            'content' => 'Updated Content',
            'is_completed' => 1 // (true は 1 としてDBに保存される)
        ]);
    }

    /**
     * 4. チェックリスト項目削除 (DELETE /checklist-items/{item})
     */
    public function test_an_authenticated_user_can_delete_a_checklist_item()
    {
        // 実行 (Act)
        $response = $this->deleteJson(
            route('checklist_items.destroy', $this->item1)
        );

        // 検証 (Assert)
        $response->assertStatus(204); // 204 No Content
        $this->assertDatabaseMissing('checklist_items', ['id' => $this->item1->id]);
    }

    /**
     * 5. チェックリスト項目並び替え (PATCH /checklist-items/update-order)
     */
    public function test_an_authenticated_user_can_update_checklist_item_order()
    {
        // 準備 (Arrange)
        // item1 (pos: 0) に加えて item2 を作成
        $item2 = ChecklistItem::factory()->create([
            'checklist_id' => $this->checklist->id,
            'content' => 'Item 2',
            'position' => 1
        ]);
        // 初期状態: [item1, item2]

        // 実行 (Act)
        // D&Dで [item2, item1] に並び替えたことをシミュレート
        $payload = [
            'checklist_id' => $this->checklist->id,
            'ordered_item_ids' => [
                $item2->id, // 0 番目
                $this->item1->id // 1 番目
            ]
        ];
        
        $response = $this->patchJson(
            route('checklist_items.updateOrder'),
            $payload
        );
        
        // 検証 (Assert)
        $response->assertStatus(200);
        
        // データベースの position が更新されたか
        $this->assertDatabaseHas('checklist_items', [
            'id' => $this->item1->id,
            'position' => 1 // 1 に変更された
        ]);
        $this->assertDatabaseHas('checklist_items', [
            'id' => $item2->id,
            'position' => 0 // 0 に変更された
        ]);
    }
    /**
     * 6. チェックリスト本体のタイトル更新 (PATCH /checklists/{checklist})
     */
    public function test_an_authenticated_user_can_update_a_checklist_title()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user と $this->checklist (title: 'Test Checklist') は作成済み
        $updatedData = [
            'title' => 'Updated Checklist Title',
        ];

        // 2. 実行 (Act)
        $response = $this->patchJson(
            route('checklists.update', $this->checklist),
            $updatedData
        );
        
        // 3. 検証 (Assert)
        $response->assertStatus(200)
                 ->assertJson($updatedData);
        
        $this->assertDatabaseHas('checklists', [
            'id' => $this->checklist->id,
            'title' => 'Updated Checklist Title'
        ]);
    }

    /**
     * 7. チェックリスト本体の削除 (DELETE /checklists/{checklist})
     */
    public function test_an_authenticated_user_can_delete_a_checklist()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->checklist と $this->item1 は作成済み
        // 削除対象が存在することを DB で確認
        $this->assertDatabaseHas('checklists', ['id' => $this->checklist->id]);
        $this->assertDatabaseHas('checklist_items', ['id' => $this->item1->id]);

        // 2. 実行 (Act)
        $response = $this->deleteJson(
            route('checklists.destroy', $this->checklist)
        );

        // 3. 検証 (Assert)
        $response->assertStatus(204); // 204 No Content
        
        // A. チェックリスト本体が削除されたか
        $this->assertDatabaseMissing('checklists', ['id' => $this->checklist->id]);
        
        // B. onDelete('cascade') により、子アイテムも削除されたか
        $this->assertDatabaseMissing('checklist_items', ['id' => $this->item1->id]);
    }
}