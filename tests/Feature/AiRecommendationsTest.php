<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Jobs\ProcessAITask;
use App\Models\Book;
use App\Models\Category;
use App\Models\AIConversation;
use App\Models\AIUsageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class AiRecommendationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_recommendations_endpoint_returns_response()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        // Create some test books
        $category = Category::factory()->create(['name' => 'Fiction']);
        Book::factory()->create([
            'title' => 'The Great Adventure',
            'description' => 'An exciting adventure story',
            'category_id' => $category->id,
            'price' => 19.99
        ]);

        // Test the endpoint without API key (should return fallback)
        $response = $this->getJson('/api/v1/ai/recommendations?query=adventure books');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommendations',
            'message',
            'provider',
            'fallback',
            'aiGenerated',
        ]);

        $this->assertDatabaseHas('ai_conversations', [
            'provider' => 'local_catalog',
            'status' => 'completed',
        ]);
    }

    public function test_ai_recommendations_requires_query_parameter()
    {
        $response = $this->getJson('/api/v1/ai/recommendations');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_ai_recommendations_with_category_filter()
    {
        $category = Category::factory()->create(['name' => 'Science Fiction']);
        Book::factory()->create([
            'title' => 'Space Odyssey',
            'category_id' => $category->id,
            'price' => 24.99
        ]);

        $response = $this->getJson('/api/v1/ai/recommendations?query=space stories&category_id=' . $category->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommendations',
            'message'
        ]);
    }

    public function test_ai_recommendations_with_long_query_is_validated()
    {
        $longQuery = str_repeat('a', 501); // Exceeds 500 character limit

        $response = $this->getJson('/api/v1/ai/recommendations?query=' . $longQuery);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_ai_recommendations_uses_ollama_when_cloud_keys_are_missing()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => true,
            'ai.providers.ollama.base_url' => 'http://localhost:11434',
            'ai.providers.ollama.model' => 'llama3.2',
        ]);

        Http::fake([
            'localhost:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'message' => 'AI generated recommendation.',
                    'recommendations' => [
                        [
                            'title' => 'Space Odyssey',
                            'reason' => 'Matches space stories.',
                            'category' => 'Science Fiction',
                            'price_range' => '$24.99',
                            'description' => 'A space adventure.',
                        ],
                    ],
                ]),
                'eval_count' => 88,
            ], 200),
        ]);

        $category = Category::factory()->create(['name' => 'Science Fiction']);
        Book::factory()->create([
            'title' => 'Space Odyssey',
            'category_id' => $category->id,
            'price' => 24.99,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'space stories',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'ollama')
            ->assertJsonPath('aiGenerated', true);

        $this->assertDatabaseHas('ai_usage_logs', [
            'provider' => 'ollama',
            'success' => true,
        ]);
    }

    public function test_primary_provider_failure_falls_back_to_next_cloud_provider()
    {
        config([
            'ai.default' => 'openai',
            'ai.fallback_chain' => ['openai', 'gemini', 'ollama'],
            'ai.providers.openai.api_key' => 'test-openai-key',
            'ai.providers.openai.base_url' => 'https://openai.test/v1',
            'ai.providers.gemini.api_key' => 'test-gemini-key',
            'ai.providers.gemini.base_url' => 'https://gemini.test/v1beta',
            'ai.providers.ollama.enabled' => false,
        ]);

        Http::fake([
            '*openai.test*' => Http::response(['error' => 'provider outage'], 500),
            '*gemini.test*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'message' => 'Gemini fallback worked.',
                                'recommendations' => [[
                                    'title' => 'Fallback Fiction',
                                    'reason' => 'Matches the request.',
                                    'category' => 'Fiction',
                                    'price_range' => '$12.50',
                                    'description' => 'A fallback recommendation.',
                                ]],
                            ]),
                        ]],
                    ],
                ]],
                'usageMetadata' => ['totalTokenCount' => 42],
            ], 200),
        ]);

        $category = Category::factory()->create(['name' => 'Fiction']);
        Book::factory()->create([
            'title' => 'Fallback Fiction',
            'category_id' => $category->id,
            'price' => 12.50,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'provider fallback request',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'gemini')
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('aiGenerated', true);

        $this->assertDatabaseHas('ai_usage_logs', [
            'provider' => 'openai',
            'success' => false,
        ]);

        $this->assertDatabaseHas('ai_usage_logs', [
            'provider' => 'gemini',
            'success' => true,
        ]);
    }

    public function test_ai_unavailable_degrades_to_local_catalog_without_losing_conversation_data()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $category = Category::factory()->create(['name' => 'Mystery']);
        Book::factory()->create([
            'title' => 'Quiet Clue',
            'description' => 'A careful mystery with gentle pacing.',
            'category_id' => $category->id,
            'price' => 15.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => '<script>alert("mystery")</script> gentle mystery',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'local_catalog')
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('aiGenerated', false)
            ->assertJsonCount(1, 'recommendations');

        $this->assertDatabaseHas('ai_conversations', [
            'provider' => 'local_catalog',
            'status' => 'completed',
            'prompt' => 'alert("mystery") gentle mystery',
        ]);
    }

    public function test_greeting_returns_chat_prompt_instead_of_random_books()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        Category::factory()->create(['name' => 'General']);
        Book::factory()->count(3)->create();

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'hi',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'local_catalog')
            ->assertJsonPath('fallback', true)
            ->assertJsonCount(0, 'recommendations');

        $this->assertStringContainsString('Tell me a genre', $response->json('message'));
    }

    public function test_local_catalog_returns_real_horror_matches()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $horror = Category::factory()->create(['name' => 'Horror']);
        $romance = Category::factory()->create(['name' => 'Romance']);

        Book::factory()->create([
            'title' => 'Midnight House',
            'description' => 'A horror story about a haunted home.',
            'category_id' => $horror->id,
            'price' => 18.99,
        ]);

        Book::factory()->create([
            'title' => 'Sunny Letters',
            'description' => 'A warm romance novel.',
            'category_id' => $romance->id,
            'price' => 14.99,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'do have any horror',
        ]);

        $response->assertOk()
            ->assertJsonPath('recommendations.0.title', 'Midnight House')
            ->assertJsonPath('recommendations.0.category', 'Horror');

        $this->assertNotSame('Sunny Letters', $response->json('recommendations.0.title'));
    }

    public function test_local_catalog_does_not_return_random_books_when_no_match_exists()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $category = Category::factory()->create(['name' => 'Romance']);
        Book::factory()->create([
            'title' => 'Sunny Letters',
            'description' => 'A warm romance novel.',
            'category_id' => $category->id,
            'price' => 14.99,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'do have any horror',
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'recommendations');

        $this->assertStringContainsString('I do not see any horror books', $response->json('message'));
        $this->assertStringContainsString('Categories with books right now: Romance (1)', $response->json('message'));
    }

    public function test_ai_no_match_response_is_expanded_with_catalog_guidance()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => true,
            'ai.providers.ollama.base_url' => 'http://localhost:11434',
            'ai.providers.ollama.model' => 'llama3.2',
        ]);

        Http::fake([
            'localhost:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'message' => 'Can\'t find horror books in our catalog.',
                    'recommendations' => [],
                ]),
                'eval_count' => 30,
            ], 200),
        ]);

        $mystery = Category::factory()->create(['name' => 'Mystery']);
        Category::factory()->create(['name' => 'Romance']);
        Book::factory()->create([
            'title' => 'Quiet Clue',
            'description' => 'A careful mystery.',
            'category_id' => $mystery->id,
            'price' => 15.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'do you have any horror books?',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'ollama')
            ->assertJsonCount(0, 'recommendations');

        $this->assertStringContainsString('horror books', $response->json('message'));
        $this->assertStringContainsString('Categories with books right now:', $response->json('message'));
        $this->assertStringContainsString('Mystery (1)', $response->json('message'));
    }

    public function test_category_name_request_scans_books_inside_that_category()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $mystery = Category::factory()->create(['name' => 'Mystery']);
        $fiction = Category::factory()->create(['name' => 'Fiction']);

        Book::factory()->create([
            'title' => 'Quiet Clue',
            'description' => 'A careful detective story.',
            'category_id' => $mystery->id,
            'price' => 15.00,
        ]);

        Book::factory()->create([
            'title' => 'Bright Road',
            'description' => 'A general fiction novel.',
            'category_id' => $fiction->id,
            'price' => 12.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'Mystery',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'local_catalog')
            ->assertJsonPath('aiGenerated', false)
            ->assertJsonPath('recommendations.0.title', 'Quiet Clue')
            ->assertJsonPath('recommendations.0.category', 'Mystery');
    }

    public function test_mood_request_with_category_word_uses_ollama_context()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => true,
            'ai.providers.ollama.base_url' => 'http://localhost:11434',
            'ai.providers.ollama.model' => 'llama3.2',
        ]);

        Http::fake([
            'localhost:11434/api/generate' => Http::response([
                'response' => json_encode([
                    'message' => 'For a dark mystery mood, start with Quiet Clue.',
                    'recommendations' => [[
                        'title' => 'Quiet Clue',
                        'reason' => 'It fits a darker mystery mood.',
                        'category' => 'Mystery',
                        'price_range' => '$15.00',
                        'description' => 'A careful detective story.',
                    ]],
                ]),
                'eval_count' => 45,
            ], 200),
        ]);

        $mystery = Category::factory()->create(['name' => 'Mystery']);
        Book::factory()->create([
            'title' => 'Quiet Clue',
            'description' => 'A careful detective story with a dark mood.',
            'category_id' => $mystery->id,
            'price' => 15.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'give me a book for a dark mystery mood',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'ollama')
            ->assertJsonPath('aiGenerated', true)
            ->assertJsonPath('recommendations.0.title', 'Quiet Clue');
    }

    public function test_hyphenated_category_request_scans_books_inside_that_category()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => true,
        ]);

        $nonFiction = Category::factory()->create(['name' => 'Non-Fiction']);
        $biography = Category::factory()->create(['name' => 'Biography']);

        Book::factory()->create([
            'title' => 'Real World Notes',
            'description' => 'A practical non-fiction book.',
            'category_id' => $nonFiction->id,
            'price' => 21.00,
        ]);

        Book::factory()->create([
            'title' => 'A Life Remembered',
            'description' => 'A biography.',
            'category_id' => $biography->id,
            'price' => 18.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'give me non fiction',
        ]);

        $response->assertOk()
            ->assertJsonPath('provider', 'local_catalog')
            ->assertJsonPath('aiGenerated', false)
            ->assertJsonPath('recommendations.0.title', 'Real World Notes')
            ->assertJsonPath('recommendations.0.category', 'Non-Fiction');

        $this->assertStringContainsString('Non-Fiction category', $response->json('message'));
    }

    public function test_empty_category_request_explains_category_has_no_books()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        Category::factory()->create(['name' => 'Mystery']);
        $fiction = Category::factory()->create(['name' => 'Fiction']);
        Book::factory()->create([
            'title' => 'Bright Road',
            'description' => 'A general fiction novel.',
            'category_id' => $fiction->id,
            'price' => 12.00,
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'Mystery',
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'recommendations');

        $this->assertStringContainsString('The Mystery category exists, but it does not have books', $response->json('message'));
        $this->assertStringContainsString('Fiction (1)', $response->json('message'));
    }

    public function test_genre_overview_lists_categories_with_book_counts()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $fiction = Category::factory()->create(['name' => 'Fiction']);
        Category::factory()->create(['name' => 'Mystery']);
        Book::factory()->create(['category_id' => $fiction->id]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'what genres do you have?',
        ]);

        $response->assertOk()
            ->assertJsonCount(0, 'recommendations');

        $this->assertStringContainsString('Fiction (1)', $response->json('message'));
        $this->assertStringContainsString('Mystery (0)', $response->json('message'));
    }

    public function test_ai_recommendations_complete_under_five_seconds_for_local_fallback()
    {
        config([
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $category = Category::factory()->create(['name' => 'Performance']);
        Book::factory()->count(5)->create(['category_id' => $category->id]);

        $started = microtime(true);
        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'fast recommendation',
        ]);

        $response->assertOk();
        $this->assertLessThan(5, microtime(true) - $started);
    }

    public function test_ai_queue_job_completes_and_has_retry_configuration()
    {
        config([
            'queue.default' => 'sync',
            'ai.providers.openai.api_key' => null,
            'ai.providers.gemini.api_key' => null,
            'ai.providers.ollama.enabled' => false,
        ]);

        $category = Category::factory()->create(['name' => 'Queued']);
        Book::factory()->create(['category_id' => $category->id]);

        ProcessAITask::dispatchSync([
            'feature' => 'recommendation',
            'query' => 'queued recommendation',
            'category_id' => $category->id,
        ]);

        $job = new ProcessAITask([]);

        $this->assertSame(3, $job->tries);
        $this->assertSame([10, 30, 60], $job->backoff);
        $this->assertDatabaseHas('ai_conversations', [
            'provider' => 'local_catalog',
            'status' => 'completed',
            'prompt' => 'queued recommendation',
        ]);
    }

    public function test_ai_tracking_tables_include_required_schema_fields()
    {
        foreach (['ai_usage_logs', 'ai_conversations'] as $table) {
            $this->assertTrue(Schema::hasColumn($table, 'id'));
            $this->assertTrue(Schema::hasColumn($table, 'user_id'));
            $this->assertTrue(Schema::hasColumn($table, 'metadata'));
            $this->assertTrue(Schema::hasColumn($table, 'created_at'));
            $this->assertTrue(Schema::hasColumn($table, 'updated_at'));
            $this->assertTrue(Schema::hasColumn($table, 'deleted_at'));
        }

        $this->assertTrue(Schema::hasColumn('ai_conversations', 'response'));
    }

    public function test_chatbot_view_has_client_side_html_escape_helper()
    {
        $view = file_get_contents(resource_path('views/components/api-chatbot.blade.php'));

        $this->assertStringContainsString('function escapeApiChatHtml', $view);
        $this->assertStringContainsString('escapeApiChatHtml(message)', $view);
        $this->assertStringContainsString('title: escapeApiChatHtml(book.title)', $view);
        $this->assertStringContainsString('description: escapeApiChatHtml(book.description)', $view);
    }
}
