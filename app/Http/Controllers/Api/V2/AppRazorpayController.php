<?php

namespace App\Http\Controllers\Api\V2;

use App\Models\CombinedOrder;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

/**
 * Native Razorpay checkout for the ToYeZa mobile app.
 *
 * Flow:
 *   1. POST app/razorpay/order   -> server creates a Razorpay order for the
 *      user's unpaid combined order and returns the public key + order id.
 *   2. The app opens the native Razorpay SDK (UPI apps, cards, netbanking).
 *   3. POST app/razorpay/verify  -> server checks the signature, the order
 *      notes and the amount with Razorpay, then marks the order paid.
 *
 * Nothing is marked paid on the client's word alone.
 */
class AppRazorpayController extends Controller
{
    private function api(): Api
    {
        return new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
    }

    private function ownedUnpaidOrder(Request $request): ?CombinedOrder
    {
        $combinedOrder = CombinedOrder::with('orders')->find($request->combined_order_id);
        if (!$combinedOrder || $combinedOrder->orders->isEmpty()) {
            return null;
        }
        $userId = auth()->id();
        foreach ($combinedOrder->orders as $order) {
            if ((int) $order->user_id !== (int) $userId) {
                return null;
            }
        }
        return $combinedOrder;
    }

    private function isPaid(CombinedOrder $combinedOrder): bool
    {
        return $combinedOrder->orders->every(fn ($o) => $o->payment_status === 'paid');
    }

    public function createOrder(Request $request)
    {
        $request->validate(['combined_order_id' => 'required|integer']);

        $combinedOrder = $this->ownedUnpaidOrder($request);
        if (!$combinedOrder) {
            return response()->json(['result' => false, 'message' => translate('Order not found')], 404);
        }
        if ($this->isPaid($combinedOrder)) {
            return response()->json(['result' => false, 'message' => translate('Order is already paid')], 422);
        }

        $amountPaise = (int) round($combinedOrder->grand_total * 100);
        if ($amountPaise < 100) {
            return response()->json(['result' => false, 'message' => translate('Invalid order amount')], 422);
        }

        try {
            $rzpOrder = $this->api()->order->create([
                'receipt' => 'CO-' . $combinedOrder->id,
                'amount' => $amountPaise,
                'currency' => 'INR',
                'notes' => [
                    'combined_order_id' => (string) $combinedOrder->id,
                    'user_id' => (string) auth()->id(),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::warning('App Razorpay order create failed', ['combined_order_id' => $combinedOrder->id, 'error' => $e->getMessage()]);
            return response()->json(['result' => false, 'message' => translate('Could not start payment. Please try again.')], 502);
        }

        $user = auth()->user();
        return response()->json([
            'result' => true,
            'key' => env('RAZOR_KEY'),
            'order_id' => $rzpOrder['id'],
            'amount' => $amountPaise,
            'currency' => 'INR',
            'name' => get_setting('website_name') ?: env('APP_NAME'),
            'description' => 'Order #' . $combinedOrder->id,
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->phone,
            ],
        ]);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'combined_order_id' => 'required|integer',
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $combinedOrder = $this->ownedUnpaidOrder($request);
        if (!$combinedOrder) {
            return response()->json(['result' => false, 'message' => translate('Order not found')], 404);
        }
        if ($this->isPaid($combinedOrder)) {
            return response()->json(['result' => true, 'message' => translate('Payment is successful')]);
        }

        $api = $this->api();
        try {
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ]);

            // The Razorpay order must belong to this combined order and cover its total.
            $rzpOrder = $api->order->fetch($request->razorpay_order_id);
            $expectedPaise = (int) round($combinedOrder->grand_total * 100);
            if (($rzpOrder['notes']['combined_order_id'] ?? null) !== (string) $combinedOrder->id
                || (int) $rzpOrder['amount'] !== $expectedPaise) {
                return response()->json(['result' => false, 'message' => translate('Payment does not match this order')], 422);
            }

            $payment = $api->payment->fetch($request->razorpay_payment_id);
            if ($payment['order_id'] !== $request->razorpay_order_id) {
                return response()->json(['result' => false, 'message' => translate('Payment does not match this order')], 422);
            }
            if ($payment['status'] === 'authorized') {
                try {
                    $payment = $payment->capture(['amount' => $payment['amount'], 'currency' => 'INR']);
                } catch (\Exception $e) {
                    // Auto-capture may have won the race; re-read the real status.
                    $payment = $api->payment->fetch($request->razorpay_payment_id);
                }
            }
            if ($payment['status'] !== 'captured') {
                return response()->json(['result' => false, 'message' => translate('Payment was not completed')], 422);
            }
        } catch (\Exception $e) {
            \Log::warning('App Razorpay verify failed', ['combined_order_id' => $combinedOrder->id, 'payment_id' => $request->razorpay_payment_id, 'error' => $e->getMessage()]);
            return response()->json(['result' => false, 'message' => translate('Payment verification failed')], 422);
        }

        checkout_done($combinedOrder->id, json_encode([
            'id' => $payment['id'],
            'method' => $payment['method'],
            'amount' => $payment['amount'],
            'currency' => $payment['currency'],
        ]));

        return response()->json(['result' => true, 'message' => translate('Payment is successful')]);
    }
}
