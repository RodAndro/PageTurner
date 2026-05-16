<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Book;
use Illuminate\Database\Seeder;

class SampleBooksSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::all();
        
        foreach ($categories as $category) {
            for ($i = 1; $i <= 3; $i++) {
                Book::create([
                    'title' => 'Sample Book ' . $i . ' - ' . $category->name,
                    'author' => 'Author ' . $i,
                    'description' => 'This is a sample book in the ' . $category->name . ' category. A great read for anyone interested in ' . $category->name . ' topics.',
                    'isbn' => '978-' . str_pad($category->id, 3, '0', STR_PAD_LEFT) . str_pad($i, 6, '0', STR_PAD_LEFT) . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT),
                    'category_id' => $category->id,
                    'price' => rand(299, 1999) / 100,
                    'stock_quantity' => rand(5, 50),
                    'cover_image' => 'https://picsum.photos/seed/book' . $category->id . $i . '/200/300.jpg',
                ]);
            }
        }
    }
}
