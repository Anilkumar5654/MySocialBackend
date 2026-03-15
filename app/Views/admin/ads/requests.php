<?= $this->extend('admin/layout/main') ?>
<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    /* 📊 Table Styling */
    .table-pro thead th { 
        background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase; 
        letter-spacing: 0.5px; border: none; padding: 15px 20px; font-weight: 800;
    }
    .table-pro td { vertical-align: middle; padding: 18px 20px; border-top: 1px solid var(--border-soft); }

    /* 🏷️ Badges & Tags */
    .tag-pro {
        font-size: 9px; font-weight: 800; padding: 4px 10px; border-radius: 4px;
        text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid var(--border-soft); display: inline-flex; align-items: center; gap: 5px;
    }
    .tag-blue { background: rgba(93, 120, 255, 0.1); color: var(--primary-blue); border-color: rgba(93, 120, 255, 0.2); }
    .tag-gray { background: #f4f7fa; color: var(--text-dark); }

    /* 🚀 Content Type Badges */
    .ctype-badge { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; border: 1px solid var(--border-soft); }
    .ctype-boost { background: #fff9e6; color: #ff9f43; border-color: #ffe5b5; }
    .ctype-ad { background: #eef2ff; color: var(--primary-blue); border-color: #dbe4ff; }

    /* 🔘 Action Button Pro */
    .btn-review-pro { 
        background: #fff; color: var(--primary-blue); border: 1px solid var(--border-soft); font-weight: 700; 
        font-size: 11px; padding: 10px 20px; border-radius: 8px; transition: 0.3s;
        text-transform: uppercase; display: inline-block; letter-spacing: 0.5px; box-shadow: var(--card-shadow);
    }
    .btn-review-pro:hover { 
        background: var(--primary-blue); color: #fff; transform: translateY(-2px); border-color: var(--primary-blue);
    }
    
    /* 🔒 Lock Styling */
    .btn-locked { background: #f8f9fa !important; color: #a0aec0 !important; cursor: not-allowed !important; transform: none !important; box-shadow: none !important; }
    .tag-locked { background: rgba(239, 68, 68, 0.1); color: #ef4444; border-color: rgba(239, 68, 68, 0.2); }

    /* Text & Avatars */
    .black-text { color: #000 !important; font-weight: 700; }
    .avatar-pro { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-soft); background: #f8f9fa; }
    
    .pulse-orange {
        width: 8px; height: 8px; background: var(--accent-orange); border-radius: 50%;
        display: inline-block; margin-right: 6px; animation: shadow-pulse 2s infinite;
    }
    @keyframes shadow-pulse {
        0% { box-shadow: 0 0 0 0 rgba(249, 155, 45, 0.4); }
        70% { box-shadow: 0 0 0 8px rgba(249, 155, 45, 0); }
        100% { box-shadow: 0 0 0 0 rgba(249, 155, 45, 0); }
    }
</style>

<div class="content-header pt-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-tasks mr-2 text-primary"></i> Ad Approvals
            </h1>
            <p class="text-muted small mb-0 font-weight-bold">
                <span class="pulse-orange"></span> PENDING QUEUE: 
                <span class="text-dark"><?= !empty($ads) ? count($ads) : 0 ?> Requests</span>
            </p>
        </div>
        <div>
            <a href="<?= base_url('admin/ads') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-list mr-1"></i> VIEW ALL ADS
            </a>
        </div>
    </div>
</div>

<section class="content mt-4">
    <div class="container-fluid">
        
        <?php if(session()->has('success')): ?>
            <div class="alert shadow-sm mb-4" style="background: rgba(10, 187, 135, 0.05); border: 1px solid var(--accent-green); color: var(--accent-green); border-radius: 10px;">
                <i class="fas fa-check-circle mr-2"></i> <?= session('success') ?>
            </div>
        <?php endif; ?>

        <div class="card card-pro">
            <div class="table-responsive">
                <table class="table table-pro mb-0">
                    <thead>
                        <tr>
                            <th>Campaign Info</th>
                            <th>Ad Type</th>
                            <th>Budget</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($ads)): ?>
                            <?php foreach($ads as $req): ?>
                            <?php 
                                $isLocked = false;
                                if (!empty($req->locked_by) && $req->locked_by != session('admin_id')) {
                                    $lockTime = strtotime($req->locked_at);
                                    if ((time() - $lockTime) < 900) {
                                        $isLocked = true;
                                    }
                                }
                            ?>
                            <tr style="<?= $isLocked ? 'opacity: 0.8; background: #fafafa;' : '' ?>">
                                <td>
                                    <?php 
                                        $place = $req->placement ?? 'feed';
                                        $cls = 'tag-gray'; $icon = 'fa-link';
                                        if($place == 'reel') { $cls = 'tag-blue'; $icon = 'fa-bolt'; }
                                        elseif($place == 'video') { $cls = 'tag-blue'; $icon = 'fa-play-circle'; }
                                        elseif($place == 'instream') { $cls = 'tag-gray'; $icon = 'fa-film'; }
                                    ?>
                                    <div class="mb-2 d-flex align-items-center">
                                        <span class="tag-pro <?= $cls ?> mr-2">
                                            <i class="fas <?= $icon ?>"></i> <?= strtoupper($place) ?>
                                        </span>
                                        
                                        <?php if($isLocked): ?>
                                            <span class="tag-pro tag-locked">
                                                <i class="fas fa-lock mr-1"></i> IN REVIEW BY <?= strtoupper($req->locked_admin_name ?? 'ADMIN') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <img src="<?= get_media_url($req->avatar, 'profile') ?>" class="avatar-pro" onerror="this.src='<?= base_url('assets/img/default-avatar.png') ?>'">
                                            <?php if($req->is_verified): ?>
                                                <div class="position-absolute" style="bottom: -4px; right: -4px; background: #fff; border-radius: 50%; padding: 1px;">
                                                    <i class="fas fa-check-circle text-primary" style="font-size: 11px;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-3">
                                            <div class="black-text text-truncate" style="font-size: 14px; max-width: 250px;">
                                                <?= esc($req->title) ?>
                                            </div>
                                            <div class="text-primary small font-weight-bold mt-1">
                                                @<?= esc($req->username) ?> <span class="text-muted mx-1">•</span> <?= time_ago($req->created_at) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <?php if(($req->ad_content_type ?? $req->ad_type) == 'boosted_content'): ?>
                                        <span class="ctype-badge ctype-boost">
                                            <i class="fas fa-rocket"></i> BOOSTED POST
                                        </span>
                                        <div class="mt-2 text-muted small font-weight-bold" style="font-size: 10px;">ID: #<?= $req->source_post_id ?></div>
                                    <?php else: ?>
                                        <span class="ctype-badge ctype-ad">
                                            <i class="fas fa-layer-group"></i> CUSTOM AD
                                        </span>
                                        <div class="mt-2 text-muted small font-weight-bold" style="font-size: 10px;">
                                            <?= strpos($req->media_type ?? '', 'video') !== false ? 'Video Creative' : 'Image Banner' ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="black-text" style="font-size: 15px;">₹<?= number_format($req->budget, 2) ?></div>
                                    <div class="text-muted small font-weight-bold mt-1" style="font-size: 10px; text-transform: uppercase;">
                                        <?= strtoupper($req->bid_type) ?> </div>
                                </td>

                                <td class="text-right">
                                    <?php if($isLocked): ?>
                                        <button class="btn-review-pro btn-locked" disabled title="Locked for review">
                                            LOCKED <i class="fas fa-lock ml-1" style="font-size: 9px;"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/ads/view/'.$req->id) ?>" class="btn-review-pro">
                                            REVIEW <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <i class="fas fa-check-double fa-3x text-muted mb-3 opacity-25"></i>
                                    <h6 class="text-muted font-weight-bold">All clear! No pending ads to moderate.</h6>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
