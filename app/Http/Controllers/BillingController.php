<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $workspace = $request->user()->activeWorkspace;

        if (!$workspace) {
            abort(404, 'No active workspace found.');
        }

        // Provide safe defaults so the Vue template never sees null
        $workspace->subscription_status       = $workspace->subscription_status       ?? 'trial';
        $workspace->monthly_message_limit     = $workspace->monthly_message_limit     ?? 1000;
        $workspace->messages_sent_this_month  = $workspace->messages_sent_this_month  ?? 0;
        $workspace->trial_ends_at             = $workspace->trial_ends_at             ?? null;

        try {
            $workspace->load('plan');
        } catch (\Throwable $e) {
            Log::warning('Failed to load plan relation for workspace ' . $workspace->id . ': ' . $e->getMessage());
            // Continue without plan — frontend handles null plan gracefully
        }

        return Inertia::render('Billing/Index', [
            'workspace'      => $workspace,
            'recentInvoices' => [],
        ]);
    }

    public function plans()
    {
        return response()->json(Plan::all());
    }

    public function upgradePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:plans,id'
        ]);

        $plan = Plan::find($request->plan_id);
        $workspace = $request->user()->activeWorkspace;

        $paymentUrl = route('billing', ['mock_checkout' => true, 'plan_id' => $plan->id, 'workspace_id' => $workspace->id]);

        return response()->json(['payment_url' => $paymentUrl]);
    }

    public function handleWebhook(Request $request)
    {
        $signature = $request->header('X-Razorpay-Signature');
        $payload = $request->getContent();
        $secret = config('services.razorpay.webhook_secret');

        if (!$signature || !$secret) {
            return response()->json(['status' => 'ignored'], 400);
        }

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning("Razorpay webhook signature mismatch");
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $data = json_decode($payload, true);
        
        if (($data['event'] ?? '') === 'payment.captured') {
            $workspaceId = $data['payload']['payment']['entity']['notes']['workspace_id'] ?? null;
            $planId = $data['payload']['payment']['entity']['notes']['plan_id'] ?? null;

            if ($workspaceId && $planId) {
                $workspace = \App\Models\Workspace::find($workspaceId);
                $plan = Plan::find($planId);

                if ($workspace && $plan) {
                    $workspace->update([
                        'subscription_status'   => 'active',
                        'plan_id'               => $plan->id,
                        'subscription_ends_at'  => now()->addDays(30),
                        'monthly_message_limit' => $plan->max_messages_per_month ?? 1000,
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
