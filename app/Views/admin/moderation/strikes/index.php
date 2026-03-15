<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<?php $current_filter = service('request')->getGet('status') ?? 'ALL'; ?>

<style>
    /* 📱 MOBILE FRIENDLY CARDS */
    .stat-card-pro { 
        background: #fff; border-radius: 15px; padding: 20px; 
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); position: relative; 
        transition: 0.3s; border: 1px solid var(--border-soft);
        cursor: pointer; text-decoration: none !important; display: block;
        margin-bottom: 15px; 
    }
    .stat-card-pro:hover { transform: translateY(-3px); border-color: var(--primary-blue); }
    .card-strike { border-bottom: 4px solid var(--accent-red) !important; }
    .card-claim { border-bottom: 4px solid var(--primary-blue) !important; }
    .card-total { border-bottom: 4px solid #333 !important; }

    .stat-label-pro { font-size: 10px; text-transform: uppercase; font-weight: 800; color: var(--text-muted); letter-spacing: 0.8px; }
    .stat-value-pro { font-size: 28px; font-weight: 800; color: #000; margin-top: 5px; display: block; }
    .stat-icon-bg { position: absolute; right: 20px; top: 50%; transform: translateY(-50%); font-size: 30px; opacity: 0.1; }
    .black-name { color: #000 !important; font-weight: 700; font-size: 14px; }
    
    @media (max-width: 768px) {
        .stat-value-pro { font-size: 22px; }
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-8">
                <h1 style="color: #000; font-weight: 800; letter-spacing: -0.5px; margin:0;">Decision Logs</h1>
                <p class="text-muted small mb-0">Audit history & penalties</p>
            </div>
            <div class="col-4 text-right">
                <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" onclick="openStrikeModal('CHANNEL', '', '')">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content mt-3">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-4 col-12">
                <a href="<?= base_url('admin/moderation/strikes?status=ACTIVE') ?>" class="stat-card-pro card-strike">
                    <div class="stat-label-pro">Active Strikes</div>
                    <span class="stat-value-pro text-danger">
                        <?= $stats['active_strikes'] ?>
                    </span>
                    <i class="fas fa-gavel stat-icon-bg"></i>
                </a>
            </div>

            <div class="col-md-4 col-12">
                <a href="<?= base_url('admin/moderation/strikes?status=CLAIM') ?>" class="stat-card-pro card-claim">
                    <div class="stat-label-pro">Total Claims</div>
                    <span class="stat-value-pro text-primary">
                        <?= $stats['total_claims'] ?>
                    </span>
                    <i class="fas fa-copyright stat-icon-bg"></i>
                </a>
            </div>

            <div class="col-md-4 col-12">
                <a href="<?= base_url('admin/moderation/strikes?status=ALL') ?>" class="stat-card-pro card-total">
                    <div class="stat-label-pro">Audit Records</div>
                    <span class="stat-value-pro">
                        <?= $stats['total_records'] ?>
                    </span>
                    <i class="fas fa-history stat-icon-bg"></i>
                </a>
            </div>
        </div>

        <div class="card card-pro mt-2">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light" style="font-size: 10px; text-transform: uppercase;">
                            <tr>
                                <th class="py-3 px-4 border-0">Creator</th>
                                <th class="py-3 border-0">Violation</th>
                                <th class="py-3 text-center border-0">Type</th>
                                <th class="py-3 text-center border-0">Status</th>
                                <th class="py-3 text-right px-4 border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($strikes)): foreach($strikes as $s): ?>
                            <tr>
                                <td class="align-middle px-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($s->offender_avatar, 'profile') ?>" style="width: 35px; height: 35px; border-radius: 8px; object-fit: cover; margin-right: 10px;">
                                        <div class="black-name"><?= esc($s->channel_name) ?></div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-dark font-weight-bold small"><?= esc($s->reason) ?></div>
                                    <small class="text-muted" style="font-size: 9px;"><?= date('d M', strtotime($s->created_at)) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="font-weight-bold small" style="color: <?= ($s->type == 'STRIKE') ? 'red' : 'blue' ?>;">
                                        <?= $s->type ?>
                                    </span>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="badge <?= ($s->status == 'ACTIVE') ? 'badge-danger' : 'badge-light border' ?>" style="font-size: 9px;">
                                        <?= $s->status ?>
                                    </span>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <a href="<?= base_url('admin/moderation/strikes/view/' . $s->id) ?>" class="btn btn-xs btn-light border">
                                        <i class="fas fa-eye text-primary"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted">No records.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->include('admin/moderation/strikes/modal_snippet') ?>

<script>
    function openStrikeModal(type, content_id, channel_id) {
        $('#strike_content_type').val(type);
        $('#strike_content_id').val(content_id);
        $('#strike_channel_id').val(channel_id);
        $('#addStrikeModal').modal('show');
    }
</script>

<?= $this->endSection() ?>
