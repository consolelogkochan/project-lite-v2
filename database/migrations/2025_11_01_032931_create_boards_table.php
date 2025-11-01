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
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedBigInteger('owner_id'); // このボードを作成したユーザーのID
            $table->string('background_color')->nullable(); // 背景色（例: #FFFFFF）
            $table->string('background_image_url')->nullable(); // 背景画像のURL
            $table->timestamps();

            // 外部キー制約：owner_idはusersテーブルのidを参照する
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boards');
    }
};
