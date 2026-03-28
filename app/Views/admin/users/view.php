<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php 
if (!function_exists('format_number_k')) {
    function format_number_k($number) {
        $num = (int)$number;
        if ($num >= 1000000) return round($num / 1000000, 1) . 'M';
        if ($num >= 1000) return round($num / 1000, 1) . 'K';
        return $num;
    }
}

// LOGIC & VARIABLES
$user_avatar = get_media_url($user->avatar, 'profile');
$user_cover = get_media_url($user->cover_photo, 'cover');
$total_strikes = count($strikes);
$active_strikes = 0;
foreach($strikes as $s) { if($s->status == 'ACTIVE') $active_strikes++; }
$last_3_strikes = array_slice($strikes, 0, 3);
$total_txns = count($transactions);
// Controller now handles limiting/filtering for $transactions
$last_3_txns = $transactions; 
$total_content = ($user->posts_count ?? 0) + ($user->videos_count ?? 0) + ($user->reels_count ?? 0);

// LOCATION SYNCHRONIZATION
$full_location = implode(', ', array_filter([
    $user->location ?? null,
    $user->district ?? null,
    $user->state ?? null,
    $user->country ?? null
]));
?>

<style>
    /* 🎨 PAGE SPECIFIC COMPONENTS (Powered by Global Variables) */
    
    /* Profile Header */
    .profile-header-new {
        height: 15rem;
        background-color: var(--sidebar-dark);
        position: relative;
        border-radius: var(--radius-lg, 1rem);
        margin-bottom: 8rem;
    }

    .cover-photo-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        opacity: 0.6;
        border-radius: inherit;
    }

    .header-overlay-content {
        position: absolute;
        bottom: 0;
        left: 2.5rem;
        display: flex;
        align-items: flex-end;
        gap: 1.5rem;
        width: calc(100% - 5rem);
        transform: translateY(50%);
        z-index: 10;
    }

    .avatar-profile-lg {
        width: 10rem;
        height: 10rem;
        border-radius: var(--radius-lg, 1.5rem);
        border: 0.35rem solid var(--bg-light, #ffffff);
        background-color: var(--bg-light, #ffffff);
        box-shadow: var(--shadow-lg, 0 10px 25px rgba(0,0,0,0.15));
        object-fit: cover;
        flex-shrink: 0;
    }

    .user-title-box {
        padding-bottom: 1rem;
        flex-grow: 1;
    }

    .main-name {
        color: var(--bg-light, #ffffff);
        font-size: var(--font-size-xl, 2rem);
        font-weight: var(--font-weight-black, 800);
        margin-bottom: 0.25rem;
    }

    .sub-handle {
        color: var(--bg-light, #ffffff);
        background-color: rgba(0, 0, 0, 0.5);
        padding: 0.25rem 1rem;
        border-radius: var(--radius-md, 0.5rem);
        font-weight: var(--font-weight-semibold, 600);
        font-size: var(--font-size-sm, 0.875rem);
        backdrop-filter: blur(8px);
        border: 1px solid rgba(255,255,255,0.1);
        display: inline-block;
    }

    /* Info Cards & Data Rows */
    .info-card-pro {
        background-color: var(--bg-surface, #ffffff);
        border-radius: var(--radius-md, 0.75rem);
        box-shadow: var(--card-shadow);
        border: 1px solid var(--border-soft);
        height: 100%;
        overflow: hidden;
    }

    .card-top-danger { border-top: 4px solid var(--accent-red); }
    .card-top-success { border-top: 4px solid var(--accent-green); }

    .info-card-header {
        padding: 1rem 1.25rem;
        background-color: var(--bg-light);
        border-bottom: 1px solid var(--border-soft);
        font-size: var(--font-size-xs, 0.75rem);
        font-weight: var(--font-weight-bold, 700);
        text-transform: uppercase;
        color: var(--text-dark);
    }

    .data-row-pro {
        background-color: var(--bg-light);
        border: 1px solid var(--border-soft);
        padding: 0.75rem;
        border-radius: var(--radius-sm, 0.5rem);
        margin-bottom: 0.5rem;
    }

    /* Badges & Pills */
    .channel-id-box {
        font-family: monospace;
        color: var(--primary-blue);
        font-size: var(--font-size-xs, 0.75rem);
        background-color: rgba(93, 120, 255, 0.05);
        padding: 0.5rem;
        border-radius: var(--radius-sm, 0.5rem);
        border: 1px dashed var(--primary-blue);
        display: block;
        margin-top: 0.25rem;
    }

    .counter-pill-pro {
        background-color: var(--sidebar-dark);
        padding: 0.15rem 0.5rem;
        border-radius: var(--radius-sm, 0.5rem);
        font-size: var(--font-size-xs, 0.65rem);
        color: var(--bg-light, #ffffff);
        font-weight: var(--font-weight-semibold, 600);
    }

    .bg-primary-custom { background-color: var(--primary-blue); }
    
    /* Utilities */
    .avatar-md-bordered {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 50%;
        border: 2px solid var(--primary-blue);
        padding: 2px;
    }
    
    .kyc-img-preview {
        width: 100%;
        height: 15rem;
        object-fit: contain;
        background-color: var(--border-soft);
        border-radius: var(--radius-sm, 0.5rem);
    }

    /* Text Colors mapped to variables */
    .text-danger-custom { color: var(--accent-red); }
    .text-warning-custom { color: var(--accent-orange); }
    .text-success-custom { color: var(--accent-green); }
    .text-primary-custom { color: var(--primary-blue); }

    /* Mobile Responsive Upgrade */
    @media (max-width: 768px) {
        .profile-header-new { 
            height: 11rem; 
            margin-bottom: 10rem; 
        }
        
        .header-overlay-content {
            flex-direction: column;
            align-items: center;
            left: 0;
            width: 100%;
            transform: translateY(85%);
            text-align: center;
            gap: 0.75rem;
        }

        .avatar-profile-lg {
            width: 8rem;
            height: 8rem;
            border-radius: var(--radius-md, 1.25rem);
        }

        .main-name { 
            font-size: 1.75rem; 
            color: var(--sidebar-dark) !important; 
            text-shadow: none; 
        }
        
        .sub-handle {
            background-color: var(--sidebar-dark);
            color: var(--bg-light, #ffffff) !important;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="profile-header-new shadow-sm">
        <img src="<?= $user_cover ?>" class="cover-photo-img" onerror="this.src='https://images.unsplash.com/photo-1557683316-973673baf926?auto=format&fit=crop&w=1200'">
        <div class="header-overlay-content">
            <img src="<?= $user_avatar ?>" class="avatar-profile-lg">
            <div class="user-title-box">
                <h1 class="main-name">
                    <?= strtoupper($user->name) ?: 'MEMBER' ?>
                    <?php if($user->is_verified): ?> <i class="fas fa-check-circle text-primary-custom ml-1"></i> <?php endif; ?>
                </h1>
                <span class="sub-handle">@<?= $user->username ?> | UID: #<?= $user->id ?></span>
            </div>
        </div>
    </div>

    <div class="row mb-2 text-center">
        <div class="col-lg-3 col-6 mb-3">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <span class="label text-danger-custom">Active Strikes</span>
                <h4 class="font-weight-bold mb-0"><?= $active_strikes ?> <small class="text-muted">/ <?= $total_strikes ?></small></h4>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <span class="label text-warning-custom">Trust Score</span>
                <h4 class="font-weight-bold mb-0 text-warning-custom"><?= $user->trust_score ?: 100 ?></h4>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <span class="label">Followers</span>
                <h4 class="font-weight-bold mb-0"><?= format_number_k($user->followers_count) ?></h4>
            </div>
        </div>
        <div class="col-lg-3 col-6 mb-3">
            <div class="card p-4 h-100 border-0 shadow-sm">
                <span class="label text-primary-custom">Total Content</span>
                <h4 class="font-weight-bold mb-0"><?= format_number_k($total_content) ?></h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="info-card-pro">
                <div class="info-card-header">Identity Bio</div>
                <div class="card-body p-3">
                    <div class="data-row-pro"><span class="label">Bio Description</span><p class="text-dark small mb-0"><?= ($user->bio ?? null) ?: 'No bio available.' ?></p></div>
                    <div class="data-row-pro"><span class="label">Mobile Number</span><span class="text-dark font-weight-bold small"><?= ($user->phone ?? null) ?: 'Not Provided' ?></span></div>
                    <div class="data-row-pro"><span class="label">Date of Birth</span><span class="text-dark font-weight-bold small"><?= ($user->dob ?? null) ? date('d M, Y', strtotime($user->dob)) : 'Not Set' ?></span></div>
                    <div class="data-row-pro"><span class="label">Gender</span><span class="text-dark font-weight-bold small"><?= strtoupper($user->gender ?? 'N/A') ?: 'N/A' ?></span></div>
                    <div class="data-row-pro"><span class="label">Full Address</span><span class="text-dark small d-block"><?= $full_location ?: 'Location not updated.' ?></span></div>
                    <div class="data-row-pro"><span class="label">Email Address</span><span class="text-dark font-weight-bold small d-block text-truncate"><?= $user->email ?></span></div>
                    <div class="data-row-pro d-flex justify-content-between align-items-center"><span class="label mb-0">Verification</span><span class="badge badge-success px-3 py-1"><?= $user->is_verified ? 'VERIFIED' : 'UNVERIFIED' ?></span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="info-card-pro">
                <div class="info-card-header">KYC Verification</div>
                <div class="card-body p-3">
                    <?php if(isset($kyc_data) && $kyc_data): ?>
                        <div class="data-row-pro"><span class="label">KYC Name</span><span class="text-dark font-weight-bold small"><?= strtoupper($kyc_data->full_name) ?></span></div>
                        <div class="data-row-pro"><span class="label">KYC Document</span><span class="badge badge-neutral text-primary-custom font-weight-bold"><?= $kyc_data->document_type ?></span></div>
                        <div class="data-row-pro"><span class="label">Document Number</span><span class="text-dark font-weight-bold small"><?= $kyc_data->document_number ?></span></div>
                        <div class="data-row-pro d-flex justify-content-between align-items-center mb-2">
                            <span class="label mb-0">KYC Status</span>
                            <span class="badge badge-<?= ($kyc_data->status == 'APPROVED') ? 'success' : (($kyc_data->status == 'REJECTED') ? 'danger' : 'warning') ?> px-3 py-1">
                                <?= $kyc_data->status ?>
                            </span>
                        </div>
                        <button class="btn btn-sm btn-block btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#kycDocsModal">
                            <i class="fas fa-eye mr-1"></i> VIEW DOCUMENTS
                        </button>
                        <?php if($kyc_data->rejection_reason): ?>
                            <div class="mt-2 p-2 rounded bg-light border-soft text-danger-custom small"><b>Reason:</b> <?= $kyc_data->rejection_reason ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="py-5 text-center text-muted small opacity-50">
                            <i class="fas fa-id-card fa-2x mb-2"></i><br>KYC NOT SUBMITTED
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="info-card-pro">
                <div class="info-card-header">Linked Channel</div>
                <div class="card-body p-3 text-center">
                    <?php if(isset($user->unique_id) && $user->is_creator): ?>
                        <img src="<?= $user_avatar ?>" class="avatar-md-bordered mb-2">
                        <h6 class="font-weight-bold text-dark mb-0"><?= strtoupper($user->name) ?></h6>
                        <div class="channel-id-box"><?= $user->unique_id ?></div>
                        
                        <div class="mt-3 p-2 rounded bg-light border-soft">
                            <span class="label">Monetization Status</span>
                            <?php if(($user->is_monetization_enabled ?? 0) == 1): ?>
                                <span class="badge badge-success px-3 py-1"><i class="fas fa-dollar-sign mr-1"></i> ENABLED</span>
                                <div class="small text-success mt-1 font-weight-bold"><?= $user->channel_monetization ?? 'APPROVED' ?></div>
                            <?php else: ?>
                                <span class="badge badge-neutral px-3 py-1"><i class="fas fa-times-circle mr-1"></i> DISABLED</span>
                                <div class="small text-muted mt-1 font-weight-bold"><?= $user->channel_monetization ?? 'NOT_APPLIED' ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="py-5 text-muted small opacity-50"><i class="fas fa-video-slash fa-2x mb-2"></i><br>NO CHANNEL</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-4">
            <div class="info-card-pro card-top-danger">
                <div class="info-card-header d-flex justify-content-between">Violation Log <span class="counter-pill-pro"><?= $total_strikes ?> Hits</span></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php if($last_3_strikes): foreach($last_3_strikes as $s): ?>
                                <tr class="small border-bottom">
                                    <td class="p-3 font-weight-bold"><?= substr($s->reason, 0, 15) ?>..</td>
                                    <td class="p-3 text-right"><span class="badge badge-neutral text-danger-custom"><?= $s->status ?></span></td>
                                </tr>
                            <?php endforeach; else: ?>
                                <tr><td class="text-center py-5 text-muted small">Record Clean</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="info-card-pro card-top-success">
                <div class="info-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <span><i class="fas fa-wallet mr-2 text-success-custom"></i> Creator Revenue Dashboard</span>
                    <form class="form-inline mt-2 mt-md-0 d-flex">
                        <div class="input-group input-group-sm mr-2 mb-1 mb-md-0">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                            <input type="date" class="form-control" name="start_date">
                        </div>
                        <div class="input-group input-group-sm mr-2 mb-1 mb-md-0">
                            <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-calendar-alt"></i></span></div>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                        <button type="submit" class="btn btn-sm btn-success px-3">Filter</button>
                    </form>
                </div>
                <div class="card-body p-4">
                    <div class="row text-center">
                        <div class="col-md-3 border-right border-md-bottom mb-3 mb-md-0">
                            <span class="label">Total Earning</span>
                            <h3 class="font-weight-bold text-success-custom">₹<?= number_format($finance_stats['total_earnings'] ?? 0, 2) ?></h3>
                            <small class="text-muted">Approved & Paid</small>
                        </div>
                        <div class="col-md-3 border-right border-md-bottom mb-3 mb-md-0">
                            <span class="label">Total Payout</span>
                            <h3 class="font-weight-bold text-primary-custom">₹<?= number_format($finance_stats['total_payouts'] ?? 0, 2) ?></h3>
                            <small class="text-muted">Completed Withdrawals</small>
                        </div>
                        <div class="col-md-3 border-right border-md-bottom mb-3 mb-md-0">
                            <span class="label">Pending Revenue</span>
                            <h3 class="font-weight-bold text-warning-custom">₹<?= number_format($finance_stats['pending_amount'] ?? 0, 2) ?></h3>
                            <small class="text-muted">In Verification</small>
                        </div>
                        <div class="col-md-3">
                            <span class="label text-dark">Wallet Balances</span>
                            <div class="mt-1">
                                <div class="d-flex justify-content-between px-3">
                                    <small class="font-weight-bold">Creator:</small>
                                    <span class="text-dark font-weight-bold">₹<?= number_format($finance_stats['creator_balance'] ?? 0, 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between px-3">
                                    <small class="font-weight-bold text-muted">Spending:</small>
                                    <span class="text-muted font-weight-bold">₹<?= number_format($finance_stats['spending_balance'] ?? 0, 2) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="info-card-pro">
                <div class="info-card-header d-flex flex-wrap justify-content-between align-items-center">
                    <span>Recent Financial Logs <span class="counter-pill-pro bg-primary-custom">Total: <?= $total_txns ?></span></span>
                    
                    <form class="form-inline mt-2 mt-md-0 d-flex" method="GET" action="">
                        <div class="input-group input-group-sm mr-1 mb-1 mb-md-0">
                            <div class="input-group-prepend"><span class="input-group-text px-2"><i class="fas fa-calendar-alt"></i></span></div>
                            <input type="date" name="txn_start" class="form-control" value="<?= $_GET['txn_start'] ?? '' ?>">
                        </div>
                        <div class="input-group input-group-sm mr-1 mb-1 mb-md-0">
                            <div class="input-group-prepend"><span class="input-group-text px-2"><i class="fas fa-calendar-alt"></i></span></div>
                            <input type="date" name="txn_end" class="form-control" value="<?= $_GET['txn_end'] ?? '' ?>">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                        <?php if(isset($_GET['txn_start'])): ?>
                            <a href="<?= current_url() ?>" class="btn btn-sm btn-light ml-1">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="px-4 py-3">Description</th>
                                <th class="py-3">Wallet</th>
                                <th class="py-3 text-center">Date</th>
                                <th class="px-4 py-3 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($last_3_txns): foreach($last_3_txns as $tx): ?>
                                <tr class="small border-bottom">
                                    <td class="px-4 py-3 font-weight-bold"><?= $tx->description ?></td>
                                    <td class="py-3 text-uppercase"><?= $tx->wallet_type ?></td>
                                    <td class="py-3 text-center text-muted"><?= date('M d, Y', strtotime($tx->created_at)) ?></td>
                                    <td class="px-4 py-3 text-right font-weight-bold <?= ($tx->type == 'credit') ? 'text-success-custom' : 'text-danger-custom' ?>">
                                        <?= ($tx->type == 'credit') ? '+' : '-' ?> ₹<?= number_format($tx->amount, 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">No movement found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="info-card-pro">
                <div class="info-card-header d-flex justify-content-between">
                    Security Center
                    <span class="badge badge-<?= ($user->is_banned) ? 'danger' : 'success' ?>"><?= ($user->is_banned) ? 'BANNED' : 'ACTIVE' ?></span>
                </div>
                <div class="card-body p-3">
                    <div class="data-row-pro d-flex justify-content-between align-items-center">
                        <span class="label mb-0">Account ID</span>
                        <span class="text-dark font-weight-bold small">#<?= $user->id ?></span>
                    </div>
                    <div class="data-row-pro d-flex justify-content-between align-items-center">
                        <span class="label mb-0">2FA Status</span>
                        <span class="badge badge-<?= ($user->two_factor_secret) ? 'primary' : 'neutral' ?>">
                            <?= ($user->two_factor_secret) ? 'ENABLED' : 'DISABLED' ?>
                        </span>
                    </div>
                    <div class="data-row-pro">
                        <span class="label">Last Activity</span>
                        <span class="text-dark small"><?= ($user->last_active ?? null) ? date('d M, h:i A', strtotime($user->last_active)) : 'Never' ?></span>
                    </div>

                    <div class="mt-3">
                        <a href="<?= base_url('admin/users/force_logout_all/'.$user->id) ?>" 
                           onclick="return confirm('Bhai, kya aap sach mein saare sessions band karna chahte hain? User logout ho jayega.')"
                           class="btn btn-sm btn-block btn-outline-danger mb-2 font-weight-bold">
                            <i class="fas fa-sign-out-alt mr-1"></i> FORCE LOGOUT ALL
                        </a>
                        <div class="row no-gutters">
                            <div class="col-6 pr-1">
                                <a href="<?= base_url('admin/users/reset_password_trigger/'.$user->id) ?>" class="btn btn-sm btn-block btn-light font-weight-bold">
                                    <i class="fas fa-key mr-1"></i> RESET PASS
                                </a>
                            </div>
                            <div class="col-6 pl-1">
                                <a href="<?= base_url('admin/users/toggle_2fa/'.$user->id) ?>" class="btn btn-sm btn-block btn-light font-weight-bold">
                                    <i class="fas fa-shield-alt mr-1"></i> TOGGLE 2FA
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="info-card-pro">
                <div class="info-card-header d-flex justify-content-between align-items-center">
                    Active Session & Device Logs
                    <span class="counter-pill-pro" style="background-color: var(--accent-orange);">Latest 10 Sessions</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light small">
                            <tr>
                                <th class="px-4 py-3">Device & OS</th>
                                <th class="py-3">IP Address</th>
                                <th class="py-3">Location</th>
                                <th class="py-3 text-center">Login Time</th>
                                <th class="py-3 text-center">Logout Time</th>
                                <th class="py-3 text-center">Duration</th>
                                <th class="px-4 py-3 text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(isset($sessions) && !empty($sessions)): foreach($sessions as $session): 
                                $login = strtotime($session->created_at);
                                $logout = $session->logged_out_at ? strtotime($session->logged_out_at) : null;
                                $duration = $logout ? round(($logout - $login) / 60) . ' mins' : 'Active';
                            ?>
                                <tr class="small border-bottom">
                                    <td class="px-4 py-3">
                                        <div class="d-flex align-items-center">
                                            <i class="fas <?= ($session->device_type == 'Mobile') ? 'fa-mobile-alt' : 'fa-desktop' ?> mr-2 text-muted"></i>
                                            <div>
                                                <span class="font-weight-bold d-block"><?= $session->device_model ?: 'Unknown Device' ?></span>
                                                <span class="text-muted small"><?= $session->os_name ?: 'Unknown OS' ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 font-weight-bold text-primary-custom"><?= $session->ip_address ?></td>
                                    <td class="py-3">
                                        <span class="d-block"><?= $session->location_city ?: 'Unknown' ?></span>
                                        <small class="text-muted"><?= $session->location_state ?></small>
                                    </td>
                                    <td class="py-3 text-center"><?= date('d M, h:i A', $login) ?></td>
                                    <td class="py-3 text-center"><?= $logout ? date('d M, h:i A', $logout) : '<span class="text-muted">--</span>' ?></td>
                                    <td class="py-3 text-center font-weight-bold"><?= $duration ?></td>
                                    <td class="px-4 py-3 text-right">
                                        <?php if($session->is_active && !$logout): ?>
                                            <span class="badge badge-success">ACTIVE</span>
                                        <?php else: ?>
                                            <span class="badge badge-neutral text-muted">CLOSED</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-muted small">No session history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if($kyc_data): ?>
<div class="modal fade" id="kycDocsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content rounded border-0">
            <div class="modal-header bg-light">
                <h5 class="modal-title font-weight-bold text-dark">KYC Documents: <?= $kyc_data->document_type ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4 bg-surface">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <span class="label text-center mb-2 d-block">FRONT SIDE</span>
                        <div class="border-soft rounded p-1">
                            <img src="<?= get_media_url($kyc_data->front_image_url, 'kyc') ?>" class="kyc-img-preview shadow-sm">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <span class="label text-center mb-2 d-block">BACK SIDE</span>
                        <div class="border-soft rounded p-1">
                            <img src="<?= get_media_url($kyc_data->back_image_url, 'kyc') ?>" class="kyc-img-preview shadow-sm">
                        </div>
                    </div>
                </div>
                <div class="alert alert-secondary mt-3 mb-0 py-2 small text-center border-soft">
                    <i class="fas fa-info-circle mr-1"></i> Document Number: <b><?= $kyc_data->document_number ?></b>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?= $this->endSection() ?>
