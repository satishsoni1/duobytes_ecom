<?php

namespace App\Http\Controllers\ChannelPartner;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardController extends PartnerController
{
    public function index(Request $request)
    {
        $partner = $this->partner($request);

        $orders = Order::where('user_id', $partner->user_id)
            ->where('order_from', config('channel_partner.order_from'));

        $stats = [
            'orders' => (clone $orders)->count(),
            'open' => (clone $orders)->whereNotIn('delivery_status', ['delivered', 'cancelled'])->count(),
            'spend' => (float) (clone $orders)->where('delivery_status', '!=', 'cancelled')->sum('grand_total'),
            'saved' => (float) $partner->partnerOrders()->sum('discount_total'),
        ];

        $recentOrders = (clone $orders)->latest('id')->limit(5)->get();

        $rules = $this->pricing->rulesFor($partner)->load(['product.product_translations', 'category.category_translations'])->sortBy('min_qty');

        $featured = $this->orderableProducts()
            ->where(fn ($q) => $q->where('featured', 1)->orWhere('num_of_sale', '>', 0))
            ->orderByDesc('num_of_sale')
            ->limit(8)
            ->get()
            ->map(fn ($product) => ['product' => $product, 'quote' => $this->pricing->quote($partner, $product, $product->stocks->first()->variant ?? '', max(1, (int) $product->min_qty))]);

        return view('channel_partner.dashboard', [
            'partner' => $partner,
            'stats' => $stats,
            'recentOrders' => $recentOrders,
            'rules' => $rules,
            'featured' => $featured,
            'credit' => $this->creditPosition($partner),
        ]);
    }

    public function account(Request $request)
    {
        $partner = $this->partner($request)->load('documents');

        return view('channel_partner.account', ['partner' => $partner]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $user = $request->user();
        $user->password = Hash::make($request->password);
        $user->save();

        flash(translate('Password updated'))->success();
        return back();
    }
}
