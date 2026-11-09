<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Applies a filled-in price and stock sheet (see ProductPriceStockExport).
 *
 * The whole file is validated first: if any row is invalid, nothing is saved.
 * Blank Price or Stock cells leave that value unchanged.
 */
class ProductPriceStockImporter
{
    public const COLUMN_ID = 'Stock ID';
    public const COLUMN_PRICE = 'Price';
    public const COLUMN_STOCK = 'Stock';
    public const COLUMN_BASE_STOCK = 'Stock At Download';

    private const MAX_PRICE = 99999999;

    /** Variants that this tool may update, with the product name joined on. */
    public static function eligibleStocks()
    {
        return ProductStock::query()
            ->join('products', 'products.id', '=', 'product_stocks.product_id')
            ->where('products.digital', 0)
            ->where('products.auction_product', 0)
            ->where('products.wholesale_product', 0)
            ->select('product_stocks.*', 'products.name as product_name');
    }

    /**
     * @return array{errors: string[], changes: array<int, array<string, mixed>>}
     */
    public function import($file): array
    {
        [$rows, $errors] = $this->parse($file);

        if ($errors) {
            return ['errors' => $errors, 'changes' => []];
        }

        return DB::transaction(fn () => $this->apply($rows));
    }

    private function parse($file): array
    {
        $sheets = Excel::toArray(new class implements WithCalculatedFormulas {}, $file);
        $sheet = $sheets[0] ?? [];
        $columns = [];

        foreach (array_shift($sheet) ?? [] as $index => $heading) {
            $columns[strtolower(trim((string) $heading))] = $index;
        }

        $idColumn = $columns[strtolower(self::COLUMN_ID)] ?? null;
        $priceColumn = $columns[strtolower(self::COLUMN_PRICE)] ?? null;
        $stockColumn = $columns[strtolower(self::COLUMN_STOCK)] ?? null;
        $baseColumn = $columns[strtolower(self::COLUMN_BASE_STOCK)] ?? null;

        if ($idColumn === null || $priceColumn === null || $stockColumn === null) {
            return [[], [translate('This file does not match the template. Please download the sheet from this page and edit that file.')]];
        }

        $rows = [];
        $errors = [];

        foreach ($sheet as $index => $row) {
            if (collect($row)->every(fn ($cell) => $cell === null || trim((string) $cell) === '')) {
                continue;
            }

            $rowNumber = $index + 2;
            $prefix = translate('Row') . ' ' . $rowNumber . ': ';
            $id = trim((string) ($row[$idColumn] ?? ''));

            if (!ctype_digit($id)) {
                $errors[] = $prefix . translate('Stock ID is missing or invalid.');
                continue;
            }

            if (isset($rows[$id])) {
                $errors[] = $prefix . translate('Stock ID appears more than once in the file.');
                continue;
            }

            $price = $this->number($row[$priceColumn] ?? null);
            $stock = $this->number($row[$stockColumn] ?? null);
            $baseStock = $baseColumn === null ? null : $this->number($row[$baseColumn] ?? null);

            if ($price === false || ($price !== null && ($price <= 0 || $price > self::MAX_PRICE))) {
                $errors[] = $prefix . translate('Price must be a number greater than 0.');
                continue;
            }

            if ($stock === false || ($stock !== null && ($stock < 0 || floor($stock) != $stock))) {
                $errors[] = $prefix . translate('Stock must be a whole number, 0 or more.');
                continue;
            }

            // An untouched stock cell must not overwrite sales made since the download.
            if ($stock !== null && $baseStock !== null && $baseStock !== false && (int) $baseStock === (int) $stock) {
                $stock = null;
            }

            $rows[$id] = [
                'row' => $rowNumber,
                'price' => $price === null ? null : round($price, 2),
                'stock' => $stock === null ? null : (int) $stock,
            ];
        }

        if (!$rows && !$errors) {
            $errors[] = translate('The file has no product rows.');
        }

        return [$rows, $errors];
    }

    private function apply(array $rows): array
    {
        $stocks = self::eligibleStocks()
            ->whereIn('product_stocks.id', array_keys($rows))
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $errors = [];
        foreach ($rows as $id => $row) {
            if (!$stocks->has((int) $id)) {
                $errors[] = translate('Row') . ' ' . $row['row'] . ': ' . translate('This variant no longer exists or cannot be updated here.');
            }
        }

        if ($errors) {
            return ['errors' => $errors, 'changes' => []];
        }

        $changes = [];
        $repricedProductIds = [];

        foreach ($rows as $id => $row) {
            $stock = $stocks[(int) $id];
            $oldPrice = round((float) $stock->price, 2);
            $oldStock = (int) $stock->qty;
            $priceChanged = $row['price'] !== null && $row['price'] !== $oldPrice;
            $stockChanged = $row['stock'] !== null && $row['stock'] !== $oldStock;

            if (!$priceChanged && !$stockChanged) {
                continue;
            }

            if ($priceChanged) {
                $stock->price = $row['price'];
                $repricedProductIds[$stock->product_id] = true;
            }

            if ($stockChanged) {
                $stock->qty = $row['stock'];
            }

            $stock->save();

            $changes[] = [
                'product' => $stock->product_name,
                'variant' => $stock->variant,
                'sku' => $stock->sku,
                'old_price' => $oldPrice,
                'new_price' => $priceChanged ? $row['price'] : $oldPrice,
                'old_stock' => $oldStock,
                'new_stock' => $stockChanged ? $row['stock'] : $oldStock,
            ];
        }

        // The storefront price range starts from unit_price, so keep it at the lowest variant price.
        foreach (array_keys($repricedProductIds) as $productId) {
            Product::whereKey($productId)->update([
                'unit_price' => ProductStock::where('product_id', $productId)->min('price'),
            ]);
        }

        return ['errors' => [], 'changes' => $changes];
    }

    /**
     * @return float|null|false The number, null for a blank cell, false if invalid.
     */
    private function number($value)
    {
        if ($value === null) {
            return null;
        }

        $clean = str_ireplace(['₹', 'rs.', 'rs', ',', ' '], '', trim((string) $value));

        if ($clean === '') {
            return null;
        }

        return is_numeric($clean) ? (float) $clean : false;
    }
}
