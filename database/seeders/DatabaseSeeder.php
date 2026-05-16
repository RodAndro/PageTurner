<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Category;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run CategorySeeder first
        $this->call(CategorySeeder::class);

        // Create admin account
        User::create([
            'first_name' => 'Admin',
            'middle_name' => '',
            'last_name' => 'PageTurner',
            'suffix' => '',
            'email' => 'admin@pageturner.com',
            'role' => 'admin',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create customer account
        User::create([
            'first_name' => 'Rodandro',
            'middle_name' => '',
            'last_name' => 'User',
            'suffix' => '',
            'email' => 'rodandro57@gmail.com',
            'role' => 'customer',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $users = User::factory(10)->create();
        $categories = Category::all();
        
        // Create 3 books per category
        foreach ($categories as $category) {
            Book::factory(3)->create(['category_id' => $category->id]);
        }
        
        $books = Book::all();
        $orders = Order::factory(10)->recycle($users)->create();
        OrderItem::factory(10)->recycle($orders)->recycle($books)->create();
        Review::factory(10)->recycle($users)->recycle($books)->create();
    }
}
