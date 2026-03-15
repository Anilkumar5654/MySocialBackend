<?php 
namespace App\Controllers\Admin; 

use App\Controllers\BaseController; 
use CodeIgniter\Database\BaseConnection; 

class Ads extends BaseController { 
    
    protected $db; 
    
    public function __construct() { 
        $this->db = \Config\Database::connect(); 
        // 🕒 FIX 1: Timezone Sync (IST +05:30)
        $this->db->query("SET time_zone = '+05:30'");
        date_default_timezone_set('Asia/Kolkata');

        helper(['text', 'number', 'media', 'permission', 'time', 'form', 'url', 'date']); 
    } 

    // 📊 1. DASHBOARD - MASTER STATS
    public function dashboard() { 
        if (!has_permission('ads.view')) return redirect()->to('admin/dashboard')->with('error', 'Unauthorized'); 
        
        $builder = $this->db->table('ads'); 
        $stats = [ 
            'active_ads'    => $builder->where('status', 'active')->countAllResults(false), 
            'pending_req'   => $builder->where('status', 'pending_approval')->countAllResults(false), 
            'total_clicks'  => $builder->selectSum('clicks')->get()->getRow()->clicks ?? 0, 
            'total_reach'   => $builder->selectSum('reach')->get()->getRow()->reach ?? 0,
            'total_views'   => $builder->selectSum('views')->get()->getRow()->views ?? 0,
            'total_revenue' => $builder->selectSum('spent')->get()->getRow()->spent ?? 0 
        ]; 
        
        $recentRequests = $this->db->table('ads') 
            ->select('ads.*, ads.ad_type as ad_content_type, users.username, users.avatar, users.id as user_id') 
            ->join('users', 'users.id = ads.advertiser_id', 'left') 
            ->where('ads.status', 'pending_approval') 
            ->orderBy('ads.created_at', 'DESC') 
            ->limit(6)->get()->getResult(); 
            
        return view('admin/ads/overview', ['title' => 'Ads Overview', 'stats' => $stats, 'recent_requests' => $recentRequests]); 
    } 

    // 🛡️ 2. REQUESTS QUEUE
    public function requests() { 
        if (!has_permission('ads.moderate')) return redirect()->back(); 
        
        $timeoutLimit = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $this->db->table('ads')->where('locked_at <', $timeoutLimit)->where('status', 'pending_approval')->update(['locked_by' => null, 'locked_at' => null]);

        $ads = $this->db->table('ads') 
            ->select('ads.*, ads.ad_type as ad_content_type, users.username, users.avatar, users.is_verified') 
            ->join('users', 'users.id = ads.advertiser_id', 'left') 
            ->where('ads.status', 'pending_approval') 
            ->orderBy('ads.created_at', 'ASC')->get()->getResult(); 
            
        return view('admin/ads/requests', ['title' => 'Ad Requests', 'ads' => $ads]); 
    } 

    // 👁️ 3. VIEW SINGLE AD - ✅ FIXED REACH & VIEWS ERROR
    public function view($id = null) { 
        if (!has_permission('ads.moderate') || empty($id)) return redirect()->to('admin/ads/requests'); 
        
        $ad = $this->db->table('ads') 
            // 🔥 MASTER FIX: ads.* ensures no 'Undefined property' error for reach/views
            ->select('ads.*, ads.ad_type as ad_content_type, users.username, users.avatar, users.email, users.is_verified') 
            ->join('users', 'users.id = ads.advertiser_id', 'left') 
            ->where('ads.id', $id)->get()->getRow(); 
            
        if (!$ad) return redirect()->to('admin/ads/requests')->with('error', 'Not found.'); 

        $adminId = session()->get('id');
        if ($ad->status == 'pending_approval') {
            if (!empty($ad->locked_by) && $ad->locked_by != $adminId && strtotime($ad->locked_at) > strtotime('-15 minutes')) {
                return redirect()->back()->with('error', "Locked by Moderator #{$ad->locked_by}");
            }
            $this->db->table('ads')->where('id', $id)->update(['locked_by' => $adminId, 'locked_at' => date('Y-m-d H:i:s')]);
        }
        
        return view('admin/ads/view', ['title' => 'Inspect Ad #' . $id, 'ad' => $ad]); 
    } 

    // ⏯️ 4. QUICK STATUS - PLAY/PAUSE TOGGLE
    public function quick_status($id, $status) {
        if (!has_permission('ads.manage')) return redirect()->back();
        
        $validStatuses = ['active', 'paused'];
        if (!in_array($status, $validStatuses)) return redirect()->back();

        $this->db->table('ads')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        
        return redirect()->back()->with('success', "Campaign is now " . strtoupper($status));
    }

    // 📂 5. ALL CAMPAIGNS - ✅ FIXED SYNC ISSUES
    public function campaigns() { 
        if (!has_permission('ads.view')) return redirect()->to('admin/dashboard'); 
        $status = $this->request->getGet('status'); 
        
        $builder = $this->db->table('ads');
        // 🔥 Re-synced Select: ads.* explicitly called first to prevent stdClass reach error
        $builder->select('ads.*, ads.ad_type as ad_content_type, u.username as advertiser_username, u.avatar as advertiser_avatar') 
            ->join('users u', 'u.id = ads.advertiser_id', 'left') 
            ->where('ads.status !=', 'pending_approval') 
            ->orderBy('ads.created_at', 'DESC'); 
            
        if ($status) $builder->where('ads.status', $status); 
        
        $campaigns = $builder->get()->getResult(); 
        
        return view('admin/ads/campaigns', [
            'title' => 'Campaigns', 
            'campaigns' => $campaigns, 
            'current_status' => $status
        ]); 
    } 

    // 💸 6. UPDATE STATUS & MODERATION
    public function update_status() { 
        if (!has_permission('ads.manage')) return redirect()->back(); 
        
        $ad_id = $this->request->getPost('ad_id'); 
        $new_status = $this->request->getPost('status'); 
        $reason = $this->request->getPost('rejection_reason');
        $priority = $this->request->getPost('priority_weight') ?? 1;
        $daily_limit = $this->request->getPost('daily_limit') ?? 500;

        $ad = $this->db->table('ads')->where('id', $ad_id)->get()->getRow(); 
        if (!$ad) return redirect()->back(); 
        
        $this->db->transStart(); 
        
        // 💰 REJECTION REFUND LOGIC
        if ($new_status === 'rejected' && $ad->status !== 'rejected') { 
            $refundAmount = max(0, (float)$ad->budget - (float)$ad->spent); 
            if ($refundAmount > 0) { 
                $this->db->table('spending_wallets')->where('user_id', $ad->advertiser_id)->increment('balance', $refundAmount); 
                $this->db->table('wallet_transactions')->insert([ 
                    'user_id' => $ad->advertiser_id, 'wallet_type' => 'spending', 'amount' => $refundAmount, 
                    'type' => 'credit', 'description' => "Refund: Ad #$ad_id Rejected", 'created_at' => date('Y-m-d H:i:s') 
                ]); 
            } 
        } 
        
        $this->db->table('ads')->where('id', $ad_id)->update([ 
            'status' => $new_status, 
            'admin_rejection_reason' => $reason,
            'priority_weight' => $priority,
            'daily_limit' => $daily_limit,
            'locked_by' => null, 
            'locked_at' => null, 
            'updated_at' => date('Y-m-d H:i:s') 
        ]); 
        
        $this->db->transComplete(); 
        return redirect()->to('admin/ads/requests')->with('success', "Campaign successfully updated to $new_status"); 
    } 

    // ⚙️ 7. SETTINGS
    public function settings() { 
        if (!has_permission('ads.settings')) return redirect()->back(); 
        
        $net = $this->db->table('ad_network_settings')->get()->getResultArray(); 
        $gen = $this->db->table('ad_settings')->get()->getResultArray(); 
        
        $map = []; 
        foreach ($gen as $s) $map[$s['setting_key']] = $s['setting_value']; 
        foreach ($net as $s) $map[$s['setting_key']] = $s['setting_value']; 
        
        return view('admin/ads/settings', ['title' => 'Ads Settings', 'settings' => $map]); 
    } 

    // 💾 8. SAVE SETTINGS - 🔥 SYNCED FOR CAPPING & REVENUE FIELDS
    public function save_settings() { 
        if (!has_permission('ads.settings')) return redirect()->back(); 
        
        $posts = $this->request->getPost(); 
        // Sync check: global_ad_status yahan rakha hai kyunki ye ad_network_settings table ka part hai
        $networkKeys = ['fb_app_id', 'fb_placement_reels', 'fb_placement_video', 'active_ad_provider', 'fb_test_mode', 'global_ad_status'];
        
        $this->db->transStart(); 
        foreach ($posts as $key => $value) { 
            if ($key === csrf_token() || $key === 'csrf_test_name') continue;

            // Decision: Kaunsi table mein save karna hai?
            $table = in_array($key, $networkKeys) ? 'ad_network_settings' : 'ad_settings';
            
            $exists = $this->db->table($table)->where('setting_key', $key)->countAllResults(); 
            
            $data = ['setting_key' => $key, 'setting_value' => $value];
            // ad_settings table mein updated_at column maintain karna hai
            if ($table === 'ad_settings') $data['updated_at'] = date('Y-m-d H:i:s');

            if ($exists > 0) { 
                $this->db->table($table)->where('setting_key', $key)->update($data); 
            } else { 
                $this->db->table($table)->insert($data); 
            } 
        } 
        $this->db->transComplete(); 
        return redirect()->back()->with('success', 'Settings Saved Successfully'); 
    } 

    // 🗑️ 9. DELETE AD
    public function delete($id) { 
        if (!has_permission('ads.delete')) return redirect()->back(); 
        $ad = $this->db->table('ads')->where('id', $id)->get()->getRow(); 
        if ($ad) {
            $refund = max(0, (float)$ad->budget - (float)$ad->spent);
            if ($refund > 0) {
                $this->db->table('spending_wallets')->where('user_id', $ad->advertiser_id)->increment('balance', $refund);
            }
            $this->db->table('ads')->where('id', $id)->delete();
        }
        return redirect()->to('admin/ads/campaigns')->with('success', 'Ad Campaign Deleted');
    }
}
