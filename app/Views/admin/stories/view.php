<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME: STORY DETAIL UI */
    .story-card-pro {
        background: #fff; 
        border: 1px solid var(--border-soft); 
        border-radius: 12px; 
        overflow: hidden; 
        position: relative; 
        margin-bottom: 25px;
        box-shadow: var(--card-shadow);
        transition: 0.3s;
    }
    .story-card-pro:hover { transform: translateY(-5px); }
    
    .media-box-pro {
        position: relative;
        width: 100%;
        aspect-ratio: 9/16;
        background: #f4f7fa;
    }

    .story-img-pro { width: 100%; height: 100%; object-fit: cover; }

    .video-label {
        position: absolute; top: 10px; right: 10px; color: #fff; 
        background: rgba(0,0,0,0.5); padding: 4px 8px; border-radius: 4px; 
        font-size: 10px; font-weight: 700;
    }
    
    .caption-pro {
        padding: 12px 10px 5px 10px;
        font-size: 12px;
        color: var(--text-dark);
        font-weight: 500;
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
        background: #fff;
    }

    .footer-pro {
        padding: 10px; 
        background: #fff; 
        display: flex; justify-content: space-between; align-items: center;
        border-top: 1px solid var(--border-soft);
    }
    
    .view-pill { 
        font-size: 11px; color: var(--text-muted); font-weight: 700; 
        display: flex; align-items: center; gap: 4px; 
    }
    .view-pill i { color: var(--primary-blue); }

    /* Simple Filter Style */
    .filter-pro {
        background: #fff; border: 1px solid var(--border-soft); color: var(--text-dark); 
        padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; outline: none;
    }
</style>

<div class="content-header pb-2">
    <div class="container-fluid">
        <div class="row align-items-center">
            
            <div class="col-md-6 d-flex align-items-center mb-2 mb-md-0">
                <a href="<?= base_url('admin/stories') ?>" class="btn btn-light btn-sm mr-3 shadow-sm" style="border-radius: 50%; width: 35px; height: 35px; display:flex; align-items:center; justify-content:center; border: 1px solid var(--border-soft);">
                    <i class="fas fa-arrow-left text-primary"></i>
                </a>
                <img src="<?= get_media_url($user->avatar, 'profile') ?>" style="width: 45px; height: 45px; border-radius: 10px; border: 1px solid var(--border-soft); margin-right: 15px; object-fit: cover;">
                <div>
                    <h5 style="color: #000; font-weight: 700; margin-bottom: 0;"><?= $user->name ?: $user->username ?></h5>
                    <small class="text-muted">Viewing: <span class="text-primary font-weight-bold"><?= ucfirst($status) ?> Stories</span> (<?= count($stories) ?>)</small>
                </div>
            </div>

            <div class="col-md-6 d-flex justify-content-md-end align-items-center">
                
                <form action="" method="get" class="mr-3">
                    <select name="status" class="filter-pro shadow-sm" onchange="this.form.submit()">
                        <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active Stories</option>
                        <option value="expired" <?= $status == 'expired' ? 'selected' : '' ?>>Story History</option>
                    </select>
                </form>

                <?php if(has_permission('stories.delete')): ?>
                    <a href="<?= base_url('admin/stories/delete_all/'.$user->id) ?>" onclick="return confirm('Confirm: Delete ALL stories for this user?')" class="btn btn-sm btn-danger px-3 shadow-sm" style="border-radius: 8px; font-weight: 700; border: none;">
                        <i class="fas fa-trash-alt mr-1"></i> Clear All
                    </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<section class="content mt-3">
    <div class="container-fluid">
        <div class="row">
            <?php foreach($stories as $story): ?>
                <?php 
                    $mediaUrl = get_media_url($story->media_url, 'story'); 
                    $captionText = $story->content ?? $story->caption ?? '';
                ?>
                
                <div class="col-6 col-sm-4 col-md-3 col-lg-2">
                    <div class="story-card-pro">
                        
                        <div class="media-box-pro">
                            <?php if($story->media_type == 'image'): ?>
                                <img src="<?= $mediaUrl ?>" class="story-img-pro">
                            <?php else: ?>
                                <video src="<?= $mediaUrl ?>#t=1" class="story-img-pro"></video>
                                <div class="video-label"><i class="fas fa-play"></i></div>
                            <?php endif; ?>
                            
                            <div style="position: absolute; bottom: 0; left: 0; width: 100%; background: linear-gradient(to top, rgba(0,0,0,0.7), transparent); padding: 20px 10px 5px; color: #fff; font-size: 10px; text-align: right; font-weight: 600;">
                                <?= time_ago($story->created_at) ?>
                            </div>
                        </div>

                        <div class="caption-pro" title="<?= esc($captionText) ?>">
                            <?= !empty($captionText) ? esc($captionText) : '<span class="text-muted font-italic">No Caption</span>' ?>
                        </div>

                        <div class="footer-pro">
                            <div class="view-pill">
                                <i class="fas fa-eye"></i> <?= number_format($story->view_count ?? 0) ?>
                            </div>

                            <?php if(has_permission('stories.delete')): ?>
                                <a href="<?= base_url('admin/stories/delete/'.$story->id) ?>" onclick="return confirm('Delete this story?')" class="text-danger" style="font-size: 14px;">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if(empty($stories)): ?>
                <div class="col-12 text-center py-5">
                    <div style="font-size: 40px; color: #dee2e6; margin-bottom: 15px;"><i class="fas fa-folder-open"></i></div>
                    <p class="text-muted font-weight-bold">No <?= $status ?> stories found for this user.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
