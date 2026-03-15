<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PROFESSIONAL FORM UI */
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
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1) !important; 
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
        font-size: 11px; 
        font-weight: 700; 
        text-transform: uppercase; 
        color: var(--text-dark); 
        letter-spacing: 0.5px;
    }
    
    label { 
        font-weight: 700 !important; 
        font-size: 11px; 
        color: var(--text-muted); 
        text-transform: uppercase; 
        margin-bottom: 8px; 
        display: block; 
    }
    
    .media-preview-box-pro { 
        width: 100%; 
        max-width: 320px; 
        border: 1px solid var(--border-soft); 
        border-radius: 10px; 
        overflow: hidden; 
        margin-bottom: 15px; 
        background: #f8f9fa; 
        box-shadow: var(--card-shadow);
    }
    
    .btn-save-pro { 
        background: var(--primary-blue); 
        color: #fff; 
        font-weight: 700; 
        border-radius: 10px; 
        padding: 15px; 
        text-transform: uppercase; 
        border: none; 
        width: 100%; 
        box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2); 
        transition: 0.3s; 
    }
    .btn-save-pro:hover { 
        transform: translateY(-1px); 
        box-shadow: 0 6px 15px rgba(93, 120, 255, 0.3); 
        color: #fff; 
    }
</style>

<?php 
    $media_url = '';
    if ($post->type == 'photo' || $post->type == 'video') {
        $mediaType = ($post->type == 'video') ? 'post_video' : 'post_image';
        $media_url = get_media_url($post->content, $mediaType);
    }
?>

<div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-edit mr-2 text-primary"></i> Edit Post
        </h1>
        <a href="<?= base_url('admin/posts/view/'.$post->id) ?>" class="btn btn-light btn-sm shadow-sm" style="border-radius: 8px; font-weight: 600; border: 1px solid var(--border-soft);">
            <i class="fas fa-arrow-left mr-1"></i> BACK
        </a>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="<?= base_url('admin/posts/update/'.$post->id) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card-pro">
                        <div class="card-header-pro"><i class="fas fa-align-left mr-2 text-primary"></i> Post Content</div>
                        <div class="card-body p-4">
                            
                            <?php if($post->type == 'text'): ?>
                                <div class="form-group">
                                    <label>Text Content</label>
                                    <textarea name="content" class="form-control form-control-pro" rows="8" style="height: auto !important;" placeholder="Edit post text..."><?= esc($post->content) ?></textarea>
                                </div>
                            
                            <?php else: ?>
                                <div class="form-group">
                                    <label>Media File (<?= ucfirst($post->type) ?>)</label>
                                    
                                    <div class="media-preview-box-pro">
                                        <?php if($post->type == 'photo'): ?>
                                            <img src="<?= $media_url ?>" style="width: 100%; height: auto; display: block;" onerror="this.src='https://placehold.co/300x200/f4f7fa/333?text=Image+Not+Found';">
                                        <?php elseif($post->type == 'video'): ?>
                                            <video src="<?= $media_url ?>" controls style="width: 100%; height: auto; display: block; background: #000;"></video>
                                        <?php endif; ?>
                                    </div>

                                    <label class="mt-3">Storage Path (Read Only)</label>
                                    <input type="text" class="form-control form-control-pro" value="<?= esc($post->content) ?>" disabled style="background: #f8f9fa !important; font-size: 13px;">
                                    
                                    <input type="hidden" name="content" value="<?= esc($post->content) ?>">
                                    
                                    <div class="mt-2 text-warning small font-weight-bold">
                                        <i class="fas fa-info-circle mr-1"></i> File paths cannot be changed once uploaded.
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-pro">
                        <div class="card-header-pro"><i class="fas fa-cog mr-2 text-info"></i> Settings</div>
                        <div class="card-body p-4">
                            <div class="form-group mb-3">
                                <label>Content Type</label>
                                <input type="text" class="form-control form-control-pro" value="<?= strtoupper($post->type) ?>" disabled style="background: #f8f9fa !important; font-weight: 700;">
                            </div>

                            <div class="form-group mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control form-control-pro">
                                    <option value="published" <?= $post->status == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="archived" <?= $post->status == 'archived' ? 'selected' : '' ?>>Archived</option>
                                    <option value="draft" <?= $post->status == 'draft' ? 'selected' : '' ?>>Draft</option>
                                </select>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label>Visibility</label>
                                <select name="feed_scope" class="form-control form-control-pro">
                                    <option value="public" <?= $post->feed_scope == 'public' ? 'selected' : '' ?>>Public (Global)</option>
                                    <option value="followers" <?= $post->feed_scope == 'followers' ? 'selected' : '' ?>>Followers Only</option>
                                </select>
                            </div>

                            <button type="submit" class="btn btn-save-pro">
                                <i class="fas fa-save mr-2"></i> UPDATE POST
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?= $this->endSection() ?>
