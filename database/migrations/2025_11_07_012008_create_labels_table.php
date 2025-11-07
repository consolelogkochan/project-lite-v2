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
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            // ラベルはボードに属する
            $table->foreignId('board_id')->constrained()->onDelete('cascade');
            $table->string('name'); // ラベル名
            $table->string('color'); // 色 (例: 'red', 'bg-blue-500', '#FF0000'など)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('labels');
    }
};
