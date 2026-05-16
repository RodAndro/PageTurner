<?php

namespace App\Services;

use App\Models\AIConversation;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Support\Str;

class BookRecommendationService
{
    public function __construct(private AIServiceManager $ai)
    {
    }

    public function recommend(string $query, ?int $categoryId = null, ?int $userId = null): array
    {
        $query = $this->sanitizeInput($query);

        if ($this->isGreeting($query)) {
            return $this->recordAndReturn($userId, 'local_catalog', true, $categoryId, [], $query, [
                'message' => "Hi! Tell me a genre, author, book title, movie, or reading mood and I'll look through the PageTurner catalog for you.",
                'recommendations' => [],
            ]);
        }

        $categoryMatch = $categoryId ? Category::query()->find($categoryId) : $this->matchingCategory($query);
        $isDirectCategoryRequest = $categoryMatch && $this->isDirectCategoryRequest($query, $categoryMatch);
        $books = $this->availableBooks($categoryMatch, $query);
        $categories = $this->availableCategories();

        if ($this->asksForGenres($query)) {
            return $this->recordAndReturn($userId, 'local_catalog', true, $categoryId, $books, $query, [
                'message' => $this->genreOverviewMessage($categories),
                'recommendations' => [],
            ]);
        }

        if ($isDirectCategoryRequest) {
            $payload = empty($books)
                ? $this->noExactMatchResponse($query, $categories)
                : $this->categoryRecommendations($categoryMatch->name, $books);

            return $this->recordAndReturn($userId, 'local_catalog', true, $categoryMatch->id, $books, $query, $payload);
        }

        $prompt = $this->buildPrompt($query, $books, $categories);

        try {
            $aiResult = $this->ai->generateWithFallback($prompt, [
                'feature' => 'recommendation',
                'user_id' => $userId,
                'source' => 'chatbot',
            ]);

            $payload = $this->parseResponse($aiResult['text'], $books, $categories, $query);
            $provider = $aiResult['provider'];
            $fallback = (bool) ($aiResult['fallback'] ?? false);
        } catch (\Throwable $e) {
            $payload = $this->localCatalogFallback($query, $books, $categories);
            $provider = 'local_catalog';
            $fallback = true;
        }

        $payload = array_merge([
            'recommendations' => [],
            'message' => 'Here are some books you might enjoy.',
        ], $payload, [
            'provider' => $provider,
            'fallback' => $fallback,
            'ai_generated' => $provider !== 'local_catalog',
        ]);

        return $this->recordAndReturn($userId, $provider, $fallback, $categoryId, $books, $query, $payload);
    }

    protected function recordAndReturn(
        ?int $userId,
        string $provider,
        bool $fallback,
        ?int $categoryId,
        array $books,
        string $query,
        array $payload
    ): array {
        $payload = array_merge([
            'recommendations' => [],
            'message' => 'Here are some books you might enjoy.',
        ], $payload, [
            'provider' => $provider,
            'fallback' => $fallback,
            'ai_generated' => $provider !== 'local_catalog',
        ]);

        AIConversation::create([
            'user_id' => $userId,
            'provider' => $provider,
            'status' => 'completed',
            'prompt' => $query,
            'response' => $payload,
            'metadata' => [
                'category_id' => $categoryId,
                'available_books' => count($books),
            ],
        ]);

        return $payload;
    }

    protected function availableBooks(?Category $categoryMatch, string $query): array
    {
        $terms = $this->searchTerms($query);

        if ($categoryMatch) {
            return Book::query()
                ->with('category')
                ->where('category_id', $categoryMatch->id)
                ->latest('id')
                ->take(50)
                ->get()
                ->map(fn (Book $book) => $this->bookPayload($book))
                ->all();
        }

        $books = Book::query()
            ->with('category')
            ->when($terms->isNotEmpty(), function ($bookQuery) use ($terms) {
                $bookQuery->where(function ($inner) use ($terms) {
                    foreach ($terms as $term) {
                        $inner->orWhere('title', 'like', "%{$term}%")
                            ->orWhere('author', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$term}%"));
                    }
                });
            })
            ->latest('id')
            ->take(50)
            ->get()
            ->map(fn (Book $book) => $this->bookPayload($book))
            ->all();

        return $books;
    }

    protected function bookPayload(Book $book): array
    {
        return [
                'id' => $book->id,
                'title' => $book->title,
                'author' => $book->author ?? 'Unknown',
                'description' => Str::limit((string) $book->description, 220),
                'price' => (float) $book->price,
                'category' => $book->category?->name ?? 'Uncategorized',
            ];
    }

    protected function categoryRecommendations(string $categoryName, array $books): array
    {
        return [
            'message' => "Here are books I found inside the {$categoryName} category.",
            'recommendations' => collect($books)->take(5)->map(fn (array $book) => [
                'title' => $book['title'],
                'reason' => "This book is listed under {$categoryName}.",
                'category' => $book['category'],
                'price_range' => '$' . number_format((float) $book['price'], 2),
                'description' => $book['description'] ?: "A {$categoryName} book from the PageTurner catalog.",
            ])->all(),
        ];
    }

    protected function buildPrompt(string $query, array $books, array $categories): string
    {
        $bookList = collect($books)->take(20)->map(function (array $book): string {
            return "- {$book['title']} by {$book['author']} | {$book['category']} | \${$book['price']} | {$book['description']}";
        })->implode("\n") ?: 'No exact catalog matches found.';

        $categoryList = empty($categories) ? 'No categories available.' : $this->categoryListText($categories);

        return <<<PROMPT
You are PageTurner AI, a book recommendation assistant.

User request: "{$query}"

Matching catalog books:
{$bookList}

Available PageTurner categories:
{$categoryList}

Return valid JSON only with this shape:
{
  "message": "short friendly response",
  "recommendations": [
    {
      "title": "catalog title",
      "reason": "why it matches",
      "category": "category",
      "price_range": "price",
      "description": "short summary"
    }
  ]
}

Rules:
- If matching catalog books are listed, recommend only those books.
- If no exact catalog matches are listed, do not invent book titles. Explain that the catalog has no exact match and suggest categories that have books.
- Do not invent unavailable book details.
- Do not claim a category has books unless its count is greater than zero.
- Keep the response concise.
PROMPT;
    }

    protected function parseResponse(string $text, array $books, array $categories, string $query): array
    {
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $text = $matches[0];
        }

        $decoded = json_decode($text, true);
        if (!is_array($decoded)) {
            return empty($books)
                ? $this->noExactMatchResponse($query, $categories)
                : $this->localCatalogFallback($query, $books, $categories);
        }

        $decoded['recommendations'] = array_values(array_filter(
            $decoded['recommendations'] ?? [],
            fn ($item) => is_array($item) && !empty($item['title'])
        ));

        if (empty($books) || empty($decoded['recommendations'])) {
            return $this->noExactMatchResponse($query, $categories);
        }

        return $decoded;
    }

    protected function localCatalogFallback(string $query, array $books, array $categories = []): array
    {
        $terms = $this->searchTerms($query);

        if ($terms->isEmpty()) {
            return [
                'message' => 'Tell me a genre, author, title, or reading mood and I will search the PageTurner catalog for matching books.',
                'recommendations' => [],
            ];
        }

        $ranked = collect($books)
            ->map(function (array $book) use ($terms): array {
                $haystack = strtolower($book['title'] . ' ' . $book['author'] . ' ' . $book['category'] . ' ' . $book['description']);

                $book['match_score'] = $terms->sum(fn ($term) => str_contains($haystack, $term) ? 1 : 0);

                return $book;
            })
            ->filter(fn (array $book) => $book['match_score'] > 0)
            ->sortByDesc('match_score')
            ->take(3)
            ->values();

        if ($ranked->isEmpty()) {
            return $this->noExactMatchResponse($query, $categories);
        }

        return [
            'message' => 'AI providers are unavailable right now, so I found the closest matching books in the PageTurner catalog.',
            'recommendations' => $ranked->map(fn (array $book) => [
                'title' => $book['title'],
                'reason' => 'Matched your request using title, author, category, and description keywords.',
                'category' => $book['category'],
                'price_range' => '$' . number_format((float) $book['price'], 2),
                'description' => $book['description'] ?: 'A catalog recommendation from PageTurner.',
            ])->all(),
        ];
    }

    protected function searchTerms(string $query)
    {
        $stopWords = [
            'any', 'are', 'book', 'books', 'can', 'could', 'do', 'does', 'for',
            'have', 'hello', 'hey', 'hi', 'like', 'me', 'please', 'recommend',
            'show', 'some', 'the', 'there', 'with', 'you',
        ];

        return collect(preg_split('/[^a-z0-9]+/i', strtolower($query)) ?: [])
            ->map(fn ($term) => trim($term))
            ->filter(fn ($term) => strlen($term) > 2 && !in_array($term, $stopWords, true))
            ->values();
    }

    protected function isGreeting(string $query): bool
    {
        return in_array(strtolower(trim($query)), ['hi', 'hello', 'hey', 'good morning', 'good afternoon', 'good evening'], true);
    }

    protected function sanitizeInput(string $query): string
    {
        return trim(strip_tags($query));
    }

    protected function noExactMatchResponse(string $query, array $categories): array
    {
        $topic = $this->requestedTopic($query);
        $matchingCategory = $topic
            ? collect($categories)->first(fn (array $category) => $this->normalizeCategoryText($category['name']) === $this->normalizeCategoryText($topic))
            : null;
        $categoryText = empty($categories)
            ? 'I do not see any categories loaded in the catalog yet.'
            : 'Categories with books right now: ' . $this->categoryListText($categories, true) . '.';

        $suggestion = $this->categorySuggestion($topic, $categories);
        $suggestionText = $suggestion
            ? " The closest category with books to try is {$suggestion}."
            : ' Try asking for one of those categories, a specific author, a title, or a mood like "dark mystery", "fast-paced adventure", or "light romance".';

        $prefix = $matchingCategory && (int) $matchingCategory['books_count'] === 0
            ? "The {$matchingCategory['name']} category exists, but it does not have books in the current PageTurner catalog yet."
            : ($topic
                ? "I do not see any {$topic} books in the current PageTurner catalog."
                : 'I could not find an exact catalog match for that request.');

        return [
            'message' => "{$prefix} {$categoryText}{$suggestionText}",
            'recommendations' => [],
        ];
    }

    protected function requestedTopic(string $query): ?string
    {
        $category = $this->matchingCategory($query);
        if ($category) {
            return strtolower($category->name);
        }

        $terms = $this->searchTerms($query);

        return $terms->first();
    }

    protected function categorySuggestion(?string $topic, array $categories): ?string
    {
        if (!$topic || empty($categories)) {
            return null;
        }

        $fallbacks = [
            'horror' => ['Thriller', 'Mystery', 'Fantasy', 'Fiction', 'Suspense'],
            'scary' => ['Thriller', 'Mystery', 'Fantasy', 'Fiction', 'Suspense'],
            'ghost' => ['Thriller', 'Mystery', 'Fantasy', 'Fiction'],
            'romance' => ['Romance', 'Fiction', 'Drama'],
            'love' => ['Romance', 'Fiction', 'Drama'],
            'space' => ['Science Fiction', 'Fiction', 'Science'],
            'magic' => ['Fantasy', 'Fiction'],
            'adventure' => ['Adventure', 'Fiction'],
        ];

        $normalizedTopic = $this->normalizeCategoryText($topic);
        $normalized = collect($categories)
            ->filter(fn (array $category) => (int) $category['books_count'] > 0)
            ->mapWithKeys(fn (array $category) => [$this->normalizeCategoryText($category['name']) => $category['name']]);

        if ($normalized->has($normalizedTopic)) {
            return $normalized->get($normalizedTopic);
        }

        foreach ($fallbacks[$normalizedTopic] ?? [] as $candidate) {
            $normalizedCandidate = $this->normalizeCategoryText($candidate);
            if ($normalized->has($normalizedCandidate)) {
                return $normalized->get($normalizedCandidate);
            }
        }

        return $normalized->first();
    }

    protected function availableCategories(): array
    {
        return Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->limit(12)
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'books_count' => (int) $category->books_count,
            ])
            ->all();
    }

    protected function genreOverviewMessage(array $categories): string
    {
        if (empty($categories)) {
            return 'I do not see any genres or categories loaded in the PageTurner catalog yet.';
        }

        return 'Here are the PageTurner genres I found in the catalog: ' . $this->categoryListText($categories) . '. Ask for any genre with books and I will recommend matching titles.';
    }

    protected function categoryListText(array $categories, bool $onlyWithBooks = false): string
    {
        $filtered = collect($categories)
            ->when($onlyWithBooks, fn ($items) => $items->filter(fn (array $category) => (int) $category['books_count'] > 0))
            ->take(8)
            ->map(fn (array $category) => $category['name'] . ' (' . (int) $category['books_count'] . ')');

        return $filtered->isEmpty() ? 'none yet' : $filtered->implode(', ');
    }

    protected function matchingCategory(string $query): ?Category
    {
        $terms = $this->searchTerms($query);
        if ($terms->isEmpty()) {
            return null;
        }

        $normalizedQuery = $this->normalizeCategoryText($query);
        $categories = Category::query()->orderBy('name')->get();

        $exact = $categories->first(fn (Category $category) => $this->normalizeCategoryText($category->name) === $normalizedQuery);
        if ($exact) {
            return $exact;
        }

        $contained = $categories->first(function (Category $category) use ($normalizedQuery): bool {
            $normalizedName = $this->normalizeCategoryText($category->name);

            return $normalizedName !== '' && str_contains($normalizedQuery, $normalizedName);
        });

        if ($contained) {
            return $contained;
        }

        return $categories->first(function (Category $category) use ($terms): bool {
            $normalizedName = $this->normalizeCategoryText($category->name);

            return $terms->contains(function (string $term) use ($normalizedName): bool {
                $normalizedTerm = $this->normalizeCategoryText($term);

                return $normalizedTerm !== '' && str_contains($normalizedName, $normalizedTerm);
            });
        });
    }

    protected function isDirectCategoryRequest(string $query, Category $category): bool
    {
        $normalizedQuery = $this->normalizeCategoryText($query);
        $normalizedCategory = $this->normalizeCategoryText($category->name);

        if ($normalizedQuery === $normalizedCategory) {
            return true;
        }

        $directPrefixes = [
            'give me ',
            'show me ',
            'list ',
            'books in ',
            'category ',
        ];

        $lowerQuery = strtolower(trim($query));
        foreach ($directPrefixes as $prefix) {
            if (str_starts_with($lowerQuery, $prefix) && $normalizedQuery === $this->normalizeCategoryText($prefix . $category->name)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizeCategoryText(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower($value)) ?? '';
    }

    protected function asksForGenres(string $query): bool
    {
        $normalized = strtolower($query);

        return str_contains($normalized, 'genre')
            || str_contains($normalized, 'category')
            || str_contains($normalized, 'categories')
            || str_contains($normalized, 'what books do you have')
            || str_contains($normalized, 'what do you have');
    }
}
