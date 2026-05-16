<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id'=> Order::inRandomOrder()->first()->id,
            'book_id'=>Book::inRandomOrder()->first()->id,
            'quantity'=>fake()->numberBetween(1,10),
            'unit_price'=>fake()->randomFloat(2, 10, 100)
        ];
    }
}

