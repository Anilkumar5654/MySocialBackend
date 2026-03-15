<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<style>
    /* Professional Form Consistency */
    .form-control-pro { 
        background: #fff !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        border-radius: 8px; 
        height: 48px; 
        transition: 0.3s; 
        font-size: 14px; 
    }
    .form-control-pro:focus { 
        border-color: var(--primary-blue) !important; 
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1); 
    }
    .form-control-pro:disabled { 
        background: #f8f9fa !important; 
        color: #adb5bd !important; 
        cursor: not-allowed; 
        border-color: var(--border-soft) !important; 
    }
    
    .card-pro { 
        background: #fff; 
        border: none; 
        border-radius: 12px; 
        margin-bottom: 25px; 
        overflow: hidden; 
        box-shadow: var(--card-shadow); 
    }
    .card-header-pro { 
        background: #f8f9fa; 
        border-bottom: 1px solid var(--border-soft); 
        padding: 15px 20px; 
        display: flex; 
        align-items: center; 
        justify-content: space-between; 
    }
    
    .switch-panel-pro { 
        background: #fdfdfd; 
        border: 1px solid var(--border-soft); 
        border-radius: 10px; 
        padding: 12px 15px; 
        transition: 0.3s; 
    }
    
    label { 
        font-weight: 700 !important; 
        font-size: 10px; 
        letter-spacing: 0.8px; 
        text-transform: uppercase; 
        color: var(--text-muted); 
        margin-bottom: 8px; 
        display: block; 
    }
    .section-title-pro { 
        font-size: 12px; 
        font-weight: 700; 
        letter-spacing: 1px; 
        text-transform: uppercase; 
        margin: 0; 
    }
    
    .uid-tag-pro { 
        font-family: 'Poppins', sans-serif; 
        color: var(--primary-blue); 
        font-size: 11px; 
        font-weight: 700; 
        background: rgba(93, 120, 255, 0.05);
        padding: 4px 10px;
        border-radius: 6px;
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-sm-6">
                <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                    <i class="fas fa-user-edit mr-2 text-primary"></i> Modify Member
                </h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= base_url('admin/users/view/'.$user->id) ?>" class="btn btn-light shadow-sm btn-sm mr-2" style="border-radius: 8px; font-weight: 700; font-size: 11px; color: var(--primary-blue);">
                    <i class="fas fa-eye mr-1"></i> VIEW PROFILE
                </a>
                <a href="<?= base_url('admin/users') ?>" class="btn btn-light shadow-sm btn-sm" style="border-radius: 8px; font-weight: 700; font-size: 11px; color: var(--text-dark);">
                    <i class="fas fa-arrow-left mr-1"></i> BACK
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content pb-5">
    <div class="container-fluid">
        
        <form action="<?= base_url('admin/users/update/'.$user->id) ?>" method="post">
            
            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro text-primary">Core Identity</h6>
                    <span class="uid-tag-pro">SYS_ID: #<?= $user->id ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Permanent Unique ID (Non-Editable)</label>
                            <input type="text" class="form-control form-control-pro" value="<?= $user->unique_id ?>" disabled>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Username Handle</label>
                            <input type="text" name="username" class="form-control form-control-pro" value="<?= $user->username ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Registered Email</label>
                            <input type="email" name="email" class="form-control form-control-pro" value="<?= $user->email ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Full Display Name</label>
                            <input type="text" name="name" class="form-control form-control-pro" value="<?= $user->name ?>" placeholder="Enter name...">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Contact Phone</label>
                            <input type="text" name="phone" class="form-control form-control-pro" value="<?= $user->phone ?>" placeholder="+91...">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Geo Location</label>
                            <input type="text" name="location" class="form-control form-control-pro" value="<?= $user->location ?>" placeholder="City, Country">
                        </div>
                        <div class="col-12">
                            <label>Personal Biography</label>
                            <textarea name="bio" class="form-control form-control-pro" rows="2" style="height: auto;"><?= $user->bio ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--accent-green);">Access & Validation</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Verification Badge</label>
                            <select name="is_verified" class="form-control form-control-pro">
                                <option value="1" <?= $user->is_verified ? 'selected' : '' ?>>✅ VERIFIED PROFILE</option>
                                <option value="0" <?= !$user->is_verified ? 'selected' : '' ?>>❌ STANDARD PROFILE</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Email Status</label>
                            <select name="email_verified" class="form-control form-control-pro">
                                <option value="1" <?= $user->email_verified ? 'selected' : '' ?>>✅ VERIFIED EMAIL</option>
                                <option value="0" <?= !$user->email_verified ? 'selected' : '' ?>>⏳ PENDING VERIFICATION</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Platform Status</label>
                            <select name="is_banned" class="form-control form-control-pro">
                                <option value="0" <?= !$user->is_banned ? 'selected' : '' ?>>🟢 ACTIVE ACCESS</option>
                                <option value="1" <?= $user->is_banned ? 'selected' : '' ?>>🔴 BANNED / BLOCKED</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>KYC Ledger Status</label>
                            <select name="kyc_status" class="form-control form-control-pro">
                                <option value="NOT_SUBMITTED" <?= ($user->kyc_status == 'NOT_SUBMITTED') ? 'selected' : '' ?>>NOT SUBMITTED</option>
                                <option value="PENDING" <?= ($user->kyc_status == 'PENDING') ? 'selected' : '' ?>>PENDING REVIEW</option>
                                <option value="APPROVED" <?= ($user->kyc_status == 'APPROVED') ? 'selected' : '' ?>>APPROVED</option>
                                <option value="REJECTED" <?= ($user->kyc_status == 'REJECTED') ? 'selected' : '' ?>>REJECTED</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--accent-orange);">Permissions & Security</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">CREATOR ACCOUNT</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_creator" value="1" class="custom-control-input" id="creStat" <?= $user->is_creator ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="creStat"></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">PAYOUT ACCESS</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_payout_setup" value="1" class="custom-control-input" id="payStat" <?= $user->is_payout_setup ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="payStat"></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">UPLOADS ALLOWED</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="allow_video_uploads" value="1" class="custom-control-input" id="vidUpload" <?= $user->allow_video_uploads ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="vidUpload"></label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mt-4">
                            <label>System Role Mapping</label>
                            <select name="role_id" class="form-control form-control-pro" <?= (session()->get('id') == $user->id) ? 'disabled' : '' ?>>
                                <option value="0">REGULAR MEMBER</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?= $role->id ?>" <?= ($user->role_id == $role->id) ? 'selected' : '' ?>><?= strtoupper($role->role_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(session()->get('id') == $user->id): ?>
                                <small class="text-muted">You cannot change your own role.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mt-4">
                            <label>Administrative Force Password Reset</label>
                            <input type="password" name="password" class="form-control form-control-pro" placeholder="New secure password...">
                            <small class="text-muted">Leave empty to keep existing password.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 mb-5">
                <div class="col-12">
                    <button type="submit" class="btn btn-lg btn-block py-3 font-weight-bold shadow-sm" style="background: var(--primary-blue); color: #fff; border-radius: 12px; border: none; font-size: 16px;">
                        <i class="fas fa-save mr-2"></i> UPDATE MEMBER PROFILE
                    </button>
                </div>
            </div>

        </form>
    </div>
</section>
<?= $this->endSection() ?>
