<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Models\Workspace;
use App\Services\AutomationWorkflowService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResumeWorkflowJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $workflowId,
        public array $payload,
        public int $fromActionIndex
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $workflow = Workflow::find($this->workflowId);
        $workspace = Workspace::find($this->payload['workspace_id'] ?? null);

        if (!$workflow || !$workspace) {
            return;
        }

        \App\Tenancy\TenantContext::setWorkspace($workspace);

        try {
            $service = new AutomationWorkflowService();
            $service->runWorkflow($workflow, $this->payload, $this->fromActionIndex);
        } finally {
            \App\Tenancy\TenantContext::setWorkspace(null);
        }
    }
}
