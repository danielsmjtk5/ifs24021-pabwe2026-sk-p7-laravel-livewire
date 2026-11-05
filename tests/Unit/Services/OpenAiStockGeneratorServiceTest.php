<?php

namespace Tests\Unit\Services;

use App\Features\Ai\DTO\GeneratedStockItem;
use App\Features\Ai\Services\OpenAiStockGeneratorService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAiStockGeneratorServiceTest extends TestCase
{
    private function service(): OpenAiStockGeneratorService
    {
        return new OpenAiStockGeneratorService('https://api.openai.com/v1', 'mock-key', 'gpt-4o-mini');
    }

    public function test_empty_prompt_throws_exception(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Prompt tidak boleh kosong.');
        $this->service()->generateStocks('   ');
    }

    public function test_successful_generation(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'items' => [
                                [
                                    'name' => 'Kertas HVS A4',
                                    'sku' => 'ATK-HVS-A4',
                                    'description' => 'Kertas 80gsm',
                                    'quantity' => 40,
                                    'unit' => 'rim',
                                    'price' => 52000,
                                    'min_stock' => 10,
                                ],
                                [
                                    'name' => 'Tinta Hitam',
                                    'sku' => '',
                                    'description' => null,
                                    'quantity' => 'banyak',
                                    'unit' => ['invalid'],
                                    'price' => -100,
                                ],
                                ['sku' => 'NO-NAME'],
                                'not-an-array',
                            ],
                        ]),
                    ],
                ]],
            ], 200),
        ]);

        $results = $this->service()->generateStocks('Perlengkapan kantor', 0);

        Http::assertSent(function (Request $request) {
            return $request->hasHeader('Authorization', 'Bearer mock-key')
                && $request['model'] === 'gpt-4o-mini'
                && str_contains($request['messages'][0]['content'], 'exactly 1 realistic inventory');
        });

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(GeneratedStockItem::class, $results);

        $this->assertSame('Kertas HVS A4', $results[0]->name);
        $this->assertSame('ATK-HVS-A4', $results[0]->sku);
        $this->assertSame(40, $results[0]->quantity);
        $this->assertSame('rim', $results[0]->unit);
        $this->assertSame(52000.0, $results[0]->price);
        $this->assertSame(10, $results[0]->minStock);
        $this->assertSame('available', $results[0]->status);

        $this->assertSame('Tinta Hitam', $results[1]->name);
        $this->assertNull($results[1]->sku);
        $this->assertNull($results[1]->description);
        $this->assertSame(0, $results[1]->quantity);
        $this->assertSame('pcs', $results[1]->unit);
        $this->assertSame(0.0, $results[1]->price);
        $this->assertSame(5, $results[1]->minStock);
        $this->assertSame('out_of_stock', $results[1]->status);
    }

    public function test_api_failure_throws_exception_with_message(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response(['error' => ['message' => 'Invalid API Key provided']], 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Invalid API Key provided');
        $this->service()->generateStocks('Rencana stok');
    }

    public function test_api_failure_without_message_uses_status_code(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response('Server Error', 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OpenAI API Error: Request failed with status code 500');
        $this->service()->generateStocks('Rencana stok');
    }

    public function test_parse_content_with_markdown_fences_and_list(): void
    {
        $raw = "```json\n[{\"name\": \"Code Fence Item\", \"quantity\": 3, \"min_stock\": 5}]\n```";

        $results = $this->service()->parseContent($raw);
        $this->assertCount(1, $results);
        $this->assertSame('Code Fence Item', $results[0]->name);
        $this->assertSame('low_stock', $results[0]->status);
    }

    public function test_parse_content_supports_stocks_key(): void
    {
        $results = $this->service()->parseContent('{"stocks": [{"name": "Via Stocks Key", "quantity": 100}]}');
        $this->assertSame('Via Stocks Key', $results[0]->name);
        $this->assertSame('available', $results[0]->status);
    }

    public function test_parse_content_invalid_json(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI mengembalikan format respon yang tidak valid.');
        $this->service()->parseContent('Not valid JSON');
    }

    public function test_parse_content_empty_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada item stok yang berhasil di-generate dari AI.');
        $this->service()->parseContent('{"items": []}');
    }

    public function test_parse_content_unknown_object_shape(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tidak ada item stok yang berhasil di-generate dari AI.');
        $this->service()->parseContent('{"foo": "bar"}');
    }

    public function test_parse_content_no_valid_items(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Format item stok yang dikembalikan AI tidak sesuai spesifikasi.');
        $this->service()->parseContent('{"items": [{"bad_key": "val"}]}');
    }

    public function test_constructor_handles_swapped_endpoint_and_key(): void
    {
        Http::fake([
            'https://ai.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"items":[{"name":"Barang"}]}']]],
            ]),
        ]);

        // Key given as URL, endpoint given as secret
        $service = new OpenAiStockGeneratorService('sk-secret-key', 'https://ai.example.com/v1/');
        $this->assertCount(1, $service->generateStocks('Rencana'));

        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer sk-secret-key'));
    }

    public function test_constructor_auto_prefixes_https(): void
    {
        Http::fake([
            'https://api.openai.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"items":[{"name":"Barang"}]}']]],
            ]),
        ]);

        $service = new OpenAiStockGeneratorService('api.openai.com/v1', 'mock-key');
        $this->assertCount(1, $service->generateStocks('Rencana'));
    }

    public function test_constructor_reads_configuration_defaults(): void
    {
        config([
            'openai.endpoint' => 'https://config.example.com/v1',
            'openai.key' => 'config-key',
            'openai.model' => 'config-model',
        ]);

        Http::fake([
            'https://config.example.com/v1/chat/completions' => Http::response([
                'choices' => [['message' => ['content' => '{"items":[{"name":"Barang"}]}']]],
            ]),
        ]);

        $service = new OpenAiStockGeneratorService();
        $this->assertCount(1, $service->generateStocks('Rencana', 1));

        Http::assertSent(fn (Request $request) => $request['model'] === 'config-model'
            && $request->hasHeader('Authorization', 'Bearer config-key'));
    }
}
