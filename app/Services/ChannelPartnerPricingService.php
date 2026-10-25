<?php

namespace App\Services;

use App\Models\ChannelPartner;
use App\Models\ChannelPartnerPriceRule;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Resolves the contract price a channel partner pays for a product.
 *
 * Rule precedence (first scope that has a matching rule wins):
 *   1. product-specific rules
 *   2. category rules (product's main or any assigned category)
 *   3. "all products" rules
 *   4. the partner's default discount %
 * Within a scope, the rule with the highest min_qty <= ordered qty wins
 * (that is how bulk tiers are expressed).
 *
 * Partners never pay more than the current public (retail) price.
 */
class ChannelPartnerPricingService
{
    /** @var array<int, Collection> */
    private array $rulesCache = [];

    public function rulesFor(ChannelPartner $partner): Collection
    {
        return $this->rulesCache[$partner->id] ??= ChannelPartnerPriceRule::where('channel_partner_id', $partner->id)
            ->currentlyValid()
            ->get();
    }

    /**
     * @return array{
     *   available: bool, list_price: float, retail_price: float, unit_price: float,
     *   discount_per_unit: float, tax_per_unit: float, quantity: int,
     *   line_list: float, line_net: float, line_tax: float, line_discount: float,
     *   rule_id: ?int, rule_label: ?string, stock: int, next_tier: ?array
     * }
     */
    public function quote(ChannelPartner $partner, Product $product, ?string $variation, int $quantity): array
    {
        $variation = (string) $variation;
        $quantity = max(1, $quantity);
        $stock = $product->stocks->where('variant', $variation)->first();

        if ($stock === null) {
            return $this->unavailable($quantity);
        }

        $listPrice = (float) $stock->price;
        $retailPrice = $this->retailPrice($product, $listPrice);

        [$rule, $contractPrice] = $this->contractPrice($partner, $product, $listPrice, $quantity);

        $unitPrice = $contractPrice === null ? $retailPrice : min($contractPrice, $retailPrice);
        $unitPrice = round(max(0, $unitPrice), 2);
        $taxPerUnit = round($this->taxFor($product, $unitPrice), 2);

        return [
            'available' => true,
            'list_price' => round($listPrice, 2),
            'retail_price' => round($retailPrice, 2),
            'unit_price' => $unitPrice,
            'discount_per_unit' => round($listPrice - $unitPrice, 2),
            'tax_per_unit' => $taxPerUnit,
            'quantity' => $quantity,
            'line_list' => round($listPrice * $quantity, 2),
            'line_net' => round($unitPrice * $quantity, 2),
            'line_tax' => round($taxPerUnit * $quantity, 2),
            'line_discount' => round(($listPrice - $unitPrice) * $quantity, 2),
            'rule_id' => $rule instanceof ChannelPartnerPriceRule ? $rule->id : null,
            'rule_label' => $this->ruleLabel($rule, $partner),
            'stock' => $product->digital == 1 ? PHP_INT_MAX : (int) $stock->qty,
            'next_tier' => $this->nextTier($partner, $product, $listPrice, $retailPrice, $quantity, $unitPrice),
        ];
    }

    /**
     * Bulk tiers visible to the partner for a product, ascending by quantity.
     *
     * @return array<int, array{min_qty: int, unit_price: float, label: string}>
     */
    public function tiers(ChannelPartner $partner, Product $product, ?string $variation = null): array
    {
        $stock = $product->stocks->where('variant', (string) $variation)->first() ?? $product->stocks->first();
        if ($stock === null) {
            return [];
        }
        $listPrice = (float) $stock->price;
        $retailPrice = $this->retailPrice($product, $listPrice);

        $rules = $this->scopedRules($partner, $product);
        if ($rules->isEmpty()) {
            return [];
        }

        return $rules->sortBy('min_qty')
            ->unique('min_qty')
            ->map(fn (ChannelPartnerPriceRule $rule) => [
                'min_qty' => max(1, $rule->min_qty),
                'unit_price' => round(min($retailPrice, $this->applyRule($rule, $listPrice)), 2),
                'label' => $rule->partnerLabel(),
            ])
            ->values()
            ->all();
    }

    public function retailPrice(Product $product, float $listPrice): float
    {
        $now = time();
        $discountActive = $product->discount_start_date == null
            || ($now >= $product->discount_start_date && $now <= $product->discount_end_date);

        $price = $listPrice;
        if ($discountActive && $product->discount > 0) {
            if ($product->discount_type === 'percent') {
                $price -= ($price * $product->discount) / 100;
            } elseif ($product->discount_type === 'amount') {
                $price -= $product->discount;
            }
        }

        return max(0, $price);
    }

    public function taxFor(Product $product, float $unitPrice): float
    {
        $tax = 0;
        foreach ($product->taxes as $productTax) {
            if ($productTax->tax_type === 'percent') {
                $tax += ($unitPrice * $productTax->tax) / 100;
            } elseif ($productTax->tax_type === 'amount') {
                $tax += $productTax->tax;
            }
        }

        return $tax;
    }

    /**
     * The rules from the most specific scope that has any rule for this product.
     */
    private function scopedRules(ChannelPartner $partner, Product $product): Collection
    {
        $rules = $this->rulesFor($partner);

        $productRules = $rules->where('product_id', $product->id);
        if ($productRules->isNotEmpty()) {
            return $productRules;
        }

        $categoryIds = $this->categoryIds($product);
        $categoryRules = $rules->whereNull('product_id')->whereIn('category_id', $categoryIds);
        if ($categoryRules->isNotEmpty()) {
            return $categoryRules;
        }

        return $rules->whereNull('product_id')->whereNull('category_id');
    }

    /**
     * @return array{0: ChannelPartnerPriceRule|string|null, 1: ?float}
     */
    private function contractPrice(ChannelPartner $partner, Product $product, float $listPrice, int $quantity): array
    {
        $rule = $this->scopedRules($partner, $product)
            ->filter(fn ($r) => $r->min_qty <= $quantity)
            ->sortBy([['min_qty', 'desc'], fn ($a, $b) => $this->applyRule($a, $listPrice) <=> $this->applyRule($b, $listPrice)])
            ->first();

        if ($rule) {
            return [$rule, $this->applyRule($rule, $listPrice)];
        }

        if ($partner->default_discount > 0) {
            return ['default', $listPrice - ($listPrice * $partner->default_discount / 100)];
        }

        return [null, null];
    }

    private function applyRule(ChannelPartnerPriceRule $rule, float $listPrice): float
    {
        $price = match ($rule->discount_type) {
            'percent' => $listPrice - ($listPrice * min(100, $rule->discount_value) / 100),
            'amount' => $listPrice - $rule->discount_value,
            'fixed_price' => $rule->discount_value,
            default => $listPrice,
        };

        return max(0, $price);
    }

    private function nextTier(ChannelPartner $partner, Product $product, float $listPrice, float $retailPrice, int $quantity, float $currentUnit): ?array
    {
        $next = $this->scopedRules($partner, $product)
            ->filter(fn ($r) => $r->min_qty > $quantity)
            ->sortBy('min_qty')
            ->first(fn ($r) => min($retailPrice, $this->applyRule($r, $listPrice)) < $currentUnit);

        if (!$next) {
            return null;
        }

        return [
            'min_qty' => $next->min_qty,
            'unit_price' => round(min($retailPrice, $this->applyRule($next, $listPrice)), 2),
            'add_qty' => $next->min_qty - $quantity,
        ];
    }

    private function ruleLabel($rule, ChannelPartner $partner): ?string
    {
        if ($rule instanceof ChannelPartnerPriceRule) {
            return $rule->partnerLabel();
        }
        if ($rule === 'default') {
            return rtrim(rtrim(number_format($partner->default_discount, 2), '0'), '.') . '% ' . translate('partner discount');
        }

        return null;
    }

    private function categoryIds(Product $product): array
    {
        $ids = $product->relationLoaded('categories')
            ? $product->categories->pluck('id')->all()
            : $product->categories()->pluck('categories.id')->all();
        $ids[] = $product->category_id;

        return array_values(array_unique(array_filter($ids)));
    }

    private function unavailable(int $quantity): array
    {
        return [
            'available' => false, 'list_price' => 0.0, 'retail_price' => 0.0, 'unit_price' => 0.0,
            'discount_per_unit' => 0.0, 'tax_per_unit' => 0.0, 'quantity' => $quantity,
            'line_list' => 0.0, 'line_net' => 0.0, 'line_tax' => 0.0, 'line_discount' => 0.0,
            'rule_id' => null, 'rule_label' => null, 'stock' => 0, 'next_tier' => null,
        ];
    }
}
