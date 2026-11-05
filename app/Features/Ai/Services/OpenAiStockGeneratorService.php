<?php

namespace App\Features\Ai\Services;

use App\Features\Ai\Contracts\AiClientInterface;
use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Stock\Enums\StockStatus;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiStockGeneratorService implements AiClientInterface
{
    protected string $endpoint;
    protected string $apiKey;
    protected string $model;

    public function __construct(
        ?string $endpoint = null,
        ?string $apiKey = null,
        ?string $model = null,
    ) {
        $resolvedEndpoint = (string) ($endpoint ?? config('openai.endpoint', 'https://api.openai.com/v1'));
        $resolvedKey = (string) ($apiKey ?? config('openai.key', ''));

        // Toleransi jika nilai OPENAI_ENDPOINT dan OPENAI_KEY tertukar di .env
        if ($this->hasScheme($resolvedKey) && !$this->hasScheme($resolvedEndpoint)) {
            [$resolvedEndpoint, $resolvedKey] = [$resolvedKey, $resolvedEndpoint];
        }

        // Pastikan scheme http:// atau https:// selalu ada
        if ($resolvedEndpoint !== '' && !$this->hasScheme($resolvedEndpoint)) {
            $resolvedEndpoint = 'https://'.$resolvedEndpoint;
        }

        $this->endpoint = rtrim($resolvedEndpoint, '/');
        $this->apiKey = $resolvedKey;
        $this->model = $model ?? (string) config('openai.model', 'gpt-4o-mini');
    }

    /**
     * Generate multiple structured stock items based on a user prompt.
     *
     * @return array<int, GeneratedStockItem>
     *
     * @throws RuntimeException
     */
    public function generateStocks(string $prompt, int $count = 3): array
    {
        $prompt = trim($prompt);
        if ($prompt === '') {
            throw new RuntimeException('Prompt tidak boleh kosong.');
        }

        $count = max(1, $count);

        $systemPrompt = <<<SYS
You are an expert inventory and warehouse management assistant. Your task is to generate exactly {$count} realistic inventory (stock) items based on the user's request.
Use realistic Indonesian market prices in IDR when the request does not specify a currency.

You MUST respond strictly with a valid JSON object matching this schema:
{
  "items": [
    {
      "name": "Clear concise product name (max 100 chars)",
      "sku": "Short uppercase SKU code such as ATK-PEN-001, or null",
      "description": "Brief helpful product description (markdown allowed)",
      "quantity": 0,
      "unit": "pcs | box | kg | liter | pack | rim | etc",
      "price": 0,
      "min_stock": 5
    }
  ]
}

quantity, price and min_stock must be non-negative numbers.
Do not include any conversational text, introductory words, or explanations outside the JSON.
SYS;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(30)->post("{$this->endpoint}/chat/completions", [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
        ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? "Request failed with status code {$response->status()}";
            throw new RuntimeException("OpenAI API Error: {$errorMessage}");
        }

        return $this->parseContent((string) $response->json('choices.0.message.content'));
    }

    /**
     * Parse raw string content into an array of GeneratedStockItem.
     *
     * @return array<int, GeneratedStockItem>
     */
    public function parseContent(string $rawContent): array
    {
        $cleanContent = trim($rawContent);

        // Remove Markdown code fences if present
        if (str_starts_with($cleanContent, '```')) {
            $cleanContent = (string) preg_replace('/^```(?:json)?\s*/i', '', $cleanContent);
            $cleanContent = trim((string) preg_replace('/\s*```$/', '', $cleanContent));
        }

        $decoded = json_decode($cleanContent, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('AI mengembalikan format respon yang tidak valid.');
        }

        // Support {"items": [...]}, {"stocks": [...]} and a bare list [...]
        $items = $decoded['items'] ?? $decoded['stocks'] ?? (array_is_list($decoded) ? $decoded : []);

        if (!is_array($items) || empty($items)) {
            throw new RuntimeException('Tidak ada item stok yang berhasil di-generate dari AI.');
        }

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item) || empty($item['name'])) {
                continue;
            }

            $quantity = is_numeric($item['quantity'] ?? null) ? max(0, (int) $item['quantity']) : 0;
            $minStock = is_numeric($item['min_stock'] ?? null) ? max(0, (int) $item['min_stock']) : 5;
            $price = is_numeric($item['price'] ?? null) ? round(max(0, (float) $item['price']), 2) : null;

            $results[] = new GeneratedStockItem(
                name: mb_substr(trim((string) $item['name']), 0, 255),
                sku: $this->stringOrNull($item['sku'] ?? null),
                description: $this->stringOrNull($item['description'] ?? null),
                quantity: $quantity,
                unit: $this->stringOrNull($item['unit'] ?? null) ?? 'pcs',
                price: $price,
                minStock: $minStock,
                status: StockStatus::fromQuantity($quantity, $minStock)->value,
            );
        }

        if (empty($results)) {
            throw new RuntimeException('Format item stok yang dikembalikan AI tidak sesuai spesifikasi.');
        }

        return $results;
    }

    /**
     * Determine whether a string starts with an http(s) scheme.
     */
    protected function hasScheme(string $value): bool
    {
        return str_starts_with($value, 'http://') || str_starts_with($value, 'https://');
    }

    /**
     * Trimmed string or null when blank / non-scalar.
     */
    protected function stringOrNull(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
