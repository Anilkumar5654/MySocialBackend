<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 RESTORED ORIGINAL DESIGN LAYOUT */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .video-wrapper-pro { width: 100%; background: #000; border-radius: 12px; overflow: hidden; border: 1px solid var(--border-soft); position: relative; }
    .video-label-pro { position: absolute; top: 12px; left: 12px; z-index: 10; font-size: 9px; font-weight: 800; padding: 4px 10px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .video-id-badge { position: absolute; top: 12px; right: 12px; z-index: 10; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
    .decision-footer-pro { position: sticky; bottom: 0; background: #fff; border-top: 1px solid var(--border-soft); padding: 20px; margin: 25px -25px -25px -25px; z-index: 1000; box-shadow: 0 -10px 30px rgba(0,0,0,0.05); }
    .label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 4px; letter-spacing: 0.5px; }
    .black-text { color: #000 !important; font-weight: 700; }
    .owner-avatar-pro { width: 80px; height: 80px; border-radius: 15px; border: 4px solid #fff; object-fit: cover; background: #f8f9fa; box-shadow: var(--card-shadow); }
    .claim-info-box { background: #fff5f5; border: 1px solid #ffe3e3; border-radius: 10px; padding: 15px; margin-bottom: 20px; border-left: 5px solid red; }
    .data-detail-box { background: #f8f9fa; border: 1px solid #eee; border-radius: 8px; padding: 10px; margin-top: 10px; }
</style>

<div class="container-fluid p-4">

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px; font-weight: 600;">
            <i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px; font-weight: 600;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="<?= base_url('admin/moderation/strikes/claims') ?>" class="btn btn-light btn-sm shadow-sm font-weight-bold" style="border: 1px solid var(--border-soft); border-radius: 8px;">
            <i class="fas fa-arrow-left mr-1"></i> BACK
        </a>
        <div class="text-right">
            <span class="badge" style="background: #f4f7fa; color: var(--primary-blue); border: 1px solid var(--border-soft); padding: 8px 15px; font-weight: 800;">
                CASE ID: #<?= $strike->id ?>
            </span>
        </div>
    </div>

    <div class="claim-info-box d-flex align-items-center justify-content-between">
        <div>
            <label class="label-pro d-block mb-1 text-danger">Reported Reason</label>
            <h5 class="font-weight-bold mb-0 text-dark"><?= esc($strike->reason) ?></h5>
            <small class="text-muted">Violation Time: <strong><?= $strike->time_start ?? '00:00' ?> - <?= $strike->time_end ?? '00:00' ?></strong></small>
        </div>
        <div class="text-right">
            <label class="label-pro d-block">Source</label>
            <span class="badge badge-primary"><?= strtoupper($strike->report_source ?? 'USER') ?></span>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="video-wrapper-pro shadow-sm">
                <span class="video-label-pro bg-danger text-white">Reported Media</span>
                <span class="video-id-badge bg-white text-danger">ID: #<?= $strike->content_id ?></span>
                <video width="100%" controls poster="<?= get_media_url($strike->target_thumbnail ?? '') ?>">
                    <source src="<?= get_media_url($strike->target_video_url ?? '') ?>" type="video/mp4">
                </video>
            </div>
            <div class="data-detail-box">
                <div class="d-flex justify-content-between">
                    <span class="small text-muted font-weight-bold">OWNER: <?= esc($strike->channel_name) ?></span>
                </div>
                <div class="row text-center mt-2 border-top pt-2">
                    <div class="col-6 border-right"><small class="text-muted d-block">Uploaded On</small><b class="small text-danger"><?= $strike->offender_upload_date ? date('d M, Y', strtotime($strike->offender_upload_date)) : 'N/A' ?></b></div>
                    <div class="col-6"><small class="text-muted d-block">Total Views</small><b class="small"><?= number_format($strike->offender_views) ?></b></div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="video-wrapper-pro shadow-sm">
                <span class="video-label-pro bg-success text-white">Original Reference</span>
                <span class="video-id-badge bg-white text-success">ID: #<?= $strike->original_content_id ?? 'EXT' ?></span>
                <?php if(!empty($strike->original_video_url)): ?>
                    <video width="100%" controls poster="<?= get_media_url($strike->original_thumbnail ?? '') ?>">
                        <source src="<?= get_media_url($strike->original_video_url ?? '') ?>" type="video/mp4">
                    </video>
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light" style="height: 250px;">
                        <a href="<?= esc($strike->evidence_url) ?>" target="_blank" class="text-primary font-weight-bold">VIEW EXTERNAL EVIDENCE</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="data-detail-box">
                <div class="d-flex justify-content-between">
                    <span class="small text-muted font-weight-bold">ORIGINAL OWNER: <?= esc($strike->original_owner_name) ?></span>
                </div>
                <div class="row text-center mt-2 border-top pt-2">
                    <div class="col-6 border-right"><small class="text-muted d-block">Uploaded On</small><b class="small text-success"><?= $strike->original_upload_date ? date('d M, Y', strtotime($strike->original_upload_date)) : 'N/A' ?></b></div>
                    <div class="col-6"><small class="text-muted d-block">Total Views</small><b class="small"><?= number_format($strike->original_views) ?></b></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-pro">
                <div class="card-header-pro"><h6 class="text-dark m-0 font-weight-bold">Description & Notes</h6></div>
                <div class="card-body">
                    <p class="text-dark small mb-0"><?= !empty($strike->description) ? esc($strike->description) : 'No manual notes provided.' ?></p>
                    
                    <?php if(!empty($strike->appeal_reason)): ?>
                        <div class="p-3 mt-3" style="background: #fff9e6; border-radius: 8px; border-left: 4px solid #ffc107;">
                            <label class="label-pro text-warning">Creator's Appeal Defense</label>
                            <p class="text-dark small mb-0 font-italic">"<?= esc($strike->appeal_reason) ?>"</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row mt-2 text-dark small">
                <div class="col-4">
                    <label class="label-pro d-block">Status</label>
                    <span class="badge badge-light border text-primary"><?= $strike->status ?></span>
                </div>
                <div class="col-4">
                    <label class="label-pro d-block">Type</label>
                    <span class="font-weight-bold"><?= $strike->type ?></span>
                </div>
                <div class="col-4">
                    <label class="label-pro d-block">Severity</label>
                    <span class="text-danger font-weight-bold"><?= $strike->severity_points ?> PTS</span>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-pro text-center p-4">
                <label class="label-pro mb-3">Offender Channel</label>
                <img src="<?= get_media_url($strike->offender_avatar ?? '', 'profile') ?>" class="owner-avatar-pro mx-auto mb-3">
                <h6 class="black-text mb-1"><?= esc($strike->channel_name) ?></h6>
                <p class="text-primary font-weight-bold small mb-0">Active Strikes: <?= $strike->strikes_count ?? 0 ?></p>
            </div>

            <?php if($strike->type == 'CLAIM'): ?>
            <div class="card card-pro p-3 border-primary shadow-sm" style="background: #f0f7ff;">
                <label class="label-pro text-primary"><i class="fas fa-coins mr-1"></i> Revenue Split</label>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-center"><b>30%</b><br><small class="text-muted">Creator</small></div>
                    <i class="fas fa-exchange-alt text-muted"></i>
                    <div class="text-center"><b>70%</b><br><small class="text-muted">Original</small></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="decision-footer-pro d-flex justify-content-center align-items-center flex-wrap" style="gap: 12px;">
        <?php if($strike->type == 'PENDING_REVIEW'): ?>
            <button class="btn btn-light border px-4 font-weight-bold" onclick="autoProcessAction('REJECT')">DISMISS</button>
            <button class="btn btn-primary px-4 font-weight-bold" onclick="autoProcessAction('REVENUE_CLAIM')">CLAIM</button>
            <button class="btn btn-warning text-white px-4 font-weight-bold" onclick="autoProcessAction('REMOVE_CONTENT')">BLOCK</button>
            <button class="btn btn-danger px-4 font-weight-bold" onclick="autoProcessAction('ISSUE_STRIKE')">STRIKE</button>
        <?php else: ?>
            <div class="alert alert-info w-100 text-center m-0 shadow-sm" style="border-radius: 10px;">
                <i class="fas fa-check-circle mr-1 text-primary"></i> 
                <strong>RESOLVED AS:</strong> <span class="text-primary font-weight-bold"><?= $strike->type ?></span> 
                ON <?= date('d M, Y', strtotime($strike->updated_at ?? $strike->created_at)) ?>.
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->include('admin/moderation/strikes/modal_decision_snippet') ?>

<script>
function autoProcessAction(action) {
    if(typeof handleClaim === "function") {
        handleClaim('<?= $strike->id ?>', action, {
            content_id: '<?= $strike->content_id ?>',
            content_type: '<?= $strike->content_type ?>',
            channel_id: '<?= $strike->channel_id ?>',
            original_video_id: '<?= $strike->original_content_id ?>',
            reason: '<?= addslashes(esc($strike->reason)) ?>'
        });
    } else {
        Swal.fire('Error', 'Modal script missing!', 'error');
    }
}
</script>

<?= $this->endSection() ?>
