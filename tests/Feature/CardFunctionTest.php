<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase; // ★ 1. DBリフレッシュ
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;   // ★ 2. モデルのインポート
use App\Models\Board;  // ★
use App\Models\BoardList; // ★
use App\Models\Card;   // ★

class CardFunctionTest extends TestCase
{
    use RefreshDatabase; // ★ 3. 各テスト後にDBをリセット

    private $user;
    private $board;
    private $list;
    private $card;

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

        // 3. ボードに属するリストを作成
        $this->list = BoardList::factory()->create([
            'board_id' => $this->board->id,
            'order' => 0
        ]);

        // 4. リストに属するカードを作成（更新・削除テスト用）
        $this->card = Card::factory()->create([
            'board_list_id' => $this->list->id,
            'title' => 'Test Card',
            'order' => 0
        ]);
    }

    /**
     * 認証済みユーザーは新しいカードを作成できる
     * ★★★ 修正点: @test アノテーションを削除 ★★★
     */
    // ★★★ 修正点: メソッド名に "test_" を追加 ★★★
    public function test_an_authenticated_user_can_create_a_card()
    {
        // 1. 準備 (Arrange)
        $newCardTitle = 'Newly Created Card';

        // 2. 実行 (Act)
        $response = $this->actingAs($this->user)->postJson(
            route('cards.store', $this->list),
            ['title' => $newCardTitle]
        );

        // 3. 検証 (Assert)
        $response->assertStatus(201)
                 ->assertJson([
                     'title' => $newCardTitle,
                     'board_list_id' => $this->list->id,
                     'order' => 1
                 ]);

        $this->assertDatabaseHas('cards', [
            'title' => $newCardTitle,
            'board_list_id' => $this->list->id,
            'order' => 1
        ]);

        $this->assertEquals(2, $this->list->cards()->count());
    }
    /**
     * 認証済みユーザーはカードのタイトルを更新できる
     */
    public function test_an_authenticated_user_can_update_a_card()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user (認証ユーザー) と $this->card (更新対象) は作成済み
        $updatedTitle = 'Updated Card Title';

        // 2. 実行 (Act)
        // 認証済みユーザーとして、カード更新API (cards.update) にPATCHリクエスト
        $response = $this->actingAs($this->user)->patchJson(
            route('cards.update', $this->card),
            ['title' => $updatedTitle]
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(200) // 200 OK が返ってくる
                 ->assertJson([
                     'id' => $this->card->id,
                     'title' => $updatedTitle // 更新後のタイトルが返ってくる
                 ]);

        // B. データベースの検証
        $this->assertDatabaseHas('cards', [
            'id' => $this->card->id,
            'title' => $updatedTitle
        ]);
        
        // C. (念のため) 元のタイトルがDBから消えたことを確認
        $this->assertDatabaseMissing('cards', [
            'id' => $this->card->id,
            'title' => 'Test Card' // setUp() で設定した元のタイトル
        ]);
    }
    /**
     * 認証済みユーザーはカードを削除できる
     */
    public function test_an_authenticated_user_can_delete_a_card()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user (認証ユーザー) と $this->card (削除対象) は作成済み
        // $this->card->id が存在することを確認
        $this->assertNotNull($this->card->id);

        // 2. 実行 (Act)
        // 認証済みユーザーとして、カード削除API (cards.destroy) にDELETEリクエスト
        $response = $this->actingAs($this->user)->deleteJson(
            route('cards.destroy', $this->card)
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(204); // 204 No Content が返ってくる

        // B. データベースの検証
        $this->assertDatabaseMissing('cards', [
            'id' => $this->card->id,
            'title' => 'Test Card' // setUp() で設定したカードが消えたこと
        ]);
        
        // C. (念のため) リスト内のカードが合計0枚になったことを確認
        $this->assertEquals(0, $this->list->cards()->count());
    }
    /**
     * D&Dテスト用の追加セットアップ
     * （setUp() で作成したデータに加えて、追加のリストとカードを作成する）
     * * @return array
     */
    private function setupDragAndDropData(): array
    {
        // setUp() で $this->list (List 1) と $this->card (Card 1) は作成済み
        $list1 = $this->list;
        $card1 = $this->card; // order: 0

        // List 1 に2枚目のカードを追加
        $card2 = Card::factory()->create([
            'board_list_id' => $list1->id,
            'title' => 'Card 2',
            'order' => 1 // 2番目
        ]);

        // List 2 を作成
        $list2 = BoardList::factory()->create([
            'board_id' => $this->board->id,
            'order' => 1
        ]);

        // List 2 に3枚目のカードを追加
        $card3 = Card::factory()->create([
            'board_list_id' => $list2->id,
            'title' => 'Card 3',
            'order' => 0
        ]);

        // 初期状態:
        // List 1: [card1 (id:1, order:0), card2 (id:2, order:1)]
        // List 2: [card3 (id:3, order:0)]

        return compact('list1', 'list2', 'card1', 'card2', 'card3');
    }


    /**
     * 認証済みユーザーはカードのD&D（順序・所属リスト）を更新できる
     */
    public function test_an_authenticated_user_can_update_card_order_via_dnd()
    {
        // 1. 準備 (Arrange)
        // D&D用の複雑なデータ（2リスト、3カード）をセットアップ
        $data = $this->setupDragAndDropData();

        // 2. 実行 (Act)
        // シナリオ: List 1 の card2 を、List 2 の card3 の「上」に移動する
        
        // D&D後のフロントエンドの状態をシミュレートしたペイロード
        $listsPayload = [
            [
                'id' => $data['list1']->id,
                'cards' => [ $data['card1']->id ] // card2 が消えた
            ],
            [
                'id' => $data['list2']->id,
                'cards' => [ $data['card2']->id, $data['card3']->id ] // card2 が先頭に追加された
            ]
        ];

        // 認証済みユーザーとして、カード順序更新API (cards.updateOrder) にPATCHリクエスト
        $response = $this->actingAs($this->user)->patchJson(
            route('cards.updateOrder'),
            ['lists' => $listsPayload]
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(200) // CardController@updateOrder は 200 OK を返す
                 ->assertJson(['message' => 'Card order updated successfully.']);

        // B. データベースの検証 (カードの所属リストと順序が正しいか)
        
        // Card 1 (List 1 のまま、順序 0)
        $this->assertDatabaseHas('cards', [
            'id' => $data['card1']->id,
            'board_list_id' => $data['list1']->id,
            'order' => 0
        ]);
        
        // Card 2 (List 2 に移動し、順序 0)
        $this->assertDatabaseHas('cards', [
            'id' => $data['card2']->id,
            'board_list_id' => $data['list2']->id,
            'order' => 0 // List 2 の先頭になった
        ]);

        // Card 3 (List 2 のまま、順序 1 に押し下げられた)
        $this->assertDatabaseHas('cards', [
            'id' => $data['card3']->id,
            'board_list_id' => $data['list2']->id,
            'order' => 1 // card2 が来たため 1 になった
        ]);
    }
}