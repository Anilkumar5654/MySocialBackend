<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php 
$request = service('request'); 
$currentAdminId = session()->get('id');
?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    /* Stats Card Pro */
    .stat-card-pro { 
        background: #fff; border-radius: 12px; padding: 22px; 
        box-shadow: var(--card-shadow); position: relative; height: 100%; 
        transition: 0.3s; border: 1px solid var(--border-soft);
    }
    .stat-card-pro:hover { transform: translateY(-3px); }
    .stat-label-pro { font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--text-muted); letter-spacing: 0.5px; }
    .stat-value-pro { font-size: 26px; font-weight: 700; color: #000; margin-top: 5px; }

    /* Left Border Indicator */
    .border-claim-pro { border-left: 4px solid var(--primary-blue) !important; }
    
    /* 🔒 LOCK INDICATOR */
    .lock-indicator {
        font-size: 10px; padding: 2px 8px; border-radius: 4px;
        background: #fff0f0; color: #d9534f; border: 1px solid #ffdada;
        font-weight: 700; display: inline-flex; align-items: center; margin-left: 8px;
    }
    .btn-locked { background: #f8f9fa !important; color: #ccc !important; cursor: not-allowed; border: 1px solid #eee !important; }

    /* Pure Black Reporter Name */
    .reporter-name-black { color: #000 !important; font-weight: 700; font-size: 14px; }
    
    /* Simplified Pro Tags */
    .tag-blue { background: rgba(93, 120, 255, 0.1); color: var(--primary-blue); padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; text-transform: uppercase; border: 1px solid rgba(93, 120, 255, 0.2); }
    .tag-gray { background: #f4f7fa; color: var(--text-dark); padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 800; border: 1px solid var(--border-soft); text-transform: uppercase; }
    .time-badge-pro { background: #fff; color: var(--accent-orange); padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 700; border: 1px solid var(--border-soft); }

    .action-btn-pro { 
        width: 36px; height: 36px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 8px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); color: var(--primary-blue); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                    <i class="fas fa-copyright mr-2 text-primary"></i> Copyright Claims
                </h1>
                <p class="text-muted small mb-0">Review incoming takedown requests for content removal</p>
            </div>
            <a href="<?= base_url('admin/moderation/strikes') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-list mr-1"></i> VIEW ALL RECORDS
            </a>
        </div>
    </div>
</div>

<section class="content mt-4">
    <div class="container-fluid">
        
        <div class="row mb-4">
            <div class="col-md-4 col-12 mb-2">
                <div class="stat-card-pro border-claim-pro">
                    <div class="stat-label-pro">Pending Requests</div>
                    <div class="stat-value-pro"><?= count($claims) ?></div>
                </div>
            </div>
        </div>

        <div class="card card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 900px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Reporter</th>
                                <th class="py-4 border-0">Reported Content</th>
                                <th class="py-4 border-0">Reason</th>
                                <th class="py-4 border-0">Date</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($claims)): foreach($claims as $c): ?>
                            <?php 
                                // 🔒 LOCK LOGIC CHECK
                                $isLocked = false;
                                if (!empty($c->locked_by) && $c->locked_by != $currentAdminId) {
                                    if (strtotime($c->locked_at) > strtotime('-15 minutes')) {
                                        $isLocked = true;
                                    }
                                }
                            ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="reporter-name-black"><?= esc($c->reporter_name) ?></div>
                                    <div class="d-flex align-items-center mt-1">
                                        <span class="tag-blue mr-2"><?= $c->report_source ?? 'USER' ?></span>
                                        <small class="text-muted font-weight-bold">@<?= strtoupper(esc($c->reporter_handle)) ?></small>
                                    </div>
                                </td>

                                <td class="align-middle">
                                    <span class="tag-gray mb-1 d-inline-block"><?= $c->content_type ?></span>
                                    <?php if($isLocked): ?>
                                        <span class="lock-indicator" title="Locked by Admin ID: <?= $c->locked_by ?>">
                                            <i class="fas fa-lock mr-1"></i> BUSY
                                        </span>
                                    <?php endif; ?><br>
                                    <span class="text-dark font-weight-bold small"><?= esc($c->content_title) ?></span>
                                </td>

                                <td class="align-middle">
                                    <span class="text-dark small d-block font-weight-500"><?= esc($c->reason) ?></span>
                                    <div class="mt-1">
                                        <span class="time-badge-pro">
                                            <i class="far fa-clock mr-1"></i> <?= $c->time_start ?? '00:00' ?> - <?= $c->time_end ?? '00:00' ?>
                                        </span>
                                    </div>
                                </td>

                                <td class="align-middle text-muted small">
                                    <i class="far fa-calendar-alt mr-1"></i> <?= date('d M, Y', strtotime($c->created_at)) ?><br>
                                    <i class="far fa-clock mr-1"></i> <?= date('h:i A', strtotime($c->created_at)) ?>
                                </td>

                                <td class="align-middle text-right px-4">
                                    <?php if($isLocked): ?>
                                        <button type="button" class="action-btn-pro btn-locked" title="Admin ID #<?= $c->locked_by ?> is reviewing this.">
                                            <i class="fas fa-user-shield"></i>
                                        </button>
                                    <?php else: ?>
                                        <a href="<?= base_url('admin/moderation/strikes/view/' . $c->id) ?>" class="action-btn-pro" title="Review Case">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-muted mb-3 opacity-25"></i>
                                    <p class="text-muted font-weight-bold">No pending copyright claims found.</p>
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

<?= $this->endSection() ?>
