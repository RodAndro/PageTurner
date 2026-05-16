# Activity 8

## AI Integration for E-Commerce Systems

Project: PageTurner Bookstore  
Course/Section: BSIT 3C  
AI Providers Used: Google Gemini API and Ollama

## Problem Identification

PageTurner customers can browse books by title, author, ISBN, and category, but discovery becomes difficult when a user does not know the exact book they want. A customer may ask for "beginner programming books," "something romantic and light," or "books like an adventure story," which traditional keyword search may not handle well.

The Activity 8 AI feature solves this by adding an AI-powered book recommendation and chatbot experience. The user can describe what they want in natural language, and the system returns relevant book suggestions with short reasons. The system uses Gemini API for cloud AI responses and Ollama for local AI fallback. If AI providers are unavailable, the application still returns safe catalog-based fallback recommendations.

## Solution Overview

The AI feature is designed to be useful, resilient, and responsible:

- Users enter a natural language request through the chatbot or AI recommendation interface.
- The application builds a prompt using the user query and available catalog context.
- Gemini API is used for AI-generated recommendations when configured.
- Ollama is used as a local model provider for fallback or offline demonstrations.
- If AI calls fail, the system returns basic keyword/catalog recommendations.
- Usage is logged for cost tracking, latency monitoring, and audit purposes.
- AI keys remain in environment variables and are not hardcoded in source code.

## Implemented Components

| File | Purpose |
| --- | --- |
| `config/ai.php` | AI provider configuration for Gemini, Ollama, fallback chain, timeouts, and rate limits |
| `app/Services/AIServiceManager.php` | Multi-provider AI manager with fallback handling, usage logging, and audit logging |
| `app/Services/GeminiAIService.php` | Gemini API integration for book recommendations |
| `app/Services/BookRecommendationService.php` | Builds recommendation responses from catalog and AI output |
| `app/Jobs/ProcessAITask.php` | Queue job for background AI task processing |
| `app/Models/AIUsageLog.php` | Stores provider, token, latency, success, and cost tracking records |
| `app/Models/AIConversation.php` | Stores AI conversation/session records |
| `database/migrations/2026_05_16_020000_create_ai_tables.php` | Creates AI usage and conversation tables |
| `database/migrations/2026_05_16_030000_add_soft_deletes_to_ai_tables.php` | Adds soft delete support for AI tables |
| `resources/views/components/api-chatbot.blade.php` | API-powered chatbot UI component |
| `resources/views/components/floating-chatbot.blade.php` | Floating chatbot interface |
| `resources/views/components/ai-recommendations.blade.php` | AI recommendation UI component |
| `app/Http/Controllers/Api/ApiController.php` | API endpoints used by recommendation/chatbot features |
| `AI_CHATBOT_IMPLEMENTATION.md` | AI chatbot technical writeup |
| `AI_RECOMMENDATIONS_SETUP.md` | Gemini recommendation setup guide |

## AI Provider Setup

### Gemini API

Add the Gemini API key to `.env`:

```env
GEMINI_API_KEY=your_gemini_api_key_here
AI_DEFAULT_PROVIDER=gemini
AI_FALLBACK_ENABLED=true
AI_FALLBACK_CHAIN=gemini,ollama
GEMINI_MODEL=gemini-1.5-flash
```

### Ollama

Install and run Ollama locally:

```powershell
ollama --version
ollama pull llama3.2
ollama run llama3.2
```

Recommended `.env` values:

```env
OLLAMA_ENABLED=true
OLLAMA_BASE_URL=http://localhost:11434
OLLAMA_MODEL=llama3.2
OLLAMA_TIMEOUT_SECONDS=60
```

## Technical Architecture

The AI feature follows a layered architecture:

| Layer | Responsibility |
| --- | --- |
| Blade UI Components | Collect user prompt and display recommendations/chatbot responses |
| API Controller | Validates request and calls recommendation services |
| Recommendation Service | Selects catalog context and formats recommendation response |
| AI Service Manager | Calls Gemini or Ollama and handles fallback logic |
| Queue Job | Allows AI work to be processed asynchronously when needed |
| AI Usage Logs | Tracks provider, model, success/failure, tokens, latency, and estimated cost |
| Audit Logs | Stores AI decision metadata without exposing full sensitive prompts |

## Fallback Strategy

Fallback is required so the feature remains usable when an AI service is unavailable.

Fallback order used for this activity:

1. Gemini API
2. Ollama local model
3. Catalog-based keyword recommendations

Example fallback situations:

- Gemini API key is missing or invalid
- Gemini API request times out
- Internet connection is unavailable
- Ollama is not running
- AI response is malformed or cannot be parsed

The final fallback returns safe recommendations such as Fiction, Programming, Best Sellers, or category-based suggestions.

## Cost Tracking and Operations

The system tracks AI operations using `AIUsageLog`.

Tracked fields include:

- User ID when available
- Feature name
- Provider used
- Model used
- Token estimate or token count
- Cost estimate
- Success/failure state
- Latency in milliseconds
- Error metadata when available

This supports the Activity 8 requirement for cost tracking, provider monitoring, and operational visibility.

## Queue Processing

AI tasks can be processed through Laravel queues using:

```powershell
php artisan queue:work
```

Queue processing is useful for longer-running AI jobs, background recommendations, and future admin analytics.

For screenshots, show:

- The queue worker running
- AI task or recommendation request being processed
- No failed jobs after processing

## Commands for Screenshots

Run migrations:

```powershell
php artisan migrate
```

Show AI routes:

```powershell
php artisan route:list | findstr "ai"
```

Run AI tests:

```powershell
php artisan test tests/Feature/AiRecommendationsTest.php
php artisan test tests/Feature/FloatingChatbotTest.php
```

Run queue worker:

```powershell
php artisan queue:work
```

Check Ollama:

```powershell
ollama list
ollama run llama3.2
```

## Screenshots and Recordings Checklist

Capture screenshots or a short recording showing:

- [ ] AI feature in action with a user asking for book recommendations
- [ ] AI response with recommended books and reasons
- [ ] Admin or database view showing AI usage/cost tracking records
- [ ] Fallback mechanism working by disabling Gemini or using Ollama
- [ ] Queue worker processing AI-related work
- [ ] Error handling or safe fallback response when AI provider is unavailable

## 5-Minute Presentation Guide

### 1. Problem and Motivation

Explain that users may not know exact titles or categories, so PageTurner needs natural language discovery.

### 2. Live Demonstration

Demonstrate:

- Opening the PageTurner chatbot or AI recommendation component
- Asking for a natural language recommendation
- Showing AI-generated book suggestions
- Showing fallback by using Ollama or disabling Gemini temporarily

### 3. Technical Architecture

Briefly explain:

- Blade UI component
- API controller
- Recommendation service
- Gemini/Ollama provider layer
- Fallback chain
- Usage logging and queue support

### 4. Challenges and Learnings

Possible points:

- AI responses need parsing and validation
- API keys must be protected in `.env`
- Cloud APIs can fail or cost money, so fallback is important
- Ollama is useful for local/offline demos but may be slower
- Cost tracking and audit logs are important for responsible AI use

### 5. Q&A

Be ready to answer:

- Why Gemini and Ollama were chosen
- What happens when the AI service fails
- How user input is protected
- How costs are monitored
- How the feature could be improved in the future

## Rubric Mapping

| Component | Weight | Evidence |
| --- | ---: | --- |
| Problem Identification and Creativity | 15% | Natural language book discovery problem and AI recommendation solution |
| Technical Implementation | 30% | Gemini API integration, Ollama fallback, chatbot/recommendation components |
| Code Quality and Best Practices | 15% | Service classes, config files, environment variables, separation of concerns |
| Resilience and Operations | 15% | Multi-provider fallback, queue job, usage logs, audit logging |
| Testing and Validation | 10% | Feature tests, fallback testing, route checks, error handling |
| Documentation and Presentation | 15% | README, setup guide, implementation guide, screenshots, 5-minute demo |

## Security and Responsible AI

- API keys are stored in `.env` and not committed to source code.
- User input is validated before being sent to AI services.
- AI output should be treated as suggestions, not guaranteed facts.
- Errors are sanitized so provider keys are not exposed in logs.
- AI audit logs store metadata such as prompt hash instead of exposing full sensitive prompt text.
- Fallback recommendations avoid unsafe or unsupported claims.

## Official Documentation Used

- OpenAI Platform Documentation
- Google AI Studio / Gemini API Documentation
- Laravel Queues Documentation
- Ollama GitHub Repository and Ollama local model documentation
- Hugging Face Inference API Documentation for possible future provider expansion

## Final Notes

The goal of this activity is to demonstrate an AI feature that is useful in a real e-commerce system. PageTurner uses AI to improve book discovery while still considering fallback behavior, cost tracking, queue processing, audit logs, and responsible AI practices.
