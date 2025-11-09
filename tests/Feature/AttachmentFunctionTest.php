<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Attachment;
use Illuminate\Http\UploadedFile; // ★ 1. ダミーファイル用
use Illuminate\Support\Facades\Storage; // ★ 1. ストレージ操作用

class AttachmentFunctionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Board $board;
    private Card $card;

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
        
        // 5. 認証済みユーザーとして振る舞う
        $this->actingAs($this->user);
    }

    /**
     * 1. ファイルアップロード (POST /cards/{card}/attachments)
     */
    public function test_an_authenticated_user_can_upload_an_attachment()
    {
        // 1. 準備 (Arrange)
        // ★ 'public' ディスク (storage/app/public) をフェイク(ダミー)に切り替える
        Storage::fake('public');

        // ★ 100KBのダミー画像ファイルを作成
        $file = UploadedFile::fake()->image('avatar.jpg')->size(100);

        // 2. 実行 (Act)
        $response = $this->postJson(
            route('attachments.store', $this->card),
            ['file' => $file] // 'file' キーでダミーファイルを送信
        );

        // 3. 検証 (Assert)
        $response->assertStatus(201) // 201 Created
                 ->assertJson([
                     'file_name' => 'avatar.jpg',
                     'mime_type' => 'image/jpeg',
                     'size' => 100 * 1024, // (size はバイト単位)
                     'user_id' => $this->user->id,
                     'card_id' => $this->card->id,
                 ]);

        // ★ DBにレコードが保存されたか
        $this->assertDatabaseHas('attachments', [
            'file_name' => 'avatar.jpg',
            'card_id' => $this->card->id,
        ]);

        // ★ ストレージにファイルが保存されたか
        // 応答JSONから保存パスを取得
        $path = $response->json('file_path');
        Storage::disk('public')->assertExists($path);
    }

    /**
     * 2. ファイル削除 (DELETE /attachments/{attachment})
     */
    public function test_an_authenticated_user_can_delete_an_attachment()
    {
        // 1. 準備 (Arrange)
        Storage::fake('public');
        
        // ダミーファイルとDBレコードを作成
        $path = UploadedFile::fake()->image('test.jpg')->store('attachments', 'public');
        $attachment = Attachment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id,
            'file_path' => $path,
        ]);
        
        // ファイルが存在することを確認
        Storage::disk('public')->assertExists($path);
        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);

        // 2. 実行 (Act)
        $response = $this->deleteJson(
            route('attachments.destroy', $attachment)
        );

        // 3. 検証 (Assert)
        $response->assertStatus(204); // 204 No Content
        
        // ★ ストレージからファイルが削除されたか
        Storage::disk('public')->assertMissing($path);
        
        // ★ DBからレコードが削除されたか
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    /**
     * 3. レビュー更新 (PATCH /attachments/{attachment}/review)
     */
    public function test_an_authenticated_user_can_update_review_status()
    {
        // 1. 準備 (Arrange)
        $attachment = Attachment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id,
            'review_status' => 'pending' // 初期状態
        ]);

        // 2. 実行 (Act)
        $response = $this->patchJson(
            route('attachments.updateReviewStatus', $attachment),
            ['review_status' => 'approved'] // 'approved' に変更
        );

        // 3. 検証 (Assert)
        $response->assertStatus(200)
                 ->assertJson(['review_status' => 'approved']);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
            'review_status' => 'approved'
        ]);
    }
    
    /**
     * 4. カバー画像設定 (PATCH /cards/{card})
     */
    public function test_an_authenticated_user_can_set_a_cover_image()
    {
        // 1. 準備 (Arrange)
        // カバー画像にする添付ファイルを作成
        $attachment = Attachment::factory()->create([
            'card_id' => $this->card->id,
            'user_id' => $this->user->id,
            'mime_type' => 'image/jpeg',
        ]);
        
        $this->assertNull($this->card->cover_image_id); // 初期状態は null

        // 2. 実行 (Act)
        $response = $this->patchJson(
            route('cards.update', $this->card),
            ['cover_image_id' => $attachment->id] // 添付ファイルのIDをセット
        );
        
        // 3. 検証 (Assert)
        $response->assertStatus(200);
        $this->assertDatabaseHas('cards', [
            'id' => $this->card->id,
            'cover_image_id' => $attachment->id
        ]);
    }
    
    /**
     * 5. カバー画像バリデーション (PATCH /cards/{card})
     * (他人のカードの添付ファイルをカバーに設定できない)
     */
    public function test_user_cannot_set_cover_image_from_another_card()
    {
        // 1. 準備 (Arrange)
        // 別のカードを作成
        $anotherCard = Card::factory()->create([
            'board_list_id' => $this->card->list->id
        ]);
        // 別のカードの添付ファイルを作成
        $anotherAttachment = Attachment::factory()->create([
            'card_id' => $anotherCard->id, // ★ $anotherCard に紐づく
            'user_id' => $this->user->id,
        ]);

        // 2. 実行 (Act)
        // $this->card を更新しようとする際に、
        // $anotherAttachment (他人のID) を使おうとする
        $response = $this->patchJson(
            route('cards.update', $this->card),
            ['cover_image_id' => $anotherAttachment->id]
        );

        // 3. 検証 (Assert)
        // CardController@update の Rule::exists() が失敗するはず
        $response->assertStatus(422); // 422 Unprocessable Content
        
        $this->assertDatabaseHas('cards', [
            'id' => $this->card->id,
            'cover_image_id' => null // 変更されていない
        ]);
    }
}