<?php

namespace App\Models;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = ['name', 'email', 'phone', 'password', 'role', 'status'];

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);
    }

    public function verifyPassword($password)
    {
        return password_verify($password, $this->attributes['password']);
    }

    public function affiliate()
    {
        return Affiliate::where('user_id', '=', $this->id)->first();
    }

    public function advertiser()
    {
        return Advertiser::where('user_id', '=', $this->id)->first();
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isAffiliate()
    {
        return $this->role === 'affiliate';
    }

    public function isAdvertiser()
    {
        return $this->role === 'advertiser';
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}

class Affiliate extends Model
{
    protected $table = 'affiliates';
    protected $fillable = [
        'user_id', 'company_name', 'website', 'payout_method', 'payout_email',
        'paypal_email', 'api_key', 'api_secret', 'approval_status'
    ];

    public function user()
    {
        return User::find($this->user_id);
    }

    public function clicks()
    {
        return Click::where('affiliate_id', '=', $this->id)->get();
    }

    public function conversions()
    {
        return Conversion::where('affiliate_id', '=', $this->id)->get();
    }

    public function payouts()
    {
        return Payout::where('affiliate_id', '=', $this->id)->get();
    }

    public function smartlinks()
    {
        return Smartlink::where('affiliate_id', '=', $this->id)->get();
    }

    public function getTodayStats()
    {
        $db = \App\Core\Database::getInstance();
        $today = date('Y-m-d');
        return $db->selectOne("
            SELECT 
                COUNT(DISTINCT c.id) as clicks,
                COUNT(DISTINCT conv.id) as conversions,
                SUM(conv.payout) as earnings
            FROM clicks c
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            WHERE c.affiliate_id = ? AND DATE(c.created_at) = ?
        ", [$this->id, $today]);
    }
}

class Advertiser extends Model
{
    protected $table = 'advertisers';
    protected $fillable = [
        'user_id', 'company_name', 'website', 'contact_person', 'postback_url',
        'postback_method', 'api_key', 'api_secret'
    ];

    public function user()
    {
        return User::find($this->user_id);
    }

    public function offers()
    {
        return Offer::where('advertiser_id', '=', $this->id)->get();
    }

    public function conversions()
    {
        return Conversion::where('advertiser_id', '=', $this->id)->get();
    }

    public function ipWhitelist()
    {
        return AdvertiserIpWhitelist::where('advertiser_id', '=', $this->id)->get();
    }

    public function getStats()
    {
        $db = \App\Core\Database::getInstance();
        return $db->selectOne("
            SELECT 
                COUNT(DISTINCT conv.id) as conversions,
                SUM(conv.revenue) as revenue,
                SUM(conv.payout) as payout
            FROM conversions conv
            WHERE conv.advertiser_id = ?
        ", [$this->id]);
    }
}

class Offer extends Model
{
    protected $table = 'offers';
    protected $fillable = [
        'advertiser_id', 'name', 'description', 'landing_page_url',
        'payout_type', 'payout_value', 'revenue_type', 'revenue_value',
        'offer_category', 'status', 'preview_url', 'tracking_domain'
    ];

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }

    public function targeting()
    {
        return OfferTargeting::where('offer_id', '=', $this->id)->first();
    }

    public function caps()
    {
        return OfferCap::where('offer_id', '=', $this->id)->get();
    }

    public function clicks()
    {
        return Click::where('offer_id', '=', $this->id)->get();
    }

    public function conversions()
    {
        return Conversion::where('offer_id', '=', $this->id)->get();
    }

    public function smartlinks()
    {
        return Smartlink::where('offer_id', '=', $this->id)->get();
    }
}

class OfferTargeting extends Model
{
    protected $table = 'offer_targeting';
    protected $fillable = [
        'offer_id', 'geo_countries', 'blocked_countries', 'device_types',
        'os_types', 'os_versions', 'browsers', 'carriers', 'connection_types',
        'min_os_version'
    ];

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function getCountries()
    {
        return json_decode($this->geo_countries, true) ?? [];
    }

    public function getDevices()
    {
        return json_decode($this->device_types, true) ?? [];
    }

    public function getOSTypes()
    {
        return json_decode($this->os_types, true) ?? [];
    }
}

class OfferCap extends Model
{
    protected $table = 'offer_caps';
    protected $fillable = ['offer_id', 'cap_type', 'cap_value', 'cap_date', 'current_count'];

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function isReached()
    {
        return $this->current_count >= $this->cap_value;
    }

    public function increment()
    {
        $this->current_count++;
        return $this->save();
    }
}

class Click extends Model
{
    protected $table = 'clicks';
    protected $fillable = [
        'offer_id', 'affiliate_id', 'smartlink_id', 'click_id', 'session_id',
        'ip', 'device', 'os', 'os_version', 'browser', 'browser_version',
        'country', 'referrer', 'ua_hash', 'user_agent', 'converted',
        'conversion_id', 'sub1', 'sub2', 'sub3', 'sub4', 'sub5',
        'source', 'domain', 'channel', 'placement', 'creative_id',
        'campaign_id', 'deeplink', 'rule_id', 'force_geo', 'force_device',
        'force_os', 'sig', 'created_at'
    ];

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function conversion()
    {
        return Conversion::where('click_id', '=', $this->click_id)->first();
    }

    public function smartlink()
    {
        return Smartlink::find($this->smartlink_id);
    }

    public function getTrackingUrl($baseUrl)
    {
        $params = [
            'offer_id' => $this->offer_id,
            'aff_id' => $this->affiliate_id,
            'click_id' => $this->click_id,
            'sub1' => $this->sub1,
            'sub2' => $this->sub2,
            'sub3' => $this->sub3,
            'sub4' => $this->sub4,
            'sub5' => $this->sub5,
        ];

        return $baseUrl . '?' . http_build_query(array_filter($params));
    }
}

class Conversion extends Model
{
    protected $table = 'conversions';
    protected $fillable = [
        'click_id', 'offer_id', 'affiliate_id', 'advertiser_id',
        'advertiser_ref_id', 'transaction_id', 'payout', 'revenue',
        'advertiser_payload', 'status', 'source', 'duplicate_detected'
    ];

    public function click()
    {
        return Click::where('click_id', '=', $this->click_id)->first();
    }

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }

    public function attributionPath()
    {
        return AttributionPath::where('conversion_id', '=', $this->id)->first();
    }

    public function isConfirmed()
    {
        return $this->status === 'confirmed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }
}

class Smartlink extends Model
{
    protected $table = 'smartlinks';
    protected $fillable = [
        'advertiser_id', 'affiliate_id', 'name', 'url_slug', 'description',
        'redirect_mode', 'status', 'total_clicks'
    ];

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function rules()
    {
        return SmartlinkRule::where('smartlink_id', '=', $this->id)->get();
    }

    public function getTrackingUrl($baseUrl = null)
    {
        $baseUrl = $baseUrl ?: config('app.url');
        return $baseUrl . '/sl/' . $this->url_slug;
    }
}

class SmartlinkRule extends Model
{
    protected $table = 'smartlink_rules';
    protected $fillable = [
        'smartlink_id', 'offer_id', 'rule_order', 'matching_condition',
        'weight', 'geo_match', 'device_match', 'os_match'
    ];

    public function smartlink()
    {
        return Smartlink::find($this->smartlink_id);
    }

    public function offer()
    {
        return Offer::find($this->offer_id);
    }
}

class Payout extends Model
{
    protected $table = 'payouts';
    protected $fillable = [
        'affiliate_id', 'amount', 'method', 'status', 'reference_number', 'notes'
    ];

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }
}

class FraudLog extends Model
{
    protected $table = 'fraud_logs';
    protected $fillable = [
        'click_id', 'offer_id', 'affiliate_id', 'fraud_type', 'severity',
        'description', 'data', 'ip', 'user_agent_hash', 'blacklisted',
        'action_taken', 'reviewed', 'reviewed_by'
    ];

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function click()
    {
        return Click::where('click_id', '=', $this->click_id)->first();
    }
}

class PostbackLog extends Model
{
    protected $table = 'postback_logs';
    protected $fillable = [
        'conversion_id', 'advertiser_id', 'click_id', 'transaction_id',
        'postback_url', 'request_params', 'response_status', 'response_body',
        'ip_verified', 'ip_address', 'status', 'retry_count', 'error_message'
    ];

    public function conversion()
    {
        return Conversion::find($this->conversion_id);
    }

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }
}

class AttributionPath extends Model
{
    protected $table = 'attribution_paths';
    protected $fillable = [
        'conversion_id', 'user_fingerprint', 'click_ids', 'weights',
        'last_touch_click_id', 'attribution_model'
    ];

    public function conversion()
    {
        return Conversion::find($this->conversion_id);
    }

    public function getClickIds()
    {
        return json_decode($this->click_ids, true) ?? [];
    }

    public function getWeights()
    {
        return json_decode($this->weights, true) ?? [];
    }
}

class DailyStats extends Model
{
    protected $table = 'daily_stats';
    protected $fillable = [
        'stat_date', 'offer_id', 'affiliate_id', 'advertiser_id',
        'country', 'device', 'clicks', 'conversions', 'revenue', 'payout'
    ];

    public function offer()
    {
        return Offer::find($this->offer_id);
    }

    public function affiliate()
    {
        return Affiliate::find($this->affiliate_id);
    }

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }
}

class AdvertiserIpWhitelist extends Model
{
    protected $table = 'advertiser_ip_whitelist';
    protected $fillable = [
        'advertiser_id', 'ip_address', 'ip_range_start', 'ip_range_end',
        'description', 'active'
    ];

    public function advertiser()
    {
        return Advertiser::find($this->advertiser_id);
    }
}
