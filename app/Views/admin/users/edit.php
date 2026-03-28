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
        height: 100%;
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
                    <i class="fas fa-user-edit mr-2 text-primary"></i> Edit Member Profile
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
                    <h6 class="section-title-pro text-primary">Basic Information</h6>
                    <span class="uid-tag-pro">UID: #<?= $user->id ?></span>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>System Unique ID (Locked)</label>
                            <input type="text" class="form-control form-control-pro" value="<?= $user->unique_id ?>" disabled>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control form-control-pro" value="<?= $user->username ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control form-control-pro" value="<?= $user->email ?>" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control form-control-pro" value="<?= $user->name ?>" placeholder="Full name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-pro" value="<?= $user->phone ?>" placeholder="Enter phone">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Birth Date</label>
                            <input type="date" name="dob" class="form-control form-control-pro" value="<?= $user->dob ?>">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label>Gender</label>
                            <select name="gender" class="form-control form-control-pro">
                                <option value="Male" <?= ($user->gender == 'Male') ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= ($user->gender == 'Female') ? 'selected' : '' ?>>Female</option>
                                <option value="Other" <?= ($user->gender == 'Other') ? 'selected' : '' ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label>Bio / About User</label>
                            <textarea name="bio" class="form-control form-control-pro" rows="2" style="height: auto;"><?= $user->bio ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--primary-blue);">Address & Location</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Street Address</label>
                            <input type="text" name="location" class="form-control form-control-pro" value="<?= $user->location ?>" placeholder="Area or Colony">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>City / District</label>
                            <input type="text" name="district" class="form-control form-control-pro" value="<?= $user->district ?>" placeholder="City">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>State</label>
                            <input type="text" name="state" class="form-control form-control-pro" value="<?= $user->state ?>" placeholder="State">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Country</label>
                            <input type="text" name="country" class="form-control form-control-pro" value="<?= $user->country ?>" placeholder="Country">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--accent-green);">Account Verification & Status</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Profile Verification</label>
                            <select name="is_verified" class="form-control form-control-pro">
                                <option value="1" <?= $user->is_verified ? 'selected' : '' ?>>Verified (Blue Tick)</option>
                                <option value="0" <?= !$user->is_verified ? 'selected' : '' ?>>Not Verified</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Email Verification</label>
                            <select name="email_verified" class="form-control form-control-pro">
                                <option value="1" <?= $user->email_verified ? 'selected' : '' ?>>Verified</option>
                                <option value="0" <?= !$user->email_verified ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Account Access</label>
                            <select name="is_banned" class="form-control form-control-pro">
                                <option value="0" <?= !$user->is_banned ? 'selected' : '' ?>>Active / Normal</option>
                                <option value="1" <?= $user->is_banned ? 'selected' : '' ?>>Banned / Blocked</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>KYC Verification Status</label>
                            <select name="kyc_status" class="form-control form-control-pro">
                                <option value="NOT_SUBMITTED" <?= ($user->kyc_status == 'NOT_SUBMITTED') ? 'selected' : '' ?>>Not Submitted</option>
                                <option value="PENDING" <?= ($user->kyc_status == 'PENDING') ? 'selected' : '' ?>>Pending Approval</option>
                                <option value="APPROVED" <?= ($user->kyc_status == 'APPROVED') ? 'selected' : '' ?>>Approved</option>
                                <option value="REJECTED" <?= ($user->kyc_status == 'REJECTED') ? 'selected' : '' ?>>Rejected</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--accent-orange);">App Features & Privacy</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">CREATOR STATUS</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_creator" value="1" class="custom-control-input" id="creStat" <?= $user->is_creator ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="creStat"></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">PAYOUT ENABLED</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_payout_setup" value="1" class="custom-control-input" id="payStat" <?= $user->is_payout_setup ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="payStat"></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="switch-panel-pro d-flex align-items-center justify-content-between">
                                <span class="small text-dark font-weight-bold">PRIVATE PROFILE</span>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" name="is_private" value="1" class="custom-control-input" id="privStat" <?= $user->is_private ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="privStat"></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Comments Allowed From</label>
                            <select name="allow_comments" class="form-control form-control-pro">
                                <option value="everyone" <?= ($user->allow_comments == 'everyone') ? 'selected' : '' ?>>Everyone</option>
                                <option value="followers" <?= ($user->allow_comments == 'followers') ? 'selected' : '' ?>>Followers Only</option>
                                <option value="following" <?= ($user->allow_comments == 'following') ? 'selected' : '' ?>>Following Only</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Direct Messages (DM)</label>
                            <select name="allow_dm_requests" class="form-control form-control-pro">
                                <option value="1" <?= $user->allow_dm_requests ? 'selected' : '' ?>>Open to All</option>
                                <option value="0" <?= !$user->allow_dm_requests ? 'selected' : '' ?>>Restricted</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Video Upload Permission</label>
                            <select name="allow_video_uploads" class="form-control form-control-pro">
                                <option value="1" <?= $user->allow_video_uploads ? 'selected' : '' ?>>Allowed</option>
                                <option value="0" <?= !$user->allow_video_uploads ? 'selected' : '' ?>>Restricted</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Preferred Currency</label>
                            <input type="text" name="preferred_currency" class="form-control form-control-pro" value="<?= $user->preferred_currency ?>" placeholder="e.g. INR">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6 class="section-title-pro" style="color: var(--accent-red);">Staff & Security Controls</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Assign Staff Role</label>
                            <select name="role_id" class="form-control form-control-pro" <?= (session()->get('id') == $user->id) ? 'disabled' : '' ?>>
                                <option value="0">Regular User (No Admin Access)</option>
                                <?php foreach($roles as $role): ?>
                                    <option value="<?= $role->id ?>" <?= ($user->role_id == $role->id) ? 'selected' : '' ?>><?= strtoupper($role->role_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(session()->get('id') == $user->id): ?>
                                <small class="text-danger">Note: You cannot change your own staff permissions.</small>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label>Update Password</label>
                            <input type="password" name="password" class="form-control form-control-pro" placeholder="Enter new password">
                            <small class="text-muted">Keep this blank if you don't want to change the password.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4 mb-5 pb-5">
                <div class="col-12">
                    <button type="submit" class="btn btn-lg btn-block py-3 font-weight-bold shadow-sm" style="background: var(--primary-blue); color: #fff; border-radius: 12px; border: none; font-size: 16px;">
                        <i class="fas fa-save mr-2"></i> SAVE ALL CHANGES
                    </button>
                    <p class="text-center mt-3 text-muted small">
                        <i class="fas fa-shield-alt mr-1"></i> Changes will be saved and recorded in the system logs.
                    </p>
                </div>
            </div>

        </form>
    </div>
</section>

<?= $this->endSection() ?>
