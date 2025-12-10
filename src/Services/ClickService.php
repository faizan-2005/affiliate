<?php

namespace App\Services;

use App\Models\Click;
use App\Models\Conversion;
use App\Core\Database;

class ClickService
{
    public function createClick($data)
    {
        return Click::create($data);
    }

    public function getClickStats($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->selectOne("
            SELECT 
                COUNT(*) as total_clicks,
                COUNT(DISTINCT DATE(created_at)) as active_days,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                ROUND(COUNT(CASE WHEN converted = 1 THEN 1 END) / COUNT(*) * 100, 2) as conversion_rate,
                COUNT(DISTINCT country) as countries,
                COUNT(DISTINCT device) as devices,
                COUNT(DISTINCT browser) as browsers
            FROM clicks
            WHERE affiliate_id = ? AND created_at >= ?
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function getClicksByCountry($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->select("
            SELECT 
                country,
                COUNT(*) as clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                ROUND(COUNT(CASE WHEN converted = 1 THEN 1 END) / COUNT(*) * 100, 2) as conversion_rate
            FROM clicks
            WHERE affiliate_id = ? AND created_at >= ?
            GROUP BY country
            ORDER BY clicks DESC
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function getClicksByOffer($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->select("
            SELECT 
                c.offer_id,
                o.name as offer_name,
                COUNT(*) as clicks,
                COUNT(CASE WHEN c.converted = 1 THEN 1 END) as conversions,
                ROUND(COUNT(CASE WHEN c.converted = 1 THEN 1 END) / COUNT(*) * 100, 2) as conversion_rate,
                SUM(conv.payout) as earnings
            FROM clicks c
            JOIN offers o ON c.offer_id = o.id
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            WHERE c.affiliate_id = ? AND c.created_at >= ?
            GROUP BY c.offer_id
            ORDER BY clicks DESC
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function getDailyClickStats($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->select("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                ROUND(COUNT(CASE WHEN converted = 1 THEN 1 END) / COUNT(*) * 100, 2) as conversion_rate
            FROM clicks
            WHERE affiliate_id = ? AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function archiveOldClicks($daysThreshold = 30)
    {
        $db = Database::getInstance();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysThreshold} days"));

        // Copy old clicks to archive
        $archived = $db->raw("
            INSERT INTO clicks_archive 
            SELECT * FROM clicks 
            WHERE created_at < ? AND id NOT IN (SELECT id FROM clicks_archive)
        ", [$cutoffDate])->rowCount();

        // Delete old clicks
        $deleted = $db->delete('clicks', 'created_at < ?', [$cutoffDate]);

        log_info("Archived {$archived} clicks, deleted {$deleted} old click records");

        return [
            'archived' => $archived,
            'deleted' => $deleted
        ];
    }
}

class ConversionService
{
    public function createConversion($data)
    {
        return Conversion::create($data);
    }

    public function getConversionStats($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->selectOne("
            SELECT 
                COUNT(*) as total_conversions,
                SUM(payout) as total_payout,
                SUM(revenue) as total_revenue,
                AVG(payout) as avg_payout,
                COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_conversions,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_conversions,
                COUNT(CASE WHEN duplicate_detected = 1 THEN 1 END) as duplicate_conversions
            FROM conversions
            WHERE affiliate_id = ? AND created_at >= ?
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function getConversionsByOffer($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->select("
            SELECT 
                c.offer_id,
                o.name as offer_name,
                COUNT(*) as conversions,
                SUM(c.payout) as payout,
                SUM(c.revenue) as revenue,
                COUNT(CASE WHEN c.status = 'confirmed' THEN 1 END) as confirmed
            FROM conversions c
            JOIN offers o ON c.offer_id = o.id
            WHERE c.affiliate_id = ? AND c.created_at >= ?
            GROUP BY c.offer_id
            ORDER BY conversions DESC
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function getDailyConversionStats($affiliateId, $days = 30)
    {
        $db = Database::getInstance();
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $db->select("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as conversions,
                SUM(payout) as payout,
                SUM(revenue) as revenue
            FROM conversions
            WHERE affiliate_id = ? AND created_at >= ?
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)
        ", [$affiliateId, $startDate . ' 00:00:00']);
    }

    public function confirmConversion($conversionId)
    {
        $conversion = Conversion::find($conversionId);
        if ($conversion) {
            $conversion->status = 'confirmed';
            $conversion->save();
            return true;
        }
        return false;
    }

    public function rejectConversion($conversionId)
    {
        $conversion = Conversion::find($conversionId);
        if ($conversion) {
            $conversion->status = 'rejected';
            $conversion->save();
            return true;
        }
        return false;
    }
}

class PostbackService
{
    public function sendPostback($conversion)
    {
        $offer = $conversion->offer();
        $advertiser = $offer->advertiser();

        if (empty($advertiser->postback_url)) {
            return false;
        }

        $params = [
            'click_id' => $conversion->click_id,
            'transaction_id' => $conversion->transaction_id,
            'payout' => $conversion->payout,
            'revenue' => $conversion->revenue,
            'status' => $conversion->status
        ];

        // Add signature
        $params['sig'] = generate_signature($params, $advertiser->api_secret);

        // Build postback URL
        $method = $advertiser->postback_method ?: 'post';
        $url = $advertiser->postback_url;

        if (strtolower($method) === 'get') {
            $url .= (strpos($url, '?') ? '&' : '?') . http_build_query($params);
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => config('app.postback.timeout', 30),
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'User-Agent: Affiliate-Platform/1.0'
            ]
        ]);

        if (strtolower($method) === 'post') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_info("Postback sent to advertiser {$advertiser->id}", [
            'conversion_id' => $conversion->id,
            'http_code' => $httpCode
        ]);

        return $httpCode >= 200 && $httpCode < 300;
    }
}

class FraudService
{
    public function checkClick($clickData)
    {
        $result = [
            'is_fraud' => false,
            'type' => null,
            'severity' => 'low',
            'reason' => null,
            'details' => []
        ];

        // Check 1: Duplicate click detection
        if ($this->isDuplicateClick($clickData)) {
            $result['is_fraud'] = true;
            $result['type'] = 'duplicate_click';
            $result['severity'] = 'medium';
            $result['reason'] = 'Duplicate click detected';
            $result['details']['duplicate_count'] = $this->getClickCountInWindow($clickData);
        }

        // Check 2: Fast clicks from same IP
        if ($this->hasFastClicks($clickData)) {
            $result['is_fraud'] = true;
            $result['type'] = 'fast_clicks';
            $result['severity'] = 'high';
            $result['reason'] = 'Multiple clicks from same IP in short time';
            $result['details']['click_count'] = $this->getClickCountInWindow($clickData, 60);
        }

        // Check 3: Bot detection
        if ($this->isBotUserAgent($clickData['user_agent'] ?? '')) {
            $result['is_fraud'] = true;
            $result['type'] = 'bot_traffic';
            $result['severity'] = 'high';
            $result['reason'] = 'Bot user agent detected';
        }

        // Check 4: IP blacklist
        if ($this->isBlacklistedIP($clickData['ip'])) {
            $result['is_fraud'] = true;
            $result['type'] = 'blacklisted_ip';
            $result['severity'] = 'critical';
            $result['reason'] = 'IP address is blacklisted';
        }

        // Check 5: Geo/Device targeting mismatch
        if ($this->hasTargetingMismatch($clickData)) {
            $result['is_fraud'] = true;
            $result['type'] = 'targeting_mismatch';
            $result['severity'] = 'low';
            $result['reason'] = 'Traffic does not match offer targeting';
        }

        return $result;
    }

    protected function isDuplicateClick($clickData)
    {
        $db = Database::getInstance();
        $count = $db->selectOne("
            SELECT COUNT(*) as count FROM clicks
            WHERE ua_hash = ? AND ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            LIMIT " . config('app.fraud.duplicate_threshold', 3)
        ", [$clickData['ua_hash'] ?? '', $clickData['ip']]);

        return ($count['count'] ?? 0) >= config('app.fraud.duplicate_threshold', 3);
    }

    protected function hasFastClicks($clickData)
    {
        $db = Database::getInstance();
        $count = $db->selectOne("
            SELECT COUNT(*) as count FROM clicks
            WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
        ", [$clickData['ip']]);

        return ($count['count'] ?? 0) > 5;
    }

    protected function isBotUserAgent($userAgent)
    {
        return is_bot_user_agent($userAgent);
    }

    protected function isBlacklistedIP($ip)
    {
        $db = Database::getInstance();
        $blacklisted = $db->selectOne("
            SELECT id FROM ip_blacklist
            WHERE ip_address = ? AND (permanent = 1 OR expires_at > NOW())
        ", [$ip]);

        return !empty($blacklisted);
    }

    protected function hasTargetingMismatch($clickData)
    {
        $offer = \App\Models\Offer::find($clickData['offer_id']);
        if (!$offer) {
            return false;
        }

        $targeting = $offer->targeting();
        if (!$targeting) {
            return false;
        }

        // Check country targeting
        $allowedCountries = $targeting->getCountries();
        if (!empty($allowedCountries) && !in_array($clickData['country'], $allowedCountries)) {
            return true;
        }

        return false;
    }

    protected function getClickCountInWindow($clickData, $minutes = 60)
    {
        $db = Database::getInstance();
        $count = $db->selectOne("
            SELECT COUNT(*) as count FROM clicks
            WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ", [$clickData['ip'], $minutes]);

        return $count['count'] ?? 0;
    }
}

class AttributionService
{
    public function getAttributionPath($conversion)
    {
        // Get all clicks from this user/session
        $db = Database::getInstance();
        $click = $conversion->click();

        $clicks = $db->select("
            SELECT id, click_id, created_at FROM clicks
            WHERE session_id = ? OR ua_hash = ?
            ORDER BY created_at ASC
        ", [$click->session_id, $click->ua_hash]);

        return [
            'click_ids' => array_column($clicks, 'click_id'),
            'first_touch' => $clicks[0]['click_id'] ?? null,
            'last_touch' => $clicks[count($clicks) - 1]['click_id'] ?? null,
            'total_clicks' => count($clicks)
        ];
    }

    public function calculateAttribution($conversion, $model = 'last_click')
    {
        $path = $this->getAttributionPath($conversion);
        
        switch ($model) {
            case 'first_click':
                return $path['first_touch'];
            case 'linear':
                // Split payout equally among all clicks
                return null;
            case 'time_decay':
                // Recent clicks get more weight
                return null;
            case 'last_click':
            default:
                return $path['last_touch'];
        }
    }
}
