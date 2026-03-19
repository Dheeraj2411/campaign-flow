<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Contact;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Models\Campaign;
use App\Jobs\SendMessageJob;
use App\Services\MessagePersonalizationService;
use Illuminate\Support\Facades\Log;

class AutomationWorkflowService
{
    public function executeTrigger(string $trigger, array $payload): void
    {
        $workspaceId = $payload['workspace_id'] ?? null;
        if (!$workspaceId) {
            return;
        }

        $workflows = Workflow::where('workspace_id', $workspaceId)
            ->where('trigger', $trigger)
            ->where('is_active', true)
            ->get();

        foreach ($workflows as $workflow) {
            $this->runWorkflow($workflow, $payload);
        }
    }

    private function runWorkflow(Workflow $workflow, array $payload): void
    {
        $conditions = $workflow->conditions ?? [];
        if (!$this->conditionsMatch($conditions, $payload)) {
            return;
        }

        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'contact_id' => $payload['contact_id'] ?? null,
            'trigger' => $workflow->trigger,
            'data' => $payload,
            'status' => 'running',
        ]);

        try {
            foreach ($workflow->actions as $action) {
                $this->performAction($workflow, $payload, $action);
            }

            $execution->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error("Workflow {$workflow->id} failed: {$e->getMessage()}");
            $execution->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }

    private function conditionsMatch(array $conditions, array $payload): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if (!$field || !array_key_exists($field, $payload)) {
                return false;
            }

            $actual = data_get($payload, $field);

            switch ($operator) {
                case 'equals':
                    if ($actual != $value) return false;
                    break;
                case 'contains':
                    if (!str_contains((string)$actual, (string)$value)) return false;
                    break;
                case 'not_equals':
                    if ($actual == $value) return false;
                    break;
                default:
                    return false;
            }
        }

        return true;
    }

    private function performAction(Workflow $workflow, array $payload, array $action): void
    {
        switch ($action['type'] ?? '') {
            case 'send_campaign_message':
                $this->sendCampaignMessage($workflow, $payload, $action);
                break;
            case 'tag_contact':
                $this->tagContact($workflow, $payload, $action);
                break;
            default:
                Log::warning("Unknown workflow action type: {$action['type']}");
                break;
        }
    }

    private function sendCampaignMessage(Workflow $workflow, array $payload, array $action): void
    {
        $contactId = $payload['contact_id'] ?? null;
        $campaignId = $action['campaign_id'] ?? null;

        if (!$contactId || !$campaignId) {
            throw new \Exception('send_campaign_message action requires contact_id and campaign_id');
        }

        $campaign = Campaign::find($campaignId);
        $contact = Contact::find($contactId);

        if (!$campaign || !$contact) {
            throw new \Exception('Campaign or contact not found for workflow action');
        }

        // dispatch a single send job for this contact using campaign template body
        $message = $campaign->template ? $campaign->template->body : $campaign->body;

        $personalized = (new MessagePersonalizationService())->personalize($message, $contact);

        $log = $campaign->messageLogs()->create([
            'contact_id' => $contact->id,
            'platform' => $campaign->platform,
            'final_message' => $personalized,
            'status' => 'pending',
        ]);

        SendMessageJob::dispatch($log->id)->onQueue('campaign-send');
    }

    private function tagContact(Workflow $workflow, array $payload, array $action): void
    {
        $contactId = $payload['contact_id'] ?? null;
        $tag = $action['tag'] ?? null;

        if (!$contactId || !$tag) {
            throw new \Exception('tag_contact action requires contact_id and tag');
        }

        $contact = Contact::find($contactId);
        if (!$contact) {
            throw new \Exception('Contact not found');
        }

        $tags = collect($contact->tags ?? [])->push($tag)->unique()->values()->toArray();
        $contact->update(['tags' => $tags]);
    }
}
