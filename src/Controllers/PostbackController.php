<?php

namespace App\Controllers;

use App\Models\Click;
use App\Models\Conversion;
use App\Models\Advertiser;
use App\Models\AdvertiserIpWhitelist;
use App\Services\PostbackService;
use App\Services\ConversionService;

class PostbackController extends Controller
{
    protected $postbackService;
    protected $conversionService;

    public function __construct()
    {
        parent::__construct();
        $this->postbackService = new PostbackService();
        $this->conversionService = new ConversionService();
    }

    /**
     * Handle postback from advertiser
     * URL: /postback?click_id=xxx&transaction_id=yyy&payout=10&revenue=50&sig=xxx
     */
    public function handle()
    {
        try {
            $clickId = $this->request->input('click_id');
            $transactionId = $this->request->input('transaction_id');
            $payout = (float) $this->request->input('payout', 0);
            $revenue = (float) $this->request->input('revenue', 0);
            $sig = $this->request->input('sig');

            // Find click
            $click = Click::where('click_id', '=', $clickId)->first();

            if (!$click) {
                log_error('Postback: Click not found', [
                    'click_id' => $clickId,
                    'ip' => $this->request->ip()
                ]);

                return $this->logPostback(
                    null,
                    null,
                    'failed',
                    'Click not found',
                    $this->request->input(),
                    400,
                    'Click not found'
                );
            }

            // Get advertiser
            $offer = $click->offer();
            $advertiser = $offer->advertiser();

            // Verify IP whitelist
            if (!$this->isIPWhitelisted($advertiser->id, $this->request->ip())) {
                log_fraud('Postback from non-whitelisted IP', [
                    'advertiser_id' => $advertiser->id,
                    'ip' => $this->request->ip(),
                    'click_id' => $clickId
                ]);

                return $this->logPostback(
                    null,
                    $advertiser->id,
                    'rejected',
                    'IP not whitelisted',
                    $this->request->input(),
                    403,
                    'IP not whitelisted'
                );
            }

            // Verify signature
            if (!$this->verifyPostbackSignature($advertiser, $clickId, $transactionId, $sig)) {
                log_fraud('Invalid postback signature', [
                    'advertiser_id' => $advertiser->id,
                    'click_id' => $clickId
                ]);

                return $this->logPostback(
                    null,
                    $advertiser->id,
                    'rejected',
                    'Invalid signature',
                    $this->request->input(),
                    403,
                    'Invalid signature'
                );
            }

            // Check for duplicate conversion
            $existingConversion = Conversion::where('click_id', '=', $clickId)
                ->where('transaction_id', '=', $transactionId)
                ->first();

            if ($existingConversion) {
                log_info('Duplicate conversion detected', [
                    'click_id' => $clickId,
                    'transaction_id' => $transactionId
                ]);

                return $this->logPostback(
                    $existingConversion->id,
                    $advertiser->id,
                    'duplicate',
                    'Duplicate conversion',
                    $this->request->input(),
                    200,
                    null,
                    true
                );
            }

            // Create conversion
            $conversion = Conversion::create([
                'click_id' => $clickId,
                'offer_id' => $click->offer_id,
                'affiliate_id' => $click->affiliate_id,
                'advertiser_id' => $advertiser->id,
                'advertiser_ref_id' => $this->request->input('advertiser_ref_id'),
                'transaction_id' => $transactionId,
                'payout' => $payout,
                'revenue' => $revenue,
                'advertiser_payload' => json_encode($this->request->input()),
                'status' => 'pending',
                'source' => 'postback',
                'duplicate_detected' => false
            ]);

            // Update click as converted
            $click->converted = 1;
            $click->conversion_id = $conversion->id;
            $click->save();

            // Queue postback confirmation
            if (!empty($advertiser->postback_url)) {
                queue()->push('PostbackConfirmJob', [
                    'conversion_id' => $conversion->id
                ]);
            }

            // Log successful postback
            log_info('Postback processed', [
                'click_id' => $clickId,
                'conversion_id' => $conversion->id,
                'payout' => $payout
            ]);

            return $this->logPostback(
                $conversion->id,
                $advertiser->id,
                'success',
                'Conversion created',
                $this->request->input(),
                200,
                null,
                false
            );

        } catch (\Exception $e) {
            log_error('Postback processing error: ' . $e->getMessage());
            return $this->json(['error' => 'Postback processing error'], 500);
        }
    }

    /**
     * Check if IP is whitelisted for advertiser
     */
    protected function isIPWhitelisted($advertiserId, $ip)
    {
        // If no whitelist entries, allow all
        $whitelist = AdvertiserIpWhitelist::where('advertiser_id', '=', $advertiserId)
            ->where('active', '=', 1)
            ->get();

        if (empty($whitelist)) {
            return true;
        }

        foreach ($whitelist as $entry) {
            if ($entry->ip_address === $ip) {
                return true;
            }

            // Check IP range
            if ($entry->ip_range_start && $entry->ip_range_end) {
                if ($this->isIPInRange($ip, $entry->ip_range_start, $entry->ip_range_end)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if IP is in range
     */
    protected function isIPInRange($ip, $start, $end)
    {
        $ipLong = ip2long($ip);
        $startLong = ip2long($start);
        $endLong = ip2long($end);

        return $ipLong >= $startLong && $ipLong <= $endLong;
    }

    /**
     * Verify postback signature
     */
    protected function verifyPostbackSignature($advertiser, $clickId, $transactionId, $sig)
    {
        if (!$advertiser->api_secret) {
            return false;
        }

        $data = [
            'click_id' => $clickId,
            'transaction_id' => $transactionId
        ];

        return verify_signature($data, $sig, $advertiser->api_secret);
    }

    /**
     * Log postback attempt
     */
    protected function logPostback(
        $conversionId,
        $advertiserId,
        $status,
        $message,
        $requestParams,
        $responseStatus,
        $responseBody = null,
        $isDuplicate = false
    ) {
        \App\Models\PostbackLog::create([
            'conversion_id' => $conversionId,
            'advertiser_id' => $advertiserId,
            'click_id' => $this->request->input('click_id'),
            'transaction_id' => $this->request->input('transaction_id'),
            'postback_url' => '',
            'request_params' => json_encode($requestParams),
            'response_status' => $responseStatus,
            'response_body' => $responseBody,
            'ip_verified' => 1,
            'ip_address' => $this->request->ip(),
            'status' => $status,
            'error_message' => $message
        ]);

        return $this->json([
            'success' => in_array($status, ['success', 'pending']),
            'message' => $message,
            'conversion_id' => $conversionId,
            'duplicate' => $isDuplicate
        ], $responseStatus);
    }
}
