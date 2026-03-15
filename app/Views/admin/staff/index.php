<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<style>
    /* Professional Staff Styling */
    .staff-avatar { 
        width: 45px; 
        height: 45px; 
        border-radius: 10px; 
        border: 2px solid var(--primary-blue); 
        object-fit: cover; 
    }
    .form-control-pro { 
        background: #fff !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        height: 45px; 
        border-radius: 8px; 
    }
    .form-control-pro:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1);
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-user-shield mr-2 text-primary"></i> Staff Management
        </h1>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/staff') ?>" method="get">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="small text-muted font-weight-bold">QUICK SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Name, email, or username..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted font-weight-bold">ASSIGNED ROLE</label>
                            <select name="role" class="form-control form-control-pro">
                                <option value="">View All Roles</option>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?= $r->id ?>" <?= ($_GET['role'] ?? '') == $r->id ? 'selected' : '' ?>><?= $r->role_name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted font-weight-bold">ACCOUNT STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">All Statuses</option>
                                <option value="0" <?= ($_GET['status'] ?? '') == '0' ? 'selected' : '' ?>>Active Staff Only</option>
                                <option value="1" <?= ($_GET['status'] ?? '') == '1' ? 'selected' : '' ?>>Banned Staff Only</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm" style="background: var(--primary-blue); color: white; border-radius: 8px; height: 45px; border: none;">
                                <i class="fas fa-filter mr-1"></i> APPLY MAP
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-3 px-4 border-0">Staff Member</th>
                                <th class="py-3 border-0">Role Designation</th>
                                <th class="py-3 text-center border-0">Security Status</th>
                                <th class="py-3 text-right px-4 border-0">Action Map</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($staff)): foreach($staff as $member): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($member->avatar, 'profile') ?>" 
                                             onerror="this.src='https://ui-avatars.com/api/?name=<?= $member->username ?>&background=f4f7fa&color=5d78ff';" 
                                             class="staff-avatar">
                                        <div class="ml-3">
                                            <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;"><?= $member->name ?: $member->username ?></div>
                                            <small class="text-muted">@<?= $member->username ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <span class="badge" style="border: 1px solid var(--primary-blue); color: var(--primary-blue); padding: 5px 12px; border-radius: 4px; font-weight: 600; background: rgba(93, 120, 255, 0.05);">
                                        <?= strtoupper($member->role_name ?: 'Global Admin') ?>
                                    </span>
                                </td>
                                <td class="align-middle text-center small">
                                    <div class="text-dark font-weight-500"><?= $member->email ?></div>
                                    <div class="<?= $member->is_banned ? 'text-danger' : 'text-success' ?> font-weight-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        <?= $member->is_banned ? 'BANNED' : 'ACTIVE' ?>
                                    </div>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <div class="btn-group">
                                        <a href="<?= base_url('admin/users/view/'.$member->id) ?>" class="btn btn-sm" title="View Profile" style="color: var(--primary-blue); border: 1px solid var(--primary-blue); border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px; background: transparent;">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="<?= base_url('admin/users/edit/'.$member->id) ?>" class="btn btn-sm" title="Edit Staff" style="border: 1px solid var(--accent-orange); color: var(--accent-orange); border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px; background: transparent;">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if(session()->get('id') != $member->id && $member->id != 1): ?>
                                            <button type="button" class="btn btn-sm remove-staff" 
                                                    data-id="<?= $member->id ?>" 
                                                    data-name="<?= $member->username ?>" 
                                                    title="Remove from Staff" 
                                                    style="color: var(--accent-red); border: 1px solid var(--accent-red); border-radius: 6px; width: 34px; height: 34px; background: transparent;">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted">No staff members found in this map.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.jQuery) {
        (function($) {
            $(document).on('click', '.remove-staff', function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                const name = $(this).data('name');

                Swal.fire({
                    title: 'Revoke Staff Access?',
                    text: "User @" + name + " will lose all administrative privileges.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#fd397a', // var(--accent-red)
                    cancelButtonColor: '#abb3ba',
                    confirmButtonText: 'Yes, Revoke Access',
                    background: '#fff',
                    color: '#3d4465'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "<?= base_url('admin/staff/remove_admin/') ?>/" + id;
                    }
                });
            });
        })(window.jQuery);
    }
});
</script>

<?= $this->endSection() ?>
