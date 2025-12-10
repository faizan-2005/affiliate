<?php

namespace App\Controllers;

use App\Models\Affiliate;
use App\Models\Click;
use App\Models\Conversion;

class DashboardController extends Controller
{
    public function index()
    {
        if (!auth()->check()) {
            return $this->redirect('/login');
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->redirect('/admin/dashboard');
        } elseif ($user->isAffiliate()) {
            return $this->redirect('/affiliate/dashboard');
        } elseif ($user->isAdvertiser()) {
            return $this->redirect('/advertiser/dashboard');
        }

        return $this->redirect('/login');
    }
}

class AffiliateController extends Controller
{
    public function dashboard()
    {
        $this->requireAuth();

        if (!auth()->isAffiliate()) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $affiliate = auth()->user()->affiliate();
        
        // Get stats
        $todayStats = $affiliate->getTodayStats();
        $monthlyStats = \App\Core\Database::getInstance()->selectOne("
            SELECT 
                COUNT(DISTINCT c.id) as clicks,
                COUNT(DISTINCT conv.id) as conversions,
                SUM(conv.payout) as earnings
            FROM clicks c
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            WHERE c.affiliate_id = ? AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$affiliate->id]);

        return $this->view('affiliate/dashboard', [
            'affiliate' => $affiliate,
            'todayStats' => $todayStats,
            'monthlyStats' => $monthlyStats
        ]);
    }

    public function offers()
    {
        $this->requireAuth();
        
        $db = \App\Core\Database::getInstance();
        $offers = $db->select("
            SELECT DISTINCT o.* 
            FROM offers o
            WHERE o.status = 'active'
            ORDER BY o.created_at DESC
        ");

        return $this->view('affiliate/offers', [
            'offers' => $offers
        ]);
    }

    public function reports()
    {
        $this->requireAuth();

        $affiliate = auth()->user()->affiliate();
        $days = (int) $this->request->get('days', 30);

        $db = \App\Core\Database::getInstance();
        $stats = $db->select("
            SELECT 
                DATE(c.created_at) as date,
                COUNT(*) as clicks,
                COUNT(CASE WHEN c.converted = 1 THEN 1 END) as conversions,
                SUM(conv.payout) as earnings
            FROM clicks c
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            WHERE c.affiliate_id = ? AND c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(c.created_at)
            ORDER BY DATE(c.created_at) DESC
        ", [$affiliate->id, $days]);

        return $this->view('affiliate/reports', [
            'stats' => $stats,
            'days' => $days
        ]);
    }

    public function payouts()
    {
        $this->requireAuth();

        $affiliate = auth()->user()->affiliate();
        $payouts = \App\Models\Payout::where('affiliate_id', '=', $affiliate->id)->get();

        return $this->view('affiliate/payouts', [
            'payouts' => $payouts,
            'affiliate' => $affiliate
        ]);
    }
}

class AdvertiserController extends Controller
{
    public function dashboard()
    {
        $this->requireAuth();

        if (!auth()->isAdvertiser()) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $advertiser = auth()->user()->advertiser();
        
        $stats = \App\Core\Database::getInstance()->selectOne("
            SELECT 
                COUNT(DISTINCT c.id) as clicks,
                COUNT(DISTINCT conv.id) as conversions,
                SUM(conv.revenue) as revenue,
                SUM(conv.payout) as payout
            FROM clicks c
            LEFT JOIN conversions conv ON c.click_id = conv.click_id
            LEFT JOIN offers o ON c.offer_id = o.id
            WHERE o.advertiser_id = ? AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ", [$advertiser->id]);

        return $this->view('advertiser/dashboard', [
            'advertiser' => $advertiser,
            'stats' => $stats
        ]);
    }

    public function offers()
    {
        $this->requireAuth();

        $advertiser = auth()->user()->advertiser();
        $offers = \App\Models\Offer::where('advertiser_id', '=', $advertiser->id)->get();

        return $this->view('advertiser/offers', [
            'offers' => $offers
        ]);
    }

    public function conversions()
    {
        $this->requireAuth();

        $advertiser = auth()->user()->advertiser();
        $days = (int) $this->request->get('days', 30);

        $db = \App\Core\Database::getInstance();
        $conversions = $db->select("
            SELECT 
                c.*,
                o.name as offer_name,
                a.company_name as affiliate_name
            FROM conversions c
            JOIN offers o ON c.offer_id = o.id
            JOIN affiliates a ON c.affiliate_id = a.id
            WHERE o.advertiser_id = ? AND c.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ORDER BY c.created_at DESC
        ", [$advertiser->id, $days]);

        return $this->view('advertiser/conversions', [
            'conversions' => $conversions
        ]);
    }

    public function postbacks()
    {
        $this->requireAuth();

        $advertiser = auth()->user()->advertiser();

        $postbacks = \App\Models\PostbackLog::where('advertiser_id', '=', $advertiser->id)->get();

        return $this->view('advertiser/postbacks', [
            'postbacks' => $postbacks
        ]);
    }
}

class AdminController extends Controller
{
    public function dashboard()
    {
        $this->requireAuth();
        $this->requireRole('admin');

        $db = \App\Core\Database::getInstance();

        $stats = $db->selectOne("
            SELECT 
                (SELECT COUNT(*) FROM users) as total_users,
                (SELECT COUNT(*) FROM affiliates) as total_affiliates,
                (SELECT COUNT(*) FROM advertisers) as total_advertisers,
                (SELECT COUNT(*) FROM offers) as total_offers,
                (SELECT COUNT(*) FROM clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) as today_clicks,
                (SELECT COUNT(*) FROM conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) as today_conversions,
                (SELECT SUM(payout) FROM conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) as today_payout
        ");

        return $this->view('admin/dashboard', ['stats' => $stats]);
    }

    public function users()
    {
        $this->requireRole('admin');

        $users = \App\Models\User::all();
        return $this->view('admin/users/index', ['users' => $users]);
    }

    public function offers()
    {
        $this->requireRole('admin');

        $db = \App\Core\Database::getInstance();
        $offers = $db->select("
            SELECT o.*, a.company_name as advertiser_name 
            FROM offers o
            JOIN advertisers a ON o.advertiser_id = a.id
            ORDER BY o.created_at DESC
        ");

        return $this->view('admin/offers/index', ['offers' => $offers]);
    }

    public function reports()
    {
        $this->requireRole('admin');

        $db = \App\Core\Database::getInstance();
        $stats = $db->select("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as clicks,
                COUNT(CASE WHEN converted = 1 THEN 1 END) as conversions,
                COUNT(DISTINCT affiliate_id) as affiliates
            FROM clicks
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at) DESC
        ");

        return $this->view('admin/reports/index', ['stats' => $stats]);
    }

    public function fraud()
    {
        $this->requireRole('admin');

        $fraudLogs = $db = \App\Core\Database::getInstance()->select("
            SELECT 
                fl.*,
                o.name as offer_name,
                aff.company_name as affiliate_name
            FROM fraud_logs fl
            LEFT JOIN offers o ON fl.offer_id = o.id
            LEFT JOIN affiliates aff ON fl.affiliate_id = aff.id
            ORDER BY fl.created_at DESC
            LIMIT 100
        ");

        return $this->view('admin/fraud/index', ['fraudLogs' => $fraudLogs]);
    }
}

class OfferController extends Controller
{
    public function store()
    {
        $this->requireAuth();
        $this->requireRole('advertiser');

        $advertiser = auth()->user()->advertiser();

        $data = [
            'advertiser_id' => $advertiser->id,
            'name' => $this->input('name'),
            'description' => $this->input('description'),
            'landing_page_url' => $this->input('landing_page_url'),
            'payout_type' => $this->input('payout_type'),
            'payout_value' => $this->input('payout_value'),
            'revenue_type' => $this->input('revenue_type'),
            'revenue_value' => $this->input('revenue_value'),
            'status' => 'active'
        ];

        $offer = \App\Models\Offer::create($data);

        return $this->json(['success' => true, 'offer_id' => $offer->id]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->requireRole('advertiser');

        $offer = \App\Models\Offer::find($id);
        if (!$offer) {
            return $this->json(['error' => 'Offer not found'], 404);
        }

        $advertiser = auth()->user()->advertiser();
        if ($offer->advertiser_id !== $advertiser->id) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $data = [
            'name' => $this->input('name', $offer->name),
            'description' => $this->input('description', $offer->description),
            'status' => $this->input('status', $offer->status)
        ];

        $offer->fill($data);
        $offer->save();

        return $this->json(['success' => true]);
    }

    public function destroy($id)
    {
        $this->requireAuth();
        $this->requireRole('advertiser');

        $offer = \App\Models\Offer::find($id);
        if (!$offer) {
            return $this->json(['error' => 'Offer not found'], 404);
        }

        $advertiser = auth()->user()->advertiser();
        if ($offer->advertiser_id !== $advertiser->id) {
            return $this->json(['error' => 'Unauthorized'], 403);
        }

        $offer->delete();

        return $this->json(['success' => true]);
    }
}
