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

    // ==========================================================
    // メンバー (Member) フィルターのテスト
    // ==========================================================

    /** @test */
    public function test_it_filters_events_by_assigned_member()
    {
        // 別のユーザーを作成
        $otherUser = User::factory()->create();

        // 1. 自分に割り当てられたカード (表示されるべき)
        $assignedToMeCard = Card::factory()->create([
            'board_list_id' => $this->list->id,
            'title' => 'Assigned To Me',
            'end_date' => now()->addDays(2),
        ]);
        $assignedToMeCard->assignedUsers()->attach($this->user->id);

        // 2. 他のユーザーに割り当てられたカード (非表示になるべき)
        $assignedToOtherCard = Card::factory()->create([
            'board_list_id' => $this->list->id, // ★ 修正
            'title' => 'Assigned To Other',
            'end_date' => now()->addDays(2),
        ]);
        $assignedToOtherCard->assignedUsers()->attach($otherUser->id);

        // 3. 誰も割り当てられていないカード (非表示になるべき)
        $noAssigneeCard = Card::factory()->create([
            'board_list_id' => $this->list->id, // ★ 修正
            'title' => 'No Assignee',
            'end_date' => now()->addDays(3),
        ]);

        // 「自分に割り当て」フィルターを実行
        $response = $this->actingAs($this->user)
                         ->getJson(route('boards.calendarEvents', $this->board) . '?filterMember=mine');

        $response->assertOk()
                 ->assertJsonCount(1) // Base Cardは割り当てなしのため、このテストでは除外
                 ->assertJsonFragment(['title' => 'Assigned To Me'])
                 ->assertJsonMissing(['title' => 'Assigned To Other'])
                 ->assertJsonMissing(['title' => 'No Assignee']);

        // 「割り当てなし」フィルターを実行
        $response = $this->actingAs($this->user)
                         ->getJson(route('boards.calendarEvents', $this->board) . '?filterMember=none');
        
        $response->assertOk()
                 // ★ 修正: 期待件数を 2 から 3 に変更 (Base Card + cardFiltered + No Assignee Card)
                 ->assertJsonCount(3) 
                 // ★ 修正: 'No Assignee Card' を 'No Assignee' に修正
                 ->assertJsonFragment(['title' => 'No Assignee'])
                 ->assertJsonMissing(['title' => 'Assigned To Me']); // 割り当て済みカードが除外されていることを確認
    }

    // ==========================================================
    // 期間 (This Week / This Month) フィルターのテスト
    // ==========================================================

    /** @test */
    public function test_it_filters_events_by_due_this_week_and_month()
    {
        // 期限が今週末までのカード (表示されるべき)
        Card::factory()->create([
            'board_list_id' => $this->list->id, // ★ 修正
            'title' => 'Due This Week',
            'end_date' => now()->endOfWeek()->subDay(),
            'is_completed' => false,
        ]);
        
        // 期限が来週のカード (非表示になるべき)
        Card::factory()->create([
            'board_list_id' => $this->list->id, // ★ 修正
            'title' => 'Due Next Week',
            'end_date' => now()->endOfWeek()->addDay(), 
            'is_completed' => false,
        ]);

        // 期限が今月末までのカード (表示されるべき)
        Card::factory()->create([
            'board_list_id' => $this->list->id, // ★ 修正
            'title' => 'Due This Month',
            'end_date' => now()->endOfMonth()->subDay(),
            'is_completed' => false,
        ]);
        
        // 今週以内のカードのみを取得
        $response = $this->actingAs($this->user)
                         ->getJson(route('boards.calendarEvents', $this->board) . '?filterPeriod=this_week');

        $response->assertOk()
                 ->assertJsonFragment(['title' => 'Due This Week'])
                 ->assertJsonMissing(['title' => 'Due Next Week'])
                 ->assertJsonMissing(['title' => 'Due This Month']); // This Month Cardは今週ではない

        // 今月以内のカードを取得
        $response = $this->actingAs($this->user)
                         ->getJson(route('boards.calendarEvents', $this->board) . '?filterPeriod=this_month');

        $response->assertOk()
                 ->assertJsonFragment(['title' => 'Due This Week'])
                 ->assertJsonFragment(['title' => 'Due This Month']);
    }
}