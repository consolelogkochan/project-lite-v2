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
        Schema::create('lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('board_id'); // どのボードに属しているか
            $table->string('title'); // リストのタイトル (例: ToDo)
            $table->unsignedInteger('order')->default(0); // リストの表示順序
            $table->timestamps();

            // 外部キー制約：board_idはboardsテーブルのidを参照する
            $table->foreign('board_id')->references('id')->on('boards')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_lists');
    }
};
