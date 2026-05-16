<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Book;
use App\Models\Category;
use App\Models\Author;
use App\Services\MemoryMonitor;
use Illuminate\Support\Str;

class MillionBookSeeder extends Seeder
{
    protected int $targetCount = 1000000;
    protected int $chunkSize = 10000;
    protected int $memoryLimit = 512 * 1024 * 1024; // 512MB
    protected MemoryMonitor $memoryMonitor;
    protected array $categories = [];
    protected array $authors = [];
    protected array $publishers = [];

    public function run(): void
    {
        $this->memoryMonitor = new MemoryMonitor($this->memoryLimit);
        $this->memoryMonitor->start();
        
        Log::info('Starting 1 million book seeding', [
            'target_count' => $this->targetCount,
            'chunk_size' => $this->chunkSize,
            'memory_limit' => $this->memoryLimit,
        ]);

        try {
            $this->prepareReferenceData();
            $this->seedBooksInChunks();
            
            $this->memoryMonitor->takeSnapshot('seeding_completed');
            
            Log::info('1 million book seeding completed successfully', [
                'statistics' => $this->memoryMonitor->getStatistics(),
            ]);
            
        } catch (\Exception $e) {
            $this->memoryMonitor->takeSnapshot('seeding_failed');
            
            Log::error('1 million book seeding failed', [
                'error' => $e->getMessage(),
                'statistics' => $this->memoryMonitor->getStatistics(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Prepare reference data (categories, authors, publishers)
     */
    protected function prepareReferenceData(): void
    {
        Log::info('Preparing reference data for 1 million books');
        
        // Create diverse categories
        $categoryNames = [
            'Science Fiction', 'Fantasy', 'Mystery', 'Thriller', 'Romance',
            'Historical Fiction', 'Biography', 'Self-Help', 'Business',
            'Cooking', 'Travel', 'Children\'s Books', 'Young Adult',
            'Horror', 'Literary Fiction', 'Graphic Novels', 'Poetry',
            'Drama', 'Science', 'History', 'Art', 'Photography',
            'Crafts & Hobbies', 'Religion & Spirituality', 'Health & Fitness',
            'Parenting & Families', 'Education & Reference', 'Comics',
            'Computers & Technology', 'Engineering', 'Law', 'Medical',
            'Architecture', 'Transportation', 'Sports & Outdoors',
            'Humor', 'Entertainment', 'Music', 'Film & Television'
        ];
        
        foreach ($categoryNames as $name) {
            $this->categories[] = Category::firstOrCreate(['name' => $name])->id;
        }

        // Create diverse authors
        $authorFirstNames = ['James', 'Mary', 'John', 'Patricia', 'Robert', 'Jennifer', 'Michael', 'Linda', 'William', 'Elizabeth', 'David', 'Barbara', 'Richard', 'Susan', 'Joseph', 'Jessica', 'Thomas', 'Sarah', 'Charles', 'Karen'];
        $authorLastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore'];
        
        for ($i = 0; $i < 1000; $i++) {
            $name = $authorFirstNames[$i % 20] . ' ' . $authorLastNames[$i % 20];
            $this->authors[] = Author::firstOrCreate(['name' => $name])->id;
        }

        // Create publishers
        $publisherNames = [
            'Penguin Random House', 'HarperCollins', 'Simon & Schuster', 'Hachette', 'Macmillan',
            'Scholastic', 'Houghton Mifflin Harcourt', 'Pearson', 'Wiley', 'Springer',
            'Oxford University Press', 'Cambridge University Press', 'Harvard University Press',
            'MIT Press', 'Stanford University Press', 'Yale University Press', 'Princeton University Press',
            'Bloomsbury', 'Picador', 'Farrar, Straus and Giroux', 'Knopf', 'Random House',
            'Harper', 'William Morrow', 'Viking', 'Doubleday', 'Little, Brown and Company'
        ];
        
        foreach ($publisherNames as $name) {
            $this->publishers[] = $name;
        }

        Log::info('Reference data prepared', [
            'categories_count' => count($this->categories),
            'authors_count' => count($this->authors),
            'publishers_count' => count($this->publishers),
        ]);
    }

    /**
     * Seed books in memory-efficient chunks
     */
    protected function seedBooksInChunks(): void
    {
        $processedCount = 0;
        $chunkCount = ceil($this->targetCount / $this->chunkSize);
        
        Log::info('Starting chunked book seeding', [
            'total_chunks' => $chunkCount,
            'chunk_size' => $this->chunkSize,
        ]);

        for ($chunk = 0; $chunk < $chunkCount; $chunk++) {
            $this->memoryMonitor->takeSnapshot("chunk_{$chunk}_start");
            
            $remainingRecords = $this->targetCount - $processedCount;
            $currentChunkSize = min($this->chunkSize, $remainingRecords);
            
            $books = $this->generateBookChunk($currentChunkSize, $processedCount);
            
            // Insert chunk efficiently
            $this->insertBookChunk($books);
            
            $processedCount += $currentChunkSize;
            
            $this->memoryMonitor->takeSnapshot("chunk_{$chunk}_end");
            
            // Log progress
            $progress = round(($processedCount / $this->targetCount) * 100, 2);
            $memoryUsage = $this->memoryMonitor->getMemoryPercentage();
            
            Log::info("Chunk {$chunk} completed", [
                'processed_count' => $processedCount,
                'progress_percent' => $progress,
                'memory_usage_percent' => $memoryUsage,
                'chunk_size' => $currentChunkSize,
            ]);
            
            // Check memory usage and adjust if needed
            if ($memoryUsage > 85) {
                Log::warning('High memory usage detected', [
                    'memory_usage_percent' => $memoryUsage,
                    'chunk' => $chunk,
                    'processed_count' => $processedCount,
                ]);
                
                // Force garbage collection
                gc_collect_cycles();
                
                // Reduce chunk size if memory is critically high
                if ($memoryUsage > 95) {
                    $this->chunkSize = max(1000, intval($this->chunkSize * 0.7));
                    Log::info('Reduced chunk size due to memory pressure', [
                        'new_chunk_size' => $this->chunkSize,
                        'memory_usage_percent' => $memoryUsage,
                    ]);
                }
            }
            
            // Clear reference arrays periodically to free memory
            if ($chunk % 10 === 0) {
                $this->clearCaches();
            }
        }
    }

    /**
     * Generate a chunk of realistic book data
     */
    protected function generateBookChunk(int $count, int $offset): array
    {
        $books = [];
        $baseYear = 1900;
        
        for ($i = 0; $i < $count; $i++) {
            $bookNumber = $offset + $i + 1;
            
            // Generate realistic title
            $titlePatterns = [
                'The {adjective} {noun} of {place}',
                '{noun} and {noun}',
                'A {adjective} Journey',
                'The {color} {noun}',
                '{name}\'s {noun}',
                'Secrets of the {noun}',
                'The Last {noun}',
                'Beyond the {noun}',
                '{adjective} {noun}',
            ];
            
            $pattern = $titlePatterns[$bookNumber % count($titlePatterns)];
            $title = $this->generateTitleFromPattern($pattern, $bookNumber);
            
            // Generate realistic price distribution
            $priceCategories = [
                'budget' => ['min' => 9.99, 'max' => 14.99],
                'standard' => ['min' => 15.00, 'max' => 24.99],
                'premium' => ['min' => 25.00, 'max' => 49.99],
                'collector' => ['min' => 50.00, 'max' => 199.99],
            ];
            
            $priceCategory = array_rand($priceCategories);
            $priceRange = $priceCategories[$priceCategory];
            $price = round(mt_rand($priceRange['min'] * 100, $priceRange['max'] * 100) / 100, 2);
            
            // Generate publication date (realistic distribution)
            $publicationYear = $baseYear + mt_rand(0, 124); // 1900-2024
            $publicationMonth = mt_rand(1, 12);
            $publicationDay = mt_rand(1, 28);
            $publicationDate = sprintf('%04d-%02d-%02d', $publicationYear, $publicationMonth, $publicationDay);
            
            // Generate ISBN-13
            $isbn13 = $this->generateISBN13($bookNumber);
            
            // Generate stock quantity
            $stockCategories = [
                'out_of_stock' => 0,
                'low_stock' => mt_rand(1, 5),
                'normal_stock' => mt_rand(6, 50),
                'high_stock' => mt_rand(51, 200),
                'overstock' => mt_rand(201, 1000),
            ];
            
            $stockCategory = array_rand($stockCategories);
            $stockQuantity = $stockCategories[$stockCategory];
            
            // Generate page count
            $pageCount = mt_rand(150, 800);
            
            // Generate language
            $languages = ['English', 'Spanish', 'French', 'German', 'Italian', 'Portuguese', 'Japanese', 'Chinese', 'Korean', 'Russian'];
            $language = $languages[$bookNumber % count($languages)];
            
            // Generate format
            $formats = ['Hardcover', 'Paperback', 'Ebook', 'Audiobook', 'Large Print'];
            $format = $formats[$bookNumber % count($formats)];
            
            // Generate rating
            $ratings = [1, 2, 3, 4, 5];
            $rating = $ratings[$bookNumber % count($ratings)];
            
            // Generate description
            $description = $this->generateDescription($title, $publicationYear, $language);
            
            $books[] = [
                'title' => $title,
                'author_id' => $this->authors[$bookNumber % count($this->authors)],
                'isbn' => $isbn13,
                'price' => $price,
                'description' => $description,
                'category_id' => $this->categories[$bookNumber % count($this->categories)],
                'publication_date' => $publicationDate,
                'stock_quantity' => $stockQuantity,
                'page_count' => $pageCount,
                'language' => $language,
                'format' => $format,
                'publisher' => $this->publishers[$bookNumber % count($this->publishers)],
                'rating' => $rating,
                'created_at' => now()->subDays(mt_rand(0, 365))->format('Y-m-d H:i:s'),
                'updated_at' => now()->subDays(mt_rand(0, 30))->format('Y-m-d H:i:s'),
            ];
        }
        
        return $books;
    }

    /**
     * Generate title from pattern
     */
    protected function generateTitleFromPattern(string $pattern, int $seed): string
    {
        $adjectives = ['Great', 'Lost', 'Hidden', 'Ancient', 'Modern', 'Dark', 'Light', 'Silent', 'Golden', 'Silver'];
        $nouns = ['Secrets', 'Mystery', 'Journey', 'Discovery', 'Adventure', 'Treasure', 'Kingdom', 'Empire', 'Legacy', 'Prophecy'];
        $places = ['Atlantis', 'Eldoria', 'Mystara', 'Shadowfell', 'Crystalpeak', 'Stonehaven', 'Moonvale', 'Sunshire'];
        $colors = ['Red', 'Blue', 'Green', 'Black', 'White', 'Golden', 'Silver', 'Crystal'];
        $names = ['Alexander', 'Victoria', 'Maximus', 'Seraphina', 'Theron', 'Isabella', 'Marcus', 'Aurelia'];
        
        $replacements = [
            '{adjective}' => $adjectives[$seed % count($adjectives)],
            '{noun}' => $nouns[$seed % count($nouns)],
            '{place}' => $places[$seed % count($places)],
            '{color}' => $colors[$seed % count($colors)],
            '{name}' => $names[$seed % count($names)],
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $pattern);
    }

    /**
     * Generate valid ISBN-13
     */
    protected function generateISBN13(int $seed): string
    {
        // Generate valid ISBN-13 with proper checksum
        $prefix = '978'; // Bookland prefix
        $registrationGroup = str_pad((string)($seed % 100), 3, '0', STR_PAD_LEFT);
        $registrant = str_pad((string)(mt_rand(1000000, 9999999)), 6, '0', STR_PAD_LEFT);
        $publication = str_pad((string)(mt_rand(100, 999)), 3, '0', STR_PAD_LEFT);
        
        $isbn12 = $prefix . $registrationGroup . $registrant . $publication;
        
        // Calculate ISBN-13 checksum
        $checksum = $this->calculateISBN13Checksum($isbn12);
        
        return $isbn12 . $checksum;
    }

    /**
     * Calculate ISBN-13 checksum
     */
    protected function calculateISBN13Checksum(string $isbn12): string
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
     * Generate realistic book description
     */
    protected function generateDescription(string $title, int $year, string $language): string
    {
        $templates = [
            "A captivating {genre} novel that takes readers on an unforgettable journey through {setting}. Published in {year}, this {language} masterpiece has been praised for its {quality} prose and {element} storytelling.",
            "This {genre} work explores the depths of {theme} with remarkable insight and {quality}. Set against the backdrop of {setting}, the narrative weaves together elements of {element} and {element2}.",
            "An extraordinary {genre} adventure that challenges conventional thinking about {theme}. The author's {quality} writing style and innovative approach to {element} make this {year} publication a must-read.",
            "A profound exploration of {theme} that resonates with {quality} authenticity. This {language} {genre} combines {element} storytelling with {element2} narrative techniques to create a truly memorable reading experience.",
        ];
        
        $genres = ['literary', 'commercial', 'experimental', 'traditional'];
        $settings = ['modern society', 'historical landscapes', 'fantastical realms', 'urban environments'];
        $themes = ['human nature', 'social justice', 'personal transformation', 'technological advancement'];
        $qualities = ['lyrical', 'compelling', 'masterful', 'insightful'];
        $elements = ['character-driven', 'plot-driven', 'atmospheric', 'philosophical'];
        $element2s = ['psychological', 'emotional', 'intellectual', 'spiritual'];
        
        $template = $templates[mt_rand(0, count($templates) - 1)];
        
        $replacements = [
            '{genre}' => $genres[mt_rand(0, count($genres) - 1)],
            '{setting}' => $settings[mt_rand(0, count($settings) - 1)],
            '{year}' => $year,
            '{language}' => $language,
            '{theme}' => $themes[mt_rand(0, count($themes) - 1)],
            '{quality}' => $qualities[mt_rand(0, count($qualities) - 1)],
            '{element}' => $elements[mt_rand(0, count($elements) - 1)],
            '{element2}' => $element2s[mt_rand(0, count($element2s) - 1)],
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Insert book chunk efficiently
     */
    protected function insertBookChunk(array $books): void
    {
        try {
            // Use chunked insert for better performance
            $chunks = array_chunk($books, 1000);
            
            foreach ($chunks as $chunk) {
                DB::table('books')->insert($chunk);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to insert book chunk', [
                'error' => $e->getMessage(),
                'chunk_size' => count($books),
            ]);
            
            throw $e;
        }
    }

    /**
     * Clear caches to free memory
     */
    protected function clearCaches(): void
    {
        // Clear Laravel caches
        if (function_exists('cache')) {
            cache()->flush();
        }
        
        // Force garbage collection
        gc_collect_cycles();
        
        Log::debug('Caches cleared and garbage collection triggered');
    }

    /**
     * Get seeder statistics
     */
    public function getStatistics(): array
    {
        return [
            'target_count' => $this->targetCount,
            'chunk_size' => $this->chunkSize,
            'memory_limit' => $this->memoryLimit,
            'memory_statistics' => $this->memoryMonitor ? $this->memoryMonitor->getStatistics() : [],
        ];
    }
}
