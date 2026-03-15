<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .label-muted { color: var(--text-muted); font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 4px; }
    .data-text { color: #000; font-size: 15px; font-weight: 700; }
    
    .info-box-pro { background: #f4f7fa; border: 1px solid var(--border-soft); padding: 20px; border-radius: 12px; }
    .action-panel-pro { border-top: 1px solid var(--border-soft); padding-top: 20px; margin-top: 15px; }
    .hidden-form { display: none; }
    
    /* Pure Black Channel/User Names */
    .black-name { color: #000 !important; font-weight: 800; }

    /* Professional Status Badges */
    .st-badge { font-weight: 800; font-size: 10px; padding: 5px 12px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid transparent; }
    .st-pending { color: var(--accent-orange); background: rgba(249, 155, 45, 0.1); border-color: var(--accent-orange); }
    .st-completed { color: var(--accent-green); background: rgba(10, 187, 135, 0.1); border-color: var(--accent-green); }
    .st-failed { color: var(--accent-red); background: rgba(253, 57, 122, 0.1); border-color: var(--accent-red); }

    .btn-action-pro { height: 48px; font-weight: 700; border-radius: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-size: 13px; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i> Payout Details
            </h1>
            <a href="<?= base_url('admin/finance/withdrawal') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i> BACK TO LIST
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7">
                <div class="card-pro p-4">
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?= get_media_url($r->avatar, 'channel') ?>" 
                             onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($r->channel_name) ?>&background=f4f7fa&color=5d78ff';" 
                             style="width: 65px; height: 65px; border-radius: 12px; border: 1px solid var(--border-soft); object-fit: cover;">
                        <div class="ml-3">
                            <h4 class="black-name mb-0"><?= esc($r->channel_name) ?></h4>
                            <p class="text-primary font-weight-bold mb-0 small">@<?= strtoupper(esc($r->handle)) ?> | Request #<?= $r->id ?></p>
                        </div>
                    </div>
                    <div class="row mt-4">
                        <div class="col-6">
                            <p class="label-muted">Withdrawal Amount</p>
                            <p class="data-text" style="font-size: 24px; color: var(--primary-blue);">₹<?= number_format($r->amount, 2) ?></p>
                        </div>
                        <div class="col-6">
                            <p class="label-muted">Request Date</p>
                            <p class="data-text"><?= date('d M Y, h:i A', strtotime($r->created_at)) ?></p>
                        </div>
                    </div>
                </div>

                <div class="card-pro">
                    <div class="card-header-pro">
                        <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-user-check mr-2 text-primary"></i> IDENTITY VERIFICATION</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <p class="label-muted">Verified Real Name</p>
                                <p class="data-text text-uppercase"><?= esc($r->kyc_real_name ?: 'Not Available') ?></p>
                            </div>
                            <div class="col-md-6 mb-3">
                                <p class="label-muted">KYC Status</p>
                                <div class="mt-1">
                                    <?php 
                                        $kyc = $r->kyc_status;
                                        $kClass = ($kyc == 'APPROVED') ? 'st-completed' : (($kyc == 'PENDING') ? 'st-pending' : 'st-failed');
                                    ?>
                                    <span class="st-badge <?= $kClass ?>">
                                        <?= strtoupper($kyc) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-pro">
                    <div class="card-header-pro">
                        <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-university mr-2 text-primary"></i> PAYMENT INFO</h6>
                    </div>
                    <div class="card-body">
                        <div class="info-box-pro">
                            <?php if($r->payment_method == 'upi'): ?>
                                <p class="label-muted">UPI Address</p>
                                <p class="data-text" style="color: var(--primary-blue);"><?= esc($r->upi_id) ?></p>
                            <?php else: ?>
                                <div class="row">
                                    <div class="col-6 mb-3"><p class="label-muted">Account Holder</p><p class="data-text"><?= esc($r->account_holder_name) ?></p></div>
                                    <div class="col-6 mb-3"><p class="label-muted">Bank Name</p><p class="data-text"><?= esc($r->bank_name) ?></p></div>
                                    <div class="col-6"><p class="label-muted">Account Number</p><p class="data-text"><?= esc($r->account_number) ?></p></div>
                                    <div class="col-6"><p class="label-muted">IFSC Code</p><p class="data-text"><?= esc($r->ifsc) ?></p></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card-pro h-100">
                    <div class="card-header-pro text-center">
                        <h6 class="text-dark m-0 font-weight-bold text-uppercase">Request Status</h6>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php 
                                $status = strtolower($r->status);
                                $displayStatus = ($status == 'failed') ? 'REJECTED' : strtoupper($status);
                            ?>
                            <span class="st-badge st-<?= $status ?> px-4 py-2" style="font-size: 14px;">
                                <?= $displayStatus ?>
                            </span>
                        </div>
                        
                        <?php if($r->status == 'pending'): ?>
                            <div id="actionButtons" class="pt-3">
                                <button type="button" onclick="showApproveForm()" class="btn btn-success btn-block btn-action-pro shadow-sm mb-3">APPROVE PAYOUT</button>
                                <button type="button" onclick="showRejectForm()" class="btn btn-outline-danger btn-block btn-action-pro">REJECT REQUEST</button>
                            </div>

                            <div id="approveForm" class="hidden-form action-panel-pro">
                                <form action="<?= base_url('admin/finance/withdrawal/approve') ?>" method="post" onsubmit="return confirmApproval()">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $r->id ?>">
                                    <div class="form-group">
                                        <label class="label-muted">Bank UTR / Transaction ID</label>
                                        <input type="text" name="utr_number" id="utr_field" class="form-control" 
                                               style="height: 48px; border: 2px solid var(--accent-green); font-weight: 700; color: #000;" 
                                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '')"
                                               placeholder="ENTER UTR NUMBER" required>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-block btn-action-pro shadow-sm">CONFIRM PAYMENT</button>
                                    <button type="button" onclick="cancelAction()" class="btn btn-link btn-sm btn-block text-muted mt-2 font-weight-bold">CANCEL</button>
                                </form>
                            </div>

                            <div id="rejectForm" class="hidden-form action-panel-pro">
                                <form action="<?= base_url('admin/finance/withdrawal/reject') ?>" method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $r->id ?>">
                                    <div class="form-group">
                                        <label class="label-muted">Reason for Rejection</label>
                                        <textarea name="reason" class="form-control" style="border: 2px solid var(--accent-red); color: #000;" rows="3" placeholder="Explain why the request was denied..." required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-danger btn-block btn-action-pro shadow-sm">REJECT & REFUND</button>
                                    <button type="button" onclick="cancelAction()" class="btn btn-link btn-sm btn-block text-muted mt-2 font-weight-bold">CANCEL</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas <?= ($r->status == 'completed') ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' ?> fa-4x mb-3 opacity-25"></i>
                                <div class="mt-3 p-3 text-left" style="background: #f8f9fa; border-radius: 12px; border: 1px solid var(--border-soft);">
                                    <p class="label-muted mb-1"><?= ($r->status == 'completed') ? 'Transaction ID (UTR)' : 'Rejection Reason' ?></p>
                                    <p class="data-text mb-0"><?= ($r->status == 'completed') ? esc($r->transaction_id) : esc($r->admin_notes) ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function showApproveForm() { document.getElementById('actionButtons').style.display = 'none'; document.getElementById('approveForm').style.display = 'block'; }
    function showRejectForm() { document.getElementById('actionButtons').style.display = 'none'; document.getElementById('rejectForm').style.display = 'block'; }
    function cancelAction() { document.getElementById('actionButtons').style.display = 'block'; document.getElementById('approveForm').style.display = 'none'; document.getElementById('rejectForm').style.display = 'none'; }
    
    function confirmApproval() {
        var utr = document.getElementById('utr_field').value;
        if(utr.length < 8) { alert('UTR Number is too short. Minimum 8 characters required.'); return false; }
        return confirm('Verify UTR: ' + utr + '\n\nConfirm that bank transfer is complete? This action cannot be undone.');
    }
</script>

<?= $this->endSection() ?>
