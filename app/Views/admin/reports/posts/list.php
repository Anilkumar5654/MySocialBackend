<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php 
$request = service('request'); 
$currentAdminId = session()->get('id');
$currentFilter = $request->getGet('status') ?? 'pending'; // Default status is pending
?>

<style>
    /* 🎨 GLOBAL PRO THEME SYNC */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
    
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    
    /* Stats Card Pro - Clickable */
    .stat-card-pro { 
        background: #fff; border-radius: 12px; padding: 20px; 
        box-shadow: var(--card-shadow); position: relative; height: 100%; 
        transition: 0.3s; border: 1px solid var(--border-soft);
        cursor: pointer; text-decoration: none !important; display: block;
    }
    .stat-card-pro:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .stat-card-pro.active { border-bottom: 4px solid var(--primary-blue) !important; background: #fcfdfe; }
    
    .stat-label-pro { font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.5px; }
    .stat-value-pro { font-size: 26px; font-weight: 700; color: var(--text-dark); margin-top: 5px; }

    .border-pending { border-left: 4px solid var(--accent-orange) !important; }
    .border-resolved { border-left: 4px solid var(--accent-green) !important; }
    .border-dismissed { border-left: 4px solid #adb5bd !important; }
    
    /* 🔒 LOCK UI */
    .lock-indicator {
        font-size: 10px; padding: 2px 8px; border-radius: 4px;
        background: #fff0f0; color: #d9534f; border: 1px solid #ffdada;
        font-weight: 700; display: inline-flex; align-items: center;
    }
    .btn-locked { background: #f8f9fa !important; color: #ccc !important; cursor: not-allowed; border: 1px solid #eee !important; }

    .reporter-avatar-pro { width: 42px; height: 42px; border-radius: 10px; border: 1px solid var(--border-soft); object-fit: cover; background: #f8f9fa; }
    
    .st-badge-pro { font-size: 10px; padding: 4px 10px; border-radius: 4px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
    .st-pending { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    .st-resolved { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-dismissed { background: #f4f7fa; color: #888; border: 1px solid var(--border-soft); }

    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 6px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-mail-bulk mr-2 text-primary"></i> Post Reports
            </h1>
            <a href="<?= base_url('admin/reports') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i> BACK
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="row mb-4">
            <div class="col-4">
                <a href="?status=pending" class="stat-card-pro border-pending <?= ($currentFilter == 'pending') ? 'active' : '' ?>">
                    <div class="stat-label-pro" style="color: var(--accent-orange);">Pending</div>
                    <div class="stat-value-pro"><?= number_format($stats['pending'] ?? 0) ?></div>
                </a>
            </div>
            <div class="col-4">
                <a href="?status=resolved" class="stat-card-pro border-resolved <?= ($currentFilter == 'resolved') ? 'active' : '' ?>">
                    <div class="stat-label-pro" style="color: var(--accent-green);">Resolved</div>
                    <div class="stat-value-pro"><?= number_format($stats['resolved'] ?? 0) ?></div>
                </a>
            </div>
            <div class="col-4">
                <a href="?status=dismissed" class="stat-card-pro border-dismissed <?= ($currentFilter == 'dismissed') ? 'active' : '' ?>">
                    <div class="stat-label-pro" style="color: #888;">Dismissed</div>
                    <div class="stat-value-pro"><?= number_format($stats['dismissed'] ?? 0) ?></div>
                </a>
            </div>
        </div>

        <div class="card card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 800px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Reporter</th>
                                <th class="py-4 border-0">Reason</th>
                                <th class="py-4 text-center border-0">Status</th>
                                <th class="py-4 border-0">Date</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($reports)): ?>
                                <?php foreach($reports as $item): ?>
                                <?php 
                                    // 🔒 LOCK CHECK
                                    $isLocked = false;
                                    if ($item->status == 'pending' && !empty($item->locked_by) && $item->locked_by != $currentAdminId) {
                                        if (strtotime($item->locked_at) > strtotime('-15 minutes')) {
                                            $isLocked = true;
                                        }
                                    }
                                ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="align-middle px-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= get_media_url($item->reporter_avatar, 'profile') ?>" class="reporter-avatar-pro mr-3">
                                            <div>
                                                <div style="color: #000; font-weight: 700; font-size: 14px;"><?= esc($item->reporter_name ?? 'User') ?></div>
                                                <small class="text-primary font-weight-600">ID: <?= $item->reporter_id ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-bold d-block" style="font-size: 13px;"><?= esc($item->reason) ?></span>
                                        <?php if(!empty($item->description)): ?>
                                            <small class="text-muted">"<?= character_limiter($item->description, 40) ?>"</small>
                                        <?php endif; ?>
                                        <div class="mt-1">
                                            <span class="badge mr-2" style="background: #f4f7fa; color: var(--text-dark); font-size: 9px; border: 1px solid var(--border-soft);">POST ID: <?= $item->reportable_id ?></span>
                                            <?php if($isLocked): ?>
                                                <span class="lock-indicator" title="Locked by Admin ID: <?= $item->locked_by ?>">
                                                    <i class="fas fa-lock mr-1"></i> IN REVIEW
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php $stClass = 'st-' . strtolower($item->status); ?>
                                        <span class="st-badge-pro <?= $stClass ?>">
                                            <?= strtoupper($item->status) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-muted small">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($item->created_at)) ?><br>
                                        <i class="far fa-clock mr-1"></i> <?= date('h:i A', strtotime($item->created_at)) ?>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <div class="btn-group">
                                            <?php if($isLocked): ?>
                                                <button type="button" class="action-btn-pro btn-locked" title="Currently reviewed by another admin">
                                                    <i class="fas fa-user-shield"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="<?= base_url('admin/reports/view/'.$item->id) ?>" class="action-btn-pro mr-2" style="color: var(--primary-blue);" title="Review">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if(has_permission('reports.manage')): ?>
                                            <button type="button" class="action-btn-pro delete-report-btn" 
                                                    data-id="<?= $item->id ?>" style="color: var(--accent-red);" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3 opacity-25"></i>
                                        <p class="text-muted font-weight-bold">No <?= $currentFilter ?> reports found.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-report-btn');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            Swal.fire({
                title: 'Delete this log?',
                text: "This will permanently remove the report from history.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fd397a',
                cancelButtonColor: '#abb3ba',
                confirmButtonText: 'Yes, Delete Now',
                background: '#fff',
                color: '#3d4465'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "<?= base_url('admin/reports/delete/') ?>/" + id;
                }
            });
        }
    });
});
</script>

<?= $this->endSection() ?>
