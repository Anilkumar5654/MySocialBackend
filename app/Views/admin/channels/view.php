<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🏗️ STABILIZED PRO HEADER */
    .channel-header-box { position: relative; margin-bottom: 80px; }
    .cover-banner-pro { 
        height: 240px; width: 100%; object-fit: cover; 
        background: var(--sidebar-dark); border-radius: 12px; 
        box-shadow: var(--card-shadow);
    }
    .pfp-wrapper-pro { 
        position: absolute; bottom: -50px; left: 40px; 
        display: flex; align-items: flex-end; gap: 20px; 
    }
    .channel-pfp-pro { 
        width: 120px; height: 120px; border-radius: 20px; 
        border: 4px solid #fff; background: #fff; object-fit: cover; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
    }
    /* Channel Name Black as requested */
    .header-info-text h2 { 
        font-weight: 700; color: #000; text-transform: uppercase; 
        margin: 0; letter-spacing: -0.5px;
    }

    /* 📊 SIMPLE STAT CARDS */
    .stat-card-pro { 
        background: #fff; border: none; padding: 20px; 
        border-radius: 12px; text-align: center; 
        box-shadow: var(--card-shadow); transition: 0.3s; 
    }
    .stat-num-pro { font-size: 24px; font-weight: 700; color: var(--text-dark); display: block; }
    .stat-label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; }

    /* 📝 CLEAN CONTENT CARDS */
    .info-card-pro { 
        background: #fff; border: none; border-radius: 12px; 
        margin-bottom: 25px; overflow: hidden; box-shadow: var(--card-shadow); 
    }
    .info-card-header-pro { 
        padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid var(--border-soft); 
        font-size: 11px; font-weight: 700; text-transform: uppercase; 
        color: var(--text-dark); display: flex; align-items: center; gap: 10px; 
    }

    /* 📱 ACTION BAR PRO */
    .view-action-bar-pro { 
        background: #fff; border: 1px solid var(--border-soft); 
        border-radius: 12px; padding: 20px; box-shadow: var(--card-shadow); 
        display: flex; align-items: center; justify-content: space-between; 
    }

    /* Modal Form Styles */
    .form-control-pro { background: #f8f9fa !important; border: 1px solid var(--border-soft) !important; color: var(--text-dark) !important; border-radius: 8px; }

    @media (max-width: 768px) {
        .pfp-wrapper-pro { left: 50%; transform: translateX(-50%); flex-direction: column; align-items: center; text-align: center; bottom: -120px; }
        .channel-header-box { margin-bottom: 140px; }
        .view-action-bar-pro { flex-direction: column; text-align: center; gap: 15px; }
    }
</style>

<?php 
    $user_avatar = get_media_url($channel->avatar, 'channel'); 
    $user_cover  = get_media_url($channel->cover_photo, 'cover'); 
    
    $active_strikes_count = 0;
    foreach($strikes as $s) { if($s->status == 'ACTIVE') $active_strikes_count++; }
?>

<div class="channel-header-box">
    <div style="background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.3)), url('<?= $user_cover ?>') center center / cover; border-radius: 12px; height: 240px; box-shadow: var(--card-shadow);"></div>
    <div class="pfp-wrapper-pro">
        <img src="<?= $user_avatar ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($channel->name) ?>&background=f4f7fa&color=5d78ff';" class="channel-pfp-pro">
        <div class="header-info-text">
            <h2><?= esc($channel->name) ?></h2>
            <div class="mt-1">
                <span class="badge" style="background: var(--primary-blue); color: #fff; padding: 6px 15px; border-radius: 30px; font-size: 11px; font-weight: 600;">
                    OWNER: @<?= strtoupper(esc($channel->handle)) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<section class="content px-3">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stat-card-pro">
                    <span class="stat-label-pro" style="color: var(--accent-orange);">Trust Score</span>
                    <span class="stat-num-pro"><?= $channel->trust_score ?>%</span>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card-pro">
                    <span class="stat-label-pro" style="color: var(--primary-blue);">Total Views</span>
                    <span class="stat-num-pro"><?= number_format($channel->total_views ?? 0) ?></span>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card-pro">
                    <span class="stat-label-pro" style="color: var(--accent-red);">Strikes</span>
                    <span class="stat-num-pro"><?= $active_strikes_count ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="info-card-pro">
                    <div class="info-card-header-pro"><i class="fas fa-info-circle text-primary"></i> About Channel</div>
                    <div class="card-body">
                        <p class="text-dark small mb-0 font-weight-500"><?= nl2br(esc($channel->about_text ?? $channel->description ?? 'No bio available.')) ?></p>
                    </div>
                </div>

                <div class="info-card-pro">
                    <div class="info-card-header-pro"><i class="fas fa-gavel text-danger"></i> Strike History</div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead style="font-size: 10px; background: #f8f9fa;">
                                    <tr>
                                        <th class="px-4 border-0">Date</th>
                                        <th class="border-0 text-center">Type</th>
                                        <th class="border-0">Reason</th>
                                        <th class="border-0 text-right px-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($strikes)): foreach($strikes as $s): ?>
                                    <tr style="border-bottom: 1px solid var(--border-soft);">
                                        <td class="px-4 py-3 align-middle small text-muted"><?= date('d M, Y', strtotime($s->created_at)) ?></td>
                                        <td class="align-middle text-center">
                                            <span class="badge" style="background: <?= ($s->type == 'STRIKE') ? 'rgba(253, 57, 122, 0.1); color: var(--accent-red);' : 'rgba(249, 155, 45, 0.1); color: var(--accent-orange);' ?> padding: 4px 10px; font-size: 10px;">
                                                <?= $s->type ?>
                                            </span>
                                        </td>
                                        <td class="align-middle text-dark small font-weight-bold"><?= esc($s->reason) ?></td>
                                        <td class="align-middle text-right px-4">
                                            <span class="badge" style="background: #f4f7fa; color: <?= ($s->status == 'ACTIVE') ? 'var(--accent-green)' : '#888' ?>; font-size: 10px;">
                                                <?= $s->status ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; else: ?>
                                    <tr><td colspan="4" class="text-center py-5 text-muted small font-weight-bold">No violations found.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="info-card-pro" style="border-top: 3px solid <?= ($channel->monetization_status == 'APPROVED') ? 'var(--accent-green)' : 'var(--primary-blue)' ?>;">
                    <div class="info-card-header-pro">Monetization</div>
                    <div class="card-body text-center py-4">
                        <?php if($channel->monetization_status == 'APPROVED'): ?>
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-dark font-weight-bold">ACTIVE</h5>
                            <button onclick="handleToggle('SUSPEND')" class="btn btn-outline-danger btn-block mt-3 font-weight-bold" style="border-radius: 8px;">SUSPEND EARNINGS</button>
                        <?php else: ?>
                            <i class="fas fa-clock fa-3x text-warning mb-3"></i>
                            <h5 class="text-dark font-weight-bold"><?= $channel->monetization_status ?></h5>
                            <button onclick="handleToggle('APPROVE')" class="btn btn-primary btn-block mt-3 font-weight-bold" style="background: var(--primary-blue); border-radius: 8px; border: none;">APPROVE NOW</button>
                        <?php endif; ?>
                    </div>
                </div>

                <button class="btn btn-block btn-light shadow-sm mb-4 py-3 font-weight-bold text-danger" style="border-radius: 12px; border: 1px solid var(--border-soft);" data-toggle="modal" data-target="#strikeModal">
                    <i class="fas fa-bolt mr-2"></i> PUSH NEW STRIKE
                </button>
            </div>
        </div>

        <div class="row mt-4 pb-5">
            <div class="col-12">
                <div class="view-action-bar-pro">
                    <div>
                        <h5 class="text-dark font-weight-bold mb-1">Administrative Center</h5>
                        <p class="text-muted small mb-0">Modify metadata or remove the record from system.</p>
                    </div>
                    
                    <div class="d-flex align-items-center" style="gap: 12px;">
                        <?php if (has_permission('channels.edit')): ?>
                            <a href="<?= base_url('admin/channels/edit/'.$channel->id) ?>" class="btn btn-sm" style="color: var(--accent-orange); border: 1px solid var(--accent-orange); border-radius: 8px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (has_permission('channels.delete')): ?>
                            <button type="button" class="btn btn-sm delete-channel-btn" data-url="<?= base_url('admin/channels/delete/'.$channel->id) ?>" data-name="<?= esc($channel->name) ?>" style="color: var(--accent-red); border: 1px solid var(--accent-red); border-radius: 8px; width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="strikeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 px-4 pt-4">
                <h5 class="modal-title text-dark font-weight-bold">ISSUE CHANNEL PENALTY</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body px-4 pb-4">
                <form id="strikeForm">
                    <input type="hidden" name="channel_id" value="<?= $channel->id ?>">
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">PENALTY TYPE</label>
                        <select name="type" class="form-control form-control-pro">
                            <option value="WARNING">⚠️ WARNING</option>
                            <option value="STRIKE" selected>🔥 STRIKE</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">REASON</label>
                        <select name="reason" class="form-control form-control-pro">
                            <option value="Copyright Violation">Copyright Violation</option>
                            <option value="Community Guidelines">Community Guidelines</option>
                            <option value="Spam/Scam">Spam/Scam</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="small text-muted font-weight-bold">DESCRIPTION</label>
                        <textarea name="description" class="form-control form-control-pro" rows="3"></textarea>
                    </div>
                    <button type="button" onclick="submitStrike()" class="btn btn-primary btn-block font-weight-bold py-3 mt-3 shadow-sm" style="background: var(--primary-blue); border: none; border-radius: 10px;">
                        CONFIRM PENALTY
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
/**
 * 🚀 Administrative Logic (Restored)
 */
function submitStrike() {
    let formData = $('#strikeForm').serialize() + "&<?= csrf_token() ?>=<?= csrf_hash() ?>";
    $.post('<?= base_url('admin/channels/issue_strike') ?>', formData, function(res) {
        if (res.status === 'success') location.reload();
    });
}

function handleToggle(actionType) {
    Swal.fire({ title: 'Are you sure?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#5d78ff' }).then((result) => {
        if (result.isConfirmed) {
            $.post('<?= base_url('admin/channels/monetization_toggle_status/'.$channel->id) ?>', { "<?= csrf_token() ?>": "<?= csrf_hash() ?>" }, function(res) {
                if (res.status === 'success') location.reload();
            });
        }
    });
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-channel-btn');
    if (btn) {
        e.preventDefault();
        const url = btn.getAttribute('data-url');
        const name = btn.getAttribute('data-name');
        Swal.fire({
            title: 'Delete Channel?',
            text: "Target: @" + name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fd397a',
            background: '#fff', color: '#3d4465'
        }).then((result) => { if (result.isConfirmed) window.location.href = url; });
    }
});
</script>

<?= $this->endSection() ?>
