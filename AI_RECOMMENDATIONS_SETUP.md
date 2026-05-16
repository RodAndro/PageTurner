# AI Book Recommendations Setup Guide

This guide explains how to set up and use the AI-powered book recommendations feature in the PageTurner Online Bookstore.

## Overview

The AI recommendations feature uses Google's Gemini API to provide personalized book recommendations based on user queries. The system analyzes available books in the store and provides intelligent suggestions tailored to customer preferences.

## Features

- **Natural Language Queries**: Users can ask for books in natural language (e.g., "I want fantasy books with strong female protagonists")
- **Category Filtering**: Optional filtering by book categories
- **Fallback System**: Graceful degradation when AI service is unavailable
- **Rate Limiting**: Built-in rate limiting to prevent abuse
- **Responsive UI**: Modern, user-friendly interface with loading states

## Setup Instructions

### 1. Configure API Key

Add your Gemini API key to your `.env` file:

```bash
# Gemini AI API
GEMINI_API_KEY=AIzaSyD83_8twPIGXXYjKuBJs7iMdJ8lanXpjCg
```

### 2. Clear Route Cache

After adding the AI routes, clear the route cache:

```bash
php artisan route:clear
```

### 3. Verify API Routes

Check that the AI recommendations endpoint is registered:

```bash
php artisan route:list | findstr "ai"
```

You should see:
```
GET|HEAD  api/v1/ai/recommendations
```

## API Usage

### Endpoint
```
GET /api/v1/ai/recommendations
```

### Parameters
- `query` (required, string, max 500 chars): Natural language query for book recommendations
- `category_id` (optional, integer): Filter by specific category

### Example Requests

```bash
# Basic recommendation
curl "http://localhost/api/v1/ai/recommendations?query=science%20fiction%20books"

# With category filter
curl "http://localhost/api/v1/ai/recommendations?query=adventure%20stories&category_id=1"
```

### Response Format

```json
{
  "recommendations": [
    {
      "title": "Book Title",
      "reason": "Brief reason why this book matches their request",
      "category": "Book category",
      "price_range": "price information",
      "description": "Brief description without spoilers"
    }
  ],
  "message": "A friendly message to the user",
  "fallback": false
}
```

## Frontend Integration

The AI recommendations component is automatically included on the welcome page (`/`). Users can:

1. Click "Get Recommendations" to open the query form
2. Enter a natural language description of what they're looking for
3. Optionally select a category filter
4. Receive AI-powered recommendations

The component includes:
- Loading states with spinner
- Error handling and fallback messages
- Responsive design for mobile and desktop
- Smooth animations and transitions

## Service Class

The `GeminiAIService` class handles:

- API communication with Google Gemini
- Prompt engineering for book recommendations
- Error handling and fallback logic
- Response parsing and validation

### Key Methods

- `getBookRecommendations($query, $availableBooks)`: Main recommendation method
- `isAvailable()`: Check if API key is configured
- `getFallbackRecommendations($query)`: Fallback when AI is unavailable

## Error Handling

The system includes comprehensive error handling:

1. **API Key Missing**: Returns 503 with user-friendly message
2. **API Errors**: Logs errors and provides fallback recommendations
3. **Network Issues**: Graceful degradation with retry suggestions
4. **Invalid Input**: Validation errors with helpful messages

## Rate Limiting

The AI recommendations endpoint is protected by rate limiting middleware to prevent abuse and manage API costs.

## Testing

The feature includes test cases in `tests/Feature/AiRecommendationsTest.php`. To run tests:

```bash
php artisan test tests/Feature/AiRecommendationsTest.php
```

## Security Considerations

- API key is stored in environment variables (not in code)
- Input validation prevents injection attacks
- Rate limiting prevents abuse
- Error messages don't expose sensitive information

## Performance Optimization

- Book data is limited to 50 most relevant books for context
- Response caching can be implemented if needed
- Lazy loading of the AI component on frontend

## Troubleshooting

### Common Issues

1. **Route not found**: Run `php artisan route:clear`
2. **API key errors**: Verify `.env` configuration
3. **Middleware issues**: Check that rate limiting middleware is registered
4. **Database errors**: Ensure categories and books tables exist

### Debug Mode

Enable debug mode in `.env` to see detailed error messages:

```bash
APP_DEBUG=true
```

## Future Enhancements

Potential improvements to consider:

1. **User Personalization**: Store user preferences and reading history
2. **Collaborative Filtering**: Recommend based on similar users' choices
3. **Trending Books**: Highlight popular and trending recommendations
4. **Multi-language Support**: Support recommendations in different languages
5. **Book Details Integration**: Direct links to recommended books

## Support

For issues with the AI recommendations feature:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify API key validity in Google Cloud Console
3. Test the API endpoint directly using curl
4. Check network connectivity to Google's API endpoints
