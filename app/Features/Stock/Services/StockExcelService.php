<?php

namespace App\Features\Stock\Services;

use App\Features\Stock\Contracts\StockRepositoryInterface;
use App\Features\Stock\Enums\StockStatus;
use App\Features\Stock\Models\Stock;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockExcelService
{
    /**
     * Spreadsheet header row (column A..J).
     *
     * @var array<int, string>
     */
    public const HEADERS = ['No', 'Name', 'SKU', 'Description', 'Quantity', 'Unit', 'Price', 'Min Stock', 'Status', 'Created At'];

    public function __construct(
        protected StockRepositoryInterface $stockRepository
    ) {}

    /**
     * Build a Spreadsheet instance containing stock data.
     *
     * @param Collection<int, Stock> $stocks
     */
    public function buildSpreadsheet(Collection $stocks, string $sheetTitle = 'Stocks'): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($sheetTitle, 0, 31));

        $columns = range('A', 'J');

        foreach (self::HEADERS as $index => $header) {
            $sheet->setCellValue("{$columns[$index]}1", $header);
        }

        // Header Styling
        $headerRange = 'A1:J1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0F766E');
        $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rowNumber = 2;
        foreach ($stocks->values() as $idx => $stock) {
            $sheet->setCellValue("A{$rowNumber}", $idx + 1);
            $sheet->setCellValue("B{$rowNumber}", $stock->name);
            $sheet->setCellValue("C{$rowNumber}", $stock->sku ?? '-');
            $sheet->setCellValue("D{$rowNumber}", $stock->description ?? '-');
            $sheet->setCellValue("E{$rowNumber}", (int) $stock->quantity);
            $sheet->setCellValue("F{$rowNumber}", $stock->unit);
            $sheet->setCellValue("G{$rowNumber}", $stock->price !== null ? (float) $stock->price : '-');
            $sheet->setCellValue("H{$rowNumber}", (int) $stock->min_stock);
            $sheet->setCellValue("I{$rowNumber}", $stock->statusEnum()->value);
            $sheet->setCellValue("J{$rowNumber}", $stock->created_at ? $stock->created_at->format('Y-m-d H:i') : '-');
            $rowNumber++;
        }

        $lastRow = max(2, $rowNumber - 1);
        $sheet->getStyle("A1:J{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Export stock items as a downloadable streamed response.
     *
     * @param Collection<int, Stock> $stocks
     */
    public function exportToResponse(Collection $stocks, string $filename = 'stocks.xlsx'): StreamedResponse
    {
        $spreadsheet = $this->buildSpreadsheet($stocks);

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Import stock items from a spreadsheet file path.
     * Expected columns: No | Name | SKU | Description | Quantity | Unit | Price | Min Stock | Status.
     *
     * @return array{imported: int, skipped: int}
     */
    public function import(string $filePath, int $userId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, false, true);

        $records = [];
        $skipped = 0;
        $now = Carbon::now();

        // First row is always the header
        array_shift($rows);

        foreach ($rows as $row) {
            $name = $this->cell($row, 'B');

            if ($name === '' || in_array(strtolower($name), ['name', 'nama'], true)) {
                $skipped++;
                continue;
            }

            $quantityRaw = $this->cell($row, 'E');
            $priceRaw = $this->cell($row, 'G');
            $minStockRaw = $this->cell($row, 'H');

            $quantity = is_numeric($quantityRaw) ? max(0, (int) $quantityRaw) : 0;
            $minStock = is_numeric($minStockRaw) ? max(0, (int) $minStockRaw) : 5;
            $status = StockStatus::tryFromLoose($this->cell($row, 'I'))
                ?? StockStatus::fromQuantity($quantity, $minStock);

            $records[] = [
                'user_id' => $userId,
                'name' => mb_substr($name, 0, 255),
                'sku' => $this->nullIfDash($this->cell($row, 'C')),
                'description' => $this->nullIfDash($this->cell($row, 'D')),
                'quantity' => $quantity,
                'unit' => $this->nullIfDash($this->cell($row, 'F')) ?? 'pcs',
                'price' => is_numeric($priceRaw) ? round((float) $priceRaw, 2) : null,
                'min_stock' => $minStock,
                'status' => $status->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->stockRepository->insertMany($records);

        return [
            'imported' => count($records),
            'skipped' => $skipped,
        ];
    }

    /**
     * Read a trimmed cell string from a row.
     *
     * @param array<string, mixed> $row
     */
    protected function cell(array $row, string $column): string
    {
        return trim((string) ($row[$column] ?? ''));
    }

    /**
     * Convert '' or '-' placeholders into null.
     */
    protected function nullIfDash(string $value): ?string
    {
        return ($value === '' || $value === '-') ? null : $value;
    }
}
