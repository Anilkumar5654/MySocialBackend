<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">
                <i class="fas fa-shield-alt mr-2 text-primary"></i> Review Report
            </h1>
            <a href="<?= base_url('admin/reports/' . $report->reportable_type . 's') ?>" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> BACK TO LIST
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="card-header-title"><i class="fas fa-file-alt mr-2 text-primary"></i> CONTENT INFO</h6>
                        <span class="badge badge-neutral">ID: #<?= $report->reportable_id ?></span>
                    </div>
                    <div class="card-body">
                        <?php if($content): ?>
                            <div class="mb-4 text-center">
                                <?php 
                                    $finalUrl = get_media_url($mediaPath, $mediaType);
                                    $thumbUrl = get_media_url($content->thumbnail_url ?? '', 'post_image'); 
                                ?>
                                <?php if($mediaType == 'post_image'): ?>
                                    <img src="<?= $finalUrl ?>" class="media-img" alt="Reported Media">
                                <?php else: ?>
                                    <div class="media-video-wrapper">
                                        <video class="media-video-player" controls poster="<?= $thumbUrl ?>">
                                            <source src="<?= $finalUrl ?>" type="video/mp4">
                                        </video>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-strong mt-3"><?= esc($content->title ?? $content->caption ?? $content->content ?? 'Untitled Content') ?></h4>
                            <p class="text-muted small mb-0"><i class="far fa-calendar-alt mr-1"></i> Uploaded: <?= date('d M Y, h:i A', strtotime($content->created_at)) ?></p>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-trash-alt fa-3x text-danger mb-3 opacity-25"></i>
                                <h5 class="text-muted font-weight-bold">Content has been deleted</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <img src="<?= get_media_url($report->reporter_avatar, 'profile') ?>" class="avatar-sm mr-3">
                            <div class="w-100">
                                <h6 class="text-strong mb-3">Reported By: <span class="text-primary"><?= esc($report->reporter_name ?? 'User') ?></span></h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="label">Reason</div>
                                        <div class="text-danger text-md text-strong"><?= esc($report->reason) ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="label">Report Date</div>
                                        <div class="text-strong"><?= date('d M Y, h:i A', strtotime($report->created_at)) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header text-center justify-content-center">
                        <h6 class="card-header-title">CREATOR INFO</h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <?php if($accusedUser): ?>
                            <img src="<?= get_media_url($accusedUser->avatar, 'profile') ?>" class="avatar-lg mb-3">
                            <h4 class="text-strong mb-0"><?= esc($accusedUser->name) ?></h4>
                            <p class="text-primary font-weight-bold mb-3">@<?= strtoupper(esc($accusedUser->username)) ?></p>
                            <div>
                                <?php if(($accusedUser->is_banned ?? 0) == 1): ?>
                                    <span class="badge badge-danger">BANNED</span>
                                <?php else: ?>
                                    <span class="badge badge-success">ACTIVE USER</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted font-weight-bold">Creator not found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card card-top-accent">
                    <div class="card-header text-center bg-white justify-content-center">
                        <h6 class="card-header-title">ACTION PANEL</h6>
                    </div>
                    <div class="card-body">
                        
                        <?php if($report->status == 'pending'): ?>
                            <form action="<?= base_url('admin/reports/action') ?>" method="post" id="actionForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="report_id" value="<?= $report->id ?>">
                                <input type="hidden" name="type" value="<?= $report->reportable_type ?>">
                                <input type="hidden" name="content_id" value="<?= $report->reportable_id ?>">
                                <input type="hidden" name="accused_user_id" value="<?= $accusedUser->id ?? '' ?>">
                                <input type="hidden" name="action" id="selectedAction">
                                <input type="hidden" name="note" id="adminNote">

                                <?php if(has_permission('reports.action')): ?>
                                    <button type="button" onclick="confirmAction('dismiss')" class="btn btn-action btn-muted">
                                        <i class="fas fa-check-circle mr-2"></i> DISMISS REPORT
                                    </button>
                                    
                                    <?php if($content): ?>
                                    <button type="button" onclick="confirmAction('delete_content')" class="btn btn-action btn-warning">
                                        <i class="fas fa-trash-alt mr-2"></i> DELETE CONTENT
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if(has_permission('users.ban')): ?>
                                    <button type="button" onclick="confirmAction('ban_user')" class="btn btn-action btn-danger">
                                        <i class="fas fa-ban mr-2"></i> BAN CREATOR
                                    </button>
                                <?php endif; ?>
                            </form>
                        
                        <?php else: ?>
                            <div class="text-center py-3">
                                <?php if($report->status == 'resolved'): ?>
                                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                    <h5 class="text-strong">RESOLVED</h5>
                                <?php else: ?>
                                    <i class="fas fa-times-circle fa-4x text-muted mb-3 opacity-50"></i>
                                    <h5 class="text-muted font-weight-bold">DISMISSED</h5>
                                <?php endif; ?>
                                <hr class="my-4">
                                <p class="text-muted small mb-1">Reviewed by</p>
                                <h6 class="text-primary font-weight-bold">
                                    <?php 
                                        if (!empty($report->reviewer_name)) echo esc($report->reviewer_name);
                                        elseif (!empty($report->reviewer_full_name)) echo esc($report->reviewer_full_name);
                                        else echo "System Admin";
                                    ?>
                                </h6>
                                <p class="text-muted small mt-2">
                                    on <?= isset($report->reviewed_at) ? date('d M Y, h:i A', strtotime($report->reviewed_at)) : 'N/A' ?>
                                </p>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
/**
 * 🚀 DYNAMIC JS THEMING 
 * Fetches alert colors directly from CSS variables to ensure the JS 
 * automatically updates if the global theme changes.
 */
document.addEventListener("DOMContentLoaded", function() {
    
    // Fetch global theme colors
    const styles = getComputedStyle(document.documentElement);
    const colorWarning = styles.getPropertyValue('--color-accent-warning').trim() || '#ff9f43';
    const colorDanger = styles.getPropertyValue('--color-accent-danger').trim() || '#ef4444';
    const colorMuted = styles.getPropertyValue('--color-border-default').trim() || '#d1d5db';
    const colorBg = styles.getPropertyValue('--color-bg-surface').trim() || '#ffffff';
    const colorText = styles.getPropertyValue('--color-text-primary').trim() || '#111827';

    window.confirmAction = function(actionType) {
        let title='', text='', color='', btnText='';
        
        if(actionType === 'dismiss') { 
            title='Dismiss Report?'; text='Confirm if no policy was violated.'; color=colorMuted; btnText='Yes, Dismiss'; 
        }
        else if(actionType === 'delete_content') { 
            title='Delete Content?'; text='This will permanently remove the content.'; color=colorWarning; btnText='Yes, Delete'; 
        }
        else if(actionType === 'ban_user') { 
            title='Ban Creator Account?'; text='This will block the creator from the platform.'; color=colorDanger; btnText='Yes, Ban User'; 
        }

        Swal.fire({
            title: title, 
            text: text, 
            icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Reason for this action...',
            showCancelButton: true, 
            confirmButtonColor: color, 
            cancelButtonColor: colorMuted,
            confirmButtonText: btnText, 
            background: colorBg, 
            color: colorText,
            preConfirm: (note) => {
                if (!note || !note.trim()) {
                    Swal.showValidationMessage('A reason is required!');
                    return false;
                }
                return note;
            }
        }).then((res) => {
            if(res.isConfirmed) {
                document.getElementById('adminNote').value = res.value;
                document.getElementById('selectedAction').value = actionType;
                document.getElementById('actionForm').submit();
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
