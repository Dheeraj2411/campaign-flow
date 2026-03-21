<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignAnalyticsController extends Controller
{
    /**
     * GET /campaigns/{campaign}/funnel
     *
     * Returns sent → delivered → read counts for a campaign,
     * built from message_status_history for accurate funnel data.
     */
    public function funnel(Request $request, int $campaign)
    {
        // Use message_status_history for funnel counts (each status triggers an INSERT)
        $funnel = DB::table('message_status_history')
            ->join('message_logs', 'message_logs.id', '=', 'message_status_history.message_log_id')
            ->where('message_logs.campaign_id', $campaign)
            ->select('message_status_history.status', DB::raw('COUNT(DISTINCT message_logs.id) as total'))
            ->groupBy('message_status_history.status')
            ->pluck('total', 'status');

        // Total messages attempted
        $totalSent = MessageLog::where('campaign_id', $campaign)->count();

        return response()->json([
            'sent'      => $totalSent,
            'delivered'  => $funnel['delivered'] ?? 0,
            'read'       => $funnel['read']      ?? 0,
            'failed'     => $funnel['failed']     ?? 0,
        ]);
    }
}
