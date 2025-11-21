<?php

namespace Tests\Feature;

use App\Models\Board;
use App\Models\BoardList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CardValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * カード作成時のバリデーションテスト: タイトル必須
     */
    public function test_card_title_is_required_on_create(): void
    {
        // 1. ユーザーとリストの準備
        $user = User::factory()->create();
        $board = Board::factory()->create(['owner_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);

        // 2. タイトルを空にしてPOSTリクエスト送信
        $response = $this->actingAs($user)
                         ->postJson(route('cards.store', ['list' => $list->id]), [
                             'title' => '', // 空文字
                         ]);

        // 3. 検証: 
        // - ステータスコード 422 (Unprocessable Entity) が返ること
        // - エラーメッセージに 'title' キーが含まれていること
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['title']);
    }

    /**
     * カード更新時のバリデーションテスト: 日付の順序
     */
    public function test_end_date_must_be_after_start_date(): void
    {
        // 1. 準備
        $user = User::factory()->create();
        $board = Board::factory()->create(['owner_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        // テスト用カード作成
        $card = $list->cards()->create([
            'title' => 'Original Title', 
            'order' => 0
        ]);

        // 2. 不正なデータ送信 (開始日 > 終了日)
        $response = $this->actingAs($user)
                         ->patchJson(route('cards.update', ['card' => $card->id]), [
                             'start_date' => '2025-01-10',
                             'end_date'   => '2025-01-01', // 開始日より前
                         ]);

        // 3. 検証
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_date']);
    }

    /**
     * カード更新時の正常系テスト
     */
    public function test_card_can_be_updated_with_valid_data(): void
    {
        $user = User::factory()->create();
        $board = Board::factory()->create(['owner_id' => $user->id]);
        $list = BoardList::factory()->create(['board_id' => $board->id]);
        $card = $list->cards()->create(['title' => 'Old Title', 'order' => 0]);

        $response = $this->actingAs($user)
                         ->patchJson(route('cards.update', ['card' => $card->id]), [
                             'title' => 'New Title',
                             'start_date' => '2025-01-01',
                             'end_date'   => '2025-01-10', // 正常
                         ]);

        $response->assertStatus(200);
        
        // DBが更新されているか確認
        $this->assertDatabaseHas('cards', [
            'id' => $card->id,
            'title' => 'New Title',
        ]);
    }
}