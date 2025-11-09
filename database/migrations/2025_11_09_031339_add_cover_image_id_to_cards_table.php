<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // 'reminder_at' カラムの後ろに追加
            // 外部キー制約
            $table->foreignId('cover_image_id')
                  ->nullable() // カバー画像は無くても良い
                  ->after('reminder_at')
                  ->constrained('attachments') // attachmentsテーブルのidを参照
                  ->onDelete('set null'); // ★ 添付ファイルが削除されたら、カバー設定も(nullに)解除する
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            // 外部キー制約を削除してからカラムを削除
            $table->dropForeign(['cover_image_id']);
            $table->dropColumn('cover_image_id');
        });
    }
};
