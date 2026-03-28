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
        background: #f8f9fa; transition: 0.3s; flex-shrink: 0;
    }
    .reel-thumb-pro:hover { transform: scale(1.05); box-shadow: var(--card-shadow); }
    
    /* Global Status Badges */
    .status-badge-pro { 
        font-size: 9px; padding: 4px 10px; border-radius: 4px; 
        font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap;
    }
    .st-published { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-processing { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    .st-blocked { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    
    .stat-pill-pro { 
        background: #f4f7fa; border: 1px solid var(--border-soft); padding: 3px 10px; 
        border-radius: 6px; font-size: 11px; color: var(--text-dark); 
        display: inline-flex; align-items: center; gap: 5px; font-weight: 600; width: 100%; justify-content: center;
    }
    
    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 6px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff; flex-shrink: 0;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); }

    /* 📱 MOBILE RESPONSIVE TWEAKS */
    @media (max-width: 768px) {
        .content-header h1 { font-size: 1.2rem !important; margin-bottom: 15px; }
        .header-actions-wrapper { width: 100%; justify-content: space-between; flex-wrap: wrap; }
        .header-actions-wrapper .dropdown, .header-actions-wrapper a { flex: 1; text-align: center; min-width: 140px; }
        .header-actions-wrapper button, .header-actions-wrapper a { width: 100%; justify-content: center; }
        
        .table-responsive { border-radius: 8px; }
        .table-custom-min { min-width: 900px; }
    }
</style>

<div class="content-header px-3 px-md-4 pt-3 pb-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px; margin: 0;">
            <i class="fas fa-mobile-alt mr-2 text-primary"></i> All Reels
        </h1>
        <div class="d-flex gap-2 mt-3 mt-md-0 header-actions-wrapper">
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle font-weight-bold w-100" type="button" data-toggle="dropdown" style="border-radius: 8px; height: 40px;">
                    Bulk Actions
                </button>
                <div class="dropdown-menu shadow-sm border-0 w-100 text-center text-md-left">
                    <a class="dropdown-item font-weight-bold" href="javascript:void(0)" onclick="applyBulk('monetize_on')"><i class="fas fa-dollar-sign mr-2 text-success"></i> Enable Monetization</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item font-weight-bold text-danger" href="javascript:void(0)" onclick="applyBulk('delete')"><i class="fas fa-trash-alt mr-2"></i> Bulk Delete</a>
                </div>
            </div>
            <a href="<?= base_url('admin/reels/export_csv') ?>" class="btn shadow-sm text-white font-weight-bold w-100" style="background: var(--primary-blue); border-radius: 8px; height: 40px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-file-csv mr-2"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<section class="content px-3 px-md-4 pt-3">
    <div class="container-fluid p-0">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/reels') ?>" method="get">
                    <div class="row align-items-end">
                        <div class="col-xl-5 col-lg-4 col-md-12 mb-3 mb-xl-0">
                            <label class="small text-muted font-weight-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Caption, Channel or Owner..." value="<?= esc($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-xl-2 col-lg-3 col-md-4 col-6 mb-3 mb-xl-0">
                            <label class="small text-muted font-weight-bold">VISIBILITY</label>
                            <select name="visibility" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="public" <?= ($_GET['visibility'] ?? '') == 'public' ? 'selected' : '' ?>>PUBLIC</option>
                                <option value="private" <?= ($_GET['visibility'] ?? '') == 'private' ? 'selected' : '' ?>>PRIVATE</option>
                            </select>
                        </div>
                        <div class="col-xl-3 col-lg-3 col-md-4 col-6 mb-3 mb-xl-0">
                            <label class="small text-muted font-weight-bold">STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="published" <?= ($_GET['status'] ?? '') == 'published' ? 'selected' : '' ?>>PUBLISHED</option>
                                <option value="processing" <?= ($_GET['status'] ?? '') == 'processing' ? 'selected' : '' ?>>PROCESSING</option>
                                <option value="blocked" <?= ($_GET['status'] ?? '') == 'blocked' ? 'selected' : '' ?>>BLOCKED</option>
                            </select>
                        </div>
                        <div class="col-xl-2 col-lg-2 col-md-4 col-12">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm text-white" style="background: var(--primary-blue); border-radius: 8px; height: 45px; border: none;">
                                <i class="fas fa-search mr-1"></i> Search 
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-custom-min">
                        <thead style="background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="py-4 px-4 border-0" style="width: 50px;"><input type="checkbox" id="selectAll"></th>
                                <th class="py-4 border-0">Reel Details</th>
                                <th class="py-4 border-0">Channel</th>
                                <th class="py-4 text-center border-0">Engagement</th>
                                <th class="py-4 text-center border-0">Status</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reels)): foreach($reels as $reel): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4"><input type="checkbox" class="reel-cb" value="<?= $reel->id ?>"></td>
                                
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($reel->thumbnail_url, 'reel') ?>" class="reel-thumb-pro" onerror="this.src='https://ui-avatars.com/api/?name=R&background=f4f7fa&color=5d78ff';">
                                        <div class="ml-3" style="min-width: 180px;">
                                            <div class="text-dark font-weight-bold text-wrap" style="font-size: 13px; max-width: 250px; line-height: 1.3;">
                                                <?= character_limiter(esc($reel->caption), 40) ?: 'No description' ?>
                                            </div>
                                            <small class="text-muted d-block mt-1"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($reel->created_at)) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-dark font-weight-bold text-truncate" style="font-size: 14px; max-width: 150px;">
                                        <?= esc($reel->channel_name ?: ($reel->full_name ?? 'Member')) ?>
                                    </div>
                                    <small class="text-primary font-weight-600">@<?= strtoupper(esc($reel->username)) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="d-flex flex-column align-items-center gap-1" style="max-width: 100px; margin: 0 auto;">
                                        <span class="stat-pill-pro"><i class="fas fa-eye text-primary"></i> <?= number_format($reel->views_count) ?></span>
                                        <span class="stat-pill-pro"><i class="fas fa-heart text-danger"></i> <?= number_format($reel->likes_count) ?></span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <?php $stClass = 'st-' . strtolower($reel->status); ?>
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
                                <tr><td colspan="6" class="text-center py-5 text-muted small font-weight-bold">No reels found in the library.</td></tr>
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
 * 🚀 UPGRADED: Bulk Action & Native JS Logic
 */

// Select All Checkboxes
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.reel-cb').forEach(cb => cb.checked = this.checked);
});

// Bulk Action Functionality
function applyBulk(action) {
    let selected = Array.from(document.querySelectorAll('.reel-cb:checked')).map(cb => cb.value);
    if(selected.length === 0) {
        Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please select at least one reel!', background: '#fff', color: '#3d4465' });
        return;
    }

    Swal.fire({
        title: 'Are you sure?',
        text: "Apply " + action.replace('_', ' ') + " to " + selected.length + " selected reels?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary-blue)',
        confirmButtonText: 'Yes, Apply',
        background: '#fff',
        color: '#3d4465'
    }).then((result) => {
        if (result.isConfirmed) {
            // Note: Add your controller AJAX/Form submit logic here for bulk actions
            console.log("Bulk action: " + action + " on IDs: " + selected);
        }
    });
}

// Delete Confirmation
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

<?= $this->endSection() ?>
