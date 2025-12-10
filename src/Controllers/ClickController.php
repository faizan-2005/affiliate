<?php

namespace App\Controllers;

use App\Models\Click;
use App\Models\Offer;
use App\Models\Affiliate;
use App\Services\ClickService;
use App\Services\FraudService;

class ClickController extends Controller
{
    protected $clickService;
    protected $fraudService;

    public function __construct()
    {
        parent::__construct();
        $this->clickService = new ClickService();
        $this->fraudService = new FraudService();
    }

    /**
     * Track a click from affiliate link
     * URL: /click?offer_id=1&aff_id=2&click_id=xxx&sub1=test&sig=xxx
     */
    public function track()
    {
        try {
            $offerId = (int) $this->request->get('offer_id');
            $affId = (int) $this->request->get('aff_id');
            $clickId = $this->request->get('click_id', generate_click_id());
            $sig = $this->request->get('sig');
            $smartlinkId = (int) $this->request->get('slid', 0);
            $ruleId = (int) $this->request->get('rule_id', 0);

            // Validate offer and affiliate exist
            $offer = Offer::find($offerId);
            $affiliate = Affiliate::find($affId);

            if (!$offer || !$affiliate) {
                log_error('Invalid offer or affiliate in click tracking', [
                    'offer_id' => $offerId,
                    'affiliate_id' => $affId
                ]);
                return $this->json(['error' => 'Invalid offer or affiliate'], 404);
            }

            // Verify signature if required
            if ($sig && !$this->verifyClickSignature($clickId, $offerId, $affId, $sig)) {
                log_fraud('Invalid click signature', [
                    'click_id' => $clickId,
                    'offer_id' => $offerId
                ]);
                return $this->json(['error' => 'Invalid signature'], 403);
            }

            // Check fraud before processing
            $fraudCheck = $this->fraudService->checkClick([
                'click_id' => $clickId,
                'offer_id' => $offerId,
                'affiliate_id' => $affId,
                'ip' => $this->request->ip(),
                'user_agent' => $this->request->userAgent(),
                'country' => get_country_from_ip($this->request->ip())
            ]);

            if ($fraudCheck['is_fraud']) {
                log_fraud('Fraud detected on click', [
                    'click_id' => $clickId,
                    'reason' => $fraudCheck['reason'],
                    'severity' => $fraudCheck['severity']
                ]);

                // Create fraud log
                \App\Models\FraudLog::create([
                    'click_id' => $clickId,
                    'offer_id' => $offerId,
                    'affiliate_id' => $affId,
                    'fraud_type' => $fraudCheck['type'],
                    'severity' => $fraudCheck['severity'],
                    'description' => $fraudCheck['reason'],
                    'data' => json_encode($fraudCheck['details']),
                    'ip' => $this->request->ip(),
                    'user_agent_hash' => hash_user_agent($this->request->userAgent())
                ]);
            }

            // Create click record
            $clickData = [
                'offer_id' => $offerId,
                'affiliate_id' => $affId,
                'smartlink_id' => $smartlinkId ?: null,
                'click_id' => $clickId,
                'session_id' => $this->request->get('session_id', generate_session_id()),
                'ip' => $this->request->ip(),
                'device' => $this->request->get('device', 'unknown'),
                'os' => $this->request->get('os', 'unknown'),
                'os_version' => $this->request->get('os_version'),
                'browser' => $this->request->get('browser', 'unknown'),
                'browser_version' => $this->request->get('browser_version'),
                'country' => get_country_from_ip($this->request->ip()),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? null,
                'ua_hash' => hash_user_agent($this->request->userAgent()),
                'user_agent' => $this->request->userAgent(),
                'sub1' => $this->request->get('sub1'),
                'sub2' => $this->request->get('sub2'),
                'sub3' => $this->request->get('sub3'),
                'sub4' => $this->request->get('sub4'),
                'sub5' => $this->request->get('sub5'),
                'source' => $this->request->get('source'),
                'domain' => $this->request->get('domain'),
                'channel' => $this->request->get('channel'),
                'placement' => $this->request->get('placement'),
                'creative_id' => $this->request->get('creative_id'),
                'campaign_id' => $this->request->get('campaign_id'),
                'deeplink' => $this->request->get('deeplink'),
                'rule_id' => $ruleId ?: null,
                'force_geo' => $this->request->get('force_geo'),
                'force_device' => $this->request->get('force_device'),
                'force_os' => $this->request->get('force_os'),
                'sig' => $sig,
                'created_at' => date('Y-m-d H:i:s')
            ];

            $click = Click::create($clickData);

            // Log the click
            log_info('Click tracked', [
                'click_id' => $click->click_id,
                'offer_id' => $offerId,
                'affiliate_id' => $affId,
                'ip' => $this->request->ip()
            ]);

            // Update affiliate stats
            $affiliate->total_clicks++;
            $affiliate->save();

            // Queue fraud checking if enabled
            if (config('app.fraud.enabled')) {
                queue()->push('FraudCheckJob', [
                    'click_id' => $click->id,
                    'click_uuid' => $clickId
                ]);
            }

            // Return tracking pixel or redirect
            if ($this->request->get('pixel')) {
                return $this->responsePixel();
            }

            // Redirect to landing page
            return $this->response->redirect($offer->landing_page_url);

        } catch (\Exception $e) {
            log_error('Click tracking error: ' . $e->getMessage());
            return $this->json(['error' => 'Tracking error'], 500);
        }
    }

    /**
     * Return 1x1 tracking pixel
     */
    protected function responsePixel()
    {
        header('Content-Type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        exit;
    }

    /**
     * Verify click signature
     */
    protected function verifyClickSignature($clickId, $offerId, $affId, $sig)
    {
        $affiliate = Affiliate::find($affId);
        if (!$affiliate || !$affiliate->api_secret) {
            return false;
        }

        $data = [
            'click_id' => $clickId,
            'offer_id' => $offerId,
            'affiliate_id' => $affId
        ];

        return verify_signature($data, $sig, $affiliate->api_secret);
    }
}
