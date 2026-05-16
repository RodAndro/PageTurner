<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAIService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $this->baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    }

    /**
     * Get book recommendations based on user query
     */
    public function getBookRecommendations(string $query, array $availableBooks = []): array
    {
        try {
            $prompt = $this->buildRecommendationPrompt($query, $availableBooks);
            
            $response = Http::withoutVerifying()->post($this->baseUrl . '?key=' . $this->apiKey, [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.7,
                    'topK' => 40,
                    'topP' => 0.95,
                    'maxOutputTokens' => 1024,
                ]
            ]);

            if (!$response->successful()) {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                return $this->getFallbackRecommendations($query);
            }

            $data = $response->json();
            return $this->parseRecommendationResponse($data, $availableBooks);

        } catch (\Exception $e) {
            Log::error('Gemini AI Service Error', [
                'error' => $e->getMessage(),
                'query' => $query
            ]);
            return $this->getFallbackRecommendations($query);
        }
    }

    /**
     * Build the prompt for book recommendations
     */
    private function buildRecommendationPrompt(string $query, array $availableBooks): string
    {
        $booksContext = '';
        if (!empty($availableBooks)) {
            $booksList = collect($availableBooks)->take(20)->map(function ($book) {
                $author = isset($book['author']) ? $book['author'] : 'Unknown';
                $category = isset($book['category']) ? $book['category'] : 'Unknown';
                return "- {$book['title']} by (Author: {$author}) - {$category} - \${$book['price']} - {$book['description']}";
            })->implode("\n");
            
            $booksContext = "\n\nAvailable books in our store:\n{$booksList}\n\n";
        }

        return "You are PageTurner AI, an intelligent assistant for the PageTurner Online Bookstore. Your role is to help customers discover books and provide personalized recommendations.

Behavior Rules:
- Be friendly, professional, concise, and helpful
- Always prioritize accuracy and customer assistance
- Never invent book details that aren't provided
- If information is unavailable, clearly say so
- Keep responses short and readable
- Recommend books using categories, prices, popularity, and relevance
- When explaining books, summarize briefly without spoilers
- Avoid harmful, offensive, illegal, or inappropriate content

User Query: {$query}{$booksContext}

Please provide book recommendations in the following JSON format:
{
    \"recommendations\": [
        {
            \"title\": \"Book Title\",
            \"reason\": \"Brief reason why this book matches their request\",
            \"category\": \"Book category\",
            \"price_range\": \"price information\",
            \"description\": \"Brief description without spoilers\"
        }
    ],
    \"message\": \"A friendly message to the user\"
}

If no books match the query from the available books, suggest general book categories or types that might interest them and recommend they browse those categories.";
    }

    /**
     * Parse the Gemini API response
     */
    private function parseRecommendationResponse(array $data, array $availableBooks): array
    {
        try {
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Try to extract JSON from the response
            if (preg_match('/\{.*\}/s', $text, $matches)) {
                $jsonData = json_decode($matches[0], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $jsonData;
                }
            }

            // Fallback if JSON parsing fails
            return [
                'recommendations' => [],
                'message' => $text,
                'fallback' => true
            ];

        } catch (\Exception $e) {
            Log::error('Error parsing Gemini response', ['error' => $e->getMessage()]);
            return $this->getFallbackRecommendations('');
        }
    }

    /**
     * Get fallback recommendations when AI fails
     */
    private function getFallbackRecommendations(string $query): array
    {
        $query = strtolower($query);
        
        // Simple keyword-based fallback recommendations
        $recommendations = [];
        $message = "I'm having trouble connecting to my AI service right now. Here are some general recommendations:";

        if (str_contains($query, 'fiction') || str_contains($query, 'novel')) {
            $recommendations = [
                [
                    'title' => 'Browse Fiction Section',
                    'reason' => 'Find engaging stories and novels',
                    'category' => 'Fiction',
                    'price_range' => 'Various prices available',
                    'description' => 'Explore our wide collection of fiction books'
                ]
            ];
        } elseif (str_contains($query, 'programming') || str_contains($query, 'code') || str_contains($query, 'tech')) {
            $recommendations = [
                [
                    'title' => 'Browse Programming Books',
                    'reason' => 'Enhance your technical skills',
                    'category' => 'Technology',
                    'price_range' => 'Various prices available',
                    'description' => 'Discover programming guides and tech books'
                ]
            ];
        } else {
            $recommendations = [
                [
                    'title' => 'Browse Best Sellers',
                    'reason' => 'Popular books chosen by our customers',
                    'category' => 'Various',
                    'price_range' => 'Various prices available',
                    'description' => 'Check out our most popular books'
                ]
            ];
        }

        return [
            'recommendations' => $recommendations,
            'message' => $message,
            'fallback' => true
        ];
    }

    /**
     * Check if the service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
