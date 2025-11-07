<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Label;

class LabelFunctionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Board $board;
    private Card $card;
    private Label $label;

    /**
     * テストのセットアップ（各テスト実行前に呼ばれる）
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. 認証済みユーザーを作成
        $this->user = User::factory()->create();
        
        // 2. ユーザーが所有するボードを作成
        $this->board = Board::factory()->create([
            'owner_id' => $this->user->id
        ]);
        // ボードメンバーとしてアタッチ
        $this->board->users()->attach($this->user->id);

        // 3. ボードに属するリストを作成
        $list = BoardList::factory()->create([
            'board_id' => $this->board->id,
        ]);

        // 4. リストに属するカードを作成
        $this->card = Card::factory()->create([
            'board_list_id' => $list->id,
        ]);
        
        // 5. ボードに属するラベルを作成 (更新・削除・一覧取得テスト用)
        $this->label = Label::factory()->create([
            'board_id' => $this->board->id,
            'name' => 'Test Label',
            'color' => 'bg-blue-500'
        ]);
        
        // ★ 6. 認証済みユーザーとして振る舞う
        $this->actingAs($this->user);
    }

    /**
     * 1. ラベル一覧取得 (GET /boards/{board}/labels)
     * 認証済みユーザーはボードのラベル一覧を取得できる
     */
    public function test_an_authenticated_user_can_get_board_labels()
    {
        // 実行 (Act)
        $response = $this->getJson(route('labels.index', $this->board));

        // 検証 (Assert)
        $response->assertStatus(200)
                 ->assertJsonCount(1) // setUp() で 1つ作成したため
                 ->assertJson([
                     [
                         'id' => $this->label->id,
                         'name' => 'Test Label'
                     ]
                 ]);
    }
    
    /**
     * 2. ラベル作成 (POST /boards/{board}/labels)
     * 認証済みユーザーは新しいラベルを作成できる
     */
    public function test_an_authenticated_user_can_create_a_label()
    {
        // 準備 (Arrange)
        $newLabelData = [
            'name' => 'New Label',
            'color' => 'bg-red-500'
        ];
        
        // 実行 (Act)
        $response = $this->postJson(route('labels.store', $this->board), $newLabelData);

        // 検証 (Assert)
        $response->assertStatus(201) // 201 Created
                 ->assertJson($newLabelData);

        $this->assertDatabaseHas('labels', [
            'board_id' => $this->board->id,
            'name' => 'New Label'
        ]);
    }

    /**
     * 3. ラベル更新 (PATCH /labels/{label})
     * 認証済みユーザーはラベルを更新できる
     */
    public function test_an_authenticated_user_can_update_a_label()
    {
        // 準備 (Arrange)
        $updatedData = [
            'name' => 'Updated Name',
            'color' => 'bg-purple-500'
        ];

        // 実行 (Act)
        $response = $this->patchJson(route('labels.update', $this->label), $updatedData);
        
        // 検証 (Assert)
        $response->assertStatus(200)
                 ->assertJson($updatedData);
        
        $this->assertDatabaseHas('labels', [
            'id' => $this->label->id,
            'name' => 'Updated Name'
        ]);
    }

    /**
     * 4. ラベル削除 (DELETE /labels/{label})
     * 認証済みユーザーはラベルを削除できる
     */
    public function test_an_authenticated_user_can_delete_a_label()
    {
        // 実行 (Act)
        $response = $this->deleteJson(route('labels.destroy', $this->label));

        // 検証 (Assert)
        $response->assertStatus(204); // 204 No Content
        $this->assertDatabaseMissing('labels', ['id' => $this->label->id]);
    }

    /**
     * 5. ラベル割り当て (POST /cards/{card}/labels/{label})
     * 認証済みユーザーはカードにラベルを割り当てできる
     */
    public function test_an_authenticated_user_can_attach_a_label_to_a_card()
    {
        // 準備 (Arrange)
        // カードにラベルが割り当てられていないことを確認
        $this->assertCount(0, $this->card->labels);

        // 実行 (Act)
        $response = $this->postJson(
            route('labels.attach', ['card' => $this->card, 'label' => $this->label])
        );

        // 検証 (Assert)
        $response->assertStatus(200);
        
        // 中間テーブル 'card_label' にレコードが作成されたか
        $this->assertDatabaseHas('card_label', [
            'card_id' => $this->card->id,
            'label_id' => $this->label->id
        ]);

        // (参考) カードのリレーションを再読み込みして確認
        $this->assertCount(1, $this->card->fresh()->labels);
    }
    
    /**
     * 6. ラベル解除 (DELETE /cards/{card}/labels/{label})
     * 認証済みユーザーはカードからラベルを解除できる
     */
    public function test_an_authenticated_user_can_detach_a_label_from_a_card()
    {
        // 準備 (Arrange)
        // 最初にラベルを割り当てておく
        $this->card->labels()->attach($this->label->id);
        $this->assertDatabaseHas('card_label', [
            'card_id' => $this->card->id,
            'label_id' => $this->label->id
        ]);
        $this->assertCount(1, $this->card->fresh()->labels);

        // 実行 (Act)
        $response = $this->deleteJson(
            route('labels.detach', ['card' => $this->card, 'label' => $this->label])
        );

        // 検証 (Assert)
        $response->assertStatus(200);
        
        // 中間テーブル 'card_label' からレコードが削除されたか
        $this->assertDatabaseMissing('card_label', [
            'card_id' => $this->card->id,
            'label_id' => $this->label->id
        ]);

        // (参考) カードのリレーションを再読み込みして確認
        $this->assertCount(0, $this->card->fresh()->labels);
    }
}