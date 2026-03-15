<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<style>
    /* Page Specific Styles for Professional Consistency */
    .form-control-pro { 
        background: #fff !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        height: 48px; 
        border-radius: 8px; 
        transition: 0.3s;
    }
    .form-control-pro:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1);
    }
    .permission-group { 
        background: #f8f9fa !important; 
        padding: 20px; 
        border-radius: 12px; 
        border: 1px solid var(--border-soft) !important; 
        margin-bottom: 20px; 
        transition: 0.3s; 
    }
    .permission-group:hover { 
        border-color: var(--primary-blue) !important; 
        background: #fff !important;
        box-shadow: var(--card-shadow);
    }
    .permission-title { 
        color: var(--primary-blue); 
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 0.75rem; 
        display: block; 
        letter-spacing: 1px; 
        margin-bottom: 15px; 
        border-bottom: 1px solid var(--border-soft); 
        padding-bottom: 8px; 
    }
    /* Custom Blue Checkbox */
    .custom-control-input:checked ~ .custom-control-label::before { 
        background-color: var(--primary-blue) !important; 
        border-color: var(--primary-blue) !important; 
    }
    label { color: var(--text-dark) !important; font-size: 13px; font-weight: 600; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-sm-6">
                <h1 style="font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px;">
                    <i class="fas fa-user-edit mr-2 text-primary"></i> Edit Role: <span class="text-primary"><?= strtoupper($role->role_name) ?></span>
                </h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= base_url('admin/roles') ?>" class="btn btn-light shadow-sm" style="border-radius: 8px; font-weight: 600; color: var(--text-dark); border: 1px solid var(--border-soft);">
                    <i class="fas fa-arrow-left mr-1"></i> BACK TO LIST
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <form action="<?= base_url('admin/roles/update/'.$role->id) ?>" method="POST">
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="small text-muted mb-2">ROLE DESIGNATION</label>
                            <input type="text" name="role_name" class="form-control form-control-pro" value="<?= $role->role_name ?>" required placeholder="e.g. Moderator">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="small text-muted mb-2">BRIEF DESCRIPTION</label>
                            <input type="text" name="description" class="form-control form-control-pro" value="<?= $role->description ?>" placeholder="Define responsibilities...">
                        </div>
                    </div>

                    <div class="d-flex align-items-center mt-5 mb-4">
                        <h5 class="m-0 font-weight-bold" style="color: var(--text-dark); font-size: 16px;">PERMISSION MAPPING</h5>
                        <div class="ml-3 flex-grow-1" style="height: 1px; background: var(--border-soft);"></div>
                    </div>

                    <div class="row">
                        <?php foreach ($permissions as $group => $perms): ?>
                        <div class="col-md-4">
                            <div class="permission-group">
                                <span class="permission-title">
                                    <i class="fas fa-shield-alt mr-2"></i><?= strtoupper($group) ?>
                                </span>
                                <?php foreach ($perms as $perm): ?>
                                <div class="custom-control custom-checkbox mb-3">
                                    <input class="custom-control-input" type="checkbox" name="permissions[]" 
                                           id="<?= $perm ?>" value="<?= $perm ?>" 
                                           <?= in_array($perm, $selected_perms) ? 'checked' : '' ?>>
                                    <label for="<?= $perm ?>" class="custom-control-label" style="cursor: pointer; font-weight: 500; font-size: 14px;">
                                        <?= ucwords(str_replace(['.', '_'], ' ', $perm)) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <button type="submit" class="btn btn-block py-3 font-weight-bold shadow-sm" style="background: var(--primary-blue); color: #fff; border-radius: 10px; font-size: 15px; letter-spacing: 0.5px; border: none; transition: 0.3s;">
                                <i class="fas fa-save mr-2"></i> UPDATE ROLE PERMISSIONS
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
