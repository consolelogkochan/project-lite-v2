<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Board;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BoardList>
 */
class BoardListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->word,
            'board_id' => Board::factory(), // ボードを自動作成して紐付け
            'order' => 0,
        ];
    }
}
