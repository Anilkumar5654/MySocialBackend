<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME SYNC */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
    
    .post-thumb-pro { 
        width: 50px; height: 50px; border-radius: 8px; 
        object-fit: cover; border: 1px solid var(--border-soft); 
        margin-right: 12px; background: #f8f9fa; 
    }
    
    .post-text-snippet { 
        font-size: 13px; color: var(--text-dark); font-weight: 500;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; 
        overflow: hidden; line-height: 1.4; 
    }

    /* Status Badges */
    .st-badge { font-size: 10px; padding: 5px 12px; border-radius: 4px; font-weight: 800; text-transform: uppercase; }
    .st-published { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .st-archived { background: #f4f7fa; color: #888; border: 1px solid var(--border-soft); }

    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 6px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff;
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); }

    .stat-pill-pro { 
        background: #f4f7fa; border: 1px solid var(--border-soft); 
        padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-edit mr-2 text-primary"></i> All Posts
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/posts') ?>" method="get">
                    <div class="row">
                        <div class="col-md-5 col-12 mb-2">
                            <label class="small text-muted font-weight-bold">SEARCH</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Caption, User..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <label class="small text-muted font-weight-bold">TYPE</label>
                            <select name="type" class="form-control form-control-pro">
                                <option value="">All Posts</option>
                                <option value="text" <?= ($_GET['type'] ?? '') == 'text' ? 'selected' : '' ?>>Text Only</option>
                                <option value="photo" <?= ($_GET['type'] ?? '') == 'photo' ? 'selected' : '' ?>>Photo Post</option>
                                <option value="video" <?= ($_GET['type'] ?? '') == 'video' ? 'selected' : '' ?>>Video Post</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-6 mb-2">
                            <label class="small text-muted font-weight-bold">STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">All Status</option>
                                <option value="published" <?= ($_GET['status'] ?? '') == 'published' ? 'selected' : '' ?>>Published</option>
                                <option value="archived" <?= ($_GET['status'] ?? '') == 'archived' ? 'selected' : '' ?>>Archived</option>
                            </select>
                        </div>
                        <div class="col-md-2 col-12 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm text-white" style="background: var(--primary-blue); border-radius: 8px; height: 45px; border: none;">
                                Search
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
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0" style="width: 45%;">Post Info</th>
                                <th class="py-4 border-0">User</th>
                                <th class="py-4 text-center border-0">Stats</th>
                                <th class="py-4 text-center border-0">Status</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($posts as $post): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <div class="d-flex align-items-center">
                                        
                                        <?php if($post->type == 'photo' && !empty($post->main_media)): ?>
                                            <img src="<?= get_media_url($post->main_media, 'post_image') ?>" 
                                                 class="post-thumb-pro" 
                                                 onclick="window.open(this.src, '_blank')"
                                                 style="cursor: zoom-in;"
                                                 alt="Post Image">

                                        <?php elseif($post->type == 'video'): ?>
                                            <div class="post-thumb-pro d-flex align-items-center justify-content-center">
                                                <i class="fas fa-video text-primary"></i>
                                            </div>

                                        <?php else: ?>
                                            <div class="post-thumb-pro d-flex align-items-center justify-content-center">
                                                <i class="fas fa-align-left text-muted"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div>
                                            <div class="post-text-snippet">
                                                <?= !empty($post->content) ? esc($post->content) : '<span class="text-muted font-italic">No Caption</span>' ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt mr-1"></i> <?= date('d M Y', strtotime($post->created_at)) ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div class="text-dark font-weight-bold" style="font-size: 14px;"><?= $post->full_name ?: 'User' ?></div>
                                    <small class="text-primary font-weight-bold">@<?= strtoupper($post->username) ?></small>
                                </td>
                                <td class="align-middle text-center">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="stat-pill-pro text-dark mb-1">
                                            <i class="fas fa-eye mr-1 text-primary"></i> <?= number_format($post->views_count) ?>
                                        </span>
                                        <span class="stat-pill-pro text-muted">
                                            <i class="fas fa-heart mr-1 text-danger"></i> <?= number_format($post->likes_count) ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <?php 
                                        $stClass = ($post->status == 'published') ? 'st-published' : 'st-archived';
                                    ?>
                                    <span class="st-badge <?= $stClass ?>">
                                        <?= strtoupper($post->status) ?>
                                    </span>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <div class="btn-group">
                                        <?php if(has_permission('posts.view')): ?>
                                        <a href="<?= base_url('admin/posts/view/'.$post->id) ?>" class="action-btn-pro mr-2" style="color: var(--primary-blue);" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php if(has_permission('posts.edit')): ?>
                                        <a href="<?= base_url('admin/posts/edit/'.$post->id) ?>" class="action-btn-pro mr-2" style="color: var(--accent-orange);" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>

                                        <?php if(has_permission('posts.delete')): ?>
                                        <button type="button" class="action-btn-pro delete-post-btn" data-id="<?= $post->id ?>" style="color: var(--accent-red);" title="Delete">
                                            <i class="fas fa-trash"></i>
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
/**
 * 🚀 FIXED: Native Logic for Professional SweetAlert
 */
document.addEventListener("DOMContentLoaded", function() {
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-post-btn');
        if (btn) {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            Swal.fire({
                title: 'Delete this post?',
                text: "Post content and files will be removed permanently!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#fd397a', 
                cancelButtonColor: '#abb3ba',
                confirmButtonText: 'Yes, Delete Now',
                background: '#fff',
                color: '#3d4465'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "<?= base_url('admin/posts/delete/') ?>/" + id;
                }
            });
        }
    });
});
</script>

<?= $this->endSection() ?>
