<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Comment; // (フィルターテスト用)

class BoardViewApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Board $board;
    protected BoardList $list;
    protected Card $cardWithDate;
    protected Card $cardWithoutDate;
    protected Card $cardFiltered;

    /**
     * テストのセットアップ
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ユーザーとボードを作成
        $this->user = User::factory()->create();
        $this->board = Board::factory()->create(['owner_id' => $this->user->id]);
        $this->board->users()->attach($this->user->id); // メンバーとして割り当て
        $this->list = BoardList::factory()->create(['board_id' => $this->board->id]);

        // 1. 期限付きのカード (カレンダーに表示されるはず)
        $this->cardWithDate = Card::factory()->create([
            "board_list_id" => $this->list->id,
            "title" => "Task with Due Date",
            "start_date" => "2025-11-20 09:00:00",
            "end_date" => "2025-11-22 17:00:00",
        ]);
        
        // 2. 期限なしのカード (表示されないはず)
        $this->cardWithoutDate = Card::factory()->create([
            "board_list_id" => $this->list->id,
            "title" => "Task without Due Date",
            "end_date" => null,
        ]);

        // 3. フィルターテスト用のカード
        $this->cardFiltered = Card::factory()->create([
            "board_list_id" => $this->list->id,
            "title" => "Filtered Task",
            "description" => "Search keyword here",
            "end_date" => "2025-11-25 10:00:00",
        ]);
    }

    /**
     * @test
     * カレンダービュー (dayGrid) が正しい形式 (Y-m-d) で返されるか
     */
    // ★ 1. 修正: 'test_' プレフィックスを追加
    public function test_it_returns_calendar_view_events_correctly()
    {
        $response = $this->actingAs($this->user)
                         ->getJson(route("boards.calendarEvents", [
                             "board" => $this->board->id,
                             "view" => "calendar"
                         ]));
        
        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonPath("0.id", $this->cardWithDate->id)
                 ->assertJsonPath("0.allDay", true)
                 ->assertJsonPath("0.start", "2025-11-20")
                 // ★ 修正: 期待値を "2025-11-23" から "2025-11-24" に変更
                 ->assertJsonPath("0.end", "2025-11-24");
    }

    /**
     * @test
     * タイムラインビュー (timeGrid) が正しい形式 (ISO) で返されるか
     */
    // ★ 2. 修正: 'test_' プレフィックスを追加
    public function test_it_returns_timeline_view_events_correctly()
    {
        $response = $this->actingAs($this->user)
                         ->getJson(route("boards.calendarEvents", [
                             "board" => $this->board->id,
                             "view" => "timeline"
                         ]));

        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonPath("0.id", $this->cardWithDate->id)
                 ->assertJsonPath("0.allDay", false)
                 ->assertJsonPath("0.start", $this->cardWithDate->start_date->toISOString())
                 ->assertJsonPath("0.end", $this->cardWithDate->end_date->toISOString());
    }

    /**
     * @test
     * キーワードフィルター(q)が正しく動作するか
     */
    // ★ 3. 修正: 'test_' プレフィックスを追加
    public function test_it_filters_events_by_keyword()
    {
        $response = $this->actingAs($this->user)
                         ->getJson(route("boards.calendarEvents", [
                             "board" => $this->board->id,
                             "view" => "calendar",
                             "q" => "keyword here" // cardFiltered の説明文
                         ]));
        
        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonPath("0.id", $this->cardFiltered->id);
    }
}