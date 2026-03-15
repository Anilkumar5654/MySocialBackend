<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  $(document).ready(function() {
    // 1. Sidebar Toggle Fix
    if (typeof $.fn.PushMenu !== 'undefined') {
        $('[data-widget="pushmenu"]').PushMenu();
    }

    // 2. Global SweetAlert for Success Messages (English)
    <?php if(session()->getFlashdata('success')): ?>
        Swal.fire({
            icon: 'success',
            title: 'Operation Successful',
            text: '<?= session()->getFlashdata('success') ?>',
            background: '#111',
            color: '#fff',
            confirmButtonColor: '#ff007f'
        });
    <?php endif; ?>

    // 3. Global SweetAlert for Error Messages (English)
    <?php if(session()->getFlashdata('error')): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error Occurred',
            text: '<?= session()->getFlashdata('error') ?>',
            background: '#111',
            color: '#fff',
            confirmButtonColor: '#ff007f'
        });
    <?php endif; ?>

    // 4. REMOVE FROM ADMIN LOGIC (English)
    $(document).on('click', '.remove-staff', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to remove @" + name + " from the administrative staff list.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff007f', 
            cancelButtonColor: '#333',
            confirmButtonText: 'Yes, remove admin!',
            cancelButtonText: 'Cancel',
            background: '#111',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= base_url('admin/staff/remove_admin/') ?>/" + id;
            }
        });
    });

    // 5. DELETE USER/ROLE LOGIC (English)
    $(document).on('click', '.delete-user', function (e) {
        e.preventDefault();
        const id = $(this).data('id');
        const name = $(this).data('name');

        Swal.fire({
            title: 'Confirm Deletion?',
            text: "Warning: All data associated with '" + name + "' will be permanently deleted.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ff007f', 
            cancelButtonColor: '#333',
            confirmButtonText: 'Yes, delete permanently!',
            cancelButtonText: 'No, keep it',
            background: '#111',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = "<?= base_url('admin/users/delete/') ?>/" + id;
            }
        });
    });
  });
</script>
