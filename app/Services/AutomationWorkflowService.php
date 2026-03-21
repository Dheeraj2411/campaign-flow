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
    public function trigger(string $event, array $payload): void
    {
        $workspaceId = $payload['workspace_id'] ?? null;
        if (!$workspaceId) {
            return;
        }

        $workflows = Workflow::where('workspace_id', $workspaceId)
            ->where('trigger', $event)
            ->where('is_active', true)
            ->get();

        foreach ($workflows as $workflow) {
            $this->runWorkflow($workflow, $payload);
        }
    }

    public function executeTrigger(string $trigger, array $payload): void
    {
        $this->trigger($trigger, $payload);
    }

    public function runWorkflow(Workflow $workflow, array $payload, string|int $fromActionId = 0): void
    {
        if (is_array($workflow->flow_data) && !empty($workflow->flow_data['nodes'])) {
            $this->runFlowData($workflow, $payload, is_numeric($fromActionId) ? null : $fromActionId);
            return;
        }

        // --- Backwards Compatibility for Old format ---
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
            $actions = $workflow->actions ?? [];
            for ($i = (int)$fromActionId; $i < count($actions); $i++) {
                $this->performAction($workflow, $payload, $actions[$i], $i);
            }

            $execution->update(['status' => 'completed']);
        } catch (\Throwable $e) {
            Log::error("Workflow {$workflow->id} failed: {$e->getMessage()}");
            $execution->update(['status' => 'failed', 'notes' => $e->getMessage()]);
        }
    }

    public function resumeFromAction(Workflow $workflow, array $payload, string|int $fromAction): void
    {
        $this->runWorkflow($workflow, $payload, $fromAction);
    }

    private function runFlowData(Workflow $workflow, array $payload, ?string $currentNodeId = null): void
    {
        $nodes = collect($workflow->flow_data['nodes'] ?? []);
        $edges = collect($workflow->flow_data['edges'] ?? []);

        if (!$currentNodeId) {
            $triggerNode = $nodes->firstWhere('type', 'trigger');
            if (!$triggerNode) return;
            $nextNodeId = $this->getNextNodeId($triggerNode['id'], $edges);
            if (!$nextNodeId) return;
            $currentNodeId = $nextNodeId;
        }

        while ($currentNodeId) {
            $node = $nodes->firstWhere('id', $currentNodeId);
            if (!$node) break;

            if ($node['type'] === 'condition') {
                $isMatch = $this->evaluateConditionNode($node['data'], $payload);
                $handleId = $isMatch ? 'yes' : 'no';
                $currentNodeId = $this->getNextNodeId($node['id'], $edges, $handleId);

            } elseif ($node['type'] === 'action') {
                $this->executeActionNode($workflow, $payload, $node['data']);
                $currentNodeId = $this->getNextNodeId($node['id'], $edges);

            } elseif ($node['type'] === 'delay') {
                $nextNodeId = $this->getNextNodeId($node['id'], $edges);
                if ($nextNodeId) {
                    $minutes = (int)($node['data']['minutes'] ?? 5);
                    \App\Jobs\ResumeWorkflowJob::dispatch(
                        $workflow->id,
                        $payload,
                        $nextNodeId
                    )->delay(now()->addMinutes($minutes))->onQueue('low');
                }
                break; // Stop execution chain until resumed
            } else {
                $currentNodeId = $this->getNextNodeId($node['id'], $edges);
            }
        }
    }

    private function getNextNodeId(string $sourceNodeId, \Illuminate\Support\Collection $edges, ?string $sourceHandle = null): ?string
    {
        $edgeQuery = $edges->where('source', $sourceNodeId);
        if ($sourceHandle) {
            $edgeQuery = $edgeQuery->where('sourceHandle', $sourceHandle);
        }
        $edge = $edgeQuery->first();
        return $edge ? $edge['target'] : null;
    }

    private function evaluateConditionNode(array $data, array $payload): bool
    {
        $field = $data['field'] ?? null;
        $operator = $data['operator'] ?? 'is';
        $value = $data['value'] ?? null;

        if (!$field || !array_key_exists($field, $payload)) {
            return false;
        }

        $actual = data_get($payload, $field);

        switch ($operator) {
            case 'is':
            case 'equals':
                return $actual == $value;
            case 'is_not':
            case 'not_equals':
                return $actual != $value;
            case 'contains':
                return str_contains((string)$actual, (string)$value);
            default:
                return false;
        }
    }

    private function executeActionNode(Workflow $workflow, array $payload, array $data): void
    {
        $actionType = $data['actionType'] ?? '';
        $config = $data['config'] ?? [];

        switch ($actionType) {
            case 'send_message':
                $contactId = $payload['contact_id'] ?? null;
                if ($contactId && !empty($config['message_text'])) {
                    $contact = Contact::find($contactId);
                    if ($contact) {
                        $personalized = (new MessagePersonalizationService())->personalize($config['message_text'], $contact);
                        $log = \App\Models\MessageLog::create([
                            'contact_id' => $contact->id,
                            'platform' => 'whatsapp', // Default fallback
                            'final_message' => $personalized,
                            'status' => 'pending',
                        ]);
                        SendMessageJob::dispatch($log->id)->onQueue('medium');
                    }
                }
                break;
            case 'assign_conversation':
                $this->assignConversation($payload, ['user_id' => $config['user_id'] ?? null]);
                break;
            case 'change_status':
                $this->changeStatus($payload, ['status' => $config['status'] ?? 'open']);
                break;
            case 'send_template':
                $this->sendTemplate($workflow, $payload, ['template_name' => $config['template_name'] ?? '']);
                break;
            case 'webhook':
                $this->fireWebhook(['url' => $config['webhook_url'] ?? ''], $payload);
                break;
        }
    }

    // --- Legacy methods for flat structure ---
    private function conditionsMatch(array $conditions, array $payload): bool
    {
        if (empty($conditions)) return true;
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;
            if (!$field || !array_key_exists($field, $payload)) return false;
            $actual = data_get($payload, $field);
            switch ($operator) {
                case 'equals': if ($actual != $value) return false; break;
                case 'contains': if (!str_contains((string)$actual, (string)$value)) return false; break;
                case 'not_equals': if ($actual == $value) return false; break;
                default: return false;
            }
        }
        return true;
    }

    private function performAction(Workflow $workflow, array $payload, array $action, int $actionIndex = 0): void
    {
        switch ($action['type'] ?? '') {
            case 'send_campaign_message': $this->sendCampaignMessage($workflow, $payload, $action); break;
            case 'tag_contact': $this->tagContact($workflow, $payload, $action); break;
            case 'assign_conversation': $this->assignConversation($payload, $action); break;
            case 'change_status': $this->changeStatus($payload, $action); break;
            case 'delay': $this->scheduleDelay($workflow, $payload, $action, $actionIndex); return;
            case 'send_template': $this->sendTemplate($workflow, $payload, $action); break;
            case 'webhook': $this->fireWebhook($action, $payload); break;
            default: Log::warning("Unknown workflow action type: {$action['type']}"); break;
        }
    }

    private function assignConversation(array $payload, array $action): void
    {
        $conversationId = $payload['conversation_id'] ?? null;
        $userId = $action['user_id'] ?? null;
        if (!$conversationId) return;
        Conversation::where('id', $conversationId)->update(['assigned_to' => $userId]);
    }

    private function changeStatus(array $payload, array $action): void
    {
        $conversationId = $payload['conversation_id'] ?? null;
        $status = $action['status'] ?? 'open';
        if (!$conversationId) return;
        Conversation::where('id', $conversationId)->update(['status' => $status]);
    }

    private function scheduleDelay(Workflow $workflow, array $payload, array $action, int $actionIndex): void
    {
        \App\Jobs\ResumeWorkflowJob::dispatch($workflow->id, $payload, $actionIndex + 1)
            ->delay(now()->addMinutes((int)($action['minutes'] ?? 5)))
            ->onQueue('low');
    }

    private function sendTemplate(Workflow $workflow, array $payload, array $action): void
    {
        $contactId = $payload['contact_id'] ?? null;
        $workspaceId = $payload['workspace_id'] ?? null;
        if (!$contactId || !$workspaceId) throw new \Exception('send_template requires contact_id and workspace_id');
        $contact = Contact::find($contactId);
        $workspace = \App\Models\Workspace::find($workspaceId);
        if (!$contact || !$workspace) throw new \Exception('Contact or workspace not found');
        $whatsapp = new \App\Services\WhatsAppService($workspace);
        $whatsapp->sendTemplate($contact->phone, $action['template_name'] ?? '', $action['language'] ?? 'en_US');
    }

    private function fireWebhook(array $action, array $payload): void
    {
        $url = $action['url'] ?? null;
        if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
            Log::warning('Webhook action skipped: invalid URL');
            return;
        }
        try {
            \Illuminate\Support\Facades\Http::timeout(10)->post($url, $payload);
        } catch (\Exception $e) {
            Log::warning("Webhook failed for {$url}: {$e->getMessage()}");
        }
    }

    private function sendCampaignMessage(Workflow $workflow, array $payload, array $action): void
    {
        $contactId = $payload['contact_id'] ?? null;
        $campaignId = $action['campaign_id'] ?? null;
        if (!$contactId || !$campaignId) throw new \Exception('Action requires contact_id and campaign_id');
        $campaign = Campaign::find($campaignId);
        $contact = Contact::find($contactId);
        if (!$campaign || !$contact) throw new \Exception('Campaign or contact not found for workflow action');
        $message = $campaign->template ? $campaign->template->body : $campaign->body;
        $personalized = (new MessagePersonalizationService())->personalize($message, $contact);
        $log = $campaign->messageLogs()->create([
            'contact_id' => $contact->id,
            'platform' => $campaign->platform,
            'final_message' => $personalized,
            'status' => 'pending',
        ]);
        SendMessageJob::dispatch($log->id)->onQueue('medium');
    }

    private function tagContact(Workflow $workflow, array $payload, array $action): void
    {
        $contactId = $payload['contact_id'] ?? null;
        $tag = $action['tag'] ?? null;
        if (!$contactId || !$tag) throw new \Exception('tag_contact action requires contact_id and tag');
        $contact = Contact::find($contactId);
        if (!$contact) throw new \Exception('Contact not found');
        $tags = collect($contact->tags ?? [])->push($tag)->unique()->values()->toArray();
        $contact->update(['tags' => $tags]);
        $this->trigger('tag_added', [
            'workspace_id' => $contact->workspace_id,
            'contact_id' => $contact->id,
            'tag' => $tag,
        ]);
    }
}
