<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🏗️ PROFESSIONAL PLAYER SECTION */
    .player-section { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 40px 0; margin-bottom: 30px; }
    .player-container { 
        max-width: 320px; margin: 0 auto; border-radius: 20px; overflow: hidden; 
        border: 1px solid var(--border-soft); aspect-ratio: 9/16; background: #000;
        box-shadow: var(--card-shadow);
        position: relative;
    }

    /* 📊 SIMPLE STAT CARDS */
    .stat-card-pro { 
        background: #fff; border: none; padding: 20px; border-radius: 12px; 
        text-align: center; box-shadow: var(--card-shadow); transition: 0.3s; 
    }
    .stat-card-pro:hover { transform: translateY(-3px); }
    .stat-num-pro { font-size: 22px; font-weight: 700; color: var(--text-dark); display: block; }
    .stat-label-pro { font-size: 10px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px; }

    /* 📝 INFO BLOCKS PRO */
    .card-pro { background: #fff; border: none; border-radius: 12px; margin-bottom: 25px; overflow: hidden; box-shadow: var(--card-shadow); }
    .card-header-pro { 
        background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid var(--border-soft); 
        font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-dark); 
    }

    /* ⚡ PERFORMANCE BARS */
    .progress-track-pro { background: #eee; height: 8px; border-radius: 10px; overflow: hidden; margin-top: 8px; }
    .progress-fill-pro { height: 100%; border-radius: 10px; transition: 1.5s ease-in-out; }

    /* 💬 COMMENT STREAM */
    .comment-item-pro { border-bottom: 1px solid var(--border-soft); padding: 15px 20px; transition: 0.2s; }
    .comment-item-pro:hover { background: #fcfcfc; }
    .c-avatar-pro { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-soft); }

    .btn-pro-blue { 
        background: var(--primary-blue); color: #fff; font-weight: 700; padding: 14px; 
        border-radius: 10px; width: 100%; border: none; text-transform: uppercase; font-size: 11px;
        box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2); transition: 0.3s;
    }
    .btn-pro-blue:hover { transform: translateY(-1px); color: #fff; box-shadow: 0 6px 15px rgba(93, 120, 255, 0.3); }
</style>

<?php 
    $reel_src  = get_media_url($reel->video_url, 'reel'); 
    $thumb_src = get_media_url($reel->thumbnail_url, 'reel'); 
    $user_img  = get_media_url($reel->user_avatar, 'profile'); 
?>

<div class="player-section">
    <div class="container-fluid">
        <div class="player-container">
            <?php if(!empty($reel->video_url)): ?>
                <video width="100%" height="100%" controls controlsList="nodownload" oncontextmenu="return false;" poster="<?= $thumb_src ?>" style="object-fit: cover;">
                    <source src="<?= $reel_src ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="min-height: 500px;">
                    <div class="text-center">
                        <i class="fas fa-video-slash fa-2x mb-2"></i><br><small>VIDEO OFFLINE</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card-pro">
                <span class="stat-label-pro" style="color: var(--primary-blue);">Views</span>
                <span class="stat-num-pro"><?= format_number_k($reel->views_count) ?></span>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card-pro">
                <span class="stat-label-pro" style="color: var(--accent-red);">Likes</span>
                <span class="stat-num-pro"><?= format_number_k($reel->likes_count) ?></span>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card-pro">
                <span class="stat-label-pro" style="color: var(--accent-green);">Engagement</span>
                <span class="stat-num-pro"><?= $stats['viral_score'] ?>%</span>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card-pro">
                <span class="stat-label-pro" style="color: var(--accent-orange);">Watch Time</span>
                <span class="stat-num-pro"><?= $stats['watch_time_hrs'] ?>h</span>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card-pro" style="border-left: 4px solid var(--accent-red);">
                <div class="card-header-pro d-flex justify-content-between align-items-center">
                    <span class="text-danger font-weight-bold"><i class="fas fa-gavel mr-2"></i> Violation Controls</span>
                    <button class="btn btn-danger btn-sm font-weight-bold px-3" style="border-radius: 6px; font-size: 10px;" 
                            onclick="openStrikeModal('REEL', '<?= $reel->id ?>', '<?= $reel->channel_id ?>')">
                        ISSUE STRIKE
                    </button>
                </div>
                <div class="card-body py-3 d-flex justify-content-between align-items-center">
                    <span class="small text-muted">Active Channel Strikes: <b class="text-dark"><?= $stats['active_strikes'] ?? 0 ?></b></span>
                    <span class="badge" style="background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green);"><?= strtoupper($reel->status) ?></span>
                </div>
            </div>

            <div class="card-pro">
                <div class="card-header-pro"><i class="fas fa-chart-line mr-2 text-primary"></i> Engagement Stats</div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="d-flex justify-content-between mb-1"><span class="stat-label-pro">Viral Potential</span><span class="text-success small font-weight-bold"><?= $stats['viral_percent'] ?>%</span></div>
                            <div class="progress-track-pro"><div class="progress-fill-pro" style="background: var(--accent-green); width: <?= $stats['viral_percent'] ?>%"></div></div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="d-flex justify-content-between mb-1"><span class="stat-label-pro">Interaction Rate</span><span class="text-primary small font-weight-bold"><?= $stats['engagement_rate'] ?>%</span></div>
                            <div class="progress-track-pro"><div class="progress-fill-pro" style="background: var(--primary-blue); width: <?= min(100, $stats['engagement_rate'] * 2) ?>%"></div></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-pro">
                <div class="card-header-pro d-flex justify-content-between align-items-center">
                    <span>Recent Comments</span>
                    <span class="badge" style="background: #f4f7fa; color: var(--text-dark);"><?= count($comments) ?> Total</span>
                </div>
                <div class="card-body p-0">
                    <?php if(!empty($comments)): foreach($comments as $comment): ?>
                        <div class="comment-item-pro d-flex align-items-start">
                            <img src="<?= get_media_url($comment->avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= $comment->username ?>&background=f4f7fa&color=5d78ff';" class="c-avatar-pro mr-3">
                            <div style="flex: 1;">
                                <div class="d-flex justify-content-between">
                                    <span class="text-dark font-weight-bold small">@<?= strtoupper($comment->username) ?></span>
                                    <a href="<?= base_url('admin/reels/delete_comment/'.$comment->id) ?>" class="text-danger small" onclick="return confirm('Remove this comment?');"><i class="fas fa-trash-alt"></i></a>
                                </div>
                                <div class="text-muted" style="font-size: 13px; margin-top: 4px;"><?= esc($comment->content) ?></div>
                                <div class="small mt-2" style="font-size: 10px; color: #bbb;"><?= date('d M, Y | h:i A', strtotime($comment->created_at)) ?></div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="p-5 text-center text-muted small">No comments recorded.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card-pro">
                <div class="card-header-pro">Creator Info</div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-4">
                        <img src="<?= $user_img ?>" style="width: 55px; height: 55px; border-radius: 10px; border: 1px solid var(--border-soft); object-fit: cover;">
                        <div class="ml-3">
                            <div class="text-dark font-weight-bold" style="font-size: 14px;"><?= $reel->full_name ?></div>
                            <div class="text-primary small font-weight-600">@<?= strtoupper($reel->username) ?></div>
                        </div>
                    </div>
                    <div class="pt-3 border-top">
                        <div class="d-flex justify-content-between small mb-2">
                            <span class="text-muted font-weight-bold">Channel</span>
                            <a href="<?= base_url('admin/channels/view/'.$reel->channel_id) ?>" class="text-primary font-weight-bold"><?= strtoupper($reel->channel_name ?: 'N/A') ?></a>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span class="text-muted font-weight-bold">System ID</span>
                            <span class="text-dark">#REEL-<?= $reel->id ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-pro">
                <div class="card-header-pro">Reel Info</div>
                <div class="card-body p-4">
                    <label class="stat-label-pro mb-2">CAPTION</label>
                    <p class="text-dark small" style="line-height: 1.6; font-weight: 500;"><?= $reel->caption ?: 'No caption provided.' ?></p>
                    
                    <div class="mt-4 pt-4 border-top">
                        <div class="d-flex justify-content-between mb-3"><span class="stat-label-pro">Visibility</span><span class="badge" style="background: #f4f7fa; color: var(--text-dark);"><?= strtoupper($reel->visibility) ?></span></div>
                        <div class="d-flex justify-content-between"><span class="stat-label-pro">Monetization</span><span class="<?= $reel->monetization_enabled ? 'text-success' : 'text-muted' ?> font-weight-bold small"><?= $reel->monetization_enabled ? 'ENABLED' : 'OFFLINE' ?></span></div>
                    </div>
                </div>
            </div>

            <div class="mt-2 pb-5">
                <a href="<?= base_url('admin/reels/edit/'.$reel->id) ?>" class="btn-pro-blue">
                    <i class="fas fa-edit mr-2"></i> EDIT REEL
                </a>
            </div>
        </div>
    </div>
</div>

<?= $this->include('admin/moderation/strikes/modal_snippet') ?>

<script>
function openStrikeModal(type, content_id, channel_id) {
    $('#strike_content_type').val(type);
    $('#strike_content_id').val(content_id);
    $('#strike_channel_id').val(channel_id);
    $('#addStrikeModal').modal('show');
}
</script>

<?= $this->endSection() ?>
