<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\MessageLog;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\Workspace;
use Inertia\Inertia;

class BillingController extends Controller
{
    public function index(\App\Services\UsageService $usageService)
    {
        $wid = auth()->user()->active_workspace_id ?? 0;
        $workspace = Workspace::find($wid);

        $activePlans = Plan::where('is_active', true)->get();

        if (!$workspace) {
            return Inertia::render('Billing/Index', [
                'plan' => 'free',
                'plans' => $activePlans,
                'usage' => null,
                'transactions' => [],
                'gateways' => ['razorpay' => false, 'stripe' => false],
                'razorpayKeyId' => '',
                'flash' => ['razorpay_order' => null, 'success' => session('success'), 'error' => session('error')],
            ]);
        }

        return Inertia::render('Billing/Index', [
            'plan' => $workspace->plan ?? 'free',
            'plans' => $activePlans,
            'usage' => $usageService->getUsageStats($workspace),
            'transactions' => PaymentTransaction::where('workspace_id', $wid)
                ->latest()
                ->limit(20)
                ->get(),
            'gateways' => [
                'razorpay' => !empty(config('services.razorpay.key_id')),
                'stripe'   => !empty(config('services.stripe.key')),
            ],
            'razorpayKeyId' => config('services.razorpay.key_id', ''),
            'flash' => [
                'razorpay_order' => session('razorpay_order'),
                'success' => session('success'),
                'error'   => session('error'),
            ],
        ]);
    }
}
