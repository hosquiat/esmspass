# AI Description Generation for URLs

## Overview
Automatically generate descriptions for records based on URLs using AI models. This is useful for password records where you want to automatically generate a description based on the website URL.

## Implementation Options

### Option 1: OpenAI GPT API (Recommended)
**Best for: Production use with high quality results**

#### Pros:
- High quality, natural language descriptions
- Easy to implement with official SDK
- Reliable and well-documented
- Good rate limits for production use

#### Cons:
- Costs money ($0.03 per 1K tokens for GPT-4o-mini)
- Requires API key
- Depends on external service

#### Implementation:

**1. Install Package:**
```bash
composer require openai-php/client
```

**2. Add to `.env`:**
```env
OPENAI_API_KEY=sk-your-api-key-here
```

**3. Create Service Class:**
```php
<?php
// app/Services/AIDescriptionService.php

namespace App\Services;

use OpenAI;

class AIDescriptionService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function generateDescriptionFromUrl(string $url): ?string
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4o-mini', // Fast and cheap
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a helpful assistant that generates concise, professional descriptions for password manager entries based on URLs. Keep descriptions under 100 characters.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Generate a brief description for this URL: {$url}"
                    ]
                ],
                'max_tokens' => 50,
                'temperature' => 0.7,
            ]);

            return $response->choices[0]->message->content ?? null;
        } catch (\Exception $e) {
            \Log::error('AI description generation failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
```

**4. Add to `config/services.php`:**
```php
'openai' => [
    'api_key' => env('OPENAI_API_KEY'),
],
```

**5. Add API Endpoint in RecordController:**
```php
use App\Services\AIDescriptionService;

public function generateDescription(Request $request, AIDescriptionService $aiService): JsonResponse
{
    $request->validate([
        'url' => 'required|url'
    ]);

    $description = $aiService->generateDescriptionFromUrl($request->url);

    return response()->json([
        'description' => $description
    ]);
}
```

**6. Add Route:**
```php
Route::post('/records/generate-description', [RecordController::class, 'generateDescription'])
    ->name('api.records.generate-description');
```

**7. Frontend Integration (RecordForm.vue):**
```vue
<template>
  <!-- In the password type section -->
  <div v-if="record.type === 'password'">
    <label>URL</label>
    <div class="flex gap-2">
      <input v-model="record.data.url" type="url" class="flex-1">
      <button
        type="button"
        @click="generateDescription"
        :disabled="!record.data.url || generatingDescription"
        class="px-3 py-2 bg-indigo-600 text-white rounded disabled:opacity-50"
      >
        {{ generatingDescription ? 'Generating...' : 'AI Generate' }}
      </button>
    </div>
  </div>
</template>

<script setup>
const generatingDescription = ref(false);

const generateDescription = async () => {
  if (!record.value.data.url) return;

  generatingDescription.value = true;
  try {
    const response = await fetch('/api/records/generate-description', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
      },
      credentials: 'same-origin',
      body: JSON.stringify({ url: record.value.data.url })
    });

    if (!response.ok) throw new Error('Failed to generate description');

    const data = await response.json();
    if (data.description) {
      record.value.description = data.description;
    }
  } catch (error) {
    console.error('Failed to generate description:', error);
    alert('Failed to generate description. Please try again.');
  } finally {
    generatingDescription.value = false;
  }
};
</script>
```

**Cost Estimate:**
- ~$0.001 per description
- 1000 descriptions = ~$1.00

---

### Option 2: Claude API (Anthropic)
**Best for: High quality with better reasoning**

Very similar to OpenAI but uses Anthropic's Claude models.

#### Pros:
- Excellent quality reasoning
- Good for complex descriptions
- Similar pricing to OpenAI

#### Cons:
- Similar costs to OpenAI
- Requires separate API key

#### Implementation:
```bash
composer require anthropic-php/client
```

Similar implementation to OpenAI, just replace the client.

---

### Option 3: Local LLM with Ollama (Free)
**Best for: Privacy, no costs, offline use**

#### Pros:
- Completely free
- No external dependencies
- Privacy - all data stays local
- No rate limits

#### Cons:
- Requires running Ollama locally
- Slower than cloud APIs
- Lower quality than GPT-4
- Requires server resources (RAM/GPU)

#### Implementation:

**1. Install Ollama:**
```bash
curl -fsSL https://ollama.com/install.sh | sh
ollama pull llama3.2:3b  # Small, fast model
```

**2. Create Service:**
```php
<?php

namespace App\Services;

class OllamaDescriptionService
{
    public function generateDescriptionFromUrl(string $url): ?string
    {
        try {
            $response = Http::post('http://localhost:11434/api/generate', [
                'model' => 'llama3.2:3b',
                'prompt' => "Generate a brief, professional description (under 100 characters) for a password manager entry for this URL: {$url}. Only respond with the description, nothing else.",
                'stream' => false,
            ]);

            return trim($response->json('response'));
        } catch (\Exception $e) {
            \Log::error('Ollama description generation failed', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
```

---

### Option 4: Simple Rule-Based (No AI)
**Best for: Quick implementation, no costs**

#### Pros:
- Free
- Instant
- No dependencies
- Predictable

#### Cons:
- Not as natural
- Less flexible

#### Implementation:
```php
public function generateDescriptionFromUrl(string $url): string
{
    $parsed = parse_url($url);
    $domain = $parsed['host'] ?? '';

    // Remove www. and get domain name
    $domain = preg_replace('/^www\\./', '', $domain);
    $parts = explode('.', $domain);
    $name = $parts[0] ?? 'Website';

    // Capitalize and make it readable
    $name = ucwords(str_replace(['-', '_'], ' ', $name));

    // Add context based on domain
    $descriptions = [
        'github.com' => 'GitHub Account',
        'gmail.com' => 'Gmail Account',
        'outlook.com' => 'Outlook Email',
        'aws.amazon.com' => 'AWS Console',
    ];

    return $descriptions[$domain] ?? "{$name} Login";
}
```

---

## Recommendation

**For Production:** Use **OpenAI GPT-4o-mini** (Option 1)
- Best quality/cost ratio
- Reliable and fast
- Easy to implement
- ~$1 per 1000 descriptions

**For Development/Testing:** Use **Rule-Based** (Option 4)
- Free and instant
- Good for testing UI/UX
- Can switch to AI later

**For Privacy-Focused:** Use **Ollama** (Option 3)
- If you have the server resources
- All data stays on your server
- Free

---

## Next Steps

1. Choose implementation option
2. Add service class
3. Add API endpoint
4. Update RecordForm.vue
5. Add "AI Generate" button next to description field
6. Test with various URLs

Would you like me to implement any of these options?
