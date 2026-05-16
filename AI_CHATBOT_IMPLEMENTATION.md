# PageTurner AI Chatbot Implementation

## Problem Statement

PageTurner now supports very large book catalogs, but customers can still struggle to find relevant titles when they do not know the exact title, author, ISBN, or category they want. Traditional keyword search is helpful for direct lookups, but it is not enough for natural requests such as "something cozy and hopeful," "books like a space movie," or "a beginner-friendly programming book." This affects customers because discovery becomes slower and less personal, which can reduce engagement and sales. It also affects admins and operations teams because they need a scalable way to guide users through a catalog that may contain thousands or millions of records without manually curating every recommendation.

The current system already has catalog browsing and filters, but those tools depend on exact keywords and structured category choices. They do not fully understand intent, mood, reading level, or semantic similarity between a user prompt and available books. An AI-powered chatbot is the best solution because it can interpret natural language, combine the user request with catalog context, and return concise recommendations with reasons. The system uses cloud AI when available, falls back to Gemini or local Ollama, and finally uses a safe catalog-based fallback so the chatbot continues working during provider outages.

## Success Criteria

- Input: customer chat prompt or recommendation search text, with optional category filter.
- Output: JSON response containing a friendly message, recommended books, reasons, category, price range, provider, fallback status, and `ai_generated` label.
- Performance: target response time is 5 seconds or less for normal chatbot requests.
- Accuracy: at least 80% of returned recommendations should be relevant to the prompt or selected category during manual validation.
- Resilience: OpenAI, Gemini, and Ollama failures must not break the chatbot; the local catalog fallback must return a usable response.
- Security: API keys remain in `.env`, user input is sanitized, and prompts are audit logged by hash rather than exposing full sensitive content.

## AI Tools

- Primary cloud provider: OpenAI `gpt-4o-mini`
- Secondary cloud provider: Google Gemini `gemini-1.5-flash`
- Local fallback: Ollama `llama3.2`
- Final offline safety net: PageTurner catalog matching

## Ollama Demo Setup

```powershell
ollama --version
ollama pull llama3.2
ollama run llama3.2
```

Keep Ollama running during demo if no cloud API key is available.

## Implemented Files

- `config/ai.php`
- `app/Services/AIServiceManager.php`
- `app/Services/BookRecommendationService.php`
- `app/Jobs/ProcessAITask.php`
- `app/Models/AIUsageLog.php`
- `app/Models/AIConversation.php`
- `database/migrations/2026_05_16_020000_create_ai_tables.php`
- `resources/views/components/api-chatbot.blade.php`
- `app/Http/Controllers/Api/ApiController.php`

## Validation Command

```powershell
php -d memory_limit=512M vendor\bin\phpunit tests\Feature\AiRecommendationsTest.php tests\Feature\FloatingChatbotTest.php
```
