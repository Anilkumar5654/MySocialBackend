<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PROFESSIONAL REVIEW UI */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); overflow: hidden; }
    
    .check-box { padding: 20px; border-radius: 10px; background: #f8f9fa; border-left: 4px solid #ddd; margin-bottom: 15px; transition: 0.3s; }
    .check-box.pass { border-left-color: var(--accent-green); background: rgba(10, 187, 135, 0.05); }
    .check-box.fail { border-left-color: var(--accent-red); background: rgba(253, 57, 122, 0.05); }
    
    .channel-header-pro { background: #fff; padding: 25px; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; border: 1px solid var(--border-soft); }
    .stat-pill-pro { background: #f4f7fa; padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border-soft); }

    /* Channel Name Black as requested */
    .channel-name-text { color: #000 !important; font-weight: 700; margin-bottom: 0; }

    .form-control-pro { background: #f8f9fa !important; border: 1px solid var(--border-soft) !important; color: var(--text-dark) !important; border-radius: 8px; font-family: inherit; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;"><i class="fas fa-search-dollar text-primary mr-2"></i> Review Application</h1>
                <p class="text-muted small">ID: #<?= $channel->id ?> • Submitted: <?= date('d M, Y', strtotime($channel->monetization_applied_date ?? 'now')) ?></p>
            </div>
            <a href="<?= base_url('admin/channels/monetization') ?>" class="btn btn-light btn-sm shadow-sm" style="border: 1px solid var(--border-soft); font-weight: 600;"><i class="fas fa-arrow-left mr-1"></i> BACK TO LIST</a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="channel-header-pro">
            <div class="row align-items-center">
                <div class="col-md-6 d-flex align-items-center">
                    <img src="<?= get_media_url($channel->avatar, 'channel') ?>" class="rounded mr-3" style="width: 65px; height: 65px; border: 1px solid var(--border-soft); object-fit: cover;">
                    <div>
                        <h4 class="channel-name-text"><?= esc($channel->name) ?></h4>
                        <span class="text-primary font-weight-bold small">@<?= strtoupper(esc($channel->handle)) ?></span>
                        <div class="mt-1">
                            <span class="badge" style="background: #f4f7fa; color: var(--text-dark); font-size: 10px; border: 1px solid var(--border-soft);">LEVEL: <?= strtoupper($channel->creator_level) ?></span>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-md-end mt-3 mt-md-0">
                        <div class="stat-pill-pro mr-2 text-center">
                            <div class="text-dark font-weight-bold" style="font-size: 16px;"><?= number_format($channel->followers_count ?? 0) ?></div>
                            <small class="text-muted text-uppercase" style="font-size: 9px; font-weight: 700;">Followers</small>
                        </div>
                        <div class="stat-pill-pro text-center">
                            <div class="text-primary font-weight-bold" style="font-size: 16px;"><?= $channel->trust_score ?>%</div>
                            <small class="text-muted text-uppercase" style="font-size: 9px; font-weight: 700;">Trust Score</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card-pro">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h6 class="text-dark m-0 font-weight-bold">Check Channel Status</h6>
                    </div>
                    <div class="card-body">
                        
                        <div class="check-box <?= ($channel->kyc_status == 'APPROVED') ? 'pass' : 'fail' ?>">
                            <div class="d-flex align-items-center">
                                <i class="fas <?= ($channel->kyc_status == 'APPROVED') ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' ?> fa-2x mr-3"></i>
                                <div>
                                    <div class="text-dark font-weight-bold">Identity Check</div>
                                    <p class="text-muted small mb-0">The owner must be verified to get paid. Current Status: <strong><?= $channel->kyc_status ?></strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="check-box <?= ($channel->trust_score >= 70) ? 'pass' : 'fail' ?>">
                            <div class="d-flex align-items-center">
                                <i class="fas <?= ($channel->trust_score >= 70) ? 'fa-shield-alt text-success' : 'fa-exclamation-triangle text-warning' ?> fa-2x mr-3"></i>
                                <div>
                                    <div class="text-dark font-weight-bold">Trust Score Check</div>
                                    <p class="text-muted small mb-0">Score based on account behavior and reports. Current Score: <strong><?= $channel->trust_score ?>/100</strong></p>
                                </div>
                            </div>
                        </div>

                        <?php $safe = ($active_strikes == 0); ?>
                        <div class="check-box <?= $safe ? 'pass' : 'fail' ?>">
                            <div class="d-flex align-items-center">
                                <i class="fas <?= $safe ? 'fa-check-circle text-success' : 'fa-gavel text-danger' ?> fa-2x mr-3"></i>
                                <div>
                                    <div class="text-dark font-weight-bold">Copyright Check</div>
                                    <p class="text-muted small mb-0">Total Active Strikes: <strong class="<?= $safe ? '' : 'text-danger' ?>"><?= $active_strikes ?></strong></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="text-muted small font-weight-bold text-uppercase">Internal Admin Notes</label>
                            <textarea id="admin_notes" class="form-control form-control-pro" rows="3" placeholder="Enter notes here (private)..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-pro mb-4" style="border-top: 3px solid var(--primary-blue);">
                    <div class="card-body text-center p-4">
                        <h5 class="text-dark font-weight-bold mb-2">Final Decision</h5>
                        <p class="text-muted small mb-4">Review the channel before making a decision.</p>
                        
                        <button onclick="takeAction('approve')" class="btn btn-block font-weight-bold mb-3 shadow-sm" style="background: var(--accent-green); color: #fff; border-radius: 10px; height: 55px; border: none;">
                            <i class="fas fa-check-circle mr-2"></i> APPROVE NOW
                        </button>

                        <button onclick="takeAction('reject')" class="btn btn-outline-danger btn-block font-weight-bold" style="border-radius: 10px; border-width: 1.5px;">
                            <i class="fas fa-ban mr-2"></i> REJECT NOW
                        </button>

                        <div class="mt-4 pt-3 border-top">
                            <a href="<?= base_url('admin/channels/view/'.$channel->id) ?>" target="_blank" class="text-primary font-weight-bold small">
                                <i class="fas fa-external-link-alt mr-1"></i> VIEW FULL CHANNEL
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert shadow-sm" style="background: rgba(93, 120, 255, 0.05); border: 1px solid rgba(93, 120, 255, 0.1); color: var(--text-dark); font-size: 11px; border-radius: 10px;">
                    <i class="fas fa-info-circle mr-2 text-primary"></i> <strong>Note:</strong> Approving will send a notification and enable the Creator Dashboard for this user.
                </div>
            </div>

        </div>
    </div>
</section>

<form id="monetizationForm" action="<?= base_url('admin/channels/monetization/process') ?>" method="POST" style="display:none;">
    <?= csrf_field() ?>
    <input type="hidden" name="channel_id" value="<?= $channel->id ?>">
    <input type="hidden" name="action" id="monetizationAction">
    <input type="hidden" name="reason" id="monetizationReason">
</form>

<script>
    function takeAction(type) {
        let title = type === 'approve' ? 'Confirm Approval' : 'Rejection Reason';
        let text = type === 'approve' ? 'Enable monetization for <?= esc($channel->name) ?>?' : 'Tell the creator why their request was rejected.';
        let icon = type === 'approve' ? 'success' : 'warning';

        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            input: type === 'reject' ? 'textarea' : null,
            inputPlaceholder: 'Type reason here...',
            showCancelButton: true,
            confirmButtonColor: type === 'approve' ? '#0abb87' : '#fd397a',
            cancelButtonColor: '#abb3ba',
            confirmButtonText: type === 'approve' ? 'Yes, Approve' : 'Reject Now',
            background: '#fff',
            color: '#3d4465'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Processing...', allowOutsideClick: false, didOpen: () => { Swal.showLoading(); } });
                document.getElementById('monetizationAction').value = type;
                if(type === 'reject') {
                    if(!result.value) {
                        Swal.fire('Error', 'Reason is required', 'error');
                        return;
                    }
                    document.getElementById('monetizationReason').value = result.value;
                }
                document.getElementById('monetizationForm').submit();
            }
        });
    }
</script>

<?= $this->endSection() ?>
