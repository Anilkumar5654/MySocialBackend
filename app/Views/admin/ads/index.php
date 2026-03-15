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

    /* 🔍 Tabs Styling */
    .status-tab {
        border: 1px solid var(--border-soft); background: #fff; color: var(--text-muted);
        padding: 8px 16px; border-radius: 30px; font-size: 11px; font-weight: 700;
        text-transform: uppercase; cursor: pointer; transition: 0.3s; margin-right: 5px;
        text-decoration: none; display: inline-block;
    }
    .status-tab:hover, .status-tab.active {
        background: var(--primary-blue); color: #fff; border-color: var(--primary-blue);
        box-shadow: var(--card-shadow); text-decoration: none;
    }

    /* 📊 Table Styling */
    .table-pro thead th { 
        background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase; 
        letter-spacing: 0.5px; border: none; padding: 15px 20px; font-weight: 800;
    }
    .table-pro td { vertical-align: middle; padding: 18px 20px; border-top: 1px solid var(--border-soft); }

    /* 🖼️ Ad Preview */
    .ad-preview-box {
        width: 60px; height: 34px; border-radius: 6px; overflow: hidden; position: relative;
        border: 1px solid var(--border-soft); background: #f4f7fa;
    }
    .ad-preview-box img { width: 100%; height: 100%; object-fit: cover; }
    .type-overlay {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0,0,0,0.2); display: flex; align-items: center; justify-content: center;
        color: #fff; font-size: 10px;
    }

    /* 🟢 Professional Status Badges */
    .st-badge-pro {
        font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 4px;
        text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; border: 1px solid transparent;
    }
    .st-active { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border-color: var(--accent-green); }
    .st-pending { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border-color: var(--accent-orange); }
    .st-rejected { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border-color: var(--accent-red); }
    .st-paused { background: #f4f7fa; color: #888; border-color: var(--border-soft); }

    /* 🔘 Action Buttons */
    .action-btn-pro {
        width: 32px; height: 32px; border-radius: 8px; display: inline-flex;
        align-items: center; justify-content: center; transition: 0.2s;
        background: #fff; color: var(--primary-blue); border: 1px solid var(--border-soft);
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); background: var(--primary-blue); color: #fff; }
    
    .black-text { color: #000 !important; font-weight: 700; }
</style>

<div class="content-header pt-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-ad mr-2 text-primary"></i> Ads Management
            </h1>
            <p class="text-muted small mb-0 font-weight-bold">Approve, monitor, and manage user campaigns</p>
        </div>
        <a href="<?= base_url('admin/ads/create') ?>" class="btn btn-primary font-weight-bold px-4 shadow-sm" style="background: var(--primary-blue); border:none; border-radius: 10px; height: 45px; display: flex; align-items: center;">
            <i class="fas fa-plus mr-2"></i> NEW CAMPAIGN
        </a>
    </div>
</div>

<section class="content mt-4">
    <div class="container-fluid">
        
        <div class="card card-pro p-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 15px;">
                <div class="d-flex flex-wrap" style="gap: 5px;">
                    <?php $currStatus = $status ?? 'all'; ?>
                    <a href="?status=all" class="status-tab <?= $currStatus == 'all' ? 'active' : '' ?>">All</a>
                    <a href="?status=active" class="status-tab <?= $currStatus == 'active' ? 'active' : '' ?>">Active</a>
                    <a href="?status=pending_approval" class="status-tab <?= $currStatus == 'pending_approval' ? 'active' : '' ?>">Pending</a>
                    <a href="?status=rejected" class="status-tab <?= $currStatus == 'rejected' ? 'active' : '' ?>">Rejected</a>
                    <a href="?status=paused" class="status-tab <?= $currStatus == 'paused' ? 'active' : '' ?>">Paused</a>
                </div>
                <div style="flex: 1; max-width: 300px;">
                    <form method="get" action="">
                        <input type="text" name="q" class="form-control form-control-pro" placeholder="Search by ID, Title or User..." value="<?= esc($q ?? '') ?>">
                    </form>
                </div>
            </div>
        </div>

        <div class="card card-pro">
            <div class="table-responsive">
                <table class="table table-pro mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Ad Creative</th>
                            <th>Advertiser</th>
                            <th>Performance</th>
                            <th>Budget</th>
                            <th>Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($ads)): ?>
                            <?php foreach($ads as $ad): ?>
                            <tr>
                                <td><span class="text-muted font-weight-bold small">#<?= $ad['id'] ?></span></td>
                                
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="ad-preview-box mr-3">
                                            <img src="<?= get_media_url($ad['thumbnail_url'] ?: $ad['media_url'], 'ads_image') ?>" 
                                                 onerror="this.src='<?= base_url('assets/img/placeholder.png') ?>'">
                                            <div class="type-overlay">
                                                <i class="fas <?= strpos($ad['media_type'] ?? '', 'video') !== false ? 'fa-play' : 'fa-image' ?>"></i>
                                            </div>
                                        </div>
                                        <div style="max-width: 200px;">
                                            <div class="black-text text-truncate" style="font-size: 14px;">
                                                <?= esc($ad['title']) ?>
                                            </div>
                                            <div class="text-primary font-weight-bold small" style="font-size: 10px;">
                                                <?= strtoupper($ad['placement'] ?? 'Feed') ?> • <?= $ad['ad_content_type'] == 'boosted_content' ? 'BOOST' : 'AD' ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($ad['avatar'], 'profile') ?>" 
                                             class="rounded-circle mr-2 border" width="26" height="26" style="border-color: var(--border-soft) !important;">
                                        <span class="black-text small"><?= esc($ad['username']) ?></span>
                                    </div>
                                </td>

                                <td>
                                    <div class="d-flex" style="gap: 12px;">
                                        <div>
                                            <div class="black-text" style="font-size: 12px;"><?= number_format($ad['impressions']) ?></div>
                                            <div class="text-muted font-weight-bold" style="font-size: 9px;">VIEWS</div>
                                        </div>
                                        <div>
                                            <div class="text-primary font-weight-bold" style="font-size: 12px;"><?= number_format($ad['clicks']) ?></div>
                                            <div class="text-muted font-weight-bold" style="font-size: 9px;">CLICKS</div>
                                        </div>
                                    </div>
                                    <?php $ctr = $ad['impressions'] > 0 ? round(($ad['clicks']/$ad['impressions'])*100, 2) : 0; ?>
                                    <div class="small font-weight-bold mt-1" style="font-size: 10px;">
                                        CTR: <span class="<?= $ctr > 1 ? 'text-success' : 'text-muted' ?>"><?= $ctr ?>%</span>
                                    </div>
                                </td>

                                <td>
                                    <div class="black-text">$<?= number_format($ad['spent'], 2) ?></div>
                                    <div class="progress mt-1" style="height: 4px; width: 70px; background: #eee; border-radius: 10px;">
                                        <?php $percent = $ad['budget'] > 0 ? ($ad['spent'] / $ad['budget']) * 100 : 0; ?>
                                        <div class="progress-bar" style="width: <?= min(100, $percent) ?>%; background: var(--primary-blue); border-radius: 10px;"></div>
                                    </div>
                                    <div class="text-muted font-weight-bold" style="font-size: 9px; margin-top: 2px;">OF $<?= number_format($ad['budget']) ?></div>
                                </td>

                                <td>
                                    <?php 
                                        $s = $ad['status'];
                                        $cls = 'st-paused';
                                        if($s == 'active') $cls = 'st-active';
                                        if($s == 'pending_approval') $cls = 'st-pending';
                                        if($s == 'rejected') $cls = 'st-rejected';
                                    ?>
                                    <span class="st-badge-pro <?= $cls ?>">
                                        <?= str_replace('_', ' ', strtoupper($s)) ?>
                                    </span>
                                </td>

                                <td class="text-right">
                                    <div class="btn-group">
                                        <a href="<?= base_url('admin/ads/view/'.$ad['id']) ?>" class="action-btn-pro mr-1" title="Review">
                                            <i class="fas fa-eye" style="font-size: 12px;"></i>
                                        </a>
                                        <a href="<?= base_url('admin/ads/edit/'.$ad['id']) ?>" class="action-btn-pro mr-1" style="color: var(--accent-orange);" title="Edit">
                                            <i class="fas fa-pen" style="font-size: 12px;"></i>
                                        </a>
                                        <button onclick="deleteAd(<?= $ad['id'] ?>)" class="action-btn-pro" style="color: var(--accent-red);" title="Delete">
                                            <i class="fas fa-trash" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-search fa-3x text-muted mb-3 opacity-25"></i>
                                    <p class="text-muted font-weight-bold">No ads found matching your criteria.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="p-3 border-top bg-light">
                <?= $pager->links() ?>
            </div>
        </div>

    </div>
</section>

<script>
function deleteAd(id) {
    Swal.fire({
        title: 'Delete Ad?',
        text: "This campaign will be permanently removed.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#fd397a',
        cancelButtonColor: '#abb3ba',
        confirmButtonText: 'Yes, Delete',
        background: '#fff',
        color: '#3d4465'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= base_url('admin/ads/delete/') ?>' + id;
        }
    });
}
</script>

<?= $this->endSection() ?>
