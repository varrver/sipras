<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ComplaintFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ticket_number' => 'TKT-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->bothify('####')),
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'room_id' => Room::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'evidence_image' => 'complaints/evidence/example.jpg',
            'status' => 'pending',
            'admin_notes' => null,
            'resolved_image' => null,
            'resolved_at' => null,
        ];
    }
}
