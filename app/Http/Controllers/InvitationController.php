<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class InvitationController extends Controller
{
    public function send(Request $request, Workspace $workspace)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'role' => 'required|string|in:admin,member',
        ]);

        $invitation = $workspace->invitations()->create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
        ]);

        // In a real app, send email here
        // Notification::route('mail', $data['email'])->notify(new WorkspaceInvitationNotification($invitation));

        return back()->with('success', 'Invitation sent to ' . $data['email']);
    }

    public function accept(Request $request, $token)
    {
        $invitation = WorkspaceInvitation::where('token', $token)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $user = auth()->user();

        if ($user->email !== $invitation->email) {
            return Inertia::render('Error', ['message' => 'This invitation was sent to a different email address.']);
        }

        $invitation->workspace->members()->attach($user->id, ['role' => $invitation->role]);
        $invitation->update(['accepted_at' => now()]);

        $user->update(['active_workspace_id' => $invitation->workspace_id]);

        return redirect()->route('dashboard')->with('success', 'You have joined the workspace!');
    }
}
