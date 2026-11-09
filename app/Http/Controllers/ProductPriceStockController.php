<?php

namespace App\Http\Controllers;

use App\Exports\ProductPriceStockExport;
use App\Services\ProductPriceStockImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ProductPriceStockController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:product_bulk_import'])->only(['index', 'upload']);
        $this->middleware(['permission:product_bulk_export'])->only('download');
    }

    public function index()
    {
        return view('backend.product.price_stock_update.index');
    }

    public function download()
    {
        return Excel::download(new ProductPriceStockExport, 'product-price-stock-' . date('Y-m-d') . '.xlsx');
    }

    public function upload(Request $request, ProductPriceStockImporter $importer)
    {
        $request->validate(['price_stock_file' => 'required|file|max:5120']);

        $file = $request->file('price_stock_file');

        if (!in_array(strtolower($file->getClientOriginalExtension()), ['xlsx', 'xls', 'csv'], true)) {
            flash(translate('Please upload an .xlsx, .xls or .csv file.'))->error();
            return back();
        }

        try {
            $result = $importer->import($file);
        } catch (Throwable $e) {
            Log::warning('Price and stock sheet upload failed: ' . $e->getMessage());
            flash(translate('The file could not be read. No prices or stock were changed.'))->error();
            return back();
        }

        if ($result['changes']) {
            Artisan::call('cache:clear');
        }

        return redirect()->route('product_price_stock.index')->with('price_stock_result', $result);
    }
}
