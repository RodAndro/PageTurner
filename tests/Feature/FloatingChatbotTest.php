<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FloatingChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_api_endpoint_responds_to_post_requests()
    {
        // Create test data
        $category = Category::factory()->create(['name' => 'Fiction']);
        Book::factory()->create([
            'title' => 'Test Book',
            'description' => 'A test book for recommendations',
            'category_id' => $category->id,
            'price' => 19.99
        ]);

        // Test POST request (what the chatbot uses)
        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'recommend some fiction books'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommendations',
            'message'
        ]);
    }

    public function test_chatbot_api_validates_input()
    {
        // Test empty query
        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => ''
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['query']);
    }

    public function test_chatbot_api_with_category_filter()
    {
        $category = Category::factory()->create(['name' => 'Science Fiction']);
        Book::factory()->create([
            'title' => 'Space Adventure',
            'category_id' => $category->id,
            'price' => 24.99
        ]);

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => 'space stories',
            'category_id' => $category->id
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'recommendations',
            'message'
        ]);
    }

    public function test_chatbot_api_long_query_validation()
    {
        $longQuery = str_repeat('a', 501); // Exceeds 500 character limit

        $response = $this->postJson('/api/v1/ai/recommendations', [
            'query' => $longQuery
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['query']);
    }
}
