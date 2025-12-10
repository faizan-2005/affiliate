<?php

namespace App\Jobs;

use App\Models\FraudLog;
use App\Models\Click;
use App\Services\FraudService;

class FraudCheckJob
{
    protected $clickId;
    protected $fraudService;

    public function __construct($data)
    {
        $this->clickId = $data['click_uuid'] ?? null;
        $this->fraudService = new FraudService();
    }

    public function handle()
    {
        $click = Click::where('click_id', '=', $this->clickId)->first();

        if (!$click) {
            log_error("Click not found for fraud check: {$this->clickId}");
            return;
        }

        $fraudCheck = $this->fraudService->checkClick([
            'click_id' => $click->click_id,
            'offer_id' => $click->offer_id,
            'affiliate_id' => $click->affiliate_id,
            'ip' => $click->ip,
            'user_agent' => $click->user_agent,
            'country' => $click->country,
            'ua_hash' => $click->ua_hash
        ]);

        if ($fraudCheck['is_fraud']) {
            FraudLog::create([
                'click_id' => $click->click_id,
                'offer_id' => $click->offer_id,
                'affiliate_id' => $click->affiliate_id,
                'fraud_type' => $fraudCheck['type'],
                'severity' => $fraudCheck['severity'],
                'description' => $fraudCheck['reason'],
                'data' => json_encode($fraudCheck['details']),
                'ip' => $click->ip,
                'user_agent_hash' => $click->ua_hash,
                'blacklisted' => in_array($fraudCheck['type'], ['bot_traffic', 'blacklisted_ip']) ? 1 : 0
            ]);

            log_fraud("Fraud detected in background job", [
                'click_id' => $click->click_id,
                'type' => $fraudCheck['type'],
                'severity' => $fraudCheck['severity']
            ]);
        }
    }
}

class StatsRollupJob
{
    public function handle()
    {
        $db = \App\Core\Database::getInstance();

        // Get stats from last day
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $stats = $db->select("
            SELECT 
                DATE(c.created_at) as stat_date,
                c.offer_id,
                c.affiliate_id,
                o.advertiser_id,
                c.country,
                c.device,
                COUNT(*) as clicks,
                COUNT(CASE WHEN c.converted = 1 THEN 1 END) as conversions,
                SUM(COALESCE(conv.revenue, 0)) as revenue,
                SUM(COALESCE(conv.payout, 0)) as payout
            FROM clicks c
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            LEFT JOIN offers o ON c.offer_id = o.id
            WHERE DATE(c.created_at) = ?
            GROUP BY DATE(c.created_at), c.offer_id, c.affiliate_id, c.country, c.device
        ", [$yesterday]);

        foreach ($stats as $stat) {
            $existing = $db->selectOne("
                SELECT id FROM daily_stats
                WHERE stat_date = ? AND offer_id = ? AND affiliate_id = ? AND country = ? AND device = ?
            ", [$stat['stat_date'], $stat['offer_id'], $stat['affiliate_id'], $stat['country'], $stat['device']]);

            if ($existing) {
                $db->update('daily_stats', [
                    'clicks' => $stat['clicks'],
                    'conversions' => $stat['conversions'],
                    'revenue' => $stat['revenue'],
                    'payout' => $stat['payout']
                ], 'id = ?', [$existing['id']]);
            } else {
                $db->insert('daily_stats', $stat);
            }
        }

        log_info("Stats rollup completed for {$yesterday}");
    }
}

class ArchiveClicksJob
{
    public function handle()
    {
        $db = \App\Core\Database::getInstance();
        $threshold = config('app.storage.hot_days', 30);

        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$threshold} days"));

        // Copy to archive
        $db->raw("
            INSERT INTO clicks_archive 
            SELECT * FROM clicks 
            WHERE created_at < ? AND id NOT IN (SELECT id FROM clicks_archive)
        ", [$cutoffDate]);

        // Delete old clicks (optional - only delete if you want to save space)
        // $db->delete('clicks', 'created_at < ?', [$cutoffDate]);

        log_info("Click archival completed");
    }
}
