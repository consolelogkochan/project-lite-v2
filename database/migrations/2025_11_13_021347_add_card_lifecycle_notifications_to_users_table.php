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
            // notify_on_card_move の後ろに追加
            $table->boolean('notify_on_card_created')->default(true)->after('notify_on_card_move');
            $table->boolean('notify_on_card_deleted')->default(true)->after('notify_on_card_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('notify_on_card_created');
            $table->dropColumn('notify_on_card_deleted');
        });
    }
};
