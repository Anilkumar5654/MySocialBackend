<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PROFESSIONAL FORM UI */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 48px; border-radius: 8px; transition: 0.3s; 
    }
    .form-control-pro:focus { 
        border-color: var(--primary-blue) !important; 
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1) !important; 
    }
    
    .card-pro { 
        background: #fff; border: none; border-radius: 12px; 
        margin-bottom: 25px; overflow: hidden; box-shadow: var(--card-shadow); 
    }
    .card-header-pro { 
        background: #f8f9fa; border-bottom: 1px solid var(--border-soft); 
        padding: 15px 20px; font-size: 11px; font-weight: 700; 
        text-transform: uppercase; color: var(--text-dark); letter-spacing: 0.5px;
    }
    
    label { font-weight: 700 !important; font-size: 11px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; display: block; }
    
    .btn-save-pro { 
        background: var(--primary-blue); color: #fff; font-weight: 700; 
        border-radius: 10px; padding: 15px; text-transform: uppercase; 
        border: none; width: 100%; box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2); transition: 0.3s; 
    }
    .btn-save-pro:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(93, 120, 255, 0.3); color: #fff; }
</style>

<div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-edit mr-2 text-primary"></i> Edit Video
        </h1>
        <a href="<?= base_url('admin/videos/view/'.$video->id) ?>" class="btn btn-light btn-sm shadow-sm" style="border-radius: 8px; font-weight: 600; border: 1px solid var(--border-soft);">
            <i class="fas fa-times mr-1"></i> CANCEL
        </a>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <form action="<?= base_url('admin/videos/update/'.$video->id) ?>" method="post">
            <?= csrf_field() ?>
            <div class="row">
                <div class="col-lg-8">
                    <div class="card-pro">
                        <div class="card-header-pro"><i class="fas fa-info-circle mr-2 text-primary"></i> Video Details</div>
                        <div class="card-body p-4">
                            <div class="form-group mb-4">
                                <label>Title</label>
                                <input type="text" name="title" class="form-control form-control-pro" value="<?= esc($video->title) ?>" required>
                            </div>
                            <div class="form-group mb-4">
                                <label>Description</label>
                                <textarea name="description" class="form-control form-control-pro" rows="8" style="height: auto !important;" placeholder="Write video description here..."><?= esc($video->description) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Tags (Comma separated)</label>
                                <input type="text" name="tags" class="form-control form-control-pro" value="<?= esc($video->tags) ?>" placeholder="e.g. music, vlog, tech">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card-pro">
                        <div class="card-header-pro"><i class="fas fa-cog mr-2 text-info"></i> Video Settings</div>
                        <div class="card-body p-4">
                            
                            <div class="form-group mb-3">
                                <label>Category</label>
                                <select name="category" class="form-control form-control-pro">
                                    <option value="Entertainment" <?= $video->category == 'Entertainment' ? 'selected' : '' ?>>Entertainment</option>
                                    <option value="Music" <?= $video->category == 'Music' ? 'selected' : '' ?>>Music</option>
                                    <option value="Gaming" <?= $video->category == 'Gaming' ? 'selected' : '' ?>>Gaming</option>
                                    <option value="Education" <?= $video->category == 'Education' ? 'selected' : '' ?>>Education</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Visibility</label>
                                <select name="visibility" class="form-control form-control-pro">
                                    <option value="public" <?= $video->visibility == 'public' ? 'selected' : '' ?>>Public</option>
                                    <option value="private" <?= $video->visibility == 'private' ? 'selected' : '' ?>>Private</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label>Status</label>
                                <select name="status" class="form-control form-control-pro" style="border-color: var(--primary-blue) !important;">
                                    <option value="published" <?= $video->status == 'published' ? 'selected' : '' ?>>Published</option>
                                    <option value="blocked" <?= $video->status == 'blocked' ? 'selected' : '' ?>>Blocked</option>
                                    <option value="processing" <?= $video->status == 'processing' ? 'selected' : '' ?>>Processing</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label>Monetization</label>
                                <select name="monetization_enabled" class="form-control form-control-pro">
                                    <option value="1" <?= $video->monetization_enabled == 1 ? 'selected' : '' ?>>Enabled (Ads On)</option>
                                    <option value="0" <?= $video->monetization_enabled == 0 ? 'selected' : '' ?>>Disabled (Ads Off)</option>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-save-pro">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?= $this->endSection() ?>
