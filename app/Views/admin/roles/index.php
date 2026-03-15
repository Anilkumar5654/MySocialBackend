<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-3 align-items-center">
            <div class="col-sm-6">
                <h1 style="font-weight: 700; color: var(--text-dark); letter-spacing: -0.5px;">
                    <i class="fas fa-user-tag mr-2 text-primary"></i> Role Management
                </h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="<?= base_url('admin/roles/create') ?>" class="btn btn-primary" style="background: var(--primary-blue); border: none; border-radius: 8px; font-weight: 600; padding: 10px 20px; box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2);">
                    <i class="fas fa-plus-circle mr-1"></i> ADD NEW ROLE
                </a>
            </div>
        </div>
    </div>
</div>

<div class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 1px solid var(--border-soft);">
                                <th style="width: 100px; color: var(--text-dark); font-size: 11px; text-transform: uppercase;" class="py-3 px-4">Role ID</th>
                                <th style="color: var(--text-dark); font-size: 11px; text-transform: uppercase;" class="py-3">Role Designation</th>
                                <th style="color: var(--text-dark); font-size: 11px; text-transform: uppercase;" class="py-3">Description</th>
                                <th style="width: 150px; color: var(--text-dark); font-size: 11px; text-transform: uppercase;" class="text-right py-3 px-4">Action Map</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($roles)): foreach ($roles as $role): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft); transition: 0.3s;">
                                <td class="align-middle px-4 text-muted font-weight-bold">#<?= $role->id ?></td>
                                <td class="align-middle">
                                    <span class="badge" style="border: 1px solid var(--primary-blue); color: var(--primary-blue); padding: 5px 12px; border-radius: 4px; font-weight: 600; font-size: 11px; background: rgba(93, 120, 255, 0.05);">
                                        <?= strtoupper($role->role_name) ?>
                                    </span>
                                </td>
                                <td class="align-middle text-muted small"><?= $role->description ?: 'No additional details provided.' ?></td>
                                <td class="align-middle text-right px-4">
                                    <div class="btn-group">
                                        <a href="<?= base_url('admin/roles/edit/'.$role->id) ?>" class="btn btn-sm" title="Edit Role" style="color: var(--accent-orange); border: 1px solid var(--accent-orange); margin-right: 8px; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: transparent;">
                                            <i class="fas fa-edit" style="font-size: 12px;"></i>
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-sm delete-record" 
                                                data-url="<?= base_url('admin/roles/delete/'.$role->id) ?>" 
                                                data-name="<?= $role->role_name ?>"
                                                title="Delete Role"
                                                style="color: var(--accent-red); border: 1px solid var(--accent-red); border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; background: transparent;">
                                            <i class="fas fa-trash" style="font-size: 12px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open fa-2x mb-3 d-block opacity-2"></i>
                                    No administrative roles found in the current map.
                                </td>
                            </tr>
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
        $('.delete-record').on('click', function(e) {
            e.preventDefault();
            
            const url = $(this).data('url');
            const name = $(this).data('name');

            // SweetAlert Colors updated to Blue Theme
            Swal.fire({
                title: 'Delete Role?',
                text: "Are you sure you want to delete '" + name + "'?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#5d78ff', // var(--primary-blue)
                cancelButtonColor: '#abb3ba',
                confirmButtonText: 'Yes, Delete!',
                background: '#fff',
                color: '#3d4465'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });
    }
});
</script>

<?= $this->endSection() ?>
