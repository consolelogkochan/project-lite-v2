<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card; // ★ 1. 必要なモデルをインポート
use App\Notifications\DueDateReminder; // ★ 2. 通知クラスをインポート
use Illuminate\Support\Facades\Notification; // ★ 3. 通知ファサードをインポート
use Carbon\Carbon; // ★ 4. Carbon (日時操作) をインポート

class SendDueDateReminders extends Command
{
    /**
     * The name and signature of the console command.
     * (ターミナルで artisan schedule:run した時に呼ばれる名前)
     */
    protected $signature = 'reminders:send-due-dates'; // ★ 5. コマンド名を定義

    /**
     * The console command description.
     */
    protected $description = 'Scan the cards table and send due date reminders based on reminder_at timestamp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due date reminders to send...');

        // ★ 6. 「今」の時刻を取得
        $now = Carbon::now();

        // ★ 7. クエリの作成
        // 'reminder_at' が「今」から「1分後」までの間にあるカードを検索
        // (スケジュールが1分ごとに実行されることを想定)
        // さらに、関連するボードと、そのボードの全ユーザーを Eager Loading する
        $cardsToRemind = Card::with(['board.users'])
            ->where('reminder_at', '>=', $now)
            ->where('reminder_at', '<', $now->copy()->addMinute())
            ->get();

        if ($cardsToRemind->isEmpty()) {
            $this->info('No reminders to send.');
            return 0;
        }

        $this->info($cardsToRemind->count() . ' reminder(s) found. Sending notifications...');

        // ★ 8. 通知の送信
        foreach ($cardsToRemind as $card) {
            // カードが属するボードの全ユーザーを取得
            $usersToNotify = $card->board->users;

            if ($usersToNotify->isNotEmpty()) {
                // 関連する全ユーザーに通知を送信
                Notification::send($usersToNotify, new DueDateReminder($card));
            }
            
            // (TODO: 本番環境では、二重送信を防ぐために
            // $card->update(['reminder_sent_at' => now()]) のような
            // 送信済みフラグを立てるロジックを追加すると、より堅牢になります)
        }

        $this->info('All reminders sent successfully.');
        return 0;
    }
}