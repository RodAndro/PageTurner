<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class TranslateCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categoryTranslations = [
            1 => 'Fiction',
            2 => 'Science Fiction', 
            3 => 'Non-Fiction',
            4 => 'Mystery',
            5 => 'Romance',
            6 => 'History',
            7 => 'Biography',
            8 => 'Technology',
            9 => 'Business',
            10 => 'Self-Help'
        ];

        foreach ($categoryTranslations as $id => $englishName) {
            Category::where('id', $id)->update(['name' => $englishName]);
        }

        $this->command->info('Categories translated to English successfully!');
    }
}
