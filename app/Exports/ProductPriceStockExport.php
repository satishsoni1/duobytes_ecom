<?php

namespace App\Exports;

use App\Services\ProductPriceStockImporter;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One row per product variant. Only Price and Stock are meant to be edited.
 * WithStrictNullComparison keeps a stock of 0 as 0 instead of an empty cell.
 */
class ProductPriceStockExport implements FromCollection, WithHeadings, WithStyles, WithEvents, ShouldAutoSize, WithStrictNullComparison
{
    public function headings(): array
    {
        return [
            ProductPriceStockImporter::COLUMN_ID,
            'Product',
            'Variant',
            'SKU',
            ProductPriceStockImporter::COLUMN_PRICE,
            ProductPriceStockImporter::COLUMN_STOCK,
            ProductPriceStockImporter::COLUMN_BASE_STOCK,
        ];
    }

    public function collection()
    {
        return ProductPriceStockImporter::eligibleStocks()
            ->orderBy('products.name')
            ->orderBy('product_stocks.id')
            ->get()
            ->map(fn ($stock) => [
                $stock->id,
                $stock->product_name,
                $stock->variant !== null && $stock->variant !== '' ? $stock->variant : 'Default',
                $stock->sku,
                (float) $stock->price,
                (int) $stock->qty,
                (int) $stock->qty,
            ]);
    }

    public function styles(Worksheet $sheet)
    {
        $lastRow = $sheet->getHighestRow();

        $sheet->getStyle('A1:G1')->getFont()->setBold(true);
        $this->fill($sheet, 'A1:D' . $lastRow, 'FFEDEDED');
        $this->fill($sheet, 'E1:F' . $lastRow, 'FFFFF7D6');

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->getColumnDimension('G')->setVisible(false);
            },
        ];
    }

    private function fill(Worksheet $sheet, string $range, string $color): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($color);
    }
}
