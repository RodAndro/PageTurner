<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Fiction', 'description' => 'Novels and short stories'],
            ['name' => 'Mystery', 'description' => 'Detective and crime novels'],
            ['name' => 'Science Fiction', 'description' => 'Future worlds and space adventures'],
            ['name' => 'Fantasy', 'description' => 'Magical worlds and adventures'],
            ['name' => 'Romance', 'description' => 'Love stories and relationships'],
            ['name' => 'Non-Fiction', 'description' => 'Real-world facts and information'],
            ['name' => 'Biography', 'description' => 'Life stories of notable people'],
            ['name' => 'Self-Help', 'description' => 'Personal development and growth'],
            ['name' => 'History', 'description' => 'Historical events and periods'],
            ['name' => 'Children', 'description' => 'Books for young readers'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
