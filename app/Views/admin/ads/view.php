<?= $this->extend('admin/layout/main') ?>
<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    /* 📱 Dynamic Preview Container */
    .preview-bg { 
        background: #f4f7fa; padding: 20px; display: flex; justify-content: center; 
        align-items: center; border-bottom: 1px solid var(--border-soft);
        min-height: auto; 
    }
    
    /* Smart Mockups based on Type */
    .mockup-container { position: relative; box-shadow: 0 20px 40px rgba(0,0,0,0.2); overflow: hidden; background: #000; }
    .phone-mockup { width: 280px; height: 500px; border: 10px solid #212529; border-radius: 35px; }
    .video-mockup { width: 100%; max-width: 500px; aspect-ratio: 16/9; border-radius: 12px; border: 4px solid #212529; }
    .feed-mockup { width: 320px; aspect-ratio: 1/1; border-radius: 12px; border: 1px solid var(--border-soft); }

    /* Native UI Simulation */
    .ad-overlay-pro { 
        position: absolute; bottom: 0; left: 0; right: 0; padding: 15px; 
        background: linear-gradient(to top, rgba(0,0,0,0.9) 20%, transparent); z-index: 10; 
    }
    .sponsored-tag {
        position: absolute; top: 15px; left: 15px; background: rgba(0,0,0,0.5); 
        backdrop-filter: blur(5px); color: #fff; padding: 2px 8px; border-radius: 4px; 
        font-size: 9px; font-weight: 700; text-transform: uppercase; z-index: 20;
    }
    
    .cta-button-pro { 
        background: var(--primary-blue); color: #fff; text-align: center; padding: 8px; 
        border-radius: 6px; font-weight: 800; font-size: 11px; text-transform: uppercase; 
        border: none; width: 100%; margin-top: 8px;
    }
    
    /* 📊 Stats Grid */
    .stat-card-pro { background: #fff; border: 1px solid var(--border-soft); border-radius: 12px; padding: 15px; text-align: center; height: 100%; }
    .stat-value-pro { font-size: 20px; font-weight: 800; color: #000; display: block; }
    .stat-label-pro { font-size: 8px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-top: 4px; display: block; }
    
    /* ⏳ Duration Badge */
    .date-box { text-align: center; background: #f8f9fa; padding: 5px 10px; border-radius: 8px; min-width: 60px; border: 1px solid var(--border-soft); }
    .date-day { display: block; font-size: 14px; font-weight: 900; color: #000; line-height: 1; }
    .date-month { display: block; font-size: 8px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; }

    .status-active-pulse {
        width: 8px; height: 8px; background: #22c55e; border-radius: 50%;
        display: inline-block; animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(34, 197, 94, 0); }
        100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
    }

    .form-control-pro { background: #fff !important; border: 1px solid var(--border-soft) !important; color: #000 !important; border-radius: 8px; font-size: 14px; font-weight: 600; }
    .label-pro { font-size: 10px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block; }
    .black-text { color: #000 !important; font-weight: 700; }
</style>

<div class="content-header pt-3">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
        <div class="mb-2">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -1px; font-size: 24px;">
                <i class="fas fa-search-plus mr-2 text-primary"></i> Review Campaign
            </h1>
            <p class="text-muted small mb-0 font-weight-bold mt-1">
                ID: #<?= $ad->id ?> <span class="mx-1">•</span> 
                <span class="text-uppercase text-primary"><?= strtoupper($ad->placement) ?> PLACEMENT</span>
            </p>
        </div>
        
        <div class="d-flex align-items-center">
            <?php if($ad->status == 'active'): ?>
                <a href="<?= base_url('admin/ads/quick_status/'.$ad->id.'/paused') ?>" class="btn btn-outline-warning btn-sm mr-2" style="font-weight: 800; border-radius: 8px;">
                    <i class="fas fa-pause mr-1"></i> PAUSE
                </a>
            <?php elseif($ad->status == 'paused'): ?>
                <a href="<?= base_url('admin/ads/quick_status/'.$ad->id.'/active') ?>" class="btn btn-outline-success btn-sm mr-2" style="font-weight: 800; border-radius: 8px;">
                    <i class="fas fa-play mr-1"></i> RESUME
                </a>
            <?php endif; ?>

            <a href="<?= base_url('admin/ads/requests') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-times mr-1"></i> CLOSE
            </a>
        </div>
    </div>
</div>

<section class="content mt-2">
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-lg-7">
                <div class="card-pro">
                    <div class="card-header-pro d-flex justify-content-between align-items-center">
                        <h6 class="text-dark m-0 font-weight-bold small">LIVE PREVIEW</h6>
                        <?php if($ad->status == 'active'): ?>
                            <span class="badge badge-light" style="border: 1px solid #eee;"><span class="status-active-pulse mr-1"></span> LIVE NOW</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="preview-bg">
                        <?php 
                            $mediaUrl = get_media_url($ad->media_url);
                            $placement = $ad->placement;
                            $isVideo = ($ad->media_type == 'video');
                            $mockupClass = ($placement == 'reel') ? 'phone-mockup' : (($placement == 'video' || $placement == 'instream') ? 'video-mockup' : 'feed-mockup');
                        ?>
                        <div class="mockup-container <?= $mockupClass ?>">
                            <?php if($isVideo): ?>
                                <video autoplay muted loop playsinline style="width: 100%; height: 100%; object-fit: cover;">
                                    <source src="<?= $mediaUrl ?>" type="video/mp4">
                                </video>
                            <?php else: ?>
                                <img src="<?= $mediaUrl ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php endif; ?>
                            <div class="sponsored-tag">Sponsored</div>
                            
                            <?php if($placement == 'reel' || $placement == 'feed'): ?>
                            <div class="ad-overlay-pro">
                                <div class="d-flex align-items-center mb-1">
                                    <div style="width: 22px; height: 22px; background: #ddd; border-radius: 50%; margin-right: 8px;"></div>
                                    <small class="text-white font-weight-bold">@<?= esc($ad->username) ?></small>
                                </div>
                                <h6 class="text-white mb-1 small font-weight-bold"><?= esc($ad->title) ?></h6>
                                <button class="cta-button-pro"><?= strtoupper($ad->cta_label ?? 'Learn More') ?></button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="card-pro">
                    <div class="card-header-pro"><h6 class="text-dark m-0 font-weight-bold small">CAMPAIGN DURATION</h6></div>
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="date-box">
                                <span class="date-month"><?= date('M', strtotime($ad->start_date)) ?></span>
                                <span class="date-day"><?= date('d', strtotime($ad->start_date)) ?></span>
                            </div>
                            <div class="flex-grow-1 px-3 text-center position-relative">
                                <div style="height: 2px; background: #e2e8f0; width: 100%; position: absolute; top: 50%; z-index: 1;"></div>
                                <span class="badge badge-white border shadow-sm position-relative" style="z-index: 2; font-size: 11px; font-weight: 800; background:#fff;">
                                    <?php 
                                        $diff = strtotime($ad->end_date) - strtotime($ad->start_date);
                                        echo round($diff / (60 * 60 * 24)) . " DAYS TOTAL";
                                    ?>
                                </span>
                            </div>
                            <div class="date-box">
                                <span class="date-month"><?= date('M', strtotime($ad->end_date)) ?></span>
                                <span class="date-day"><?= date('d', strtotime($ad->end_date)) ?></span>
                            </div>
                        </div>
                        
                        <?php 
                            $remaining = strtotime($ad->end_date) - time();
                            $daysLeft = max(0, round($remaining / (60 * 60 * 24)));
                        ?>
                        <div class="mt-3 text-center">
                            <small class="font-weight-bold <?= $daysLeft > 0 ? 'text-primary' : 'text-danger' ?>">
                                <i class="far fa-clock mr-1"></i> <?= $daysLeft > 0 ? $daysLeft . " Days Remaining" : "Campaign Expired" ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="row mb-4">
                    <div class="col-4"><div class="stat-card-pro"><span class="stat-value-pro" style="color:var(--primary-blue)"><?= number_format($ad->reach ?? 0) ?></span><span class="stat-label-pro">Reach</span></div></div>
                    <div class="col-4"><div class="stat-card-pro"><span class="stat-value-pro" style="color:var(--accent-orange)"><?= number_format($ad->views ?? 0) ?></span><span class="stat-label-pro">Views</span></div></div>
                    <div class="col-4"><div class="stat-card-pro"><span class="stat-value-pro" style="color:var(--accent-green)">₹<?= number_format($ad->spent, 2) ?></span><span class="stat-label-pro">Spent</span></div></div>
                </div>

                <div class="card-pro p-4 mb-4">
                    <label class="label-pro">Budget Pacing (Total: ₹<?= number_format($ad->budget, 2) ?>)</label>
                    <?php $percent = ($ad->budget > 0) ? ($ad->spent / $ad->budget) * 100 : 0; ?>
                    <div class="progress mb-2" style="height: 8px; border-radius: 10px; background:#eee;">
                        <div class="progress-bar bg-success" style="width: <?= min(100, $percent) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between small">
                        <span class="black-text">Spent: ₹<?= number_format($ad->spent, 2) ?></span>
                        <span class="text-muted font-weight-bold">Goal: ₹<?= number_format($ad->budget, 2) ?></span>
                    </div>
                </div>

                <div class="card-pro" style="border-top: 4px solid var(--primary-blue);">
                    <div class="card-header-pro"><h6 class="text-dark m-0 font-weight-bold small">AD MODERATION</h6></div>
                    <div class="card-body">
                        <form action="<?= base_url('admin/ads/update_status') ?>" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="ad_id" value="<?= $ad->id ?>">
                            
                            <div class="mb-4">
                                <label class="label-pro">Update Status</label>
                                <select name="status" id="statusSelect" class="form-control-pro w-100" onchange="toggleRejection()">
                                    <option value="pending_approval" <?= $ad->status == 'pending_approval' ? 'selected' : '' ?>>🟡 Pending Review</option>
                                    <option value="active" <?= $ad->status == 'active' ? 'selected' : '' ?>>🟢 Live (Approved)</option>
                                    <option value="paused" <?= $ad->status == 'paused' ? 'selected' : '' ?>>⚪ Paused</option>
                                    <option value="rejected" <?= $ad->status == 'rejected' ? 'selected' : '' ?>>🔴 Rejected</option>
                                </select>
                            </div>

                            <div id="rejectBox" class="mb-4" style="display:none;">
                                <label class="label-pro text-danger">Rejection Reason</label>
                                <textarea name="rejection_reason" class="form-control-pro w-100" style="height: 80px;" placeholder="Describe why..."><?= esc($ad->admin_rejection_reason) ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-6">
                                    <label class="label-pro">Weight</label>
                                    <select name="priority_weight" class="form-control-pro w-100">
                                        <option value="1" <?= $ad->priority_weight == 1 ? 'selected' : '' ?>>Normal</option>
                                        <option value="5" <?= $ad->priority_weight == 5 ? 'selected' : '' ?>>High (5x)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="label-pro">Daily Cap (₹)</label>
                                    <input type="number" name="daily_limit" class="form-control-pro w-100" value="<?= $ad->daily_limit ?? 500 ?>">
                                </div>
                            </div>

                            <button type="submit" class="cta-button-pro py-3 mt-4 w-100">SAVE & UPDATE CAMPAIGN</button>
                        </form>
                    </div>
                </div>

                <div class="card-pro p-3">
                    <div class="d-flex align-items-center">
                        <img src="<?= get_media_url($ad->avatar, 'profile') ?>" style="width:40px; height:40px; border-radius:8px; object-fit:cover;" class="mr-3">
                        <div style="flex: 1; min-width: 0;">
                            <label class="label-pro mb-0">Advertiser</label>
                            <div class="black-text text-truncate">@<?= esc($ad->username) ?></div>
                        </div>
                        <a href="<?= base_url('admin/users/edit/'.$ad->advertiser_id) ?>" class="btn btn-light btn-sm border"><i class="fas fa-chevron-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function toggleRejection() {
        var status = document.getElementById('statusSelect').value;
        var box = document.getElementById('rejectBox');
        if(box) box.style.display = (status === 'rejected') ? 'block' : 'none';
    }
    document.addEventListener("DOMContentLoaded", toggleRejection);
</script>

<?= $this->endSection() ?>
