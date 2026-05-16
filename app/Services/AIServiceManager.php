<?php

namespace App\Services;

use App\Models\AIUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AIServiceManager
{
    public function generateWithFallback(string $prompt, array $options = []): array
    {
        $providers = config('ai.fallback_enabled', true)
            ? config('ai.fallback_chain', ['openai', 'gemini', 'ollama'])
            : [config('ai.default', 'openai')];

        $errors = [];

        foreach ($providers as $provider) {
            $started = microtime(true);

            try {
                $result = $this->callProvider($provider, $prompt, $options);
                $this->logUsage($provider, $result, true, $started, $options);
                $this->auditDecision($provider, $prompt, $result, $options);

                return array_merge($result, [
                    'provider' => $provider,
                    'fallback' => $provider !== config('ai.default', 'openai'),
                ]);
            } catch (\Throwable $e) {
                $error = $this->sanitizeError($e->getMessage());
                $errors[$provider] = $error;
                $this->logUsage($provider, ['tokens_used' => 0, 'model' => null], false, $started, $options, $error);

                Log::warning("{$provider} failed", [
                    'feature' => $options['feature'] ?? 'recommendation',
                    'error' => $error,
                ]);
            }
        }

        throw new RuntimeException('All AI providers failed: ' . json_encode($errors));
    }

    protected function callProvider(string $provider, string $prompt, array $options = []): array
    {
        return match ($provider) {
            'openai' => $this->callOpenAI($prompt),
            'gemini' => $this->callGemini($prompt),
            'ollama' => $this->callOllama($prompt),
            default => throw new RuntimeException("Unsupported AI provider: {$provider}"),
        };
    }

    protected function callOpenAI(string $prompt): array
    {
        $config = config('ai.providers.openai');
        if (empty($config['api_key'])) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $response = Http::timeout($this->timeoutFor('openai'))
            ->withToken($config['api_key'])
            ->post(rtrim($config['base_url'], '/') . '/chat/completions', [
                'model' => $config['model'],
                'messages' => [
                    ['role' => 'system', 'content' => 'You are PageTurner AI, a concise book recommendation assistant. Return valid JSON only.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.5,
                'response_format' => ['type' => 'json_object'],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('OpenAI request failed with status ' . $response->status());
        }

        $body = $response->json();

        return [
            'text' => $body['choices'][0]['message']['content'] ?? '{}',
            'tokens_used' => (int) ($body['usage']['total_tokens'] ?? $this->estimateTokens($prompt)),
            'model' => $config['model'],
        ];
    }

    protected function callGemini(string $prompt): array
    {
        $config = config('ai.providers.gemini');
        if (empty($config['api_key'])) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $url = rtrim($config['base_url'], '/') . '/models/' . $config['model'] . ':generateContent';
        $response = Http::timeout($this->timeoutFor('gemini'))
            ->post($url . '?key=' . $config['api_key'], [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.5,
                    'maxOutputTokens' => 1024,
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Gemini request failed with status ' . $response->status());
        }

        $body = $response->json();

        return [
            'text' => $body['candidates'][0]['content']['parts'][0]['text'] ?? '{}',
            'tokens_used' => (int) ($body['usageMetadata']['totalTokenCount'] ?? $this->estimateTokens($prompt)),
            'model' => $config['model'],
        ];
    }

    protected function callOllama(string $prompt): array
    {
        $config = config('ai.providers.ollama');
        if (!($config['enabled'] ?? false)) {
            throw new RuntimeException('Ollama is disabled.');
        }

        $response = Http::timeout($this->timeoutFor('ollama'))
            ->post(rtrim($config['base_url'], '/') . '/api/generate', [
                'model' => $config['model'],
                'prompt' => $prompt,
                'stream' => false,
                'format' => 'json',
            ]);

        if (!$response->successful()) {
            throw new RuntimeException('Ollama request failed with status ' . $response->status());
        }

        $body = $response->json();

        return [
            'text' => $body['response'] ?? '{}',
            'tokens_used' => (int) ($body['eval_count'] ?? $this->estimateTokens($prompt)),
            'model' => $config['model'],
        ];
    }

    protected function logUsage(string $provider, array $result, bool $success, float $started, array $options, ?string $error = null): void
    {
        AIUsageLog::create([
            'user_id' => $options['user_id'] ?? null,
            'feature' => $options['feature'] ?? 'recommendation',
            'provider' => $provider,
            'model' => $result['model'] ?? null,
            'tokens_used' => $result['tokens_used'] ?? 0,
            'cost_estimate' => 0,
            'success' => $success,
            'latency_ms' => (int) round((microtime(true) - $started) * 1000),
            'metadata' => array_filter([
                'error' => $error,
                'source' => $options['source'] ?? 'chatbot',
            ]),
        ]);
    }

    protected function auditDecision(string $provider, string $prompt, array $result, array $options): void
    {
        Log::channel('ai_audit')->info('AI Decision', [
            'feature' => $options['feature'] ?? 'recommendation',
            'provider' => $provider,
            'user_id' => $options['user_id'] ?? null,
            'prompt_hash' => hash('sha256', $prompt),
            'tokens_used' => $result['tokens_used'] ?? 0,
        ]);
    }

    protected function estimateTokens(string $prompt): int
    {
        return max(1, (int) ceil(str_word_count($prompt) * 1.3));
    }

    protected function timeoutFor(string $provider): int
    {
        return (int) config("ai.providers.{$provider}.timeout_seconds", config('ai.timeout_seconds', 5));
    }

    protected function sanitizeError(string $message): string
    {
        $message = preg_replace('/([?&]key=)[^&\s]+/i', '$1[redacted]', $message);
        $message = preg_replace('/(Bearer\s+)[A-Za-z0-9._\-]+/i', '$1[redacted]', $message);

        foreach (['OPENAI_API_KEY', 'GEMINI_API_KEY', 'HF_API_KEY'] as $key) {
            $value = env($key);
            if ($value) {
                $message = str_replace($value, '[redacted]', $message);
            }
        }

        return $message;
    }
}
