<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    private function workspaceId(): int
    {
        return auth()->user()->active_workspace_id ?? 0;
    }

    /**
     * Create a checkout session for the selected gateway.
     */
    public function createCheckout(Request $request)
    {
        $data = $request->validate([
            'gateway' => 'required|in:razorpay,stripe',
            'plan'    => 'required|in:pro,enterprise',
        ]);

        $plan = \App\Models\Plan::where('slug', $data['plan'])->firstOrFail();
        $amount = $plan->price;
        $currency = 'INR';

        $transaction = PaymentTransaction::create([
            'workspace_id' => $this->workspaceId(),
            'gateway'      => $data['gateway'],
            'plan'         => $data['plan'],
            'amount'       => $amount,
            'currency'     => $currency,
            'status'       => 'pending',
        ]);

        if ($data['gateway'] === 'razorpay') {
            return $this->createRazorpayOrder($transaction, $amount, $currency);
        }

        return $this->createStripeSession($transaction, $amount, $currency);
    }

    /**
     * Create Razorpay order and return checkout data.
     */
    private function createRazorpayOrder(PaymentTransaction $transaction, int $amount, string $currency)
    {
        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');

        if (!$keyId || !$keySecret) {
            return back()->with('error', 'Razorpay is not configured. Please add API keys in .env');
        }

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount'   => $amount,
                'currency' => $currency,
                'receipt'  => 'txn_' . $transaction->id,
            ]);

        if ($response->failed()) {
            $transaction->update(['status' => 'failed', 'receipt_data' => $response->json()]);
            return back()->with('error', 'Failed to create Razorpay order.');
        }

        $order = $response->json();
        $transaction->update(['gateway_order_id' => $order['id']]);

        return back()->with('razorpay_order', [
            'order_id'       => $order['id'],
            'amount'         => $amount,
            'currency'       => $currency,
            'key_id'         => $keyId,
            'transaction_id' => $transaction->id,
            'name'           => 'PingOS',
            'description'    => ucfirst($transaction->plan) . ' Plan',
        ]);
    }

    /**
     * Create Stripe checkout session.
     */
    private function createStripeSession(PaymentTransaction $transaction, int $amount, string $currency)
    {
        $stripeSecret = config('services.stripe.secret');

        if (!$stripeSecret) {
            return back()->with('error', 'Stripe is not configured. Please add API keys in .env');
        }

        $response = Http::withToken($stripeSecret)
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode'                 => 'payment',
                'success_url'         => route('billing') . '?stripe_success=1&txn=' . $transaction->id,
                'cancel_url'          => route('billing') . '?stripe_cancel=1',
                'line_items[0][price_data][currency]'    => strtolower($currency),
                'line_items[0][price_data][unit_amount]'  => $amount,
                'line_items[0][price_data][product_data][name]' => 'PingOS ' . ucfirst($transaction->plan) . ' Plan',
                'line_items[0][quantity]' => 1,
            ]);

        if ($response->failed()) {
            $transaction->update(['status' => 'failed', 'receipt_data' => $response->json()]);
            return back()->with('error', 'Failed to create Stripe session.');
        }

        $session = $response->json();
        $transaction->update([
            'gateway_order_id' => $session['id'],
            'receipt_data'     => $session,
        ]);

        return inertia()->location($session['url']);
    }

    /**
     * Handle Razorpay payment callback (from frontend JS).
     */
    public function razorpayCallback(Request $request)
    {
        $data = $request->validate([
            'razorpay_payment_id'  => 'required|string',
            'razorpay_order_id'    => 'required|string',
            'razorpay_signature'   => 'required|string',
            'transaction_id'       => 'required|integer',
        ]);

        $transaction = PaymentTransaction::findOrFail($data['transaction_id']);
        abort_if($transaction->workspace_id !== $this->workspaceId(), 403);

        // Verify signature
        $keySecret = config('services.razorpay.key_secret');
        $expected  = hash_hmac('sha256', $data['razorpay_order_id'] . '|' . $data['razorpay_payment_id'], $keySecret);

        if (hash_equals($expected, $data['razorpay_signature'])) {
            $transaction->update([
                'gateway_payment_id' => $data['razorpay_payment_id'],
                'status'             => 'completed',
                'receipt_data'       => $data,
                'activated_at'       => now(),
            ]);

            // Auto-activate plan
            $transaction->workspace->update(['plan' => $transaction->plan]);

            return back()->with('success', 'Payment successful! Your ' . ucfirst($transaction->plan) . ' plan is now active.');
        }

        $transaction->update(['status' => 'failed', 'receipt_data' => $data]);
        return back()->with('error', 'Payment verification failed.');
    }

    /**
     * Handle Stripe success redirect.
     */
    public function stripeSuccess(Request $request)
    {
        $txnId = $request->query('txn');
        if ($txnId) {
            $transaction = PaymentTransaction::find($txnId);
            if ($transaction && $transaction->workspace_id === $this->workspaceId() && $transaction->status === 'pending') {
                $transaction->update([
                    'status' => 'completed',
                    'activated_at' => now(),
                ]);
                
                // Auto-activate plan
                $transaction->workspace->update(['plan' => $transaction->plan]);
            }
        }

        return redirect()->route('billing')->with('success', 'Payment successful! Your plan is now active.');
    }
}
