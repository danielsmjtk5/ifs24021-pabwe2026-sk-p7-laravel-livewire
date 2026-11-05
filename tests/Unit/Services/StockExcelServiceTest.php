<?php

namespace Tests\Unit\Services;

use App\Features\Stock\Models\Stock;
use App\Features\Stock\Repositories\StockRepository;
use App\Features\Stock\Services\StockExcelService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class StockExcelServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockExcelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockExcelService(new StockRepository());
    }

    private function saveTemp(Spreadsheet $spreadsheet): string
    {
        $path = tempnam(sys_get_temp_dir(), 'test_excel_').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    public function test_build_spreadsheet_headers_and_rows(): void
    {
        $user = User::factory()->create();
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Barang A', 'sku' => 'A-1', 'quantity' => 7, 'unit' => 'box', 'price' => 2500, 'min_stock' => 3, 'status' => 'available']);
        Stock::factory()->create(['user_id' => $user->id, 'name' => 'Barang B', 'sku' => null, 'description' => null, 'price' => null]);

        $stocks = Stock::query()->where('user_id', $user->id)->orderBy('id')->get();
        $stocks[1]->created_at = null;

        $spreadsheet = $this->service->buildSpreadsheet($stocks, 'Custom Stocks');
        $sheet = $spreadsheet->getActiveSheet();

        $this->assertSame('Custom Stocks', $sheet->getTitle());
        $this->assertSame(StockExcelService::HEADERS, $sheet->rangeToArray('A1:J1')[0]);
        $this->assertSame('Barang A', $sheet->getCell('B2')->getValue());
        $this->assertSame('A-1', $sheet->getCell('C2')->getValue());
        $this->assertEquals(7, $sheet->getCell('E2')->getValue());
        $this->assertEquals(2500, $sheet->getCell('G2')->getValue());
        $this->assertSame('available', $sheet->getCell('I2')->getValue());
        $this->assertSame('-', $sheet->getCell('C3')->getValue());
        $this->assertSame('-', $sheet->getCell('D3')->getValue());
        $this->assertSame('-', $sheet->getCell('G3')->getValue());
        $this->assertSame('-', $sheet->getCell('J3')->getValue());
    }

    public function test_export_response_streams_xlsx(): void
    {
        $stocks = Stock::factory()->count(2)->create();

        $response = $this->service->exportToResponse($stocks, 'test.xlsx');
        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('test.xlsx', (string) $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $output = ob_get_clean();
        $this->assertNotEmpty($output);
    }

    public function test_import_spreadsheet_with_edge_cases(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['No', 'Name', 'SKU', 'Description', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status'],
            // Standard row, empty status → derived (available)
            [1, 'Imported Stock 1', 'IMP-1', 'Desc 1', 50, 'box', 15000, 5, ''],
            // Loose status label wins, placeholders → null/default
            [2, 'Imported Stock 2', '-', '-', 100, '-', '-', 5, 'Low Stock'],
            // Non numeric quantity/min stock → 0 / 5 → out_of_stock
            [3, 'Imported Stock 3', null, null, 'abc', 'kg', 'x', 'y', null],
            // Duplicate header row (skipped)
            ['No', 'Name', 'SKU', null, null, null, null, null, null],
            // Empty name (skipped)
            [5, '', 'NO-NAME', null, 10, null, null, null, null],
            // Negative quantity clamps to 0
            [6, 'Imported Stock 4', 'IMP-4', null, -9, 'pcs', 1000.5, -2, null],
        ]);

        $path = $this->saveTemp($spreadsheet);
        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 4, 'skipped' => 2], $result);

        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id, 'name' => 'Imported Stock 1', 'sku' => 'IMP-1', 'description' => 'Desc 1',
            'quantity' => 50, 'unit' => 'box', 'min_stock' => 5, 'status' => 'available',
        ]);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id, 'name' => 'Imported Stock 2', 'sku' => null, 'description' => null,
            'unit' => 'pcs', 'price' => null, 'status' => 'low_stock',
        ]);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id, 'name' => 'Imported Stock 3', 'quantity' => 0, 'min_stock' => 5,
            'unit' => 'kg', 'price' => null, 'status' => 'out_of_stock',
        ]);
        $this->assertDatabaseHas('stocks', [
            'user_id' => $user->id, 'name' => 'Imported Stock 4', 'quantity' => 0, 'min_stock' => 0, 'status' => 'out_of_stock',
        ]);
        $this->assertSame('1000.50', Stock::where('name', 'Imported Stock 4')->first()->price);
    }

    public function test_import_header_only_file_imports_nothing(): void
    {
        $user = User::factory()->create();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([StockExcelService::HEADERS]);

        $path = $this->saveTemp($spreadsheet);
        $result = $this->service->import($path, $user->id);
        @unlink($path);

        $this->assertSame(['imported' => 0, 'skipped' => 0], $result);
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_export_then_import_round_trip(): void
    {
        $owner = User::factory()->create();
        $target = User::factory()->create();
        Stock::factory()->lowStock()->create(['user_id' => $owner->id, 'name' => 'Round Trip Item', 'sku' => 'RT-1']);

        $spreadsheet = $this->service->buildSpreadsheet(Stock::where('user_id', $owner->id)->get());
        $path = $this->saveTemp($spreadsheet);

        $this->assertSame('Round Trip Item', IOFactory::load($path)->getActiveSheet()->getCell('B2')->getValue());

        $result = $this->service->import($path, $target->id);
        @unlink($path);

        $this->assertSame(1, $result['imported']);
        $this->assertDatabaseHas('stocks', ['user_id' => $target->id, 'name' => 'Round Trip Item', 'sku' => 'RT-1', 'quantity' => 3, 'status' => 'low_stock']);
    }
}
