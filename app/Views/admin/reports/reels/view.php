<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .video-wrapper-pro { 
        width: 100%; background: #000; border-radius: 12px; overflow: hidden; 
        border: 1px solid var(--border-soft); position: relative; padding-top: 56.25%; 
    }
    .video-player-pro { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .content-img-pro { 
        width: 100%; max-height: 500px; object-fit: contain; border-radius: 12px; 
        border: 1px solid var(--border-soft); background: #f8f9fa; display: block; margin: 0 auto; 
    }
    
    .owner-avatar-pro { 
        width: 100px; height: 100px; border-radius: 12px; border: 4px solid #fff; 
        object-fit: cover; background: #f8f9fa; box-shadow: var(--card-shadow); 
    }
    .reporter-avatar-pro { width: 45px; height: 45px; border-radius: 8px; border: 1px solid var(--border-soft); object-fit: cover; }
    
    /* Action Buttons Pro */
    .btn-action-pro { 
        height: 48px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
        display: flex; align-items: center; justify-content: center; width: 100%; 
        margin-bottom: 12px; border-radius: 10px; transition: 0.3s; border: none; font-size: 12px;
    }
    .btn-action-pro:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    
    .label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; letter-spacing: 0.5px; }
    .black-text { color: #000 !important; font-weight: 700; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-shield-alt mr-2 text-primary"></i> Review Report
            </h1>
            <a href="<?= base_url('admin/reports/' . $report->reportable_type . 's') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i> BACK TO LIST
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-lg-8">
                <div class="card card-pro">
                    <div class="card-header-pro d-flex justify-content-between align-items-center">
                        <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-file-alt mr-2 text-primary"></i> CONTENT INFO</h6>
                        <span class="badge" style="background: #f4f7fa; color: var(--text-dark); border: 1px solid var(--border-soft);">ID: #<?= $report->reportable_id ?></span>
                    </div>
                    <div class="card-body">
                        <?php if($content): ?>
                            <div class="mb-4 text-center">
                                <?php 
                                    $finalUrl = get_media_url($mediaPath, $mediaType);
                                    $thumbUrl = get_media_url($content->thumbnail_url ?? '', 'post_image'); 
                                ?>
                                <?php if($mediaType == 'post_image'): ?>
                                    <img src="<?= $finalUrl ?>" class="content-img-pro" alt="Reported Media">
                                <?php else: ?>
                                    <div class="video-wrapper-pro">
                                        <video class="video-player-pro" controls poster="<?= $thumbUrl ?>">
                                            <source src="<?= $finalUrl ?>" type="video/mp4">
                                        </video>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <h4 class="black-text mt-3"><?= esc($content->title ?? $content->caption ?? $content->content ?? 'Untitled Content') ?></h4>
                            <p class="text-muted small mb-0"><i class="far fa-calendar-alt mr-1"></i> Uploaded: <?= date('d M Y, h:i A', strtotime($content->created_at)) ?></p>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-trash-alt fa-3x text-danger mb-3 opacity-25"></i>
                                <h5 class="text-muted font-weight-bold">Content has been deleted</h5>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card card-pro">
                    <div class="card-body">
                        <div class="d-flex align-items-start">
                            <img src="<?= get_media_url($report->reporter_avatar, 'profile') ?>" class="reporter-avatar-pro mr-3">
                            <div style="flex: 1;">
                                <h6 class="black-text mb-3">Reported By: <span class="text-primary"><?= esc($report->reporter_name ?? 'User') ?></span></h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="label-pro">Reason</div>
                                        <div class="text-danger font-weight-bold" style="font-size: 15px;"><?= esc($report->reason) ?></div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="label-pro">Report Date</div>
                                        <div class="text-dark font-weight-600"><?= date('d M Y, h:i A', strtotime($report->created_at)) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card card-pro">
                    <div class="card-header-pro text-center">
                        <h6 class="text-dark m-0 font-weight-bold">CREATOR INFO</h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <?php if($accusedUser): ?>
                            <img src="<?= get_media_url($accusedUser->avatar, 'profile') ?>" class="owner-avatar-pro mb-3">
                            <h4 class="black-text mb-0"><?= esc($accusedUser->name) ?></h4>
                            <p class="text-primary font-weight-bold mb-2">@<?= strtoupper(esc($accusedUser->username)) ?></p>
                            <div>
                                <?php if(($accusedUser->is_banned ?? 0) == 1): ?>
                                    <span class="badge" style="background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); padding: 6px 15px;">BANNED</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); padding: 6px 15px;">ACTIVE USER</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted font-weight-bold">Creator not found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card card-pro" style="border-top: 3px solid var(--primary-blue);">
                    <div class="card-header-pro text-center bg-white">
                        <h6 class="text-dark m-0 font-weight-bold">ACTION PANEL</h6>
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
                                    <button type="button" onclick="confirmAction('dismiss')" class="btn-action-pro" style="background: #f4f7fa; color: #888; border: 1px solid var(--border-soft);">
                                        <i class="fas fa-check-circle mr-2"></i> DISMISS REPORT
                                    </button>
                                    
                                    <?php if($content): ?>
                                    <button type="button" onclick="confirmAction('delete_content')" class="btn-action-pro" style="background: var(--accent-orange); color: #fff;">
                                        <i class="fas fa-trash-alt mr-2"></i> DELETE CONTENT
                                    </button>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <?php if(has_permission('users.ban')): ?>
                                    <button type="button" onclick="confirmAction('ban_user')" class="btn-action-pro" style="background: var(--accent-red); color: #fff;">
                                        <i class="fas fa-ban mr-2"></i> BAN CREATOR
                                    </button>
                                <?php endif; ?>
                            </form>
                        
                        <?php else: ?>
                            <div class="text-center py-3">
                                <?php if($report->status == 'resolved'): ?>
                                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                                    <h5 class="black-text">RESOLVED</h5>
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
 * 🚀 NATIVE LOGIC: Professional Global Alerts
 */
document.addEventListener("DOMContentLoaded", function() {
    window.confirmAction = function(actionType) {
        let title='', text='', color='', btnText='';
        
        if(actionType === 'dismiss') { title='Dismiss Report?'; text='Confirm if no policy was violated.'; color='#abb3ba'; btnText='Yes, Dismiss'; }
        else if(actionType === 'delete_content') { title='Delete Content?'; text='This will permanently remove the content.'; color='#ff9f43'; btnText='Yes, Delete'; }
        else if(actionType === 'ban_user') { title='Ban Creator Account?'; text='This will block the creator from the platform.'; color='#fd397a'; btnText='Yes, Ban User'; }

        Swal.fire({
            title: title, text: text, icon: 'warning',
            input: 'text',
            inputPlaceholder: 'Reason for this action...',
            showCancelButton: true, confirmButtonColor: color, cancelButtonColor: '#f4f7fa',
            confirmButtonText: btnText, background: '#fff', color: '#3d4465',
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
