<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Card;
use App\Notifications\CardDueNotification;
use Illuminate\Support\Facades\Notification;

class SendCardReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-card-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notifications for cards with approaching due dates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. リマインダー条件に合致するカードを検索
        // - reminder_at が設定されている
        // - reminder_at が現在時刻を過ぎている
        // - まだリマインダーを送っていない (reminder_sent = false)
        // - 完了していない (is_completed = false)
        $cards = Card::whereNotNull('reminder_at')
                     ->where('reminder_at', '<=', now())
                     ->where('reminder_sent', false)
                     ->where('is_completed', false)
                     ->with('assignedUsers') // 割り当てユーザーも取得
                     ->get();

        foreach ($cards as $card) {
            // 2. 通知先を決定 (期限通知設定がONの割り当てユーザーのみ)
            $recipients = $card->assignedUsers->filter(function ($user) {
                return $user->notify_on_due_date;
            });

            if ($recipients->isNotEmpty()) {
                // 3. 通知送信
                Notification::send($recipients, new CardDueNotification($card));
                $this->info("Sent reminder for card: {$card->title}");
            }

            // 4. フラグを更新 (再送防止)
            $card->update(['reminder_sent' => true]);
        }
    }
}