<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* 🎨 THEME & BASE STYLES */
    :root {
        --bg-color: #f4f7fa;
        --card-bg: #ffffff;
        --text-main: #1a1c23;
        --text-light: #6c757d;
        --border-color: #eaedf2;
        --primary-blue: #4361ee;
        --accent-green: #2ec4b6;
        --accent-red: #e63946;
        --accent-orange: #ff9f1c;
    }
    
    body { background-color: var(--bg-color); }
    
    .deep-dive-card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        overflow: hidden;
        height: max-content;
    }
    
    .card-title-head {
        font-size: 14px;
        font-weight: 700;
        color: var(--text-main);
        padding: 15px 20px;
        border-bottom: 1px solid var(--border-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* 🏷️ TOP ACTIONS & BREADCRUMB */
    .top-action-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px; }
    .breadcrumb-title { font-size: 16px; font-weight: 700; color: var(--text-main); display: flex; align-items: center; }
    .breadcrumb-title i { color: var(--text-light); margin-right: 10px; cursor: pointer; }
    .breadcrumb-title span { color: var(--text-light); font-weight: 500; font-size: 14px; margin-right: 8px; }
    
    .btn-top { font-weight: 600; font-size: 13px; padding: 8px 16px; border-radius: 8px; border: 1px solid var(--border-color); background: #fff; color: var(--text-main); transition: 0.2s; white-space: nowrap; }
    .btn-top:hover { background: #f8f9fa; }
    .btn-top.btn-danger-custom { background: var(--accent-red); color: white; border: none; }
    .btn-top.btn-danger-custom:hover { background: #d90429; }

    /* 🎬 HERO BANNER CARD (VERTICAL FOR REELS) */
    .hero-banner { display: flex; padding: 20px; gap: 20px; align-items: flex-start; flex-wrap: wrap; }
    .hero-player-wrapper { 
        width: 180px; aspect-ratio: 9/16; border-radius: 12px; overflow: hidden; 
        flex-shrink: 0; background: #000; border: 1px solid var(--border-color);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .hero-info { flex: 1; min-width: 250px; }
    .hero-info h2 { font-size: 18px; font-weight: 800; color: var(--text-main); margin-bottom: 12px; line-height: 1.4; }
    .hero-creator { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
    .hero-creator img { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
    .verified-badge { background: rgba(67, 97, 238, 0.1); color: var(--primary-blue); font-size: 10px; font-weight: 700; padding: 3px 8px; border-radius: 4px; margin-left: auto; }
    
    /* Dynamic Grid for Meta Info */
    .hero-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; border-top: 1px solid var(--border-color); padding-top: 15px; }
    .meta-item .lbl { display: block; font-size: 11px; color: var(--text-light); font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
    .meta-item .val { font-size: 13px; font-weight: 700; color: var(--text-main); }
    .status-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: var(--accent-green); margin-right: 5px; }

    /* 📄 LEFT COLUMN */
    .detail-row { padding: 15px 20px; border-bottom: 1px solid var(--border-color); }
    .detail-row:last-child { border-bottom: none; }
    .d-icon { color: var(--primary-blue); width: 24px; font-size: 14px; }
    .d-lbl { font-size: 11px; color: var(--text-light); font-weight: 600; text-transform: uppercase; margin-bottom: 5px; }
    .d-val { font-size: 13px; font-weight: 700; color: var(--text-main); }
    .tag-pill { display: inline-block; background: #f4f7fa; color: var(--text-light); border: 1px solid var(--border-color); font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 20px; margin: 2px 2px 0 0; }

    .creator-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: 10px; margin-top: 15px; }
    .c-stat .s-lbl { font-size: 11px; color: var(--text-light); font-weight: 600; display: block; }
    .c-stat .s-val { font-size: 14px; font-weight: 800; color: var(--text-main); }

    /* 💬 COMMENTS STREAM */
    .comment-item-pro { border-bottom: 1px solid var(--border-color); padding: 15px; transition: 0.2s; }
    .comment-item-pro:last-child { border-bottom: none; }
    .comment-item-pro:hover { background: #fcfcfc; }
    .c-avatar-pro { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-color); }

    /* 📊 MIDDLE COLUMN DYNAMIC GRIDS */
    .stats-row-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); padding: 20px; gap: 15px; text-align: center; }
    .stat-box-img { border-right: 1px solid var(--border-color); padding-right: 10px; }
    .stat-box-img:last-child { border-right: none; padding-right: 0; }
    .stat-box-img .icon-wrap { width: 32px; height: 32px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 10px; font-size: 14px; }
    .sb-val { font-size: 18px; font-weight: 800; color: var(--text-main); margin: 5px 0 2px; }
    .sb-lbl { font-size: 11px; font-weight: 600; color: var(--text-light); display: block; }
    .sb-trend { font-size: 10px; font-weight: 700; }
    .trend-up { color: var(--accent-green); }
    
    .audience-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; padding: 20px; }
    .aud-title { font-size: 12px; font-weight: 700; color: var(--text-main); margin-bottom: 15px; }
    .geo-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 12px; font-weight: 600; }
    .geo-lbl { color: var(--text-light); }
    .geo-val { color: var(--text-main); }
    
    .age-row { display: flex; align-items: center; margin-bottom: 8px; font-size: 11px; font-weight: 600; }
    .age-bar-track { flex-grow: 1; height: 6px; background: #eaedf2; border-radius: 3px; margin: 0 10px; overflow: hidden; }
    .age-bar-fill { height: 100%; background: var(--primary-blue); border-radius: 3px; }

    /* ⚡ RIGHT COLUMN DYNAMIC GRIDS */
    .quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; padding: 15px; }
    .qa-btn { border: 1px solid var(--border-color); background: #fff; padding: 10px; border-radius: 8px; font-size: 12px; font-weight: 600; color: var(--text-main); text-align: left; transition: 0.2s; white-space: nowrap; }
    .qa-btn i { width: 16px; margin-right: 5px; text-align: center; }
    .qa-btn:hover { background: #f8f9fa; }

    .monetize-badge { background: rgba(46, 196, 182, 0.1); color: var(--accent-green); font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 700; }
    .m-earnings { font-size: 24px; font-weight: 800; color: var(--text-main); margin-top: 5px; }
    
    .copyright-pass { background: rgba(46, 196, 182, 0.1); color: var(--accent-green); width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; margin-right: 15px; }
    .ai-score-track { height: 8px; background: #eaedf2; border-radius: 4px; margin: 10px 0 15px; overflow: hidden; }
    .ai-score-fill { height: 100%; background: var(--accent-green); }
    .check-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 8px; }
    
    .rep-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid var(--border-color); font-size: 12px; font-weight: 600; }
    .rep-row:last-child { border-bottom: none; }

    /* 📱 RESPONSIVE TWEAKS */
    @media (max-width: 991px) {
        .hero-banner { flex-direction: column; align-items: center; text-align: center; }
        .hero-player-wrapper { width: 220px; }
        .stat-box-img { border-right: none; border-bottom: 1px solid var(--border-color); padding-bottom: 10px; }
        .stat-box-img:last-child { border-bottom: none; }
    }
</style>

<div class="container-fluid pt-3">
    <div class="top-action-bar">
        <div class="breadcrumb-title">
            <i class="fas fa-arrow-left" onclick="window.history.back()"></i>
            <span>Reel Management /</span> Reel Deep Dive
        </div>
        <div class="d-flex flex-wrap gap-2 action-buttons-mobile">
            <a href="<?= base_url('admin/reels/edit/'.$reel->id) ?>" class="btn btn-top"><i class="fas fa-pen mr-1"></i> Edit Reel</a>
            <button class="btn btn-top" onclick="updateStatus('blocked')"><i class="fas fa-ban mr-1 text-warning"></i> Block</button>
            <button class="btn btn-top btn-danger-custom shadow-sm" onclick="deleteReel('<?= $reel->id ?>')"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
        </div>
    </div>
</div>

<div class="container-fluid">
    
    <div class="deep-dive-card hero-banner">
        <div class="hero-player-wrapper">
            <?php if(!empty($reel->video_url)): ?>
                <video width="100%" height="100%" controls controlsList="nodownload" oncontextmenu="return false;" poster="<?= get_media_url($reel->thumbnail_url, 'reel') ?>" style="object-fit: cover;">
                    <source src="<?= get_media_url($reel->video_url, 'reel') ?>" type="video/mp4">
                </video>
            <?php else: ?>
                <div class="d-flex align-items-center justify-content-center h-100 text-muted" style="background: #111;">
                    <div class="text-center">
                        <i class="fas fa-video-slash fa-2x mb-2 text-secondary"></i><br><small>OFFLINE</small>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="hero-info">
            <h2><?= character_limiter(esc($reel->caption), 80) ?: 'No caption provided' ?></h2>
            <div class="hero-creator">
                <img src="<?= get_media_url($reel->user_avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($reel->username) ?>';">
                <div>
                    <div style="font-size: 14px; font-weight: 700; color: var(--text-main);"><?= esc($reel->full_name) ?> <?php if($reel->user_verified) echo '<i class="fas fa-check-circle text-primary ml-1"></i>'; ?></div>
                    <div style="font-size: 12px; color: var(--text-light);">@<?= esc($reel->username) ?></div>
                </div>
                <?php if($reel->user_verified): ?>
                    <span class="verified-badge">Verified Creator</span>
                <?php endif; ?>
            </div>
            
            <div class="hero-meta-grid">
                <div class="meta-item">
                    <span class="lbl">Reel ID</span>
                    <span class="val">REEL-<?= $reel->id ?>-<?= substr(md5($reel->unique_id ?? $reel->id), 0, 4) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Uploaded</span>
                    <span class="val"><?= date('M d, Y', strtotime($reel->created_at)) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Duration</span>
                    <span class="val"><?= gmdate("i:s", $reel->duration) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Category</span>
                    <span class="val"><?= esc($reel->category) ?: 'General' ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Status</span>
                    <span class="val">
                        <i class="status-dot" style="background: <?= $reel->status == 'published' ? 'var(--accent-green)' : 'var(--accent-orange)' ?>"></i> 
                        <?= ucfirst($reel->status) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-xl-3 col-lg-4 col-md-12 mb-4">
            
            <div class="deep-dive-card h-100">
                <div class="card-title-head">Reel Details</div>
                
                <div class="detail-row d-flex">
                    <i class="fas fa-eye d-icon text-success"></i>
                    <div class="ml-3">
                        <div class="d-lbl">Visibility</div>
                        <div class="d-val"><i class="fas fa-circle text-success" style="font-size: 8px; vertical-align: middle;"></i> <?= ucfirst($reel->visibility) ?></div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-tags d-icon text-info"></i>
                    <div class="ml-3 w-100">
                        <div class="d-lbl">Tags</div>
                        <div class="mt-1">
                            <?php if($reel->tags): foreach(explode(',', $reel->tags) as $tag): ?>
                                <span class="tag-pill"><?= trim($tag) ?></span>
                            <?php endforeach; else: echo '<span class="text-muted small">No tags</span>'; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-align-left d-icon text-secondary"></i>
                    <div class="ml-3 w-100">
                        <div class="d-lbl">Full Caption</div>
                        <div class="d-val text-muted" style="font-size: 12px; font-weight: 500; line-height: 1.4; max-height: 150px; overflow-y: auto;">
                            <?= nl2br(esc($reel->caption)) ?: 'No description provided.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="deep-dive-card mt-4">
                <div class="card-title-head">Creator Information</div>
                <div class="p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= get_media_url($reel->user_avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($reel->username) ?>';" style="width:40px; height:40px; border-radius:50%; object-fit:cover;">
                        <div class="ml-3">
                            <div style="font-size: 14px; font-weight: 700; color: var(--text-main);"><?= esc($reel->full_name) ?> <?php if($reel->user_verified) echo '<i class="fas fa-check-circle text-primary" style="font-size:12px;"></i>'; ?></div>
                            <div style="font-size: 12px; color: var(--text-light);">@<?= esc($reel->username) ?></div>
                        </div>
                    </div>
                    <div class="creator-stats-grid">
                        <div class="c-stat">
                            <span class="s-lbl">Followers</span>
                            <span class="s-val"><?= format_number_k($reel->followers_count ?? 0) ?></span>
                        </div>
                        <div class="c-stat">
                            <span class="s-lbl">Videos/Reels</span>
                            <span class="s-val"><?= $reel->channel_videos_count ?? 0 ?></span>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/channels/view/'.$reel->channel_id) ?>" class="btn btn-block mt-3 font-weight-bold" style="background: #f8f9fa; border: 1px solid var(--border-color); color: var(--text-main); font-size: 12px; padding: 10px; border-radius: 8px;">View Creator Profile</a>
                </div>
            </div>

            <div class="deep-dive-card mt-4">
                <div class="card-title-head d-flex justify-content-between align-items-center">
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
                                <div class="text-muted" style="font-size: 12px; margin-top: 4px;"><?= esc($comment->content) ?></div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <div class="p-4 text-center text-muted small">No comments recorded.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="col-xl-6 col-lg-8 col-md-12 mb-4">
            
            <div class="deep-dive-card">
                <div class="stats-row-grid">
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: rgba(67, 97, 238, 0.1); color: var(--primary-blue);"><i class="fas fa-eye"></i></div>
                        <div class="sb-lbl">Views</div>
                        <div class="sb-val"><?= format_number_k($reel->views_count) ?></div>
                        <?php if($stats['recent_views'] > 0): ?>
                            <div class="sb-trend trend-up"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_views']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: rgba(230, 57, 70, 0.1); color: var(--accent-red);"><i class="fas fa-heart"></i></div>
                        <div class="sb-lbl">Likes</div>
                        <div class="sb-val"><?= format_number_k($reel->likes_count) ?></div>
                        <?php if($stats['recent_likes'] > 0): ?>
                            <div class="sb-trend trend-up"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_likes']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: rgba(67, 97, 238, 0.1); color: var(--primary-blue);"><i class="fas fa-comment"></i></div>
                        <div class="sb-lbl">Comments</div>
                        <div class="sb-val"><?= format_number_k($reel->comments_count) ?></div>
                        <?php if($stats['recent_comments'] > 0): ?>
                            <div class="sb-trend trend-up"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_comments']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: #f4f7fa; color: var(--text-main);"><i class="fas fa-share"></i></div>
                        <div class="sb-lbl">Shares</div>
                        <div class="sb-val"><?= format_number_k($reel->shares_count ?? 0) ?></div>
                        <div class="sb-trend text-muted">Total</div>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: rgba(67, 97, 238, 0.1); color: var(--primary-blue);"><i class="fas fa-clock"></i></div>
                        <div class="sb-lbl">Watch Time</div>
                        <div class="sb-val"><?= $stats['watch_time_hrs'] ?>h</div>
                        <div class="sb-trend text-muted">Total hours</div>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap" style="background: rgba(255, 159, 28, 0.1); color: var(--accent-orange);"><i class="fas fa-bolt"></i></div>
                        <div class="sb-lbl">Viral Score</div>
                        <div class="sb-val"><?= $stats['viral_score'] ?></div>
                        <div class="sb-trend text-success"><?= $stats['viral_percent'] ?>% active</div>
                    </div>
                </div>
            </div>

            <div class="deep-dive-card">
                <div class="card-title-head">
                    Performance Analytics
                    <select class="form-control form-control-sm w-auto font-weight-bold" style="border-radius: 6px; font-size: 11px;">
                        <option>Last 7 Days (Views)</option>
                    </select>
                </div>
                <div class="p-3">
                    <canvas id="performanceChart" height="90"></canvas>
                </div>
            </div>

            <div class="deep-dive-card">
                <div class="card-title-head">Audience Insights</div>
                <div class="audience-grid">
                    
                    <div>
                        <div class="aud-title">Top Countries</div>
                        <?php if(!empty($geo_stats)): foreach($geo_stats as $geo): 
                            $pct = ($geo->count / max(1, $reel->views_count)) * 100;
                            $flag = '🌐';
                            if(strtolower($geo->country) == 'india') $flag = '🇮🇳';
                            if(strtolower($geo->country) == 'usa' || strtolower($geo->country) == 'united states') $flag = '🇺🇸';
                        ?>
                            <div class="geo-row">
                                <span class="geo-lbl"><?= $flag ?> <?= esc($geo->country) ?: 'Unknown' ?></span>
                                <span class="geo-val"><?= round($pct, 1) ?>%</span>
                            </div>
                        <?php endforeach; else: echo '<div class="text-muted small">No geography data available yet.</div>'; endif; ?>
                    </div>

                    <div style="text-align: center;">
                        <div class="aud-title text-left">Device Type</div>
                        <?php if(!empty($device_stats)): ?>
                            <div style="position: relative; width: 100px; height: 100px; margin: 0 auto;">
                                <canvas id="deviceChart"></canvas>
                            </div>
                            <div class="mt-3 text-left pl-2" id="deviceLegend"></div>
                        <?php else: echo '<div class="text-muted small text-left">No device data.</div>'; endif; ?>
                    </div>

                    <div>
                        <div class="aud-title">Age Group</div>
                        <?php if(!empty($age_stats)): foreach($age_stats as $age): 
                            $pct = ($age->count / max(1, $reel->views_count)) * 100;
                        ?>
                            <div class="age-row">
                                <span class="geo-lbl" style="width: 35px;"><?= esc($age->age_group) ?></span>
                                <div class="age-bar-track"><div class="age-bar-fill" style="width: <?= $pct ?>%;"></div></div>
                                <span class="geo-val" style="width: 25px; text-align: right;"><?= round($pct, 1) ?>%</span>
                            </div>
                        <?php endforeach; else: echo '<div class="text-muted small">No demographic data.</div>'; endif; ?>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-xl-3 col-lg-12 col-md-12 mb-4">
            
            <div class="row">
                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">Quick Actions</div>
                        <div class="quick-actions-grid">
                            <button class="qa-btn" onclick="updateStatus('blocked')"><i class="fas fa-ban text-danger"></i> Block</button>
                            <button class="qa-btn" onclick="updateStatus('public')"><i class="fas fa-globe text-success"></i> Public</button>
                            <button class="qa-btn" onclick="openStrikeModal('REEL', '<?= $reel->id ?>', '<?= $reel->channel_id ?>')"><i class="fas fa-exclamation-triangle text-warning"></i> Issue Strike</button>
                            <button class="qa-btn text-danger" onclick="deleteReel('<?= $reel->id ?>')"><i class="fas fa-trash-alt text-danger"></i> Delete Reel</button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">
                            Monetization
                            <span class="monetize-badge" style="<?= !$reel->monetization_enabled ? 'background: rgba(108, 117, 125, 0.1); color: var(--text-light);' : '' ?>">
                                <i class="fas fa-circle" style="font-size:6px; vertical-align:middle; margin-right:3px;"></i> 
                                <?= $reel->monetization_enabled ? 'Monetized' : 'Disabled' ?>
                            </span>
                        </div>
                        <div class="p-3">
                            <div style="font-size: 12px; font-weight: 600; color: var(--text-light);">Real Earnings</div>
                            <div class="m-earnings">₹<?= number_format($creator_earnings->total_earnings ?? 0, 2) ?></div>
                            
                            <div class="d-flex justify-content-between mt-3 mb-3">
                                <div>
                                    <div style="font-size: 11px; font-weight: 600; color: var(--text-light);">Ad Impressions</div>
                                    <div style="font-size: 14px; font-weight: 800; color: var(--text-main);"><?= format_number_k($ad_stats->total_imps ?? 0) ?></div>
                                </div>
                                <div class="text-right">
                                    <div style="font-size: 11px; font-weight: 600; color: var(--text-light);">Avg RPM</div>
                                    <div style="font-size: 14px; font-weight: 800; color: var(--text-main);">₹<?= number_format($ad_stats->avg_rpm ?? 0, 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">Copyright Status</div>
                        <div class="p-3">
                            <?php if(empty($reel->copyright_status) || $reel->copyright_status == 'NONE'): ?>
                                <div class="d-flex align-items-center mb-4">
                                    <div class="copyright-pass"><i class="fas fa-check"></i></div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 800; color: var(--text-main);">No Copyright Issues</div>
                                        <div style="font-size: 11px; font-weight: 500; color: var(--text-light);">Content is safe</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center mb-4">
                                    <div class="copyright-pass" style="background: rgba(230, 57, 70, 0.1); color: var(--accent-red);"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div style="font-size: 13px; font-weight: 800; color: var(--text-main);">Violation Found</div>
                                        <div style="font-size: 11px; font-weight: 500; color: var(--accent-red);"><?= esc($reel->copyright_status) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <span style="font-size: 12px; font-weight: 700; color: var(--text-light);">AI Scan Score</span>
                                <span style="font-size: 14px; font-weight: 800; color: var(--text-main);"><?= $reel->ai_safety_score ?? 'N/A' ?>/100</span>
                            </div>
                            <?php if(isset($reel->ai_safety_score)): ?>
                                <div class="ai-score-track"><div class="ai-score-fill" style="width: <?= $reel->ai_safety_score ?>%;"></div></div>
                            <?php else: ?>
                                <div class="ai-score-track"><div class="ai-score-fill bg-secondary" style="width: 0%;"></div></div>
                            <?php endif; ?>

                            <div class="check-row"><span style="color: var(--text-light);"><i class="fas fa-check-circle text-success mr-2"></i> Audio Check</span><span style="color: var(--text-main);">Passed</span></div>
                            <div class="check-row"><span style="color: var(--text-light);"><i class="fas fa-check-circle text-success mr-2"></i> Visual Match</span><span style="color: var(--text-main);">Passed</span></div>
                            <div class="check-row"><span style="color: var(--text-light);"><i class="fas fa-check-circle text-success mr-2"></i> Policy Check</span><span style="color: var(--text-main);">Passed</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">Reports & Violations</div>
                        <div class="p-3">
                            <div class="rep-row"><span style="color: var(--text-light);"><i class="fas fa-flag text-danger mr-2"></i> Total Reports</span><span style="color: var(--text-main);"><?= count($reports) ?></span></div>
                            <div class="rep-row"><span style="color: var(--text-light);"><i class="fas fa-exclamation-triangle text-warning mr-2"></i> Active Strikes</span><span style="color: var(--text-main);"><?= !empty($video_strike) ? '1' : '0' ?></span></div>
                            <div class="rep-row"><span style="color: var(--text-light);"><i class="fas fa-clipboard-list text-primary mr-2"></i> Last Report</span><span style="color: var(--text-main);"><?= !empty($reports) ? date('M d, Y', strtotime($reports[0]->created_at)) : 'None' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?= $this->include('admin/moderation/strikes/modal_snippet') ?>

<script>
// Graph 1: Performance Line Chart (Real Data)
const graphData = <?= json_encode($graph_data) ?>;
if(graphData && graphData.length > 0) {
    const ctxLine = document.getElementById('performanceChart').getContext('2d');
    let labels = []; let viewData = []; 
    
    graphData.forEach(item => { 
        labels.push(item.date); 
        viewData.push(item.count); 
    });

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Views', data: viewData, borderColor: '#4361ee', backgroundColor: 'rgba(67, 97, 238, 0.1)', borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { grid: { borderDash: [5, 5], color: '#eaedf2' }, ticks: { font: { size: 10 } } }
            }
        }
    });
} else {
    document.getElementById('performanceChart').outerHTML = '<div class="text-center text-muted p-4 small">No trend data available for the last 7 days.</div>';
}

// Graph 2: Device Donut Chart (Real Data)
const deviceStats = <?= json_encode($device_stats) ?>;
if(deviceStats && deviceStats.length > 0) {
    const ctxDonut = document.getElementById('deviceChart').getContext('2d');
    let devLabels = []; let devData = []; let devColors = ['#4361ee', '#2ec4b6', '#ff9f1c', '#7209b7'];
    let legendHtml = '';

    deviceStats.forEach((item, index) => {
        let labelName = item.device_type ? item.device_type.charAt(0).toUpperCase() + item.device_type.slice(1) : 'Unknown';
        let pct = ((item.count / <?= max(1, $reel->views_count) ?>) * 100).toFixed(1);
        let color = devColors[index % devColors.length];
        
        devLabels.push(labelName);
        devData.push(item.count);
        legendHtml += `<div class="geo-row mb-1"><span class="geo-lbl"><i class="fas fa-circle" style="color: ${color}; font-size: 8px;"></i> ${labelName}</span><span class="geo-val">${pct}%</span></div>`;
    });

    document.getElementById('deviceLegend').innerHTML = legendHtml;

    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            labels: devLabels,
            datasets: [{ data: devData, backgroundColor: devColors, borderWidth: 0, cutout: '70%' }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: true } }
        }
    });
}

// Admin Actions Logic
function updateStatus(s) {
    Swal.fire({
        title: 'Change Visibility?',
        text: "Are you sure you want to change status to: " + s.toUpperCase(),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--primary-blue)',
        confirmButtonText: 'Yes, update'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("<?= base_url('admin/reels/update_status') ?>", {id: "<?= $reel->id ?>", status: s}, function() { location.reload(); });
        }
    });
}

function deleteReel(id) {
    Swal.fire({
        title: 'Permanently Delete?',
        text: "This action cannot be undone.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: 'var(--accent-red)',
        confirmButtonText: 'Yes, Delete'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = "<?= base_url('admin/reels/delete/') ?>" + id; }
    });
}

function openStrikeModal(t, id, c_id) {
    $('#strike_content_type').val(t); 
    $('#strike_content_id').val(id); 
    $('#strike_channel_id').val(c_id); 
    $('#addStrikeModal').modal('show');
}
</script>

<?= $this->endSection() ?>
