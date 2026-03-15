<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME SYNC */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
    
    .reel-thumb-pro { 
        width: 45px; height: 75px; border-radius: 6px; 
        border: 1px solid var(--border-soft); object-fit: cover; 
        background: #f8f9fa; transition: 0.3s; 
    }
    .reel-thumb-pro:hover { transform: scale(1.05); box-shadow: var(--card-shadow); }
    
    /* Global Status Badges */
    .status-badge-pro { 
        font-size: 9px; padding: 4px 10px; border-radius: 4px; 
        font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    .st-published { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-processing { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    
    .stat-pill-pro { 
        background: #f4f7fa; border: 1px solid var(--border-soft); padding: 3px 10px; 
        border-radius: 6px; font-size: 11px; color: var(--text-dark); 
        display: inline-flex; align-items: center; gap: 5px; font-weight: 600;
    }
    
    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 6px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-mobile-alt mr-2 text-primary"></i> All Reels
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/reels') ?>" method="get">
                    <div class="row">
                        <div class="col-md-5 col-12 mb-2">
                            <label class="small text-muted font-weight-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Caption, Channel or Owner..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <label class="small text-muted font-weight-bold">VISIBILITY</label>
                            <select name="visibility" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="public" <?= ($_GET['visibility'] ?? '') == 'public' ? 'selected' : '' ?>>PUBLIC</option>
                                <option value="private" <?= ($_GET['visibility'] ?? '') == 'private' ? 'selected' : '' ?>>PRIVATE</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="small text-muted font-weight-bold">STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="published" <?= ($_GET['status'] ?? '') == 'published' ? 'selected' : '' ?>>PUBLISHED</option>
                                <option value="processing" <?= ($_GET['status'] ?? '') == 'processing' ? 'selected' : '' ?>>PROCESSING</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-12 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm text-white" style="background: var(--primary-blue); border-radius: 8px; height: 45px; border: none;">
                                Search 
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 1000px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="py-4 px-4 border-0">Reel Details</th>
                                <th class="py-4 border-0">Channel</th>
                                <th class="py-4 text-center border-0">Engagement</th>
                                <th class="py-4 text-center border-0">Status</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reels)): foreach($reels as $reel): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($reel->thumbnail_url, 'reel') ?>" class="reel-thumb-pro" onerror="this.src='https://ui-avatars.com/api/?name=R&background=f4f7fa&color=5d78ff';">
                                        <div class="ml-3">
                                            <div class="text-dark font-weight-bold" style="font-size: 13px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                                <?= $reel->caption ?: 'No description' ?>
                                            </div>
                                            <small class="text-muted d-block mt-1"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($reel->created_at)) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-dark font-weight-bold" style="font-size: 14px;">
                                        <?= $reel->channel_name ?: ($reel->full_name ?? 'Member') ?>
                                    </div>
                                    <small class="text-primary font-weight-600">@<?= strtoupper($reel->username) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="stat-pill-pro"><i class="fas fa-eye text-primary"></i> <?= number_format($reel->views_count) ?></span>
                                        <span class="stat-pill-pro"><i class="fas fa-heart text-danger"></i> <?= number_format($reel->likes_count) ?></span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <?php 
                                        $stClass = 'st-' . strtolower($reel->status);
                                    ?>
                                    <span class="status-badge-pro <?= $stClass ?>">
                                        <?= strtoupper($reel->status) ?>
                                    </span>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <div class="d-flex justify-content-end" style="gap: 8px;">
                                        <?php if(has_permission('reels.view')): ?>
                                        <a href="<?= base_url('admin/reels/view/'.$reel->id) ?>" class="action-btn-pro" style="color: var(--primary-blue);" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php if(has_permission('reels.edit')): ?>
                                        <a href="<?= base_url('admin/reels/edit/'.$reel->id) ?>" class="action-btn-pro" style="color: var(--accent-orange);" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if(has_permission('reels.delete')): ?>
                                        <button type="button" class="action-btn-pro delete-reel-btn" 
                                                data-url="<?= base_url('admin/reels/delete/'.$reel->id) ?>" 
                                                data-name="<?= esc($reel->caption ?: 'Reel #'.$reel->id) ?>"
                                                style="color: var(--accent-red);" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted small font-weight-bold">No reels found in the library.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
/**
 * 🚀 FIXED: Native JS Logic for Global Theme
 */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-reel-btn');
    if (btn) {
        e.preventDefault();
        const url = btn.getAttribute('data-url');
        const title = btn.getAttribute('data-name');

        Swal.fire({
            title: 'Delete this Reel?',
            text: "Target: " + title + "\nThis action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fd397a',
            cancelButtonColor: '#abb3ba',
            confirmButtonText: 'Yes, Delete Reel',
            background: '#fff',
            color: '#3d4465'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
});
</script>

<?= $this->endSection() ?>.