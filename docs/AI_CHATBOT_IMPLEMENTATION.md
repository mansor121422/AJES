# AI Chatbot Implementation Guide

## Overview

This document describes the AI-powered chatbot feature implemented for the AJES school system. When a student sends a message to the principal, the system automatically generates an AI-powered response using the Groq API.

## Features

✅ **Automatic AI Responses**: Students receive instant AI-generated replies when messaging the principal
✅ **Groq API Integration**: Uses the fast, free-tier Groq API with Llama 3.1 model
✅ **Visual Indicators**: AI messages are clearly marked with a purple badge and distinct styling
✅ **Fallback Responses**: Graceful fallback when AI is unavailable
✅ **Configurable**: Easy to enable/disable and customize behavior
✅ **Error Handling**: Robust error handling and logging

## Architecture

```
Student Message → Chat Controller → AI Service → Groq API → Auto-Reply Message
```

## Files Modified/Created

### Configuration Files
- `.env` - Environment variables for API keys and settings
- `app/Config/AIChat.php` - AI chatbot configuration

### Core Implementation
- `app/Libraries/AIChatService.php` - AI service library for Groq API communication
- `app/Controllers/Chat.php` - Modified to trigger AI responses
- `app/Views/Chat/index.php` - Updated UI to display AI messages

## Setup Instructions

### 1. Environment Configuration

The `.env` file has been configured with your Groq API key:

```env
# Groq API Configuration
GROQ_API_KEY = 
GROQ_MODEL = llama-3.3-70b-versatile
GROQ_API_URL = https://api.groq.com/openai/v1/chat/completions

# AI Chat Settings
AI_CHAT_ENABLED = true
AI_CHAT_AUTO_REPLY = true
```

### 2. Database Setup

The `messages` table already includes the `is_bot` field to identify AI-generated messages. No additional migrations are needed.

### 3. Testing the Feature

1. **Login as a Student**
2. **Navigate to Chat** (`/chat`)
3. **Select the Principal** from the chat list
4. **Send a message** (e.g., "What are the school hours?")
5. **Wait 2-5 seconds** for the AI to generate a response
6. **Observe the AI reply** with the purple "🤖 AI Assistant" badge

## How It Works

### Message Flow

1. **Student sends message** to principal via chat interface
2. **Chat controller** saves the student's message
3. **Controller checks** if AI response should be triggered:
   - Sender role = STUDENT
   - Receiver role = PRINCIPAL
   - AI chat is enabled
4. **AI Service** generates response using Groq API
5. **AI response** is saved as a message from principal with `is_bot = 1`
6. **Notification** is created for the student
7. **Chat interface** displays AI message with special styling

### AI Response Generation

The AI uses the following system prompt:

```
You are an AI assistant for AJES school, responding on behalf of the principal...
```

The prompt includes:
- School context and role
- Response guidelines (polite, professional, concise)
- Topics the AI can help with
- Instructions for handling unknown questions
- Current date context

### Configuration Options

In `app/Config/AIChat.php`, you can customize:

```php
// Enable/disable AI responses
public bool $enabled = true;

// Enable automatic replies
public bool $autoReply = true;

// Which roles trigger AI responses
public array $triggerRoles = ['STUDENT'];

// Which receiver roles get AI auto-replies
public array $receiverRoles = ['PRINCIPAL'];

// AI model to use
public string $model = 'llama-3.1-70b-versatile';

// Maximum response length
public int $maxResponseLength = 500;

// Temperature (0.0 = focused, 1.0 = creative)
public float $temperature = 0.7;
```

## Visual Design

### AI Message Styling

- **Background**: Light purple (#f3e5f5)
- **Border**: Purple (#ce93d8)
- **Badge**: Purple with white text "🤖 AI Assistant"
- **Distinct from**: Regular messages (white) and user's own messages (green)

### Message Display

AI messages show:
- Message content
- Timestamp
- Purple "🤖 AI Assistant" badge
- No unsend options (bot messages cannot be unsent)

## Error Handling

### Fallback Responses

If the AI service fails, the system uses predefined fallback messages:

- "Thank you for your message. The principal will review your inquiry and respond as soon as possible."
- "Your message has been received. Please allow some time for the principal to respond to your question."
- "We appreciate you reaching out. The principal will get back to you regarding your inquiry."

### Logging

All AI interactions are logged:
- Successful AI responses: `info` level
- API errors: `error` level
- Configuration issues: `error` level

Check `writable/logs/` for detailed logs.

## API Usage & Limits

### Groq API Free Tier

- **Rate Limit**: ~30 requests per minute
- **Model**: Llama 3.1 70B Versatile
- **Speed**: Very fast (optimized for inference)
- **Cost**: Free (within limits)

### Monitoring Usage

Monitor your Groq API usage at: https://console.groq.com/usage

## Customization

### Adding More AI Triggers

To enable AI responses for other role combinations, modify `app/Config/AIChat.php`:

```php
public array $triggerRoles = ['STUDENT', 'TEACHER'];  // Add TEACHER
public array $receiverRoles = ['PRINCIPAL', 'GUIDANCE'];  // Add GUIDANCE
```

### Customizing AI Behavior

Edit the system prompt in `app/Config/AIChat.php`:

```php
public string $systemPrompt = 'Your custom prompt here...';
```

### Changing AI Model

Update the model in `.env` or `app/Config/AIChat.php`:

```env
GROQ_MODEL = llama-3.3-8b-instant  # Faster, smaller model
```

Available Groq models:
- `llama-3.3-70b-versatile` (default, powerful)
- `llama-3.3-8b-instant` (faster, lighter)
- `mixtral-8x7b-32768` (alternative)

## Troubleshooting

### AI Responses Not Working

1. **Check if AI is enabled**:
   - Verify `AI_CHAT_ENABLED = true` in `.env`
   - Check `app/Config/AIChat.php` has `$enabled = true`

2. **Verify API Key**:
   - Ensure Groq API key is correct in `.env`
   - Test API key at https://console.groq.com

3. **Check Logs**:
   - Look in `writable/logs/` for errors
   - Common issues: API key invalid, rate limit exceeded

4. **Test API Connection**:
   ```bash
   curl -H "Authorization: Bearer YOUR_API_KEY" \
        -H "Content-Type: application/json" \
        -d '{"model":"llama-3.1-70b-versatile","messages":[{"role":"user","content":"test"}]}' \
        https://api.groq.com/openai/v1/chat/completions
   ```

### Slow Responses

- Groq API is typically very fast (< 1 second)
- If slow, check network connectivity
- Consider using `llama-3.1-8b-instant` model for faster responses

### Rate Limit Exceeded

- Free tier allows ~30 requests/minute
- If exceeded, wait a minute before trying again
- Monitor usage at Groq console
- Consider upgrading Groq plan if needed

## Security Considerations

✅ **API Key Security**: Key stored in `.env` (not in code)
✅ **Input Sanitization**: All user input is sanitized before sending to AI
✅ **No Sensitive Data**: AI doesn't receive personal information
✅ **Rate Limiting**: Built-in protection against abuse
✅ **Error Handling**: Graceful degradation on failures

## Future Enhancements

Potential improvements:
- [ ] Add conversation context (remember previous messages)
- [ ] Support multiple AI providers (OpenAI, Google AI, etc.)
- [ ] Add admin panel for AI configuration
- [ ] Implement AI response templates
- [ ] Add sentiment analysis for urgent messages
- [ ] Create AI usage statistics dashboard
- [ ] Support file attachment analysis
- [ ] Multi-language support

## Support

For issues or questions:
1. Check this documentation
2. Review logs in `writable/logs/`
3. Test Groq API connectivity
4. Verify configuration settings

## Credits

- **Groq API**: Fast AI inference platform
- **Llama 3.1**: Open-source language model by Meta
- **CodeIgniter 4**: PHP framework used for implementation

---

**Last Updated**: 2026-04-02
**Version**: 1.0.0