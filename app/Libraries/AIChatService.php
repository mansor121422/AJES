<?php

namespace App\Libraries;

use CodeIgniter\HTTP\CURLRequest;
use Config\AIChat;

/**
 * AI Chat Service for handling AI-powered chat responses using Groq API
 */
class AIChatService
{
    protected AIChat $config;
    protected $client;

    public function __construct()
    {
        $this->config = config('AIChat');
        $this->client = \Config\Services::curlrequest([
            'timeout' => $this->config->timeout,
            'verify' => false,  // Disable SSL verification for local development
        ]);
    }

    /**
     * Generate an AI response for a chat message
     *
     * @param string $userMessage The user's message
     * @param array $context Additional context (sender name, role, etc.)
     * @return string The AI-generated response
     */
    public function generateResponse(string $userMessage, array $context = []): string
    {
        if (!$this->config->isConfigured()) {
            return $this->getFallbackResponse();
        }

        try {
            $response = $this->callGroqAPI($userMessage, $context);
            
            if (isset($response['choices'][0]['message']['content'])) {
                $aiResponse = trim($response['choices'][0]['message']['content']);
                
                // Truncate if too long
                if (strlen($aiResponse) > $this->config->maxResponseLength) {
                    $aiResponse = substr($aiResponse, 0, $this->config->maxResponseLength - 3) . '...';
                }
                
                return $aiResponse;
            }
            
            log_message('error', 'AI Chat: Unexpected API response format', ['response' => $response]);
            return $this->getFallbackResponse();
            
        } catch (\Exception $e) {
            log_message('error', 'AI Chat: API call failed', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage
            ]);
            return $this->getFallbackResponse();
        }
    }

    /**
     * Call Groq API to generate a response
     *
     * @param string $userMessage The user's message
     * @param array $context Additional context
     * @return array The API response
     */
    protected function callGroqAPI(string $userMessage, array $context = []): array
    {
        $body = json_encode([
            'model' => $this->config->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->buildSystemPrompt($context)
                ],
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ],
            'temperature' => $this->config->temperature,
            'max_tokens' => 300,
            'top_p' => 1,
            'stream' => false,
        ]);

        log_message('info', 'AI Chat: Sending request to Groq API', [
            'model' => $this->config->model,
            'api_url' => $this->config->apiUrl,
            'message_length' => strlen($userMessage)
        ]);

        // Use native cURL for better control
        $ch = curl_init($this->config->apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->config->apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->config->timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // Disable SSL verification for local development
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);

        if ($errno !== 0) {
            log_message('error', 'AI Chat: cURL error', [
                'errno' => $errno,
                'error' => $error
            ]);
            throw new \Exception('cURL error: ' . $error);
        }

        log_message('info', 'AI Chat: Groq API response', [
            'http_code' => $httpCode,
            'response_length' => strlen($response ?? '')
        ]);

        if ($httpCode !== 200) {
            log_message('error', 'AI Chat: Groq API returned error', [
                'status_code' => $httpCode,
                'response' => $response
            ]);
            throw new \Exception('Groq API error: ' . $httpCode . ' - ' . ($response ?? 'Unknown error'));
        }

        return json_decode($response, true);
    }

    /**
     * Build the system prompt with context
     *
     * @param array $context Additional context
     * @return string The system prompt
     */
    protected function buildSystemPrompt(array $context = []): string
    {
        $prompt = $this->config->systemPrompt;
        
        // Add context-specific information
        if (isset($context['student_name'])) {
            $prompt .= "\n\nCurrent student name: " . $context['student_name'];
        }
        
        // Add current date/time context
        $prompt .= "\n\nCurrent date: " . date('F j, Y');
        
        return $prompt;
    }

    /**
     * Get a fallback response when AI is unavailable
     *
     * @return string Fallback response
     */
    protected function getFallbackResponse(): string
    {
        $fallbacks = [
            "Thank you for your message. The principal will review your inquiry and respond as soon as possible.",
            "Your message has been received. Please allow some time for the principal to respond to your question.",
            "We appreciate you reaching out. The principal will get back to you regarding your inquiry.",
            "Thank you for contacting us. Your message is important and will be answered shortly."
        ];
        
        return $fallbacks[array_rand($fallbacks)];
    }

    /**
     * Check if the AI service is properly configured
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->config->isConfigured();
    }

    /**
     * Get configuration
     *
     * @return AIChat
     */
    public function getConfig(): AIChat
    {
        return $this->config;
    }
}