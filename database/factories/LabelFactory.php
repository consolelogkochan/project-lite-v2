<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Board;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Label>
 */
class LabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // ★ 2. ここから追加
            'board_id' => Board::factory(), // デフォルトで新しいBoardを作成
            'name' => $this->faker->word(), // ダミーの単語
            'color' => $this->faker->randomElement([
                'bg-green-500', 'bg-yellow-500', 'bg-orange-500', 'bg-red-500', 
                'bg-purple-500', 'bg-blue-500', 'bg-sky-500', 'bg-gray-500'
            ]),
        ];
    }
}
