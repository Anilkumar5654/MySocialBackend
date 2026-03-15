<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME SYNC */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
    
    .video-thumb { 
        width: 100px; height: 56px; border-radius: 6px; 
        border: 1px solid var(--border-soft); object-fit: cover; 
        background: #f8f9fa; transition: 0.3s; 
    }
    
    /* Global Status Badges */
    .status-badge-pro { 
        font-size: 9px; padding: 4px 10px; border-radius: 4px; 
        font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; 
    }
    .st-published { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-blocked { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    .st-processing { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    
    .stat-pill-pro { 
        background: #f4f7fa; border: 1px solid var(--border-soft); 
        padding: 3px 10px; border-radius: 6px; font-size: 11px; 
        color: var(--text-dark); display: inline-flex; align-items: center; gap: 5px; 
        font-weight: 600;
    }
    
    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 6px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-video mr-2 text-primary"></i> All Videos
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/videos') ?>" method="get">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="small text-muted font-weight-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Title, Channel or Owner..." value="<?= esc($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted font-weight-bold">TYPE</label>
                            <select name="visibility" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="public" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'public') ? 'selected' : '' ?>>PUBLIC</option>
                                <option value="private" <?= (isset($_GET['visibility']) && $_GET['visibility'] == 'private') ? 'selected' : '' ?>>PRIVATE</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small text-muted font-weight-bold">STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="published" <?= (isset($_GET['status']) && $_GET['status'] == 'published') ? 'selected' : '' ?>>PUBLISHED</option>
                                <option value="processing" <?= (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : '' ?>>PROCESSING</option>
                                <option value="blocked" <?= (isset($_GET['status']) && $_GET['status'] == 'blocked') ? 'selected' : '' ?>>BLOCKED</option>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end mb-2">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm text-white" style="background: var(--primary-blue); border-radius: 8px; height: 45px; border: none;">
                                <i class="fas fa-filter mr-1"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 1000px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="py-4 px-4 border-0">Video Info</th>
                                <th class="py-4 border-0">Channel</th>
                                <th class="py-4 text-center border-0">Views & Likes</th>
                                <th class="py-4 text-center border-0">Status</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($videos as $video): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="position-relative">
                                            <img src="<?= get_media_url($video->thumbnail_url, 'video') ?>" class="video-thumb" onerror="this.src='https://placehold.co/100x56/f4f7fa/333?text=Video';">
                                            <span class="badge badge-dark position-absolute" style="bottom: 4px; right: 4px; font-size: 9px; opacity: 0.8;">
                                                <?= ($video->duration > 3600) ? gmdate("H:i:s", $video->duration) : gmdate("i:s", $video->duration) ?>
                                            </span>
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-dark font-weight-bold" style="font-size: 14px; line-height: 1.2;"><?= character_limiter(esc($video->title), 40) ?></div>
                                            <small class="text-muted d-block mt-1"><i class="far fa-calendar-alt mr-1"></i> <?= date('d M, Y', strtotime($video->created_at)) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-dark font-weight-bold" style="font-size: 13px;"><?= esc($video->channel_name) ?></div>
                                    <small class="text-primary font-weight-600">@<?= strtoupper(esc($video->username)) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="d-flex flex-column align-items-center gap-1">
                                        <span class="stat-pill-pro"><i class="fas fa-eye text-primary"></i> <?= number_format($video->views_count) ?></span>
                                        <span class="stat-pill-pro"><i class="fas fa-heart text-danger"></i> <?= number_format($video->likes_count) ?></span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <?php 
                                        $stClass = 'st-' . strtolower($video->status);
                                    ?>
                                    <span class="status-badge-pro <?= $stClass ?>">
                                        <?= strtoupper($video->status) ?>
                                    </span>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <div class="d-flex justify-content-end" style="gap: 8px;">
                                        <a href="<?= base_url('admin/videos/view/'.$video->id) ?>" class="action-btn-pro" style="color: var(--primary-blue);" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>

                                        <a href="<?= base_url('admin/videos/edit/'.$video->id) ?>" class="action-btn-pro" style="color: var(--accent-orange);" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <?php if(has_permission('videos.delete')): ?>
                                        <button type="button" class="action-btn-pro delete-video-btn" 
                                                data-url="<?= base_url('admin/videos/delete/'.$video->id) ?>" 
                                                data-name="<?= esc($video->title) ?>"
                                                style="color: var(--accent-red);" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Fixed Delete Confirmation Logic
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-video-btn');
    if (btn) {
        e.preventDefault();
        const url = btn.getAttribute('data-url');
        const title = btn.getAttribute('data-name');

        if(!url) {
            console.error("Delete URL missing!");
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: "You are about to delete '" + title + "'. This action cannot be undone.",
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
                    showConfirmButton: false, // Ensures no extra buttons appear
                    didOpen: () => { Swal.showLoading(); } 
                });
                
                // Forceful redirect inside setTimeout to prevent browser halt
                setTimeout(() => {
                    window.location.href = url;
                }, 300);
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
