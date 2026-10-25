<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Models\Category;
use Illuminate\Http\Request;

class CatalogController extends PartnerController
{
    public function index(Request $request)
    {
        $partner = $this->partner($request);

        $query = $this->orderableProducts();

        if ($search = trim((string) $request->query('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('tags', 'like', '%' . $search . '%')
                    ->orWhereHas('stocks', fn ($s) => $s->where('sku', 'like', '%' . $search . '%'));
            });
        }

        $categoryId = (int) $request->query('category');
        if ($categoryId) {
            $ids = Category::where('id', $categoryId)->orWhere('parent_id', $categoryId)->pluck('id');
            $query->where(fn ($q) => $q->whereIn('category_id', $ids)
                ->orWhereHas('categories', fn ($c) => $c->whereIn('categories.id', $ids)));
        }

        match ($request->query('sort')) {
            'newest' => $query->latest('id'),
            'price_asc' => $query->orderBy('unit_price'),
            'price_desc' => $query->orderByDesc('unit_price'),
            'name' => $query->orderBy('name'),
            default => $query->orderByDesc('num_of_sale')->orderByDesc('id'),
        };

        $products = $query->paginate(24)->withQueryString();

        $cartQty = $partner->cartItems()->selectRaw('product_id, SUM(quantity) as qty')->groupBy('product_id')->pluck('qty', 'product_id');

        $cards = $products->getCollection()->map(function ($product) use ($partner) {
            $stock = $product->stocks->first();
            return [
                'product' => $product,
                'quote' => $this->pricing->quote($partner, $product, $stock->variant ?? '', max(1, (int) $product->min_qty)),
                'has_variants' => $product->stocks->count() > 1,
                'tiers' => $this->pricing->tiers($partner, $product, $stock->variant ?? ''),
            ];
        });

        $categories = Category::where('parent_id', 0)->with('category_translations')->orderBy('order_level', 'desc')->get();

        return view('channel_partner.catalog.index', compact('partner', 'products', 'cards', 'categories', 'cartQty'));
    }

    public function show(Request $request, string $slug)
    {
        $partner = $this->partner($request);
        $product = $this->orderableProducts()->where('slug', $slug)->firstOrFail();

        $variants = $product->stocks->map(fn ($stock) => [
            'variant' => (string) $stock->variant,
            'label' => $stock->variant ? str_replace('-', ' / ', $stock->variant) : translate('Standard'),
            'sku' => $stock->sku,
            'stock' => (int) $stock->qty,
            'quote' => $this->pricing->quote($partner, $product, $stock->variant, max(1, (int) $product->min_qty)),
        ])->values();

        $tiers = $this->pricing->tiers($partner, $product, $variants->first()['variant'] ?? '');
        $inCart = $partner->cartItems()->where('product_id', $product->id)->get()->keyBy('variation');

        $photos = collect(explode(',', (string) $product->photos))->filter()->map(fn ($id) => uploaded_asset($id))->filter()->values();
        if ($photos->isEmpty()) {
            $photos = collect([uploaded_asset($product->thumbnail_img) ?: static_asset('assets/img/placeholder.jpg')]);
        }

        return view('channel_partner.catalog.show', compact('partner', 'product', 'variants', 'tiers', 'inCart', 'photos'));
    }

    /**
     * Live price for a product/variant/quantity (used by the quantity steppers).
     */
    public function quote(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'variation' => ['nullable', 'string', 'max:191'],
            'quantity' => ['required', 'integer', 'min:1', 'max:' . config('channel_partner.max_line_quantity')],
        ]);

        $partner = $this->partner($request);
        $product = $this->orderableProducts()->findOrFail($data['product_id']);
        $quote = $this->pricing->quote($partner, $product, $data['variation'] ?? '', (int) $data['quantity']);

        return response()->json($this->formatQuote($quote));
    }

    public static function formatQuote(array $quote): array
    {
        return $quote + [
            'unit_price_formatted' => single_price($quote['unit_price']),
            'list_price_formatted' => single_price($quote['list_price']),
            'line_net_formatted' => single_price($quote['line_net']),
            'line_total_formatted' => single_price($quote['line_net'] + $quote['line_tax']),
            'line_discount_formatted' => single_price($quote['line_discount']),
            'next_tier_formatted' => $quote['next_tier']
                ? translate('Add') . ' ' . $quote['next_tier']['add_qty'] . ' ' . translate('more to pay') . ' ' . single_price($quote['next_tier']['unit_price']) . ' ' . translate('each')
                : null,
        ];
    }
}
