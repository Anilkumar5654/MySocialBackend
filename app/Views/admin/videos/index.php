<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PAGE SPECIFIC COMPONENTS (Powered by Global Variables) */
    
    /* Forms & Inputs */
    .form-control-pro { 
        background-color: var(--bg-surface, #ffffff) !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        height: var(--btn-height-action, 48px); 
        border-radius: var(--radius-md, 8px); 
    }
    
    /* Video Thumbnail Wrapper */
    .video-thumb-container {
        position: relative; 
        width: 6.5rem; 
        height: 3.6rem;
        border-radius: var(--radius-sm, 6px); 
        overflow: hidden; 
        border: 1px solid var(--border-soft); 
        flex-shrink: 0;
    }
    
    .video-thumb { 
        width: 100%; 
        height: 100%; 
        border-radius: var(--radius-sm, 6px); 
        object-fit: cover; 
        background-color: var(--bg-light, #f4f7fa); 
    }

    .duration-badge {
        position: absolute;
        bottom: 0.25rem;
        right: 0.25rem;
        font-size: 0.6rem;
        opacity: 0.9;
        padding: 0.15rem 0.35rem;
        border-radius: var(--radius-sm, 4px);
    }
    
    /* Stats Cards */
    .card-stats-pro {
        background-color: var(--bg-surface, #ffffff); 
        border: 1px solid var(--border-soft);
        border-radius: var(--radius-lg, 12px); 
        padding: var(--space-md, 16px) var(--space-lg, 24px); 
        box-shadow: var(--card-shadow); 
        height: 100%;
    }

    /* Custom Avatar */
    .avatar-xs {
        width: 1.5rem;
        height: 1.5rem;
        border-radius: 50%;
        border: 1px solid var(--border-soft);
        object-fit: cover;
    }

    /* Status Badges */
    .status-badge-pro { 
        font-size: var(--font-size-xs, 0.65rem); 
        padding: 0.35rem 0.75rem; 
        border-radius: var(--radius-sm, 4px); 
        font-weight: var(--font-weight-black, 800); 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        white-space: nowrap;
    }
    
    /* Using transparent hex fallback if rgba vars aren't setup, but bounded to theme colors */
    .st-published { background-color: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-blocked { background-color: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    .st-processing { background-color: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    
    /* Action Buttons */
    .action-btn-pro { 
        width: 2.25rem; 
        height: 2.25rem; 
        display: inline-flex; 
        align-items: center; 
        justify-content: center; 
        border-radius: var(--radius-sm, 6px); 
        transition: all 0.2s ease; 
        border: 1px solid var(--border-soft); 
        background-color: var(--bg-surface, #ffffff); 
        flex-shrink: 0;
    }
    .action-btn-pro:hover { 
        transform: translateY(-2px); 
        box-shadow: var(--shadow-md); 
    }

    /* Text Colors */
    .text-primary-custom { color: var(--primary-blue); }
    .text-danger-custom { color: var(--accent-red); }
    .text-success-custom { color: var(--accent-green); }

    /* Custom Table Header */
    .table-header-pro {
        background-color: var(--bg-light);
        font-size: var(--font-size-xs, 11px);
        text-transform: uppercase;
        color: var(--text-dark);
        font-weight: var(--font-weight-bold, 700);
    }

    /* 📱 MOBILE RESPONSIVE TWEAKS */
    @media (max-width: 768px) {
        .header-actions-wrapper { width: 100%; justify-content: space-between; }
        .header-actions-wrapper .dropdown, .header-actions-wrapper a { flex: 1; text-align: center; }
        .header-actions-wrapper button, .header-actions-wrapper a { width: 100%; justify-content: center; }
        
        .card-stats-pro { padding: var(--space-md, 16px); }
        .table-custom-min { min-width: 900px; }
    }
</style>

<div class="content-header px-3 px-md-4 pt-3 pb-2">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
        <h1 class="page-title">
            <i class="fas fa-video mr-2 text-primary-custom"></i> Video Management
        </h1>
        <div class="d-flex gap-2 mt-3 mt-md-0 header-actions-wrapper">
            <div class="dropdown">
                <button class="btn btn-light dropdown-toggle font-weight-bold w-100" type="button" data-toggle="dropdown">
                    Bulk Actions
                </button>
                <div class="dropdown-menu shadow-sm border-0 w-100 text-center text-md-left">
                    <a class="dropdown-item font-weight-bold" href="javascript:void(0)" onclick="applyBulk('monetize_on')">
                        <i class="fas fa-dollar-sign mr-2 text-success-custom"></i> Enable Monetization
                    </a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item font-weight-bold text-danger-custom" href="javascript:void(0)" onclick="applyBulk('delete')">
                        <i class="fas fa-trash-alt mr-2"></i> Bulk Delete
                    </a>
                </div>
            </div>
            <a href="<?= base_url('admin/videos/export_csv') ?>" class="btn btn-primary w-100 d-flex align-items-center justify-content-center">
                <i class="fas fa-file-csv mr-2"></i> Export CSV
            </a>
        </div>
    </div>
</div>

<section class="content px-3 px-md-4">
    <div class="row mb-3">
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="card-stats-pro">
                <div class="label">Total Videos</div>
                <div class="text-strong text-xl my-1"><?= number_format($stats['total_videos'] ?? 0) ?></div>
                <div class="text-muted small">Database count</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="card-stats-pro">
                <div class="label">Total Views</div>
                <div class="text-strong text-xl my-1 text-primary-custom"><?= number_format($stats['total_views'] ?? 0) ?></div>
                <div class="text-muted small">Global traffic</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="card-stats-pro">
                <div class="label">Flagged Videos</div>
                <div class="text-strong text-xl my-1 text-danger-custom"><?= number_format($stats['flagged_count'] ?? 0) ?></div>
                <div class="text-muted small">Pending reports</div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-3 col-md-6 col-sm-6 mb-3">
            <div class="card-stats-pro">
                <div class="label">Monetized</div>
                <div class="text-strong text-xl my-1 text-success-custom"><?= number_format($stats['monetized_count'] ?? 0) ?></div>
                <div class="text-muted small">Active earning</div>
            </div>
        </div>
    </div>

    <div class="card p-4">
        <form action="<?= base_url('admin/videos') ?>" method="get">
            <div class="row align-items-center">
                <div class="col-xl-4 col-lg-4 col-md-6 mb-2">
                    <input type="text" name="search" class="form-control form-control-pro" placeholder="Search Title, User, Channel..." value="<?= esc($_GET['search'] ?? '') ?>">
                </div>
                <div class="col-xl-3 col-lg-3 col-md-6 mb-2">
                    <select name="status" class="form-control form-control-pro">
                        <option value="">STATUS: ALL</option>
                        <option value="published" <?= (isset($_GET['status']) && $_GET['status'] == 'published') ? 'selected' : '' ?>>PUBLISHED</option>
                        <option value="processing" <?= (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : '' ?>>PROCESSING</option>
                        <option value="blocked" <?= (isset($_GET['status']) && $_GET['status'] == 'blocked') ? 'selected' : '' ?>>BLOCKED</option>
                    </select>
                </div>
                <div class="col-xl-3 col-lg-3 col-md-6 mb-2">
                    <select name="visibility" class="form-control form-control-pro">
                        <option value="">VISIBILITY: ALL</option>
                        <option value="public" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'public') ? 'selected' : '' ?>>PUBLIC</option>
                        <option value="private" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'private') ? 'selected' : '' ?>>PRIVATE</option>
                    </select>
                </div>
                <div class="col-xl-2 col-lg-2 col-md-6 mb-2">
                    <button type="submit" class="btn btn-primary w-100" style="height: var(--btn-height-action, 48px);">
                        <i class="fas fa-filter mr-1"></i> Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 table-custom-min">
                <thead class="table-header-pro">
                    <tr>
                        <th class="py-3 px-4 border-0" style="width: 4rem;"><input type="checkbox" id="selectAll"></th>
                        <th class="py-3 border-0">Video Info</th>
                        <th class="py-3 border-0">Channel Details</th>
                        <th class="py-3 text-center border-0">Analytics</th>
                        <th class="py-3 text-center border-0">Status</th>
                        <th class="py-3 text-center border-0">Monetization</th>
                        <th class="py-3 text-right px-4 border-0">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($videos as $video): ?>
                    <tr style="border-bottom: 1px solid var(--border-soft);">
                        <td class="align-middle px-4">
                            <input type="checkbox" class="video-cb" value="<?= $video->id ?>">
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <div class="video-thumb-container mr-3">
                                    <img src="<?= get_media_url($video->thumbnail_url, 'video') ?>" class="video-thumb" onerror="this.src='https://placehold.co/100x56/f4f7fa/333?text=Video';">
                                    <span class="badge badge-neutral duration-badge">
                                        <?= gmdate("i:s", $video->duration) ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="text-strong" style="max-width: 18rem;"><?= character_limiter(esc($video->title), 40) ?></div>
                                    <div class="label mt-1"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M, Y', strtotime($video->created_at)) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <img src="<?= $video->user_avatar ? get_media_url($video->user_avatar, 'profile') : 'https://ui-avatars.com/api/?name='.urlencode($video->username) ?>" class="avatar-xs mr-2 flex-shrink-0">
                                <div>
                                    <div class="text-strong text-truncate" style="max-width: 10rem;"><?= esc($video->channel_name) ?></div>
                                    <div class="label text-primary-custom mt-1">@<?= strtoupper(esc($video->username)) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="align-middle text-center">
                            <div class="d-flex flex-column align-items-center gap-1">
                                <span class="badge badge-neutral w-100"><i class="fas fa-eye text-primary-custom mr-1"></i> <?= number_format($video->views_count) ?></span>
                                <span class="badge badge-neutral w-100 mt-1"><i class="fas fa-heart text-danger-custom mr-1"></i> <?= number_format($video->likes_count) ?></span>
                            </div>
                        </td>
                        <td class="align-middle text-center">
                            <span class="status-badge-pro st-<?= strtolower($video->status) ?>">
                                <?= strtoupper($video->status) ?>
                            </span>
                        </td>
                        <td class="align-middle text-center">
                            <?php if($video->monetization_enabled): ?>
                                <div class="label text-success-custom mb-0"><i class="fas fa-check-circle mr-1"></i> ON</div>
                            <?php else: ?>
                                <div class="label mb-0"><i class="fas fa-times-circle mr-1"></i> OFF</div>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle text-right px-4">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?= base_url('admin/videos/view/'.$video->id) ?>" class="action-btn-pro text-primary-custom" title="Intelligence HUD">
                                    <i class="fas fa-chart-line"></i>
                                </a>
                                <a href="<?= base_url('admin/videos/edit/'.$video->id) ?>" class="action-btn-pro text-warning-custom" title="Edit Metadata">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if(has_permission('videos.delete')): ?>
                                <button type="button" class="action-btn-pro delete-video-btn text-danger-custom" 
                                        data-url="<?= base_url('admin/videos/delete/'.$video->id) ?>" 
                                        data-name="<?= esc($video->title) ?>" title="Delete Content">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dynamic Theme Color Fetcher for JS
    const styles = getComputedStyle(document.documentElement);
    const colorPrimary = styles.getPropertyValue('--primary-blue').trim() || '#3b82f6';
    const colorDanger = styles.getPropertyValue('--accent-red').trim() || '#ef4444';
    const colorBg = styles.getPropertyValue('--bg-surface').trim() || '#ffffff';
    const colorText = styles.getPropertyValue('--text-dark').trim() || '#111827';
    const colorMuted = styles.getPropertyValue('--border-soft').trim() || '#e5e7eb';

    // Select All Logic
    const selectAllBtn = document.getElementById('selectAll');
    if(selectAllBtn) {
        selectAllBtn.addEventListener('change', function() {
            document.querySelectorAll('.video-cb').forEach(cb => cb.checked = this.checked);
        });
    }

    // Bulk Action Functionality
    window.applyBulk = function(action) {
        let selected = Array.from(document.querySelectorAll('.video-cb:checked')).map(cb => cb.value);
        if(selected.length === 0) {
            Swal.fire({ 
                icon: 'error', 
                title: 'Oops...', 
                text: 'Please select at least one video!', 
                background: colorBg, 
                color: colorText 
            });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: "Apply " + action.replace('_', ' ') + " to " + selected.length + " items?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: colorPrimary,
            cancelButtonColor: colorMuted,
            confirmButtonText: 'Yes, Apply',
            background: colorBg,
            color: colorText
        }).then((result) => {
            if (result.isConfirmed) {
                // Controller method call via AJAX can be added here
                console.log("Bulk action: " + action + " on IDs: " + selected);
            }
        });
    }

    // Delete Confirmation
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-video-btn');
        if (btn) {
            const url = btn.getAttribute('data-url');
            const title = btn.getAttribute('data-name');
            Swal.fire({
                title: 'Permanent Delete?',
                text: "Content '" + title + "' will be wiped from servers.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: colorDanger,
                cancelButtonColor: colorMuted,
                confirmButtonText: 'Yes, Delete',
                background: colorBg,
                color: colorText
            }).then((result) => {
                if (result.isConfirmed) { window.location.href = url; }
            });
        }
    });
});
</script>

<?= $this->endSection() ?>
