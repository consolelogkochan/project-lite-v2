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
        Schema::table('board_user', function (Blueprint $table) {
            // 'user_id' の後ろに role カラムを追加
            $table->string('role')->default('member')->after('user_id');
            // 'role' カラムにインデックスを追加して検索を高速化
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('board_user', function (Blueprint $table) {
            $table->dropIndex(['role']); // インデックスを削除
            $table->dropColumn('role'); // カラムを削除
        });
    }
};
