<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php 
$request = service('request'); 
$currentAdminId = session()->get('id');
?>

<style>
    /* 🎨 GLOBAL PRO THEME SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .form-control-pro { background: #fff !important; border: 1px solid var(--border-soft) !important; color: var(--text-dark) !important; height: 45px; border-radius: 8px; }
    
    /* Stats Card Pro */
    .stat-card-pro { 
        background: #fff; border-radius: 12px; padding: 22px; 
        box-shadow: var(--card-shadow); position: relative; height: 100%; 
        transition: 0.3s; border: 1px solid var(--border-soft);
    }
    .stat-card-pro:hover { transform: translateY(-3px); }
    .stat-label-pro { font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.5px; }
    .stat-value-pro { font-size: 26px; font-weight: 700; color: #000; margin-top: 5px; }

    .border-appeal-pro { border-left: 4px solid var(--primary-blue) !important; }
    
    /* 🔒 LOCK INDICATOR */
    .lock-indicator {
        font-size: 10px; padding: 2px 8px; border-radius: 4px;
        background: #fff0f0; color: #d9534f; border: 1px solid #ffdada;
        font-weight: 700; display: inline-flex; align-items: center; margin-left: 8px;
    }
    .btn-locked { background: #f8f9fa !important; color: #ccc !important; cursor: not-allowed; border: 1px solid #eee !important; }

    /* Argument Boxes */
    .admin-note-box { background: rgba(93, 120, 255, 0.05); border-radius: 8px; padding: 12px; border: 1px solid rgba(93, 120, 255, 0.1); margin-bottom: 8px; }
    .creator-note-box { background: #f4f7fa; border-radius: 8px; padding: 12px; border: 1px solid var(--border-soft); }
    
    /* Pure Black Creator Names */
    .creator-name-black { color: #000 !important; font-weight: 700; font-size: 14px; }
    
    .action-btn-pro { width: 36px; height: 36px; display: inline-flex; align-items: center; justify-content: center; border-radius: 8px; transition: 0.2s; border: none; cursor: pointer; }
    .btn-approve-pro { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .btn-approve-pro:hover:not(.btn-locked) { background: var(--accent-green); color: #fff; }
    .btn-reject-pro { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    .btn-reject-pro:hover:not(.btn-locked) { background: var(--accent-red); color: #fff; }

    .tag-pro { background: #f4f7fa; color: var(--text-dark); padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; border: 1px solid var(--border-soft); text-transform: uppercase; }
    .points-label { color: var(--accent-red); font-weight: 800; font-size: 11px; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-balance-scale mr-2 text-primary"></i> Appeal Center
            </h1>
            <a href="<?= base_url('admin/moderation/strikes') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-history mr-1"></i> VIEW HISTORY
            </a>
        </div>
    </div>
</div>

<section class="content mt-4">
    <div class="container-fluid">
        
        <div class="row mb-4">
            <div class="col-md-4 col-12">
                <div class="stat-card-pro border-appeal-pro">
                    <div class="stat-label-pro">Pending Appeals</div>
                    <div class="stat-value-pro"><?= count($appeals) ?></div>
                </div>
            </div>
        </div>

        <div class="card card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 1000px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Creator / Channel</th>
                                <th class="py-4 border-0">Strike Reason</th>
                                <th class="py-4 border-0">Case Details</th>
                                <th class="py-4 border-0">Received</th>
                                <th class="text-right py-4 px-4 border-0">Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($appeals)): foreach($appeals as $a): ?>
                            <?php 
                                // 🔒 LOCK LOGIC CHECK
                                $isLocked = false;
                                if (!empty($a->locked_by) && $a->locked_by != $currentAdminId) {
                                    if (strtotime($a->locked_at) > strtotime('-15 minutes')) {
                                        $isLocked = true;
                                    }
                                }
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="creator-name-black"><?= esc($a->channel_name) ?></div>
                                    <div class="d-flex align-items-center mt-1">
                                        <small class="text-primary font-weight-bold">@<?= strtoupper(esc($a->offender_handle ?? $a->handle)) ?></small>
                                        <?php if($isLocked): ?>
                                            <span class="lock-indicator" title="Locked by Admin ID: <?= $a->locked_by ?>">
                                                <i class="fas fa-user-lock mr-1"></i> IN REVIEW
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <td class="align-middle">
                                    <span class="tag-pro mb-1 d-inline-block"><?= $a->content_type ?></span><br>
                                    <span class="badge py-0 px-2" style="font-size: 9px; color: #fff; background: <?= ($a->type == 'STRIKE') ? 'var(--accent-red)' : 'var(--accent-orange)' ?>;"><?= $a->type ?></span><br>
                                    <span class="points-label mt-1 d-inline-block">-<?= $a->severity_points ?> Strike Points</span>
                                </td>

                                <td class="align-middle" style="max-width: 400px;">
                                    <div class="admin-note-box">
                                        <small class="text-primary d-block font-weight-bold" style="font-size: 9px; text-transform: uppercase;">Original Reason:</small>
                                        <span class="small text-dark"><?= esc($a->reason) ?></span>
                                    </div>
                                    <div class="creator-note-box">
                                        <small class="text-muted d-block font-weight-bold" style="font-size: 9px; text-transform: uppercase;">Creator Appeal:</small>
                                        <span class="small font-italic text-dark">"<?= esc($a->appeal_reason) ?>"</span>
                                    </div>
                                </td>

                                <td class="align-middle text-muted small">
                                    <i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($a->appealed_at)) ?><br>
                                    <i class="far fa-clock mr-1"></i> <?= date('h:i A', strtotime($a->appealed_at)) ?>
                                </td>

                                <td class="align-middle text-right px-4">
                                    <div class="btn-group">
                                        <?php if($isLocked): ?>
                                            <button class="action-btn-pro btn-locked" title="Admin ID #<?= $a->locked_by ?> is reviewing this.">
                                                <i class="fas fa-shield-alt"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="action-btn-pro btn-approve-pro mr-2" title="Approve Appeal"
                                                    onclick="processAppeal(<?= $a->id ?>, 'APPROVED', '<?= esc($a->channel_name) ?>', <?= $a->severity_points ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="action-btn-pro btn-reject-pro" title="Reject Appeal"
                                                    onclick="processAppeal(<?= $a->id ?>, 'REJECTED', '<?= esc($a->channel_name) ?>', 0)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-muted mb-3 opacity-25"></i>
                                    <p class="text-muted font-weight-bold">All clear! No pending appeals to review.</p>
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

<div class="modal fade" id="appealDecisionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <form action="<?= base_url('admin/moderation/strikes/appeal_action') ?>" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="id" id="modal_appeal_id">
                <input type="hidden" name="status" id="modal_appeal_status">

                <div class="modal-header border-bottom bg-light py-3">
                    <h6 class="modal-title font-weight-bold text-dark">Appeal Decision: <span id="modal_channel_name" class="text-primary"></span></h6>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div id="decision_alert" class="p-3 rounded mb-4 small font-weight-bold border"></div>
                    
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold text-uppercase">Admin Feedback (Sent to Creator)</label>
                        <textarea name="admin_note" class="form-control form-control-pro" rows="3" placeholder="Explain your decision..." required></textarea>
                    </div>

                    <div id="action_info" class="p-3 rounded bg-light border small text-muted font-weight-bold"></div>
                </div>
                <div class="modal-footer bg-light border-top py-3">
                    <button type="button" class="btn btn-link text-muted font-weight-bold btn-sm" data-dismiss="modal">CANCEL</button>
                    <button type="submit" id="submit_btn" class="btn btn-sm font-weight-bold px-4 text-white shadow-sm" style="border-radius: 8px;"></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function processAppeal(id, status, channelName, points) {
    $('#modal_appeal_id').val(id);
    $('#modal_appeal_status').val(status);
    $('#modal_channel_name').text(channelName);
    
    if(status === 'APPROVED') {
        $('#decision_alert').css({'background': 'rgba(10, 187, 135, 0.05)', 'color': 'var(--accent-green)', 'border-color': 'var(--accent-green)'})
            .html('<i class="fas fa-undo-alt mr-1"></i> <b>ACCEPT APPEAL:</b> Restoring <b>'+points+' points</b> to the creator profile.');
        $('#action_info').html('<i class="fas fa-info-circle mr-1"></i> Content will be restored and the strike will be removed.');
        $('#submit_btn').css('background', 'var(--accent-green)').text('APPROVE & RESTORE');
    } else {
        $('#decision_alert').css({'background': 'rgba(253, 57, 122, 0.05)', 'color': 'var(--accent-red)', 'border-color': 'var(--accent-red)'})
            .html('<i class="fas fa-ban mr-1"></i> <b>REJECT APPEAL:</b> The strike points will remain active on the profile.');
        $('#action_info').html('<i class="fas fa-exclamation-triangle mr-1"></i> Penalty stays active and the case will be closed.');
        $('#submit_btn').css('background', 'var(--accent-red)').text('REJECT APPEAL');
    }
    $('#appealDecisionModal').modal('show');
}
</script>

<?= $this->endSection() ?>
