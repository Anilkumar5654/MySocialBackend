<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<?php 
if (!function_exists('format_number_k')) {
    function format_number_k($number) {
        $number = (float)$number;
        if ($number >= 1000000) { return round($number / 1000000, 1) . 'M'; }
        if ($number >= 1000) { return round($number / 1000, 1) . 'K'; }
        return $number;
    }
}
?>

<style>
    /* Profile Specific Styles - Professional Blue-Gray */
    .profile-header { 
        height: 200px; 
        background-color: var(--sidebar-dark); 
        position: relative; 
        border-radius: 12px; 
        overflow: visible; 
        box-shadow: var(--card-shadow);
    }
    .avatar-container { 
        position: absolute; 
        bottom: -90px; 
        width: 100%; 
        display: flex; 
        flex-direction: column; 
        align-items: center; 
        text-align: center; 
    }
    .profile-avatar { 
        width: 120px; 
        height: 120px; 
        border-radius: 15px; 
        border: 4px solid #fff; 
        object-fit: cover; 
        background: #fff; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
    }
    
    /* Stats & Logic Cards */
    .stat-card-pro { 
        background: #fff; 
        border: none; 
        border-radius: 12px; 
        padding: 20px; 
        margin-bottom: 15px; 
        box-shadow: var(--card-shadow); 
        transition: 0.3s;
    }
    .info-card-pro { 
        background: #fff; 
        border: none; 
        border-radius: 12px; 
        margin-bottom: 20px; 
        height: 100%; 
        overflow: hidden; 
        box-shadow: var(--card-shadow);
    }
    .info-card-header { 
        padding: 15px 20px; 
        background: #f8f9fa; 
        border-bottom: 1px solid var(--border-soft); 
        font-size: 11px; 
        font-weight: 700; 
        letter-spacing: 1px; 
        text-transform: uppercase; 
        color: var(--text-dark); 
    }
    
    .label-small { font-size: 10px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; margin-bottom: 4px; display: block; }
    .uid-badge-pro { 
        font-family: 'Poppins', sans-serif; 
        background: #f4f7fa; 
        color: var(--primary-blue); 
        padding: 3px 10px; 
        border-radius: 6px; 
        border: 1px solid var(--border-soft); 
        font-size: 11px; 
        font-weight: 600;
    }
    
    .data-row-pro { 
        background: #fdfdfd; 
        border: 1px solid #f4f4f4; 
        padding: 12px; 
        border-radius: 8px; 
        margin-bottom: 8px; 
    }
    .channel-id-box { 
        font-family: monospace; 
        color: var(--primary-blue); 
        font-size: 10px; 
        background: rgba(93, 120, 255, 0.05); 
        padding: 6px; 
        border-radius: 6px; 
        border: 1px dashed var(--primary-blue); 
        display: block; 
        margin-top: 5px; 
    }
    .counter-pill-pro { background: var(--sidebar-dark); padding: 2px 8px; border-radius: 8px; font-size: 10px; color: #fff; font-weight: 600; }
</style>

<?php 
    $user_avatar = get_media_url($user->avatar, 'profile'); 
    $user_cover  = get_media_url($user->cover_photo, 'cover'); 
    
    // Strike Logic (Keep as is)
    $total_strikes = count($strikes);
    $active_strikes = 0;
    foreach($strikes as $s) { if($s->status == 'ACTIVE') $active_strikes++; }
    $last_3_strikes = array_slice($strikes, 0, 3);

    // Financial Logic (Keep as is)
    $total_txns = count($transactions);
    $last_3_txns = array_slice($transactions, 0, 3);
?>

<div class="profile-header" style="background: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.3)), url('<?= $user_cover ?>') center center / cover;">
    <div class="avatar-container">
        <img src="<?= $user_avatar ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= $user->username ?>&background=f4f7fa&color=5d78ff&size=150';" class="profile-avatar">
        <div style="margin-top: 12px;">
            <h2 class="font-weight-bold mb-1" style="color: #000; text-transform: uppercase; font-size: 1.5rem; letter-spacing: -0.5px;">
                <?= $user->name ?: 'MEMBER' ?>
                <?php if($user->is_verified): ?><i class="fas fa-check-circle ml-1" style="font-size: 18px; color: var(--primary-blue);"></i><?php endif; ?>
            </h2>
            <div class="d-flex align-items-center justify-content-center gap-2">
                <span class="uid-badge-pro">ID #<?= $user->id ?></span>
                <span class="badge" style="background: var(--primary-blue); color: #fff; padding: 6px 15px; border-radius: 30px; font-size: 11px; font-weight: 700;">@<?= strtoupper($user->username) ?></span>
                <span class="uid-badge-pro"><?= $user->unique_id ?></span>
            </div>
        </div>
    </div>
</div>

<section class="content" style="margin-top: 140px; padding: 15px;">
    <div class="container-fluid">
        
        <div class="row mb-4 text-center">
            <div class="col-md-3 col-6"><div class="stat-card-pro"><span class="label-small" style="color: var(--accent-red);">Active Strikes</span><h4 class="font-weight-bold mb-0" style="color: var(--text-dark);"><?= $active_strikes ?> <small class="text-muted" style="font-size: 12px;">/ <?= $total_strikes ?></small></h4></div></div>
            <div class="col-md-3 col-6"><div class="stat-card-pro"><span class="label-small" style="color: var(--accent-orange);">Trust Score</span><h4 class="font-weight-bold mb-0" style="color: var(--accent-orange);"><?= $user->trust_score ?: 100 ?></h4></div></div>
            <div class="col-md-3 col-6"><div class="stat-card-pro"><span class="label-small">Followers</span><h4 class="font-weight-bold mb-0" style="color: var(--text-dark);"><?= format_number_k($user->followers_count) ?></h4></div></div>
            <div class="col-md-3 col-6"><div class="stat-card-pro"><span class="label-small" style="color: var(--primary-blue);">Total Content</span><h4 class="font-weight-bold mb-0" style="color: var(--text-dark);"><?= format_number_k($user->posts_count + $user->videos_count + $user->reels_count) ?></h4></div></div>
        </div>

        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="info-card-pro">
                    <div class="info-card-header"><i class="fas fa-user-circle mr-2 text-primary"></i> Identity Bio</div>
                    <div class="card-body">
                        <div class="data-row-pro"><span class="label-small">Bio Description</span><p class="text-dark small mb-0"><?= $user->bio ?: 'No bio available.' ?></p></div>
                        <div class="data-row-pro"><span class="label-small">Registered Email</span><span class="text-dark font-weight-bold small text-truncate d-block"><?= $user->email ?></span></div>
                        <div class="data-row-pro d-flex justify-content-between align-items-center"><span class="label-small mb-0">Verification</span><span class="badge" style="background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); padding: 5px 10px;"><?= $user->kyc_status ?></span></div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="info-card-pro" style="border: 1px solid rgba(93, 120, 255, 0.2);">
                    <div class="info-card-header"><i class="fas fa-tv mr-2 text-info"></i> Linked Channel</div>
                    <div class="card-body">
                        <?php if($user->channel_id): ?>
                            <div class="text-center mb-3">
                                <img src="<?= get_media_url($user->avatar, 'channel') ?>" class="rounded-circle mb-2" style="width: 55px; height: 55px; border: 2px solid var(--primary-blue); padding: 3px; background: #fff;">
                                <h6 class="font-weight-bold text-dark mb-0"><?= strtoupper($user->channel_name) ?></h6>
                                <span class="badge mt-1 mb-2" style="background: #f4f7fa; color: var(--primary-blue);">@<?= strtoupper($user->handle) ?></span>
                                <div class="channel-id-box"><?= $user->channel_unique_id ?></div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                <span class="label-small mb-0">Monetization Status</span>
                                <span class="font-weight-bold small <?= ($user->channel_monetization == 'APPROVED') ? 'text-success' : 'text-danger' ?>"><?= $user->channel_monetization ?: 'OFF' ?></span>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted"><i class="fas fa-video-slash fa-2x mb-2 opacity-25"></i><br><small class="font-weight-bold">NO LINKED CHANNEL</small></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="info-card-pro">
                    <div class="info-card-header"><i class="fas fa-shield-alt mr-2 text-warning"></i> Security Snapshot</div>
                    <div class="card-body">
                        <div class="data-row-pro">
                            <span class="label-small">Last Access IP</span>
                            <span class="text-dark font-weight-bold small"><i class="fas fa-network-wired text-muted mr-1"></i> <?= $user->last_active_ip ?? '157.34.12.XX' ?></span>
                        </div>
                        <div class="data-row-pro">
                            <span class="label-small">Primary Device Hash</span>
                            <span class="text-muted small d-block text-truncate" title="<?= $user->fcm_token ?>">
                                <i class="fas fa-fingerprint text-muted mr-1"></i> 
                                <?= $user->fcm_token ? substr($user->fcm_token, 0, 20).'...' : 'Web Session' ?>
                            </span>
                        </div>
                        <div class="data-row-pro">
                            <span class="label-small">Last Active On</span>
                            <span class="text-dark font-weight-bold small"><?= $user->last_active ? date('d M Y, h:i A', strtotime($user->last_active)) : 'Never Active' ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="info-card-pro" style="border-top: 3px solid var(--accent-red);">
                    <div class="info-card-header d-flex justify-content-between">
                        Violation Log
                        <span class="counter-pill-pro"><?= $total_strikes ?> Hits</span>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php if($last_3_strikes): foreach($last_3_strikes as $strike): ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="p-3">
                                        <div class="font-weight-bold text-dark small"><?= substr($strike->reason, 0, 20) ?>...</div>
                                        <div class="text-muted" style="font-size: 9px;"><?= $strike->type ?></div>
                                    </td>
                                    <td class="p-3 text-right align-middle">
                                        <span class="badge" style="background: <?= ($strike->status == 'ACTIVE') ? 'rgba(253, 57, 122, 0.1); color: var(--accent-red);' : '#f4f7fa; color: #888;' ?> font-size: 10px;">
                                            <?= $strike->status ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="2" class="text-center py-5 text-muted small font-weight-bold">Clean Security Record</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="info-card-pro">
                    <div class="info-card-header d-flex justify-content-between">
                        Recent Transactions
                        <span class="counter-pill-pro" style="background: var(--primary-blue);">Total Logs: <?= $total_txns ?></span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="font-size: 10px; background: #f8f9fa;">
                                    <tr>
                                        <th class="border-0 px-4 py-3">Description</th>
                                        <th class="border-0 py-3">Source Wallet</th>
                                        <th class="border-0 py-3">Reference ID</th>
                                        <th class="border-0 py-3 text-center">Execution Date</th>
                                        <th class="border-0 text-right px-4 py-3">Amount Settlement</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($last_3_txns): foreach($last_3_txns as $tx): ?>
                                    <tr style="border-bottom: 1px solid var(--border-soft);">
                                        <td class="px-4 py-3 font-weight-500 text-dark small"><?= $tx->description ?></td>
                                        <td class="py-3"><span class="badge badge-light" style="border: 1px solid #ddd;"><?= strtoupper($tx->wallet_type) ?></span></td>
                                        <td class="py-3 text-muted small">TXN-00<?= $tx->id ?></td>
                                        <td class="py-3 text-center text-muted small"><?= date('M d, Y | h:i A', strtotime($tx->created_at)) ?></td>
                                        <td class="text-right px-4 py-3 font-weight-bold <?= ($tx->type == 'credit') ? 'text-success' : 'text-danger' ?>" style="font-size: 14px;">
                                            <?= ($tx->type == 'credit') ? '+' : '-' ?> ₹<?= number_format($tx->amount, 2) ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="5" class="text-center py-5 text-muted font-weight-bold">No financial movement detected in the vault.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <?php if($total_txns > 3): ?>
                                <div class="p-3 text-center bg-light">
                                    <a href="#" class="small font-weight-bold text-primary" style="letter-spacing: 0.5px;">VIEW COMPLETE FINANCIAL LEDGER (<?= $total_txns ?> LOGS) <i class="fas fa-external-link-alt ml-2"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<?= $this->endSection() ?>
