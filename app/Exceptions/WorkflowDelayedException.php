<?php

namespace App\Exceptions;

/**
 * Thrown when a workflow action triggers a delay.
 * Signals the workflow runner to stop executing further actions;
 * remaining actions will resume via ResumeWorkflowJob.
 */
class WorkflowDelayedException extends \RuntimeException
{
}
