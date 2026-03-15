<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php $request = service('request'); ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; font-weight: 600;
    }

    /* 📊 Table Styling */
    .table-pro thead th { 
        background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase; 
        letter-spacing: 0.5px; border: none; padding: 15px 20px; font-weight: 800;
    }
    .table-pro td { vertical-align: middle; padding: 18px 20px; border-top: 1px solid var(--border-soft); }

    /* 🏷️ Badges & Tags */
    .ad-tag {
        font-size: 9px; font-weight: 800; padding: 4px 10px; border-radius: 4px;
        text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid var(--border-soft); display: inline-flex; align-items: center; gap: 5px;
    }
    .tag-reel { background: rgba(168, 85, 247, 0.1); color: #a855f7; border-color: rgba(168, 85, 247, 0.2); }
    .tag-video { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: rgba(59, 130, 246, 0.2); }
    .tag-instream { background: #f4f7fa; color: #555; }

    /* 📈 Performance Pills */
    .perf-pill {
        background: #f8f9fa; border: 1px solid var(--border-soft); padding: 5px 10px; border-radius: 6px;
        font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; color: #444;
    }

    /* 💰 Budget Bar */
    .budget-track { height: 6px; background: #eee; border-radius: 10px; overflow: hidden; margin-top: 8px; }
    .budget-fill { height: 100%; background: var(--primary-blue); border-radius: 10px; }
    .budget-warning { background: var(--accent-orange); }
    .budget-danger { background: var(--accent-red); }

    /* Pure Black Text */
    .black-text { color: #000 !important; font-weight: 700; }
    .merchant-avatar-pro { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-soft); }
    
    .action-btn-pro { 
        width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        background: #fff; border: 1px solid var(--border-soft); color: var(--primary-blue); transition: 0.2s;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); background: var(--primary-blue); color: #fff; }
</style>

<div class="content-header pt-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-history mr-2 text-primary"></i> Ad Campaigns
            </h1>
            <p class="text-muted small mb-0 font-weight-bold">Manage and monitor all historic promotions</p>
        </div>
    </div>
</div>

<section class="content mt-4">
    <div class="container-fluid">
        
        <div class="card card-pro p-3">
            <form action="<?= base_url('admin/ads/campaigns') ?>" method="get" class="row align-items-end">
                <div class="col-md-4 mb-2">
                    <label class="text-muted small font-weight-bold text-uppercase">Search Campaign</label>
                    <input type="text" name="search" class="form-control form-control-pro" placeholder="Title, ID or Channel..." value="<?= esc($request->getGet('search')) ?>">
                </div>
                <div class="col-md-2 mb-2">
                    <label class="text-muted small font-weight-bold text-uppercase">Goal</label>
                    <select name="status" class="form-control form-control-pro">
                        <option value="">All Status</option>
                        <option value="active" <?= $request->getGet('status')=='active'?'selected':'' ?>>🟢 Active</option>
                        <option value="paused" <?= $request->getGet('status')=='paused'?'selected':'' ?>>⚪ Paused</option>
                        <option value="rejected" <?= $request->getGet('status')=='rejected'?'selected':'' ?>>🔴 Rejected</option>
                    </select>
                </div>
                <div class="col-md-4 mb-2">
                    <button type="submit" class="btn btn-primary font-weight-bold px-4" style="height: 45px; border-radius: 8px; background: var(--primary-blue); border: none;">
                        <i class="fas fa-filter mr-1"></i> APPLY FILTERS
                    </button>
                    <a href="<?= base_url('admin/ads/campaigns') ?>" class="btn btn-light ml-2 font-weight-bold" style="height: 45px; border-radius: 8px; border: 1px solid var(--border-soft);">RESET</a>
                </div>
            </form>
        </div>

        <div class="card card-pro">
            <div class="table-responsive">
                <table class="table table-pro mb-0">
                    <thead>
                        <tr>
                            <th>Campaign Details</th>
                            <th>Performance (Reach/Views)</th>
                            <th class="text-center">Weight</th>
                            <th style="width: 240px;">Budget & Progress</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($campaigns)): ?>
                            <?php foreach($campaigns as $ad): ?>
                            <tr>
                                <td>
                                    <div class="mb-2">
                                        <?php 
                                            $place = $ad->placement;
                                            $tagCls = ($place == 'reel') ? 'tag-reel' : (($place == 'video') ? 'tag-video' : 'tag-instream');
                                            $icon = ($place == 'reel') ? 'fa-bolt' : (($place == 'video') ? 'fa-play-circle' : 'fa-film');
                                        ?>
                                        <span class="ad-tag <?= $tagCls ?>">
                                            <i class="fas <?= $icon ?>"></i> <?= strtoupper($place) ?> 
                                            <span style="opacity:0.3; margin: 0 5px;">|</span>
                                            <?= ($ad->ad_type ?? '') == 'boosted_content' ? 'BOOST' : 'AD' ?>
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($ad->advertiser_avatar, 'profile') ?>" 
                                             class="merchant-avatar-pro mr-3"
                                             onerror="this.src='<?= base_url('assets/img/default-avatar.png') ?>'">
                                        <div>
                                            <div class="black-text text-truncate" style="font-size: 14px; max-width: 220px;">
                                                <?= esc($ad->title) ?>
                                            </div>
                                            <div class="text-primary font-weight-bold small" style="font-size: 11px;">
                                                @<?= esc($ad->advertiser_username) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex mb-1" style="gap: 5px;">
                                        <span class="perf-pill" title="Reach (1s)"><i class="fas fa-users text-purple"></i> <?= number_format($ad->reach ?? 0) ?></span>
                                        <span class="perf-pill" title="Quality Views (3s+)"><i class="fas fa-eye text-primary"></i> <?= number_format($ad->views ?? 0) ?></span>
                                    </div>
                                    <?php $ir = ($ad->reach > 0) ? ($ad->views / $ad->reach) * 100 : 0; ?>
                                    <div class="small font-weight-bold ml-1">
                                        INTEREST: <span class="<?= $ir > 15 ? 'text-success' : 'text-muted' ?>"><?= number_format($ir, 1) ?>%</span>
                                    </div>
                                </td>

                                <td class="text-center">
                                    <span class="badge shadow-sm" style="background: #fff; border: 1px solid var(--border-soft); color: #000; padding: 6px 12px; border-radius: 6px;">
                                        <?= $ad->priority_weight ?>x
                                    </span>
                                </td>

                                <td>
                                    <?php 
                                        $percent = ($ad->budget > 0) ? ($ad->spent / $ad->budget) * 100 : 0;
                                        $barCls = ($percent > 90) ? 'budget-danger' : (($percent > 75) ? 'budget-warning' : '');
                                    ?>
                                    <div class="d-flex justify-content-between mb-1 small">
                                        <span class="black-text">₹<?= number_format($ad->spent, 2) ?></span>
                                        <span class="text-muted font-weight-bold"><?= round($percent, 1) ?>%</span>
                                    </div>
                                    <div class="budget-track">
                                        <div class="budget-fill <?= $barCls ?>" style="width: <?= min(100, $percent) ?>%"></div>
                                    </div>
                                    <div class="d-flex justify-content-between mt-1" style="font-size: 10px;">
                                        <span class="text-muted font-weight-bold">LIMIT: ₹<?= number_format($ad->daily_limit ?? 0) ?></span>
                                        <span class="<?= $ad->status == 'active' ? 'text-success' : ($ad->status == 'paused' ? 'text-warning' : 'text-danger') ?> font-weight-bold">
                                            <?= strtoupper($ad->status) ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="text-right">
                                    <a href="<?= base_url('admin/ads/view/'.$ad->id) ?>" class="action-btn-pro mr-1" title="View & Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="javascript:void(0)" class="action-btn-pro delete-campaign" 
                                       data-id="<?= $ad->id ?>" data-title="<?= esc($ad->title) ?>"
                                       style="color: var(--accent-red);" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-folder-open fa-3x text-muted mb-3 opacity-25"></i>
                                    <p class="text-muted font-weight-bold">No campaign history found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(isset($pager)): ?>
            <div class="p-3 border-top bg-light">
                <?= $pager->links() ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Delete Confirmation
    const deleteBtns = document.querySelectorAll('.delete-campaign');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const adId = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');
            if (confirm(`Are you sure you want to delete campaign: "${title}"?\nThis action cannot be undone.`)) {
                window.location.href = "<?= base_url('admin/ads/delete/') ?>" + adId;
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
