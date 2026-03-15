<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 20px 25px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: #000 !important; height: 50px; border-radius: 8px; font-weight: 600; 
    }
    .form-control-pro:focus { border-color: var(--primary-blue) !important; box-shadow: var(--card-shadow); }
    
    .label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; }
    .black-text { color: #000 !important; font-weight: 700; }
    
    .input-addon-pro { background: #f4f7fa; border: 1px solid var(--border-soft); color: var(--text-muted); width: 50px; justify-content: center; display: flex; align-items: center; border-radius: 8px 0 0 8px; }
    .input-group .form-control-pro { border-radius: 0 8px 8px 0; }

    /* Sticky Save Bar Pro */
    .save-bar-pro { 
        position: fixed; bottom: 0; right: 0; left: 250px; background: rgba(255, 255, 255, 0.9); 
        backdrop-filter: blur(10px); border-top: 1px solid var(--border-soft); z-index: 1000; 
        padding: 15px 40px; display: flex; align-items: center; justify-content: flex-end;
        box-shadow: 0 -5px 20px rgba(0,0,0,0.03);
    }
    .btn-save-pro { background: var(--primary-blue); color: #fff; border-radius: 10px; height: 48px; padding: 0 40px; font-weight: 800; border: none; box-shadow: 0 4px 15px rgba(93, 120, 255, 0.2); }
    
    @media (max-width: 992px) { .save-bar-pro { left: 0 !important; justify-content: center; } }
</style>

<div class="content-header pt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-6">
                <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -1px;">
                    <i class="fas fa-envelope-open-text mr-2 text-primary"></i> Email Settings
                </h1>
                <p class="text-muted small mb-0 font-weight-bold">CONFIGURE SYSTEM MAIL SERVER (SMTP)</p>
            </div>
        </div>
    </div>
</div>

<section class="content pb-5 mt-3">
    <div class="container-fluid">
        
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert shadow-sm mb-4" style="background: rgba(10, 187, 135, 0.05); border: 1px solid var(--accent-green); color: var(--accent-green); border-radius: 10px;">
                <i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/settings/smtp/update') ?>" method="post">
            <?= csrf_field() ?>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card card-pro">
                        <div class="card-header card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-server text-primary mr-2"></i> MAIL SERVER DETAILS</h6>
                        </div>
                        <div class="card-body p-4">
                            <p class="small text-muted mb-4 pb-2 border-bottom">
                                These settings are used for sending <b>Login OTPs</b> and <b>Security Alerts</b> to users.
                            </p>
                            
                            <div class="form-group row align-items-center mb-4">
                                <label class="col-sm-4 label-pro">SMTP Host</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-addon-pro"><i class="fas fa-globe"></i></div>
                                        <input type="text" name="smtp_host" class="form-control-pro" placeholder="e.g. smtp.gmail.com" value="<?= $settings['smtp_host'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-4">
                                <label class="col-sm-4 label-pro">SMTP Port</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-addon-pro"><i class="fas fa-plug"></i></div>
                                        <input type="text" name="smtp_port" class="form-control-pro" placeholder="e.g. 587 or 465" value="<?= $settings['smtp_port'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-4">
                                <label class="col-sm-4 label-pro">Server Email ID</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-addon-pro"><i class="fas fa-user"></i></div>
                                        <input type="email" name="smtp_user" class="form-control-pro" placeholder="your-email@example.com" value="<?= $settings['smtp_user'] ?? '' ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-4">
                                <label class="col-sm-4 label-pro">App Password</label>
                                <div class="col-sm-8">
                                    <div class="input-group">
                                        <div class="input-addon-pro"><i class="fas fa-key"></i></div>
                                        <input type="password" name="smtp_pass" class="form-control-pro" placeholder="••••••••••••" value="<?= $settings['smtp_pass'] ?? '' ?>">
                                    </div>
                                    <small class="text-info mt-2 d-block font-weight-bold">
                                        <i class="fas fa-info-circle mr-1"></i> Use Gmail App Passwords for higher security.
                                    </small>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-2">
                                <label class="col-sm-4 label-pro">Security Mode</label>
                                <div class="col-sm-8">
                                    <select name="smtp_crypto" class="form-control-pro">
                                        <option value="tls" <?= (isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'tls') ? 'selected' : '' ?>>TLS (Recommended)</option>
                                        <option value="ssl" <?= (isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'ssl') ? 'selected' : '' ?>>SSL (Legacy)</option>
                                        <option value="none" <?= (isset($settings['smtp_crypto']) && $settings['smtp_crypto'] == 'none') ? 'selected' : '' ?>>None (Not Secure)</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="save-bar-pro">
                <button type="submit" class="btn-save-pro shadow-sm">
                    <i class="fas fa-save mr-2"></i> UPDATE EMAIL CONFIG
                </button>
            </div>
        </form>

    </div>
</section>

<div style="height: 100px;"></div>

<?= $this->endSection() ?>
