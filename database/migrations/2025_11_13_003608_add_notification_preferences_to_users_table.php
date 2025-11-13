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
        Schema::table('users', function (Blueprint $table) {
            // 'status' カラムの後ろに追加
            $table->boolean('notify_on_comment')->default(true)->after('status');
            $table->boolean('notify_on_due_date')->default(true)->after('notify_on_comment');
            $table->boolean('notify_on_card_move')->default(false)->after('notify_on_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 配列を使わず、1行ずつ記述します
            $table->dropColumn('notify_on_comment');
            $table->dropColumn('notify_on_due_date');
            $table->dropColumn('notify_on_card_move');
        });
    }
};
