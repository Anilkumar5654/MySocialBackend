<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME SYNC */
    .card-pro { 
        background: #fff; border: none; border-radius: 12px; 
        margin-bottom: 25px; overflow: hidden; box-shadow: var(--card-shadow); 
    }
    .card-header-pro { 
        background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid var(--border-soft); 
        font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-dark); 
    }
    
    /* Post Content Styling */
    .post-view-box { 
        max-width: 650px; margin: 0 auto; background: #fff; 
        border-radius: 12px; border: 1px solid var(--border-soft); 
        padding: 20px; box-shadow: var(--card-shadow); 
    }
    .post-media-main { 
        width: 100%; border-radius: 8px; margin-bottom: 15px; 
        border: 1px solid var(--border-soft); background: #f8f9fa; 
    }
    .post-text-main { 
        color: var(--text-dark); font-size: 15px; line-height: 1.6; 
        white-space: pre-wrap; font-weight: 500; 
    }

    /* Simple Stat Grid */
    .stats-grid-pro { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px; } 
    .stat-card-pro { 
        background: #fff; border: none; padding: 15px; border-radius: 12px; 
        text-align: center; box-shadow: var(--card-shadow); 
    }
    .stat-num-pro { font-size: 22px; font-weight: 700; color: var(--text-dark); display: block; line-height: 1; margin-bottom: 5px; }
    .stat-label-pro { font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }
    
    /* Comments Pro */
    .comment-item-pro { border-bottom: 1px solid var(--border-soft); padding: 15px; display: flex; align-items: flex-start; justify-content: space-between; transition: 0.2s; }
    .comment-item-pro:hover { background: #fcfcfc; }
    .comment-avatar-pro { width: 38px; height: 38px; border-radius: 8px; margin-right: 12px; object-fit: cover; border: 1px solid var(--border-soft); }
    .comment-user-pro { font-weight: 700; color: #000; font-size: 13px; }

    /* Performance Bars */
    .progress-track-pro { background: #eee; height: 8px; border-radius: 10px; overflow: hidden; margin-top: 8px; }
    .progress-fill-pro { height: 100%; border-radius: 10px; transition: 1s ease-in-out; }

    .btn-edit-pro { 
        background: var(--primary-blue); color: #fff; font-weight: 700; 
        padding: 15px; border-radius: 10px; display: block; text-align: center; 
        text-transform: uppercase; font-size: 13px; transition: 0.3s; border: none; 
        box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2);
    }
    .btn-edit-pro:hover { transform: translateY(-1px); color: #fff; box-shadow: 0 6px 15px rgba(93, 120, 255, 0.3); }
</style>

<?php 
    // Logic as per your provided code
    $mediaType = ($post->type == 'video') ? 'post_video' : 'post_image';
    $media_url = get_media_url($post->main_media, $mediaType);
    $user_img  = get_media_url($post->user_avatar, 'profile'); 
?>

<div class="content-header pb-2">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-file-alt mr-2 text-primary"></i> Post Details
        </h1>
        <a href="<?= base_url('admin/posts') ?>" class="btn btn-light btn-sm shadow-sm" style="border-radius: 8px; font-weight: 600; border: 1px solid var(--border-soft);">
            <i class="fas fa-arrow-left mr-1"></i> BACK
        </a>
    </div>
</div>

<section class="content">
    
    <div class="mb-5">
        <div class="container-fluid">
            <div class="post-view-box">
                <div class="d-flex align-items-center mb-4">
                    <img src="<?= $user_img ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= $post->username ?>&background=f4f7fa&color=5d78ff';" style="width: 45px; height: 45px; border-radius: 10px; margin-right: 12px; object-fit:cover; border: 1px solid var(--border-soft);">
                    <div>
                        <div style="font-weight: 700; color: #000; font-size: 15px;"><?= $post->full_name ?: $post->username ?></div>
                        <div class="text-muted small"><?= date('d M Y, h:i A', strtotime($post->created_at)) ?></div>
                    </div>
                </div>

                <?php if($post->type == 'photo'): ?>
                    <img src="<?= $media_url ?>" class="post-media-main" oncontextmenu="return false;">
                <?php elseif($post->type == 'video'): ?>
                    <video src="<?= $media_url ?>" class="post-media-main" controls controlsList="nodownload" style="background:#000;"></video>
                <?php endif; ?>

                <?php if(!empty($post->content)): ?>
                     <div class="post-text-main mt-3"><?= esc($post->content) ?></div>
                <?php endif; ?>
                
                <div class="mt-4 pt-3 border-top d-flex justify-content-between text-muted small font-weight-bold">
                    <span><i class="fas fa-heart mr-1 text-danger"></i> <?= number_format($post->likes_count) ?></span>
                    <span><i class="fas fa-comment mr-1 text-primary"></i> <?= number_format($post->comments_count) ?></span>
                    <span><i class="fas fa-share mr-1 text-success"></i> <?= number_format($post->shares_count) ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="stats-grid-pro">
            <div class="stat-card-pro">
                <span class="stat-num-pro" style="color: var(--primary-blue);"><?= format_number_k($post->views_count) ?></span>
                <span class="stat-label-pro">Total Views</span>
            </div>
            <div class="stat-card-pro">
                <span class="stat-num-pro" style="color: var(--accent-red);"><?= format_number_k($post->likes_count) ?></span>
                <span class="stat-label-pro">Total Likes</span>
            </div>
            <div class="stat-card-pro">
                <span class="stat-num-pro" style="color: var(--accent-green);"><?= $stats['viral_score'] ?></span>
                <span class="stat-label-pro">Viral Score</span>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                 <div class="card-pro">
                    <div class="card-header-pro"><i class="fas fa-chart-line mr-2 text-primary"></i> Engagement Stats</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-dark small font-weight-bold">Viral Reach</span>
                                    <span class="text-success small font-weight-bold"><?= $stats['viral_percent'] ?>%</span>
                                </div>
                                <div class="progress-track-pro">
                                    <div class="progress-fill-pro" style="background: var(--accent-green); width: <?= $stats['viral_percent'] ?>%"></div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-4">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="text-dark small font-weight-bold">Interaction</span>
                                    <span class="text-primary small font-weight-bold"><?= $stats['engagement_rate'] ?>%</span>
                                </div>
                                <div class="progress-track-pro">
                                    <div class="progress-fill-pro" style="background: var(--primary-blue); width: <?= min(100, $stats['engagement_rate'] * 2) ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-pro">
                    <div class="card-header-pro d-flex justify-content-between align-items-center">
                        <span>Recent Comments</span>
                        <span class="badge" style="background: #f4f7fa; color: var(--text-dark); border: 1px solid var(--border-soft);"><?= count($comments) ?></span>
                    </div>
                    <div class="card-body p-0">
                        <?php if(!empty($comments)): ?>
                            <?php foreach($comments as $comment): ?>
                            <div class="comment-item-pro">
                                <div class="d-flex align-items-start" style="flex: 1;">
                                    <img src="<?= get_media_url($comment->avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= $comment->username ?>&background=f4f7fa&color=5d78ff';" class="comment-avatar-pro">
                                    <div>
                                        <div class="comment-user-pro">@<?= strtoupper($comment->username) ?></div>
                                        <div class="text-muted small" style="line-height: 1.4;"><?= esc($comment->content) ?></div>
                                    </div>
                                </div>
                                <a href="<?= base_url('admin/posts/delete_comment/'.$comment->id) ?>" class="text-danger ml-2" onclick="return confirm('Delete this comment?');"><i class="fas fa-trash-alt"></i></a>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="p-5 text-center text-muted small font-weight-bold">No comments recorded yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                 <div class="card-pro">
                    <div class="card-header-pro">Creator Profile</div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <img src="<?= $user_img ?>" style="width: 55px; height: 55px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-soft);">
                            <div class="ml-3">
                                <div style="font-weight: 700; color: #000; font-size: 14px;"><?= $post->full_name ?: $post->username ?></div>
                                <div class="text-primary font-weight-bold small">@<?= strtoupper($post->username) ?></div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-top small font-weight-bold">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Posted On</span>
                                <span class="text-dark"><?= date('d M Y', strtotime($post->created_at)) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-pro">
                    <div class="card-header-pro">Post Info</div>
                    <div class="card-body">
                        <div class="small font-weight-bold">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Type</span>
                                <span class="badge" style="background: #f4f7fa; color: var(--text-dark);"><?= strtoupper($post->type) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Location</span>
                                <span class="text-dark"><?= $post->location ?: 'N/A' ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Status</span>
                                <span class="<?= $post->status == 'published' ? 'text-success' : 'text-warning' ?>"><?= strtoupper($post->status) ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Visibility</span>
                                <span class="text-dark"><?= strtoupper($post->feed_scope) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row pb-5 mt-2">
            <div class="col-12">
                <a href="<?= base_url('admin/posts/edit/'.$post->id) ?>" class="btn-edit-pro">
                    <i class="fas fa-edit mr-2"></i> Edit Post
                </a>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
