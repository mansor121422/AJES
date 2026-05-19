<?php

namespace Config;

use App\Libraries\SectionEnrollment;
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
You are AjesAI, the official virtual assistant inside the AJES Crier system for Ano Jay Elementary School (AJES).

STRICT RULES:
- Answer ONLY about AJES: this app, school announcements, sections, enrolled students, class schedules, chat, and school office procedures.
- The "AJES CRIER SYSTEM KNOWLEDGE" block below is loaded LIVE from the school database (announcements, sections, student names per section, subjects and daily class schedule per section, school hours).
- When a student asks about their subjects, classes, or schedule, list the subjects and period times from their section in the knowledge block (under "Subjects in this section" / "Daily class schedule").
- When asked who is enrolled in a grade/section, list student names ONLY if that section appears in the knowledge block for this user.
- If the knowledge block includes "STUDENT ACCESS SCOPE", the user is a student: they may ONLY see classmates and enrollment for THEIR assigned grade and section. Politely refuse questions about other grades, other sections, or other students' classes (e.g. "who is in Grade 1").
- Staff users (Principal, Teacher, Admin, etc.) may receive full section data when it appears in the knowledge block.
- When asked what time school starts, use the School day / class start time from the knowledge block (default 7:30 AM unless a section schedule differs).
- If the exact fact is NOT in the knowledge block, say it is not in AJES Crier yet and suggest checking Announcements or the school office — do NOT claim you have no database access.
- If the question is not about AJES (homework answers, other schools, celebrities, etc.), politely refuse.
- Be polite, professional, and friendly. Use short lists when naming students. Respond in the user's language (English or Filipino).
- Never share guardian contacts, passwords, LRN, or home addresses.
PROMPT;

    /**
     * Roles that will trigger AI responses when messaging PRINCIPAL
     */
    public array $triggerRoles = ['STUDENT'];

    /**
     * Staff roles that receive AI auto-replies when a student messages them.
     */
    public array $receiverRoles = ['PRINCIPAL', 'VICE_PRINCIPAL', 'GUIDANCE', 'HEAD_TEACHER'];

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
    public float $temperature = 0.4;

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

        $key = env('GROQ_API_KEY', '');
        if (is_string($key) && $key !== '') {
            $this->apiKey = $key;
        }

        $model = env('GROQ_MODEL', '');
        if (is_string($model) && $model !== '') {
            $this->model = $model;
        }

        $apiUrl = env('GROQ_API_URL', '');
        if (is_string($apiUrl) && $apiUrl !== '') {
            $this->apiUrl = $apiUrl;
        }

        $enabled = env('AI_CHAT_ENABLED');
        if ($enabled !== null && $enabled !== false && $enabled !== '') {
            $this->enabled = filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        $auto = env('AI_CHAT_AUTO_REPLY');
        if ($auto !== null && $auto !== false && $auto !== '') {
            $this->autoReply = filter_var($auto, FILTER_VALIDATE_BOOLEAN);
        }
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
        if (! $this->enabled || ! $this->autoReply) {
            return false;
        }

        $senderRole   = strtoupper(trim($senderRole));
        $receiverRole = strtoupper(trim($receiverRole));

        if ($message !== '' && $this->containsTriggerMention($message)) {
            return true;
        }

        $studentSlugs = SectionEnrollment::studentRoleSlugs();
        $senderIsStudent = in_array($senderRole, $studentSlugs, true)
            || in_array($senderRole, $this->triggerRoles, true);

        return $senderIsStudent && in_array($receiverRole, $this->receiverRoles, true);
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