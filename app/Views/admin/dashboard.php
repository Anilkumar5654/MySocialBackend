<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 IMAGE REPLICA CSS */
    :root {
        --bg-main: #f0f2f5; --card-bg: #ffffff; --border-soft: #eef0f3;
        --text-dark: #333333; --text-muted: #8898aa;
        --c-blue: #5578c2; --c-orange: #f29b46; --c-red: #d9534f; --c-green: #52a57e;
    }

    body { background-color: var(--bg-main); }

    /* Top Stats Cards */
    .stat-card { border-radius: 8px; color: #fff; padding: 20px 15px; display: flex; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .stat-card.c-blue { background: var(--c-blue); }
    .stat-card.c-orange { background: var(--c-orange); }
    .stat-card.c-red { background: var(--c-red); }
    .stat-card.c-green { background: var(--c-green); }
    
    .stat-icon-wrapper { font-size: 32px; opacity: 0.9; margin-right: 15px; width: 45px; text-align: center; }
    .stat-details h6 { margin: 0; font-size: 13px; font-weight: 600; text-transform: capitalize; opacity: 0.9; }
    .stat-details h3 { margin: 0; font-size: 26px; font-weight: 700; }

    /* Main Grid Cards */
    .dash-card { background: var(--card-bg); border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); margin-bottom: 20px; border: 1px solid var(--border-soft); overflow: hidden; height: auto; }
    .dash-header { padding: 15px 20px; border-bottom: 1px solid var(--border-soft); display: flex; justify-content: space-between; align-items: center; }
    .dash-header h5 { margin: 0; font-size: 15px; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; }
    .dash-header h5 i { color: var(--c-blue); margin-right: 10px; width: 20px; text-align: center; }
    .view-all { font-size: 12px; color: var(--c-blue); font-weight: 600; text-decoration: none; }

    /* 📱 SCROLLABLE WRAPPER FOR MOBILE */
    .list-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    
    .list-item { display: flex; align-items: center; padding: 15px 20px; border-bottom: 1px solid var(--border-soft); min-width: 380px; }
    .list-item:last-child { border-bottom: none; }
    
    .item-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-right: 15px; min-width: 40px; }
    .item-thumb { width: 60px; height: 40px; border-radius: 4px; object-fit: cover; margin-right: 15px; min-width: 60px; border: 1px solid #ddd; }
    .item-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 15px; min-width: 40px; border: 1px solid #ddd; }
    
    .item-info { flex: 1; overflow: hidden; display: flex; flex-direction: column; justify-content: center; }
    .title-row { display: flex; align-items: center; margin-bottom: 2px; }
    .item-title { font-size: 14px; font-weight: 700; color: #000; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-right: 8px; }
    .item-sub { font-size: 11px; color: var(--text-muted); font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .item-right { text-align: right; min-width: 80px; margin-left: 10px; display: flex; flex-direction: column; justify-content: center; }
    
    .status-badge { font-size: 9px; padding: 3px 6px; border-radius: 4px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; display: inline-block; line-height: 1; }
    .status-pending { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
    .status-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .status-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .status-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }

    .qa-btn { display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 12px 20px; border-radius: 6px; color: #fff; font-weight: 600; font-size: 14px; margin-bottom: 10px; text-decoration: none; border: none; }
    .qa-btn:hover { color: #fff; opacity: 0.9; }
    .qa-orange { background: var(--c-orange); }
    .qa-red { background: var(--c-red); }

    /* 🔥 ZIDDI 2x2 GRID FIX (Ye global theme ko override karega) 🔥 */
    @media (min-width: 768px) {
        .content .row > .col-lg-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
        }
    }

</style>

<section class="content pt-4">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="stat-card c-blue">
                    <div class="stat-icon-wrapper"><i class="fas fa-users"></i></div>
                    <div class="stat-details">
                        <h6>Total Users</h6>
                        <h3><?= isset($total_users) ? number_format($total_users) : '0' ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-card c-orange">
                    <div class="stat-icon-wrapper"><i class="fas fa-tv"></i></div>
                    <div class="stat-details">
                        <h6>Total Channels</h6>
                        <h3><?= isset($total_channels) ? number_format($total_channels) : '0' ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-card c-red">
                    <div class="stat-icon-wrapper"><i class="fas fa-video"></i></div>
                    <div class="stat-details">
                        <h6>Total Videos</h6>
                        <h3><?= isset($total_videos) ? number_format($total_videos) : '0' ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="stat-card c-green">
                    <div class="stat-icon-wrapper"><i class="fas fa-dollar-sign"></i></div>
                    <div class="stat-details">
                        <h6>Total Revenue</h6>
                        <h3>$<?= isset($total_revenue) ? number_format($total_revenue, 2) : '0.00' ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            
            <div class="col-lg-6 col-md-6">
                
                <?php if(isset($latest_videos)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-play-circle"></i> Live Video Uploads</h5>
                        <a href="<?= base_url('admin/videos') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($latest_videos)): ?><div class="p-3 text-center small text-muted">No recent uploads.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($latest_videos as $vid): ?>
                        <div class="list-item">
                            <img src="<?= get_media_url($vid->thumbnail_url, 'video_img') ?>" class="item-thumb" onerror="this.src='<?= base_url('assets/img/placeholder.png') ?>'">
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title"><?= esc($vid->title) ?></div>
                                    <?php 
                                        $vStatus = strtolower($vid->status ?? 'published');
                                        $bClass = ($vStatus == 'published') ? 'status-success' : (($vStatus == 'processing') ? 'status-pending' : 'status-danger');
                                    ?>
                                    <span class="status-badge <?= $bClass ?>"><?= esc($vStatus) ?></span>
                                </div>
                                <div class="item-sub"><?= esc($vid->channel_name ?? 'Unknown Channel') ?></div>
                            </div>
                            <div class="item-right">
                                <div class="mb-1"><?= dashboard_time_badge($vid->created_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($new_users)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-user-plus"></i> New Users</h5>
                        <a href="<?= base_url('admin/users') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($new_users)): ?><div class="p-3 text-center small text-muted">No new users.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($new_users as $user): ?>
                        <div class="list-item">
                            <img src="<?= get_media_url($user->avatar, 'profile') ?>" class="item-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($user->name) ?>&background=f0f2f5&color=5578c2'">
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title"><?= esc($user->name) ?></div>
                                    <?php if(isset($user->email_verified) && $user->email_verified == 1): ?>
                                        <span class="status-badge status-success">Verified</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Unverified</span>
                                    <?php endif; ?>
                                </div>
                                <div class="item-sub">@<?= esc($user->username) ?></div>
                            </div>
                            <div class="item-right">
                                <div><?= dashboard_time_badge($user->created_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($recent_strikes)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-gavel text-danger"></i> Moderation Strikes</h5>
                        <a href="<?= base_url('admin/moderation/strikes') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($recent_strikes)): ?><div class="p-3 text-center small text-muted">No active strikes.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($recent_strikes as $strike): ?>
                        <div class="list-item align-items-start">
                            <div class="item-icon bg-light text-danger border mt-1"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="item-info">
                                <div class="title-row">
                                    <?php 
                                        $targetName = $strike->content_type . ' #' . $strike->content_id;
                                        if ($strike->content_type == 'VIDEO' && !empty($strike->video_title)) {
                                            $targetName = 'Video: ' . $strike->video_title;
                                        } elseif ($strike->content_type == 'REEL' && !empty($strike->reel_caption)) {
                                            $captionShort = strlen($strike->reel_caption) > 15 ? substr($strike->reel_caption, 0, 15) . '...' : $strike->reel_caption;
                                            $targetName = 'Reel: ' . $captionShort;
                                        } elseif ($strike->content_type == 'CHANNEL') {
                                            $targetName = 'Channel: ' . $strike->channel_name;
                                        }
                                    ?>
                                    <div class="item-title text-capitalize" title="<?= esc($targetName) ?>"><?= esc($targetName) ?></div>
                                    <?php 
                                        $sStatus = strtolower($strike->status ?? 'active');
                                        $sClass = ($sStatus == 'active') ? 'status-danger' : 'status-success';
                                    ?>
                                    <span class="status-badge <?= $sClass ?>"><?= esc($sStatus) ?></span>
                                    <span class="status-badge status-info ml-1"><?= esc($strike->report_source ?? 'System') ?></span>
                                </div>
                                <div class="item-sub text-truncate mb-1">
                                    <span class="text-dark font-weight-bold">@<?= esc($strike->channel_name ?? 'Unknown') ?></span> • <?= esc($strike->reason) ?>
                                </div>
                            </div>
                            <div class="item-right mt-1">
                                <div><?= dashboard_time_badge($strike->created_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($pending_reports)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-flag text-warning"></i> Pending Reports</h5>
                        <a href="<?= base_url('admin/moderation/reports') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($pending_reports)): ?><div class="p-3 text-center small text-muted">No pending reports.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($pending_reports as $report): ?>
                        <div class="list-item">
                            <div class="item-icon bg-light text-warning border"><i class="fas fa-bullhorn"></i></div>
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title"><?= esc($report->reason) ?></div>
                                    <span class="status-badge status-pending">New</span>
                                </div>
                                <div class="item-sub">Target: <?= esc($report->reportable_type) ?> • By: @<?= esc($report->reporter) ?></div>
                            </div>
                            <div class="item-right">
                                <div><?= dashboard_time_badge($report->created_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-6 col-md-6">
                
                <?php if(isset($pending_kyc)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-id-card text-info"></i> KYC Verification</h5>
                        <a href="<?= base_url('admin/kyc/requests') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($pending_kyc)): ?><div class="p-3 text-center small text-muted">No pending KYC.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($pending_kyc as $kyc): ?>
                        <div class="list-item">
                            <img src="<?= get_media_url($kyc->avatar ?? '', 'profile') ?>" class="item-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($kyc->full_name) ?>'">
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title"><?= esc($kyc->full_name) ?></div>
                                    <span class="status-badge status-pending">Pending Review</span>
                                </div>
                                <div class="item-sub">Doc: <?= esc($kyc->document_type) ?></div>
                            </div>
                            <div class="item-right">
                                <div><?= dashboard_time_badge($kyc->submitted_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($pending_withdrawals)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-money-check-alt text-success"></i> Finance Payouts</h5>
                        <a href="<?= base_url('admin/finance/withdrawal') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($pending_withdrawals)): ?><div class="p-3 text-center small text-muted">No pending payouts.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($pending_withdrawals as $with): ?>
                        <div class="list-item">
                            <div class="item-icon bg-light text-success border"><i class="fas fa-university"></i></div>
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title text-success font-weight-bold">$<?= number_format($with->amount, 2) ?></div>
                                    <?php 
                                        $wStatus = strtolower($with->status ?? 'pending');
                                        $wClass = ($wStatus == 'completed') ? 'status-success' : (($wStatus == 'pending') ? 'status-pending' : 'status-danger');
                                    ?>
                                    <span class="status-badge <?= $wClass ?>"><?= esc($wStatus) ?></span>
                                </div>
                                <div class="item-sub">@<?= esc($with->username ?? 'User') ?> • <?= strtoupper($with->payment_method ?? 'Bank') ?></div>
                            </div>
                            <div class="item-right">
                                <div><?= dashboard_time_badge($with->created_at) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(isset($monetization_requests)): ?>
                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-hand-holding-usd text-primary"></i> Monetization Req.</h5>
                        <a href="<?= base_url('admin/channels/monetization') ?>" class="view-all">View All</a>
                    </div>
                    <?php if(empty($monetization_requests)): ?><div class="p-3 text-center small text-muted">No pending requests.</div><?php endif; ?>
                    <div class="list-wrapper">
                        <?php foreach($monetization_requests as $req): ?>
                        <div class="list-item">
                            <img src="<?= get_media_url($req->avatar, 'profile') ?>" class="item-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($req->name) ?>'">
                            <div class="item-info">
                                <div class="title-row">
                                    <div class="item-title"><?= esc($req->name) ?></div>
                                    <span class="status-badge status-pending">Pending Review</span>
                                </div>
                                <div class="item-sub">@<?= esc($req->handle) ?></div>
                            </div>
                            <div class="item-right">
                                <div><?= dashboard_time_badge($req->monetization_applied_date) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="dash-card">
                    <div class="dash-header">
                        <h5><i class="fas fa-bolt text-warning"></i> Quick Actions</h5>
                    </div>
                    <div class="p-3">
                        <a href="<?= base_url('admin/channels') ?>" class="qa-btn qa-orange">
                            <span><i class="fas fa-ban mr-2"></i> Suspend Channel</span> <i class="fas fa-caret-right"></i>
                        </a>
                        <a href="<?= base_url('admin/videos') ?>" class="qa-btn qa-red">
                            <span><i class="fas fa-trash-alt mr-2"></i> Delete Video</span> <i class="fas fa-trash"></i>
                        </a>
                        <a href="<?= base_url('admin/settings/points') ?>" class="qa-btn" style="background: #7f8fa6;">
                            <span><i class="fas fa-cog mr-2"></i> System Config</span> <i class="fas fa-caret-right"></i>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
