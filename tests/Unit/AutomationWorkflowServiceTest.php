<?php

namespace Tests\Unit;

use App\Models\Campaign;
use App\Models\Contact;
use App\Models\User;
use App\Models\Workspace;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\AutomationWorkflowService;
use App\Services\CampaignStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutomationWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_runs_workflow_send_campaign_message_action()
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_id' => $user->id, 'name' => 'Test Workspace']);

        $campaign = Campaign::create([
            'workspace_id' => $workspace->id,
            'name' => 'Test Campaign',
            'platform' => 'whatsapp',
            'status' => Campaign::STATUS_DRAFT,
            'body' => 'Hello {{name}}',
        ]);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => 'Bob',
            'phone' => '+15551234567',
        ]);

        $workflow = Workflow::create([
            'workspace_id' => $workspace->id,
            'name' => 'Send followup',
            'trigger' => 'incoming_message',
            'conditions' => [],
            'actions' => [['type' => 'send_campaign_message', 'campaign_id' => $campaign->id]],
            'is_active' => true,
        ]);

        $service = new AutomationWorkflowService();

        $service->executeTrigger('incoming_message', [
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'message' => 'Hi',
        ]);

        $execution = WorkflowExecution::where('workflow_id', $workflow->id)->first();
        $this->assertNotNull($execution);
        $this->assertEquals('completed', $execution->status);

        $this->assertDatabaseHas('message_logs', [
            'campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'status' => 'pending',
        ]);
    }

    public function test_campaign_status_service_computes_partial_and_completed_states()
    {
        $user = User::factory()->create();
        $workspace = Workspace::create(['owner_id' => $user->id, 'name' => 'Status test workspace']);

        $campaign = Campaign::create([
            'workspace_id' => $workspace->id,
            'name' => 'Status test campaign',
            'platform' => 'whatsapp',
            'status' => Campaign::STATUS_RUNNING,
            'body' => 'Hello',
        ]);

        $contact = Contact::create([
            'workspace_id' => $workspace->id,
            'name' => 'Test User',
            'phone' => '+15559876543',
        ]);

        $campaign->messageLogs()->createMany([
            ['contact_id' => $contact->id, 'platform' => 'whatsapp', 'final_message' => 'a', 'status' => 'sent', 'created_at' => now(), 'updated_at' => now()],
            ['contact_id' => $contact->id, 'platform' => 'whatsapp', 'final_message' => 'b', 'status' => 'failed', 'error_message' => 'err', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $statusService = new CampaignStatusService();
        $statusService->refresh($campaign);

        $this->assertEquals(Campaign::STATUS_PARTIAL, $campaign->fresh()->status);

        $campaign->messageLogs()->update(['status' => 'sent', 'error_message' => null]);
        $statusService->refresh($campaign);

        $this->assertEquals(Campaign::STATUS_COMPLETED, $campaign->fresh()->status);

        $campaign->messageLogs()->update(['status' => 'failed']);
        $statusService->refresh($campaign);

        $this->assertEquals(2, \App\Models\MessageLog::where('campaign_id', $campaign->id)->where('status', 'failed')->count());
        $this->assertEquals(Campaign::STATUS_FAILED, $campaign->fresh()->status);
    }
}
