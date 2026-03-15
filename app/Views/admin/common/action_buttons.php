<?php 
/**
 * 🚀 UNIVERSAL ACTION CENTER - PRO BLUE EDITION (FIXED LOGIC)
 */
?>

<style>
    .action-center-wrapper { display: inline-flex; gap: 6px; background: transparent; padding: 2px; }

    .btn-action-pro {
        width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;
        border-radius: 6px; font-size: 12px; transition: all 0.2s ease-in-out;
        border: 1px solid var(--border-soft); background: #fff; cursor: pointer;
        text-decoration: none !important;
    }

    .btn-view-pro { color: var(--primary-blue); border-color: var(--primary-blue); }
    .btn-view-pro:hover { background: var(--primary-blue); color: #fff; box-shadow: 0 4px 10px rgba(93, 120, 255, 0.2); }

    .btn-edit-pro { color: var(--accent-orange); border-color: var(--accent-orange); }
    .btn-edit-pro:hover { background: var(--accent-orange); color: #fff; box-shadow: 0 4px 10px rgba(249, 155, 45, 0.2); }

    .btn-ban-pro { color: #6c757d; border-color: #6c757d; }
    .btn-ban-pro:hover { background: #6c757d; color: #fff; box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2); }
    
    .btn-unban-pro { color: var(--accent-green); border-color: var(--accent-green); }
    .btn-unban-pro:hover { background: var(--accent-green); color: #fff; box-shadow: 0 4px 10px rgba(10, 187, 135, 0.2); }

    .btn-delete-pro { color: var(--accent-red); border-color: var(--accent-red); }
    .btn-delete-pro:hover { background: var(--accent-red); color: #fff; box-shadow: 0 4px 10px rgba(253, 57, 122, 0.2); }
</style>

<div class="action-center-wrapper">
    <?php if (has_permission($type . '.view')): ?>
        <a href="<?= base_url("admin/{$type}/view/{$id}") ?>" class="btn-action-pro btn-view-pro" title="View Details">
            <i class="fas fa-eye"></i>
        </a>
    <?php endif; ?>

    <?php if (has_permission($type . '.edit')): ?>
        <a href="<?= base_url("admin/{$type}/edit/{$id}") ?>" class="btn-action-pro btn-edit-pro" title="Edit Record">
            <i class="fas fa-edit"></i>
        </a>
    <?php endif; ?>

    <?php if (($type === 'users' || $type === 'channels') && has_permission($type . '.ban')): ?>
        <?php 
            $isBanned = isset($row->is_banned) ? $row->is_banned : 0; 
            $proClass = $isBanned ? 'btn-unban-pro' : 'btn-ban-pro';
            $icon = $isBanned ? 'fa-user-check' : 'fa-user-slash';
            $title = $isBanned ? 'Restore Access' : 'Restrict Access';
        ?>
        <a href="<?= base_url("admin/{$type}/toggle_ban/{$id}") ?>" class="btn-action-pro <?= $proClass ?>" title="<?= $title ?>">
            <i class="fas <?= $icon ?>"></i>
        </a>
    <?php endif; ?>

    <?php if (has_permission($type . '.delete')): ?>
        <?php 
            $displayTitle = '';
            if ($type === 'videos') { $displayTitle = $row->title ?? 'Video #'.$id; }
            elseif ($type === 'reels') { $displayTitle = $row->caption ?? 'Reel #'.$id; }
            elseif ($type === 'users' || $type === 'channels') { $displayTitle = $row->username ?? $row->name ?? 'User #'.$id; }
            else { $displayTitle = 'Record #'.$id; }
        ?>
        <button type="button" 
                class="btn-action-pro btn-delete-pro delete-universal-btn" 
                data-url="<?= base_url("admin/{$type}/delete/{$id}") ?>" 
                data-name="<?= esc($displayTitle) ?>" 
                title="Delete">
            <i class="fas fa-trash-alt"></i>
        </button>
    <?php endif; ?>
</div>

<script>
/**
 * 🔥 FIXED LOGIC: Native JavaScript Event Listener with Timeout
 * window.delCenterInit ensure karta hai ki script har row ke liye repeat na ho
 */
if (typeof window.delCenterInit === 'undefined') {
    window.delCenterInit = true;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-universal-btn');
        if (btn) {
            e.preventDefault();
            const url = btn.getAttribute('data-url');
            const name = btn.getAttribute('data-name');

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You are about to delete '" + name + "'. This cannot be undone.",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#fd397a', 
                    cancelButtonColor: '#abb3ba',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel',
                    background: '#fff',
                    color: '#3d4465'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: 'Deleting...',
                            text: 'Please wait',
                            allowOutsideClick: false,
                            showConfirmButton: false, // Extra buttons hata diye
                            didOpen: () => { Swal.showLoading(); }
                        });
                        
                        // 🚀 Timeout lagana zaruri hai browser redirect block se bachne ke liye
                        setTimeout(() => {
                            window.location.href = url;
                        }, 300);
                    }
                });
            } else {
                if (confirm('Are you sure you want to delete ' + name + '?')) {
                    window.location.href = url;
                }
            }
        }
    });
}
</script>
