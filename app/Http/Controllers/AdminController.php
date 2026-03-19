<?php

namespace App\Http\Controllers;

use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Ensure only the admin can access.
     */
    private function authorizeAdmin(): void
    {
        abort_if(!auth()->user()?->isPlatformAdmin(), 403, 'Admin access only.');
    }

    public function dashboard()
    {
        $this->authorizeAdmin();

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users'      => User::count(),
                'total_workspaces' => Workspace::count(),
                'total_revenue'    => PaymentTransaction::where('status', 'completed')->sum('amount'),
                'active_campaigns' => \App\Models\Campaign::where('status', 'running')->count(),
            ],
            'recentUsers' => User::with('activeWorkspace:id,name,plan')->latest()->limit(5)->get(),
        ]);
    }

    /**
     * List all users with their status and workspace info.
     */
    public function users()
    {
        $this->authorizeAdmin();

        $users = User::with('activeWorkspace:id,name,plan')
            ->latest()
            ->paginate(50);

        return Inertia::render('Admin/Users', [
            'users' => $users,
        ]);
    }

    /**
     * Toggle user active/inactive status.
     */
    public function toggleUserStatus(User $user)
    {
        $this->authorizeAdmin();
        abort_if($user->email === 'admin@admin.com', 403, 'Cannot deactivate the admin account.');

        $user->update(['is_active' => !$user->is_active]);

        return back()->with('success', $user->name . ' is now ' . ($user->is_active ? 'active' : 'deactivated') . '.');
    }

    /**
     * List all payment transactions (pending first).
     */
    public function transactions()
    {
        $this->authorizeAdmin();

        $transactions = PaymentTransaction::with('workspace:id,name,plan')
            ->latest()
            ->paginate(50);

        return Inertia::render('Admin/Transactions', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Manually activate a plan for a transaction.
     */
    public function activateTransaction(PaymentTransaction $transaction)
    {
        $this->authorizeAdmin();
        abort_if($transaction->status !== 'completed', 422, 'Can only activate completed transactions.');
        abort_if($transaction->activated_at !== null, 422, 'Already activated.');

        $transaction->update(['activated_at' => now()]);
        $transaction->workspace->update(['plan' => $transaction->plan]);

        return back()->with('success', 'Plan activated to ' . ucfirst($transaction->plan) . '.');
    }

    /**
     * List all plans for editing.
     */
    public function plans()
    {
        $this->authorizeAdmin();
        return Inertia::render('Admin/Plans/Index', [
            'plans' => \App\Models\Plan::all(),
        ]);
    }

    /**
     * Update plan pricing and limits.
     */
    public function updatePlan(Request $request, \App\Models\Plan $plan)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'price'                  => 'required|integer|min:0',
            'max_contacts'           => 'required|integer',
            'max_campaigns'          => 'required|integer',
            'max_messages_per_month' => 'required|integer',
            'features'               => 'nullable|array',
            'is_active'              => 'required|boolean',
        ]);

        $plan->update($validated);
        
        // Clear plan limits cache
        \Illuminate\Support\Facades\Cache::forget("plan_limits:{$plan->slug}");

        return back()->with('success', "Plan {$plan->name} updated successfully.");
    }

    /**
     * Toggle the profile edit permission for a user.
     */
    public function toggleProfilePermission(User $user)
    {
        $this->authorizeAdmin();
        
        $user->update(['can_edit_profile' => !$user->can_edit_profile]);

        return back()->with('success', "Profile editing for {$user->name} is now " . ($user->can_edit_profile ? 'enabled' : 'disabled') . ".");
    }

    /**
     * Manually update a user's workspace plan from the admin panel.
     */
    public function updateUserPlan(Request $request, User $user)
    {
        $this->authorizeAdmin();

        $validated = $request->validate([
            'plan' => 'required|exists:plans,slug',
        ]);

        if ($user->activeWorkspace) {
            $user->activeWorkspace->update(['plan' => $validated['plan']]);
            
            try {
                $user->notify(new \App\Notifications\PlanUpgraded($validated['plan']));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send PlanUpgraded notification: ' . $e->getMessage());
            }
            
            return back()->with('success', "Plan for {$user->name}'s workspace updated to " . ucfirst($validated['plan']) . '.');
        }

        return back()->with('error', 'User does not have an active workspace.');
    }
}
