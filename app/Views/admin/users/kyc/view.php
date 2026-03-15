<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🏗️ Professional Header Logic */
    .profile-header-pro { 
        background: var(--sidebar-dark); 
        padding: 40px 20px;
        text-align: center;
        border-radius: 12px;
        margin-bottom: 40px;
        box-shadow: var(--card-shadow);
        color: #fff;
    }
    
    .profile-avatar-wrapper {
        position: relative;
        display: inline-block;
        margin-bottom: 15px;
    }

    .profile-avatar-pro { 
        width: 120px; 
        height: 120px; 
        border-radius: 15px; 
        border: 4px solid #fff; 
        object-fit: cover; 
        background: #fff; 
        box-shadow: 0 8px 25px rgba(0,0,0,0.1); 
    }

    /* White Pro Cards Styling */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); height: 100%; }
    .card-header-pro { border-bottom: 1px solid var(--border-soft); padding: 18px 25px; display: flex; justify-content: space-between; align-items: center; }
    
    /* Document Display Pro */
    .doc-preview-container-pro { 
        position: relative; 
        overflow: hidden; 
        border-radius: 12px; 
        border: 1px solid var(--border-soft); 
        height: 240px; 
        background: #f8f9fa; 
        transition: 0.3s;
    }
    .doc-preview-container-pro:hover { border-color: var(--primary-blue); box-shadow: var(--card-shadow); }
    .doc-preview-pro { width: 100%; height: 100%; object-fit: contain; cursor: zoom-in; }
    
    /* Status Badges Pro */
    .status-badge-pro { padding: 8px 20px; border-radius: 8px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .status-PENDING { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    .status-APPROVED { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .status-REJECTED { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }

    .info-label-pro { font-size: 10px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; margin-bottom: 6px; display: block; font-weight: 700; }
    .info-value-pro { color: var(--text-dark); font-weight: 600; font-size: 15px; }
</style>

<?php 
    $user_avatar = get_media_url($request->avatar ?? '', 'profile');
    $front_img = get_media_url($request->front_image_url ?? '', 'kyc');
    $back_img  = get_media_url($request->back_image_url ?? '', 'kyc');
    $status    = $request->status ?? 'PENDING';
?>

<div class="profile-header-pro">
    <div class="profile-avatar-wrapper">
        <img src="<?= $user_avatar ?>" 
             onerror="this.src='https://ui-avatars.com/api/?name=<?= $request->username ?? 'U' ?>&background=f4f7fa&color=5d78ff';"
             class="profile-avatar-pro">
    </div>
    <h2 class="font-weight-bold mb-1" style="letter-spacing: -0.5px; color: #fff;"><?= strtoupper($request->full_name ?? $request->username) ?></h2>
    <div class="d-flex align-items-center justify-content-center gap-2">
        <span class="badge" style="background: var(--primary-blue); color: #fff; padding: 6px 18px; border-radius: 30px; font-size: 11px; font-weight: 700;">
            @<?= strtoupper($request->username ?? 'member') ?>
        </span>
        <span class="status-badge-pro status-<?= $status ?> ml-2"><?= $status ?></span>
    </div>
</div>

<section class="content" style="padding: 0 15px 50px;">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="card-pro">
                    <div class="card-header-pro">
                        <span class="font-weight-bold small text-uppercase" style="color: var(--text-dark);">Application Details</span>
                        <i class="fas fa-info-circle text-muted"></i>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <span class="info-label-pro">Linked Email</span>
                            <div class="info-value-pro"><?= $request->email ?></div>
                        </div>
                        <div class="mb-4">
                            <span class="info-label-pro">Submission Date</span>
                            <div class="info-value-pro"><?= date('d M Y, h:i A', strtotime($request->submitted_at)) ?></div>
                        </div>
                        
                        <?php if($status == 'REJECTED'): ?>
                        <div class="p-3 rounded mb-4" style="background: rgba(253, 57, 122, 0.05); border: 1px solid rgba(253, 57, 122, 0.2);">
                            <span class="info-label-pro" style="color: var(--accent-red);">Rejection Reason</span>
                            <p class="text-dark small mb-0 font-weight-500"><?= $request->rejection_reason ?></p>
                        </div>
                        <?php endif; ?>

                        <a href="<?= base_url('admin/users/view/'.$request->user_id) ?>" class="btn btn-block py-3 font-weight-bold shadow-sm" style="border-radius: 10px; border: 1px dashed var(--border-soft); color: var(--text-dark); background: #f8f9fa;">
                            <i class="fas fa-external-link-alt mr-2 text-primary"></i> OPEN FULL PROFILE
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card-pro mb-4">
                    <div class="card-header-pro">
                        <span class="font-weight-bold small text-uppercase" style="color: var(--primary-blue);">KYC Evidence Documents</span>
                        <span class="badge" style="background: var(--bg-light); color: var(--text-dark); padding: 5px 12px;"><?= strtoupper(str_replace('_', ' ', $request->document_type)) ?></span>
                    </div>
                    <div class="card-body">
                        <div class="p-3 mb-4 rounded" style="background: #f8f9fa; border-left: 4px solid var(--primary-blue);">
                            <div class="row">
                                <div class="col-sm-6">
                                    <span class="info-label-pro">Document Number</span>
                                    <div class="info-value-pro" style="color: var(--primary-blue); font-family: monospace;"><?= strtoupper($request->document_number) ?></div>
                                </div>
                                <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                                    <span class="info-label-pro">Verify Name Match</span>
                                    <div class="info-value-pro"><?= strtoupper($request->full_name) ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <span class="info-label-pro mb-2">Front Side Image</span>
                                <div class="doc-preview-container-pro">
                                    <img src="<?= $front_img ?>" class="doc-preview-pro" onclick="previewImage('<?= $front_img ?>', 'Front ID')">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <span class="info-label-pro mb-2">Back Side Image</span>
                                <div class="doc-preview-container-pro">
                                    <img src="<?= $back_img ?>" class="doc-preview-pro" onclick="previewImage('<?= $back_img ?>', 'Back ID')">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($status == 'PENDING'): ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <button onclick="confirmApprove()" class="btn btn-block py-3 font-weight-bold shadow-sm" style="background: var(--accent-green); color: #fff; border-radius: 10px; border: none;">
                            <i class="fas fa-check-double mr-2"></i> APPROVE VERIFICATION
                        </button>
                    </div>
                    <div class="col-md-6 mb-3">
                        <button onclick="confirmReject()" class="btn btn-block py-3 font-weight-bold shadow-sm" style="background: transparent; border: 1px solid var(--accent-red); color: var(--accent-red); border-radius: 10px;">
                            <i class="fas fa-ban mr-2"></i> REJECT REQUEST
                        </button>
                    </div>
                </div>
                <?php elseif($status == 'APPROVED'): ?>
                <div class="card-pro p-4 text-center" style="border-top: 3px solid var(--accent-red);">
                    <p class="text-muted small mb-3">Verified user detected. Use revoke if documents are found invalid later.</p>
                    <button onclick="confirmRevoke()" class="btn btn-outline-danger px-5 font-weight-bold" style="border-radius: 30px; border-width: 2px;">
                        REVOKE KYC STATUS
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<form id="actionForm" action="<?= base_url('admin/kyc/action') ?>" method="POST" style="display: none;">
    <?= csrf_field() ?>
    <input type="hidden" name="kyc_id" value="<?= $request->id ?>">
    <input type="hidden" name="user_id" value="<?= $request->user_id ?>">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="rejection_reason" id="formReason">
</form>

<script>
    function previewImage(url, title) {
        Swal.fire({
            imageUrl: url,
            imageAlt: title,
            background: '#fff',
            confirmButtonColor: '#5d78ff',
            imageWidth: '100%',
            imageHeight: 'auto'
        });
    }

    function confirmApprove() {
        Swal.fire({
            title: 'Approve KYC?',
            text: "User will get creator badge and bonus points!",
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: '#0abb87',
            confirmButtonText: 'Yes, Verify',
            background: '#fff', color: '#3d4465'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAction').value = 'approve';
                document.getElementById('actionForm').submit();
            }
        });
    }

    function confirmReject() {
        Swal.fire({
            title: 'Reject KYC?',
            input: 'textarea',
            inputPlaceholder: 'Reason for rejection (e.g. ID not clear)...',
            showCancelButton: true,
            confirmButtonColor: '#fd397a',
            confirmButtonText: 'Reject',
            background: '#fff', color: '#3d4465',
            inputValidator: (value) => { if (!value) return 'Reason is required!'; }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAction').value = 'reject';
                document.getElementById('formReason').value = result.value;
                document.getElementById('actionForm').submit();
            }
        });
    }

    function confirmRevoke() {
        Swal.fire({
            title: 'Revoke KYC?',
            text: "This will remove verification and deduct points!",
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Reason for revocation...',
            showCancelButton: true,
            confirmButtonColor: '#fd397a',
            confirmButtonText: 'Revoke Now',
            background: '#fff', color: '#3d4465',
            inputValidator: (value) => { if (!value) return 'Reason is required!'; }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('formAction').value = 'reject';
                document.getElementById('formReason').value = result.value;
                document.getElementById('actionForm').submit();
            }
        });
    }
</script>

<?= $this->endSection() ?>
