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
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_list_id'); // どのリストに属しているか
            $table->string('title'); // カードのタイトル
            $table->text('description')->nullable(); // カードの詳細説明
            $table->unsignedInteger('order')->default(0); // リスト内でのカードの表示順序
            $table->timestamps();

            // 外部キー制約：board_list_idはlistsテーブルのidを参照する
            $table->foreign('board_list_id')->references('id')->on('lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cards');
    }
};
