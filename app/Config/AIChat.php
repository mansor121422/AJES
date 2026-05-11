<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class AIChat extends BaseConfig
{
    /**
     * Enable or disable AI chatbot responses
     */
    public bool $enabled = true;

    /**
     * Enable automatic replies
     */
    public bool $autoReply = true;

    /**
     * Groq API Key
     */
    public string $apiKey = '';

    /**
     * Groq API Model
     */
    public string $model = 'llama-3.3-70b-versatile';

    /**
     * Groq API URL
     */
    public string $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * Timeout for API requests (in seconds)
     */
    public int $timeout = 30;

    /**
     * AI Assistant Name
     */
    public string $aiName = 'AjesAI';

    /**
     * System prompt for the AI assistant
     */
    public string $systemPrompt = <<<PROMPT
You are AjesAI, an AI assistant for AJES school. Your role is to help answer student inquiries about school policies, procedures, and general information.

Guidelines:
- Be polite, professional, and friendly
- Keep responses concise (2-4 sentences)
- If you don't know something, suggest speaking with the principal directly
- Always maintain a helpful and supportive tone
- Respond in the same language as the student's message
- Do not provide personal information or make promises on behalf of the school
- For urgent matters, advise the student to contact the school office directly

You are assisting students with questions about:
- School policies and procedures
- Academic programs and requirements
- Student services and activities
- General school information
- Administrative processes
PROMPT;

    /**
     * Roles that will trigger AI responses when messaging PRINCIPAL
     */
    public array $triggerRoles = ['STUDENT'];

    /**
     * Roles that will receive AI auto-replies (the receiver role)
     */
    public array $receiverRoles = ['PRINCIPAL'];

    /**
     * Trigger mentions that will activate AI response (case-insensitive)
     * The AI will respond when the message contains these mentions
     */
    public array $triggerMentions = ['@ajesai', '@ajes'];

    /**
     * Maximum length for AI responses (in characters)
     */
    public int $maxResponseLength = 500;

    /**
     * Temperature for AI responses (0.0 to 1.0)
     * Lower = more focused, Higher = more creative
     */
    public float $temperature = 0.7;

    /**
     * Whether to show AI indicator in chat UI
     */
    public bool $showAIIndicator = true;

    /**
     * AI indicator text
     */
    public string $aiIndicatorText = '🤖 AI Assistant';

    public function __construct()
    {
        parent::__construct();

        // Load from environment variables
        $this->apiKey = $_ENV['GROQ_API_KEY'] ?? '';
        $this->model = $_ENV['GROQ_MODEL'] ?? $this->model;
        $this->apiUrl = $_ENV['GROQ_API_URL'] ?? $this->apiUrl;
        $this->enabled = filter_var($_ENV['AI_CHAT_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        $this->autoReply = filter_var($_ENV['AI_CHAT_AUTO_REPLY'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Check if AI chat is enabled and configured
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->apiKey);
    }

    /**
     * Check if a message should trigger AI response
     */
    public function shouldTriggerResponse(string $senderRole, string $receiverRole, string $message = ''): bool
    {
        if (!$this->enabled || !$this->autoReply) {
            return false;
        }

        // Check if message contains trigger mentions
        if (!empty($message) && $this->containsTriggerMention($message)) {
            return true;
        }

        // Check role-based trigger (student to principal)
        return in_array($senderRole, $this->triggerRoles, true)
            && in_array($receiverRole, $this->receiverRoles, true);
    }

    /**
     * Check if a message contains a trigger mention
     */
    public function containsTriggerMention(string $message): bool
    {
        $lowerMessage = strtolower($message);
        foreach ($this->triggerMentions as $mention) {
            if (strpos($lowerMessage, strtolower($mention)) !== false) {
                return true;
            }
        }
        return false;
    }
}