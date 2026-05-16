<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;
    
    // Cache category IDs to avoid repeated queries during seeding
    protected static ?array $categoryIds = null;
    protected static array $publishers = [
        'Penguin Random House',
        'HarperCollins',
        'Simon & Schuster',
        'Hachette',
        'Macmillan',
        'Scholastic',
        'Houghton Mifflin Harcourt',
        'Pearson',
        'Wiley',
        'Springer',
        'Oxford University Press',
        'Cambridge University Press',
        'Harvard University Press',
        'MIT Press',
        'Stanford University Press',
        'Yale University Press',
        'Princeton University Press',
        'Bloomsbury',
        'Picador',
        'Farrar, Straus and Giroux',
        'Knopf',
        'Random House',
        'Harper',
        'William Morrow',
        'Viking',
        'Doubleday',
        'Little, Brown and Company'
    ];

    // Format-based pricing ranges
    protected static $formats = [
        'Paperback' => ['min' => 12.99, 'max' => 29.99],
        'Hardcover' => ['min' => 24.99, 'max' => 49.99],
        'Ebook' => ['min' => 9.99, 'max' => 19.99],
        'Audiobook' => ['min' => 19.99, 'max' => 39.99],
        'Large Print' => ['min' => 29.99, 'max' => 59.99],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->unique()->sentence(rand(2, 6)),
            'author' => $this->faker->name(),
            'isbn' => $this->generateValidIsbn13(),
            'format' => $format = $this->faker->randomElement(array_keys(self::$formats)),
            'price' => $this->generateFormatBasedPrice($format),
            'description' => $this->faker->paragraph(rand(2, 5)),
            'category_id' => $this->getRandomCategoryId(),
            'publisher' => $this->faker->randomElement(self::$publishers),
            'publication_date' => $this->faker->dateTimeBetween('-50 years', 'now')->format('Y-m-d'),
            'language' => $this->faker->randomElement(['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese']),
            'page_count' => $this->faker->numberBetween(150, 800),
            'rating' => $this->faker->numberBetween(1, 5),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'cover_image' => 'https://picsum.photos/seed/' . $this->faker->numberBetween(1, 1000) . '/800/600',
            'is_active' => $this->faker->boolean(85), // 85% active
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Generate valid ISBN-13 with proper checksum
     */
    public function generateValidIsbn13(): string
    {
        $isbn12 = '978' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999999), 9, '0', STR_PAD_LEFT);
        
        // Calculate ISBN-13 checksum
        $checksum = $this->calculateIsbn13Checksum($isbn12);
        
        return $isbn12 . $checksum;
    }

    /**
     * Calculate ISBN-13 checksum
     */
    protected function calculateIsbn13Checksum(string $isbn12): string
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$isbn12[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        
        $remainder = $sum % 10;
        $checkDigit = $remainder === 0 ? 0 : 10 - $remainder;
        
        return (string)$checkDigit;
    }

    /**
     * Generate format-based pricing with realistic ranges
     */
    public function generateFormatBasedPrice(?string $format = null): float
    {
        $format ??= $this->faker->randomElement(array_keys(self::$formats));
        $priceRange = self::$formats[$format];
        
        return round(
            mt_rand($priceRange['min'] * 100, $priceRange['max'] * 100) / 100,
            2
        );
    }

    /**
     * Get random category ID with caching
     */
    public function getRandomCategoryId(): int
    {
        // Load category IDs once and cache them
        if (self::$categoryIds === null) {
            self::$categoryIds = Category::query()->pluck('id')->all();
        }

        if (!empty(self::$categoryIds)) {
            $validCount = Category::query()->whereIn('id', self::$categoryIds)->count();
            if ($validCount !== count(self::$categoryIds)) {
                self::$categoryIds = Category::query()->pluck('id')->all();
            }
        }

        if (empty(self::$categoryIds)) {
            self::$categoryIds = [Category::factory()->create(['name' => 'General'])->id];
        }
        
        return $this->faker->randomElement(self::$categoryIds);
    }

    /**
     * Create a bestseller state factory
     */
    public function bestseller(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'stock_quantity' => $this->faker->numberBetween(100, 1000), // High stock
                'rating' => $this->faker->numberBetween(4, 5), // High rating
                'is_active' => true, // Always active
                'price' => $this->faker->numberBetween(15.00, 49.99), // Premium pricing
            ]);
        });
    }

    /**
     * Create a low stock state factory
     */
    public function lowStock(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'stock_quantity' => $this->faker->numberBetween(1, 10), // Low stock
                'is_active' => true,
            ]);
        });
    }

    /**
     * Create an out of stock state factory
     */
    public function outOfStock(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'stock_quantity' => 0,
                'is_active' => false, // Inactive when out of stock
            ]);
        });
    }

    /**
     * Create a recently published state factory
     */
    public function recentlyPublished(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'publication_date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
                'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
                'updated_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            ]);
        });
    }

    /**
     * Create a classic book state factory
     */
    public function classic(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'publication_date' => $this->faker->dateTimeBetween('-100 years', '-20 years')->format('Y-m-d'),
                'author' => $this->faker->name() . ' ' . $this->faker->lastName(),
                'publisher' => $this->faker->randomElement([
                    'Penguin Classics', 'Oxford World\'s Classics', 'Modern Library', 'Everyman\'s Library'
                ]),
                'format' => 'Hardcover',
                'language' => 'English',
            ]);
        });
    }

    /**
     * Create a modern book state factory
     */
    public function modern(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'publication_date' => $this->faker->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
                'format' => $this->faker->randomElement(['Ebook', 'Audiobook']),
                'language' => $this->faker->randomElement(['English', 'Spanish', 'French', 'German']),
                'publisher' => $this->faker->randomElement([
                    'Penguin Random House', 'HarperCollins', 'Simon & Schuster', 'Hachette'
                ]),
            ]);
        });
    }

    /**
     * Create a technical book state factory
     */
    public function technical(): self
    {
        return $this->state(function (array $attributes) {
            return array_merge($attributes, [
                'title' => $this->faker->sentence() . ': ' . $this->faker->sentence(),
                'description' => $this->faker->paragraph(8) . ' ' . $this->faker->paragraph(4),
                'category_id' => $this->getCategoryIdByName('Technology'),
                'publisher' => $this->faker->randomElement([
                    'O\'Reilly Media', 'Manning Publications', 'Packt Publishing', 'No Starch Press'
                ]),
                'format' => $this->faker->randomElement(['Paperback', 'Ebook']),
                'page_count' => $this->faker->numberBetween(300, 1200),
            ]);
        });
    }

    /**
     * Get category ID by name (for technical books)
     */
    protected function getCategoryIdByName(string $name): int
    {
        $categoryId = Category::query()->where('name', $name)->value('id');
        
        return $categoryId ?: $this->getRandomCategoryId();
    }

    /**
     * Reset cached category IDs
     */
    public static function resetCategoryCache(): void
    {
        self::$categoryIds = null;
    }
}
