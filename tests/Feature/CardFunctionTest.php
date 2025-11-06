<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase; // ★ 1. DBリフレッシュ
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;   // ★ 2. モデルのインポート
use App\Models\Board;  // ★
use App\Models\BoardList; // ★
use App\Models\Card;   // ★
use App\Models\Comment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

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

    /**
     * 認証済みユーザーはカードの詳細情報を取得できる
     */
    public function test_an_authenticated_user_can_get_card_details()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user, $this->list, $this->card は作成済み
        // さらに $this->card に紐づくコメントを作成
        $comment = Comment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id
        ]);

        // 2. 実行 (Act)
        // 認証済みユーザーとして、カード詳細API (cards.show) にGETリクエスト
        $response = $this->actingAs($this->user)->getJson(
            route('cards.show', $this->card)
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(200) // 200 OK
                 ->assertJson([
                     'id' => $this->card->id,
                     'title' => $this->card->title,
                     
                     // B. リレーション(list)が読み込まれているか
                     'list' => [
                         'id' => $this->list->id,
                         'title' => $this->list->title
                     ],

                     // C. リレーション(comments)が読み込まれているか
                     'comments' => [
                         [ // 最初のコメント (配列になっている)
                             'id' => $comment->id,
                             'content' => $comment->content,
                             
                             // D. コメントのユーザー情報(アバターURL含む)が読み込まれているか
                             'user' => [
                                'id' => $this->user->id,
                                'name' => $this->user->name,
                                'avatar_url' => $this->user->avatar_url // $appends のテスト
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * 認証済みユーザーはカードにコメントを投稿できる
     */
    public function test_an_authenticated_user_can_post_a_comment()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user (認証ユーザー) と $this->card (投稿先) は作成済み
        $commentContent = 'This is a test comment.';

        // 2. 実行 (Act)
        // 認証済みユーザーとして、コメント投稿API (comments.store) にPOSTリクエスト
        $response = $this->actingAs($this->user)->postJson(
            route('comments.store', $this->card),
            ['content' => $commentContent]
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(201) // 201 Created が返ってくる
                 ->assertJson([
                     'content' => $commentContent,
                     'card_id' => $this->card->id,
                     'user_id' => $this->user->id,
                     // B. ユーザー情報(アバターURL含む)がネストされているか
                     'user' => [
                         'id' => $this->user->id,
                         'avatar_url' => $this->user->avatar_url
                     ]
                 ]);

        // C. データベースの検証
        $this->assertDatabaseHas('comments', [
            'card_id' => $this->card->id,
            'user_id' => $this->user->id,
            'content' => $commentContent
        ]);
    }

    /**
     * 認証済みユーザーは「自分」のコメントを編集できる
     */
    public function test_an_authenticated_user_can_update_their_own_comment()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user と $this->card が作成済み
        // $this->user が投稿したコメントを作成
        $comment = Comment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id,
            'content' => 'Original content'
        ]);
        $updatedContent = 'Updated content';

        // 2. 実行 (Act)
        // 認証済みユーザーとして、コメント更新API (comments.update) にPATCHリクエスト
        $response = $this->actingAs($this->user)->patchJson(
            route('comments.update', $comment),
            ['content' => $updatedContent]
        );

        // 3. 検証 (Assert)
        $response->assertStatus(200) // 200 OK
                 ->assertJson([
                     'content' => $updatedContent,
                     'user' => ['id' => $this->user->id] // ユーザー情報も含まれている
                 ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => $updatedContent
        ]);
    }

    /**
     * 認証済みユーザーは「他人」のコメントを編集できない (403 Forbidden)
     */
    public function test_an_authenticated_user_cannot_update_others_comment()
    {
        // 1. 準備 (Arrange)
        // 別のユーザー（他人）を作成
        $otherUser = User::factory()->create();
        // 他人が投稿したコメントを作成
        $othersComment = Comment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $otherUser->id,
            'content' => 'Others content'
        ]);

        // 2. 実行 (Act)
        // $this->user として「他人」のコメントを更新しようとする
        $response = $this->actingAs($this->user)->patchJson(
            route('comments.update', $othersComment),
            ['content' => 'Malicious update']
        );

        // 3. 検証 (Assert)
        $response->assertStatus(403); // 403 Forbidden が返ってくる

        // データベースが更新されていないことを確認
        $this->assertDatabaseHas('comments', [
            'id' => $othersComment->id,
            'content' => 'Others content' // 元のまま
        ]);
    }

    /**
     * 認証済みユーザーは「自分」のコメントを削除できる
     */
    public function test_an_authenticated_user_can_delete_their_own_comment()
    {
        // 1. 準備 (Arrange)
        // $this->user が投稿したコメントを作成
        $comment = Comment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id
        ]);

        // 2. 実行 (Act)
        // 認証済みユーザーとして、コメント削除API (comments.destroy) にDELETEリクエスト
        $response = $this->actingAs($this->user)->deleteJson(
            route('comments.destroy', $comment)
        );

        // 3. 検証 (Assert)
        $response->assertStatus(204); // 204 No Content
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /**
     * 認証済みユーザーは「他人」のコメントを削除できない (403 Forbidden)
     */
    public function test_an_authenticated_user_cannot_delete_others_comment()
    {
        // 1. 準備 (Arrange)
        // 別のユーザー（他人）を作成
        $otherUser = User::factory()->create();
        // 他人が投稿したコメントを作成
        $othersComment = Comment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $otherUser->id
        ]);

        // 2. 実行 (Act)
        // $this->user として「他人」のコメントを削除しようとする
        $response = $this->actingAs($this->user)->deleteJson(
            route('comments.destroy', $othersComment)
        );

        // 3. 検証 (Assert)
        $response->assertStatus(403); // 403 Forbidden が返ってくる

        // データベースから削除されていないことを確認
        $this->assertDatabaseHas('comments', ['id' => $othersComment->id]);
    }

    /**
     * 認証済みユーザーはカードの期限日とリマインダーを設定できる
     */
    public function test_an_authenticated_user_can_set_card_dates_and_reminder()
    {
        // 1. 準備 (Arrange)
        // setUp() で $this->user と $this->card が作成済み
        
        // (Carbonライブラリをインポート)
        // use Illuminate\Support\Carbon; 
        // ※ CardFunctionTest クラスの use 文のブロックに Carbon を追加してください
        
        // ★ 修正: ->micro(0) を追加してマイクロ秒をゼロにする
        $startDate = Carbon::now()->addDay()->setHour(9)->setMinute(0)->setSecond(0)->micro(0);
        $endDate = Carbon::now()->addDays(2)->setHour(12)->setMinute(0)->setSecond(0)->micro(0);
        
        // "1 hour before" (endDate の1時間前)
        // (endDate が既にマイクロ秒ゼロなので、copy() でOK)
        $reminderAt = $endDate->copy()->subHour();

        // 2. 実行 (Act)
        // 日付保存API (cards.update) にPATCHリクエスト
        $response = $this->actingAs($this->user)->patchJson(
            route('cards.update', $this->card),
            [
                'start_date' => $startDate->toDateTimeString(), // "Y-m-d H:i:s" 形式
                'end_date' => $endDate->toDateTimeString(),
                'reminder_at' => $reminderAt->toDateTimeString(),
            ]
        );

        // 3. 検証 (Assert)
        
        // A. レスポンスの検証
        $response->assertStatus(200) // 200 OK
                 ->assertJson([
                     'id' => $this->card->id,
                     // $casts が 'datetime' なので、ISO 8601 形式 (T...Z) で返ってくる
                     'start_date' => $startDate->toISOString(),
                     'end_date' => $endDate->toISOString(),
                     'reminder_at' => $reminderAt->toISOString(),
                 ]);

        // B. データベースの検証 (DBには "Y-m-d H:i:s" 形式で保存されている)
        $this->assertDatabaseHas('cards', [
            'id' => $this->card->id,
            'start_date' => $startDate->toDateTimeString(),
            'end_date' => $endDate->toDateTimeString(),
            'reminder_at' => $reminderAt->toDateTimeString(),
        ]);
    }

    /**
     * スケジュールタスクは reminder_at が来たカードの通知を送信する
     */
    public function test_schedule_task_sends_due_date_reminders()
    {
        // 1. 準備 (Arrange)
        
        // ★ 通知を「フェイク」する
        // (実際には送信せず、テスト側でキャッチできるようにする)
        Notification::fake();

        // ★ 時間を「凍結」する
        // (Carbon::now() が常にこの指定日時に固定される)
        $now = Carbon::create(2025, 11, 10, 12, 0, 0);
        Carbon::setTestNow($now);

        // setUp() で $this->user (通知を受け取る人) が作成済み
        // $this->user をボードのメンバーにする (※setUp() では未実装のためここで実行)
        $this->board->users()->attach($this->user->id);
        
        // $this->card (カード1) の reminder_at を「今」に設定
        $this->card->update([
            'reminder_at' => $now
        ]);
        
        // (比較用) まだリマインド時刻が来ていないカード
        $cardLater = Card::factory()->create([
            'board_list_id' => $this->list->id,
            'reminder_at' => $now->copy()->addDay() // 明日
        ]);

        // 2. 実行 (Act)
        // artisan コマンド 'reminders:send-due-dates' を実行
        $this->artisan('reminders:send-due-dates');

        // 3. 検証 (Assert)
        
        // A. $this->card (今リマインド) の通知が...
        //    $this->user (メンバー) に...
        //    DueDateReminder クラスで 1 回送信されたか？
        Notification::assertSentTo(
            [$this->user], // 通知先ユーザー
            \App\Notifications\DueDateReminder::class, // 通知クラス
            function ($notification, $channels) {
                // (オプション) 送信された通知が正しいカードのものか確認
                return $notification->card->id === $this->card->id;
            }
        );

        // B. $cardLater (明日リマインド) の通知は「送信されなかった」か？
        Notification::assertNotSentTo(
            [$this->user],
            \App\Notifications\DueDateReminder::class,
            function ($notification, $channels) use ($cardLater) {
                return $notification->card->id === $cardLater->id;
            }
        );
        
        // 時間の凍結を解除 (他のテストに影響しないように)
        Carbon::setTestNow();
    }
}