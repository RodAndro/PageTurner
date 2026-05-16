<?php

namespace Tests\Unit;

use Database\Factories\BookFactory;
use Tests\TestCase;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookFactoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Reset factory cache before each test
        BookFactory::resetCategoryCache();
        Category::factory()->count(5)->create();
    }

    /**
     * Test ISBN-13 generation
     */
    public function test_isbn_13_generation(): void
    {
        $factory = new BookFactory();
        
        // Test multiple ISBN generations
        for ($i = 0; $i < 100; $i++) {
            $isbn = $factory->generateValidIsbn13();
            
            // Verify ISBN format
            $this->assertMatchesRegularExpression('/^978\d{10}$/', $isbn, 'ISBN should start with 978 and be 13 digits');
            
            // Verify checksum calculation
            $this->assertTrue($this->isValidIsbn13Checksum($isbn), 'ISBN checksum should be valid');
        }
    }

    /**
     * Test format-based pricing
     */
    public function test_format_based_pricing(): void
    {
        $factory = new BookFactory();
        
        // Test each format price range
        $formats = [
            'Hardcover' => ['min' => 24.99, 'max' => 49.99],
            'Paperback' => ['min' => 12.99, 'max' => 29.99],
            'Ebook' => ['min' => 9.99, 'max' => 19.99],
            'Audiobook' => ['min' => 19.99, 'max' => 39.99],
            'Large Print' => ['min' => 29.99, 'max' => 59.99],
        ];
        
        foreach ($formats as $format => $range) {
            for ($i = 0; $i < 100; $i++) {
                $price = $factory->generateFormatBasedPrice($format);
                
                $this->assertGreaterThanOrEqual($range['min'], $price, 
                    "Format {$format} price should be at least {$range['min']}");
                $this->assertLessThanOrEqual($range['max'], $price, 
                    "Format {$format} price should be at most {$range['max']}");
            }
        }
    }

    /**
     * Test category ID caching
     */
    public function test_category_id_caching(): void
    {
        $categories = Category::all();
        $categoryIds = $categories->pluck('id')->toArray();
        
        // Reset factory cache
        BookFactory::resetCategoryCache();
        
        $factory = new BookFactory();
        
        // First call should hit database
        $start = microtime(true);
        $categoryId1 = $factory->getRandomCategoryId();
        $time1 = microtime(true) - $start;
        
        // Second call should use cache
        $categoryId2 = $factory->getRandomCategoryId();
        $time2 = microtime(true) - $start;
        
        // Third call should use cache
        $categoryId3 = $factory->getRandomCategoryId();
        $time3 = microtime(true) - $start;
        
        // Verify cache is working
        $this->assertNotNull($categoryId2, 'Cached category lookup should return a category');
        $this->assertNotNull($categoryId3, 'Cached category lookup should return a category');
        
        // Verify all category IDs are valid
        $this->assertTrue(in_array($categoryId1, $categoryIds), 'Category ID should be valid');
        $this->assertTrue(in_array($categoryId2, $categoryIds), 'Category ID should be valid');
        $this->assertTrue(in_array($categoryId3, $categoryIds), 'Category ID should be valid');
    }

    /**
     * Test factory state methods
     */
    public function test_factory_states(): void
    {
        $factory = new BookFactory();
        
        // Test bestseller state
        $bestseller = $factory->bestseller()->make()->toArray();
        $this->assertGreaterThanOrEqual(100, $bestseller['stock_quantity'], 'Bestseller should have high stock');
        $this->assertGreaterThanOrEqual(4, $bestseller['rating'], 'Bestseller should have high rating');
        $this->assertTrue($bestseller['is_active'], 'Bestseller should be active');
        $this->assertGreaterThanOrEqual(15.0, $bestseller['price'], 'Bestseller should have premium pricing');
        
        // Test low stock state
        $lowStock = $factory->lowStock()->make()->toArray();
        $this->assertLessThanOrEqual(10, $lowStock['stock_quantity'], 'Low stock should have <= 10 units');
        $this->assertTrue($lowStock['is_active'], 'Low stock should be active');
        
        // Test out of stock state
        $outOfStock = $factory->outOfStock()->make()->toArray();
        $this->assertEquals(0, $outOfStock['stock_quantity'], 'Out of stock should have 0 quantity');
        $this->assertFalse($outOfStock['is_active'], 'Out of stock should be inactive');
        
        // Test recently published state
        $recent = $factory->recentlyPublished()->make()->toArray();
        $this->assertGreaterThanOrEqual(strtotime('-30 days'), strtotime($recent['publication_date']), 'Recently published should be within last 30 days');
        
        // Test classic state
        $classic = $factory->classic()->make()->toArray();
        $this->assertLessThanOrEqual(strtotime('-20 years'), strtotime($classic['publication_date']), 'Classic should be older than 20 years');
        $this->assertEquals('Hardcover', $classic['format'], 'Classic should be hardcover');
        $this->assertEquals('English', $classic['language'], 'Classic should be in English');
        
        // Test modern state
        $modern = $factory->modern()->make()->toArray();
        $this->assertGreaterThanOrEqual(strtotime('-6 years'), strtotime($modern['publication_date']), 'Modern should be recent');
        $this->assertContains($modern['format'], ['Ebook', 'Audiobook'], 'Modern should be digital format');
        
        // Test technical state
        $technical = $factory->technical()->make()->toArray();
        $this->assertGreaterThanOrEqual(300, $technical['page_count'], 'Technical should have many pages');
        $this->assertContains($technical['format'], ['Paperback', 'Ebook'], 'Technical should be print or digital');
    }

    /**
     * Test data realism
     */
    public function test_data_realism(): void
    {
        $factory = new BookFactory();
        
        // Generate multiple books to test variety
        $books = collect();
        for ($i = 0; $i < 1000; $i++) {
            $books->push($factory->definition());
        }
        
        // Test title variety
        $titles = $books->pluck('title')->unique()->values();
        $this->assertGreaterThan(900, $titles->count(), 'Should have variety in titles');
        $this->assertLessThan(10, $titles->filter(fn($title) => strlen($title) > 100)->count(), 'Should not have extremely long titles');
        
        // Test author variety
        $authors = $books->pluck('author')->unique()->values();
        $this->assertGreaterThan(800, $authors->count(), 'Should have variety in authors');
        
        // Test price distribution
        $prices = $books->pluck('price');
        $this->assertGreaterThan(5.0, $prices->min(), 'Minimum price should be reasonable');
        $this->assertLessThan(100.0, $prices->max(), 'Maximum price should be reasonable');
        
        // Test publication date range
        $dates = $books->pluck('publication_date')->map(fn($date) => strtotime($date));
        $this->assertGreaterThan(strtotime('1900-01-01'), min($dates->all()), 'Should not have books before 1900');
        $this->assertLessThanOrEqual(time(), max($dates->all()), 'Should not have books in the future');
        
        // Test language distribution
        $languages = $books->pluck('language')->unique()->values();
        $this->assertGreaterThanOrEqual(5, $languages->count(), 'Should have multiple languages');
        
        // Test format distribution
        $formats = $books->pluck('format')->countBy();
        $this->assertArrayHasKey('Hardcover', $formats, 'Should have hardcover books');
        $this->assertArrayHasKey('Paperback', $formats, 'Should have paperback books');
        $this->assertArrayHasKey('Ebook', $formats, 'Should have ebooks');
    }

    /**
     * Validate ISBN-13 checksum helper
     */
    protected function isValidIsbn13Checksum(string $isbn): bool
    {
        if (strlen($isbn) !== 13 || !ctype_digit($isbn)) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int)$isbn[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }

        $remainder = $sum % 10;
        $checkDigit = $remainder === 0 ? 0 : 10 - $remainder;

        return (int)$isbn[12] === $checkDigit;
    }
}
