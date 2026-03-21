<?php

$columns = [
    'workspaces' => ['msg_per_minute', 'plan_id', 'subscription_status', 'monthly_message_limit', 'messages_sent_this_month'],
    'conversations' => ['assigned_to', 'locked_by', 'bot_active'],
    'message_logs' => ['failure_reason', 'attempt_count'],
    'workflows' => ['flow_data'],
    'message_templates' => ['message_type', 'interactive_config'],
];

foreach ($columns as $table => $cols) {
    foreach ($cols as $col) {
        try {
            Illuminate\Support\Facades\DB::select('SELECT ' . $col . ' FROM ' . $table . ' LIMIT 1');
            echo $table.'.'.$col.': OK' . "\n";
        } catch(\Exception $e) {
            echo $table.'.'.$col.': MISSING' . "\n";
        }
    }
}
