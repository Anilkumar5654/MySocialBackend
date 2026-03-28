<?= $this->extend('admin/layout/main') ?>
<?= $this->section('content') ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* 🎨 PAGE SPECIFIC COMPONENTS (Powered by Global Variables) */
    
    /* Layout & Cards */
    .deep-dive-card {
        background-color: var(--bg-surface, #ffffff);
        border: 1px solid var(--border-soft);
        border-radius: var(--radius-lg, 12px);
        margin-bottom: var(--space-lg, 20px);
        box-shadow: var(--card-shadow);
        overflow: hidden;
        height: max-content;
    }
    
    .card-title-head {
        font-size: var(--font-size-md, 14px);
        font-weight: var(--font-weight-bold, 700);
        color: var(--text-dark);
        padding: var(--space-md, 15px) var(--space-lg, 20px);
        border-bottom: 1px solid var(--border-soft);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* 🏷️ Top Actions & Breadcrumb */
    .top-action-bar { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        margin-bottom: var(--space-lg, 20px); 
        flex-wrap: wrap; 
        gap: var(--space-md, 15px); 
    }
    .breadcrumb-title { 
        font-size: var(--font-size-lg, 16px); 
        font-weight: var(--font-weight-bold, 700); 
        color: var(--text-dark); 
        display: flex; 
        align-items: center; 
    }
    .breadcrumb-title i { color: var(--text-muted); margin-right: var(--space-sm, 10px); cursor: pointer; }
    .breadcrumb-title span { color: var(--text-muted); font-weight: var(--font-weight-medium, 500); font-size: var(--font-size-md, 14px); margin-right: var(--space-sm, 8px); }
    
    .btn-top { 
        font-weight: var(--font-weight-semibold, 600); 
        font-size: var(--font-size-sm, 13px); 
        padding: var(--space-sm, 8px) var(--space-md, 16px); 
        border-radius: var(--radius-md, 8px); 
        border: 1px solid var(--border-soft); 
        background-color: var(--bg-surface, #ffffff); 
        color: var(--text-dark); 
        transition: all 0.2s ease; 
        white-space: nowrap; 
    }
    .btn-top:hover { background-color: var(--bg-light); box-shadow: var(--shadow-sm); }
    .btn-danger-custom { background-color: var(--accent-red); color: #ffffff; border: none; }
    .btn-danger-custom:hover { opacity: 0.9; color: #ffffff; }

    /* 🎬 Hero Banner */
    .hero-banner { display: flex; padding: var(--space-lg, 20px); gap: var(--space-lg, 20px); align-items: flex-start; flex-wrap: wrap; }
    .hero-thumb { position: relative; width: 16.25rem; height: 9rem; border-radius: var(--radius-md, 10px); overflow: hidden; flex-shrink: 0; background-color: var(--bg-light); }
    .hero-thumb img { width: 100%; height: 100%; object-fit: cover; }
    .hero-thumb .play-btn { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: rgba(0,0,0,0.6); color: #ffffff; width: 2.5rem; height: 2.5rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: var(--font-size-sm, 14px); }
    .hero-thumb .duration { position: absolute; bottom: 0.5rem; right: 0.5rem; background-color: rgba(0,0,0,0.8); color: #ffffff; font-size: var(--font-size-xs, 10px); padding: 0.2rem 0.4rem; border-radius: var(--radius-sm, 4px); font-weight: var(--font-weight-semibold, 600); }
    
    .hero-info { flex: 1; min-width: 15.6rem; }
    .hero-info h2 { font-size: 1.25rem; font-weight: var(--font-weight-black, 800); color: var(--text-dark); margin-bottom: 0.5rem; line-height: 1.3; }
    .hero-creator { display: flex; align-items: center; gap: var(--space-sm, 10px); margin-bottom: var(--space-lg, 20px); flex-wrap: wrap; }
    
    .avatar-md { width: 2.25rem; height: 2.25rem; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-soft); }
    
    /* Dynamic Grid */
    .hero-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--space-md, 15px); border-top: 1px solid var(--border-soft); padding-top: var(--space-md, 15px); }
    .meta-item .lbl { display: block; font-size: var(--font-size-xs, 11px); color: var(--text-muted); font-weight: var(--font-weight-semibold, 600); text-transform: uppercase; margin-bottom: 0.25rem; }
    .meta-item .val { font-size: var(--font-size-sm, 13px); font-weight: var(--font-weight-bold, 700); color: var(--text-dark); display: flex; align-items: center; }
    
    .status-dot { display: inline-block; width: 0.5rem; height: 0.5rem; border-radius: 50%; margin-right: 0.3rem; }
    .bg-dot-success { background-color: var(--accent-green); }
    .bg-dot-warning { background-color: var(--accent-orange); }

    /* 📄 Detail Rows */
    .detail-row { padding: var(--space-md, 15px) var(--space-lg, 20px); border-bottom: 1px solid var(--border-soft); }
    .detail-row:last-child { border-bottom: none; }
    .d-icon { color: var(--primary-blue); width: 1.5rem; font-size: var(--font-size-md, 14px); text-align: center; }
    .d-lbl { font-size: var(--font-size-xs, 11px); color: var(--text-muted); font-weight: var(--font-weight-semibold, 600); text-transform: uppercase; margin-bottom: 0.3rem; }
    .d-val { font-size: var(--font-size-sm, 13px); font-weight: var(--font-weight-bold, 700); color: var(--text-dark); }
    .desc-scroll-box { font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-medium, 500); line-height: 1.4; max-height: 6.25rem; overflow-y: auto; color: var(--text-muted); }
    
    .tag-pill { display: inline-block; background-color: var(--bg-light); color: var(--text-muted); border: 1px solid var(--border-soft); font-size: var(--font-size-xs, 11px); font-weight: var(--font-weight-semibold, 600); padding: 0.25rem 0.6rem; border-radius: 20px; margin: 0.15rem 0.15rem 0 0; }

    /* Creator Stats */
    .creator-stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(80px, 1fr)); gap: var(--space-sm, 10px); margin-top: var(--space-md, 15px); }
    .c-stat .s-lbl { font-size: var(--font-size-xs, 11px); color: var(--text-muted); font-weight: var(--font-weight-semibold, 600); display: block; }
    .c-stat .s-val { font-size: var(--font-size-md, 14px); font-weight: var(--font-weight-black, 800); color: var(--text-dark); }

    /* 📊 Soft Background Utilities */
    .bg-primary-soft { background-color: rgba(93, 120, 255, 0.1); color: var(--primary-blue); }
    .bg-danger-soft { background-color: rgba(253, 57, 122, 0.1); color: var(--accent-red); }
    .bg-success-soft { background-color: rgba(10, 187, 135, 0.1); color: var(--accent-green); }
    .bg-warning-soft { background-color: rgba(255, 184, 34, 0.1); color: var(--accent-orange); }
    .bg-muted-soft { background-color: var(--bg-light); color: var(--text-dark); }

    /* Middle Column Grids */
    .stats-row-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(110px, 1fr)); padding: var(--space-lg, 20px); gap: var(--space-md, 15px); text-align: center; }
    .stat-box-img { border-right: 1px solid var(--border-soft); padding-right: var(--space-sm, 10px); }
    .stat-box-img:last-child { border-right: none; padding-right: 0; }
    .icon-wrap { width: 2rem; height: 2rem; border-radius: var(--radius-md, 8px); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 0.6rem; font-size: var(--font-size-md, 14px); }
    
    .sb-val { font-size: var(--font-size-lg, 18px); font-weight: var(--font-weight-black, 800); color: var(--text-dark); margin: 0.3rem 0 0.15rem; }
    .sb-lbl { font-size: var(--font-size-xs, 11px); font-weight: var(--font-weight-semibold, 600); color: var(--text-muted); display: block; }
    .sb-trend { font-size: var(--font-size-xs, 10px); font-weight: var(--font-weight-bold, 700); }
    
    /* Audience Grid */
    .audience-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-lg, 20px); padding: var(--space-lg, 20px); }
    .aud-title { font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-bold, 700); color: var(--text-dark); margin-bottom: var(--space-md, 15px); }
    .geo-row { display: flex; justify-content: space-between; margin-bottom: var(--space-sm, 10px); font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-semibold, 600); }
    .geo-lbl { color: var(--text-muted); }
    .geo-val { color: var(--text-dark); }
    
    .age-row { display: flex; align-items: center; margin-bottom: var(--space-sm, 8px); font-size: var(--font-size-xs, 11px); font-weight: var(--font-weight-semibold, 600); }
    .age-bar-track { flex-grow: 1; height: 6px; background-color: var(--border-soft); border-radius: 3px; margin: 0 10px; overflow: hidden; }
    .age-bar-fill { height: 100%; background-color: var(--primary-blue); border-radius: 3px; }
    
    .device-chart-wrapper { position: relative; width: 6.25rem; height: 6.25rem; margin: 0 auto; }

    /* ⚡ Right Column Grids */
    .quick-actions-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--space-sm, 10px); padding: var(--space-md, 15px); }
    .qa-btn { border: 1px solid var(--border-soft); background-color: var(--bg-surface, #ffffff); padding: 0.6rem; border-radius: var(--radius-md, 8px); font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-semibold, 600); color: var(--text-dark); text-align: left; transition: all 0.2s ease; white-space: nowrap; }
    .qa-btn i { width: 1rem; margin-right: 0.3rem; text-align: center; }
    .qa-btn:hover { background-color: var(--bg-light); box-shadow: var(--shadow-sm); }

    .monetize-badge { font-size: var(--font-size-xs, 10px); padding: 0.25rem 0.5rem; border-radius: var(--radius-sm, 4px); font-weight: var(--font-weight-bold, 700); display: inline-block; }
    .m-earnings { font-size: var(--font-size-xl, 24px); font-weight: var(--font-weight-black, 800); color: var(--text-dark); margin-top: 0.3rem; }
    
    .copyright-pass { width: 2.25rem; height: 2.25rem; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: var(--font-size-lg, 16px); margin-right: var(--space-md, 15px); }
    .ai-score-track { height: 8px; background-color: var(--border-soft); border-radius: 4px; margin: 0.6rem 0 1rem; overflow: hidden; }
    .ai-score-fill { height: 100%; background-color: var(--accent-green); }
    .check-row { display: flex; justify-content: space-between; font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-semibold, 600); margin-bottom: 0.5rem; }
    
    .rep-row { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border-soft); font-size: var(--font-size-sm, 12px); font-weight: var(--font-weight-semibold, 600); }
    .rep-row:last-child { border-bottom: none; }

    /* Custom Text Colors */
    .text-primary-custom { color: var(--primary-blue); }
    .text-success-custom { color: var(--accent-green); }
    .text-danger-custom { color: var(--accent-red); }
    .text-warning-custom { color: var(--accent-orange); }

    /* 📱 Responsive Tweaks */
    @media (max-width: 991px) {
        .hero-banner { flex-direction: column; align-items: center; text-align: center; }
        .hero-thumb { width: 100%; max-width: 25rem; aspect-ratio: 16/9; height: auto; }
        .stat-box-img { border-right: none; border-bottom: 1px solid var(--border-soft); padding-bottom: 0.6rem; }
        .stat-box-img:last-child { border-bottom: none; }
    }
</style>

<div class="container-fluid pt-3">
    <div class="top-action-bar">
        <div class="breadcrumb-title">
            <i class="fas fa-arrow-left" onclick="window.history.back()"></i>
            <span>Video Management /</span> Video Deep Dive
        </div>
        <div class="d-flex flex-wrap gap-2 action-buttons-mobile">
            <a href="<?= get_media_url($video->video_url, 'video') ?>" target="_blank" class="btn btn-top text-primary-custom"><i class="fas fa-play mr-1"></i> Play</a>
            <a href="<?= base_url('admin/videos/edit/'.$video->id) ?>" class="btn btn-top"><i class="fas fa-pen mr-1"></i> Edit</a>
            <button class="btn btn-top" onclick="updateStatus('blocked')"><i class="fas fa-ban mr-1 text-warning-custom"></i> Block</button>
            <button class="btn btn-top btn-danger-custom shadow-sm" onclick="deleteVideo('<?= $video->id ?>')"><i class="fas fa-trash-alt mr-1"></i> Delete</button>
        </div>
    </div>
</div>

<div class="container-fluid">
    
    <div class="deep-dive-card hero-banner">
        <div class="hero-thumb">
            <img src="<?= get_media_url($video->thumbnail_url, 'video') ?>" onerror="this.src='https://placehold.co/300x170/e9ecef/495057?text=No+Thumbnail';">
            <div class="play-btn"><i class="fas fa-play"></i></div>
            <div class="duration"><?= gmdate("i:s", $video->duration) ?></div>
        </div>
        <div class="hero-info">
            <h2><?= esc($video->title) ?></h2>
            <div class="hero-creator">
                <img src="<?= get_media_url($video->user_avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($video->username) ?>';" class="avatar-md">
                <div class="text-left">
                    <div class="text-strong text-dark"><?= esc($video->full_name) ?> <?php if($video->user_verified) echo '<i class="fas fa-check-circle text-primary-custom ml-1"></i>'; ?></div>
                    <div class="text-muted small">@<?= esc($video->username) ?></div>
                </div>
                <?php if($video->user_verified): ?>
                    <span class="monetize-badge bg-primary-soft ml-md-auto">Verified Creator</span>
                <?php endif; ?>
            </div>
            
            <div class="hero-meta-grid">
                <div class="meta-item">
                    <span class="lbl">Video ID</span>
                    <span class="val">VID-<?= $video->id ?>-<?= substr(md5($video->unique_id), 0, 4) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Uploaded</span>
                    <span class="val"><?= date('M d, Y', strtotime($video->created_at)) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Duration</span>
                    <span class="val"><?= gmdate("i:s", $video->duration) ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Resolution</span>
                    <span class="val"><?= $video->resolution ?? '1080p' ?></span>
                </div>
                <div class="meta-item">
                    <span class="lbl">Status</span>
                    <span class="val">
                        <i class="status-dot <?= $video->status == 'published' ? 'bg-dot-success' : 'bg-dot-warning' ?>"></i> 
                        <?= ucfirst($video->status) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-lg-4 col-md-12 mb-4">
            
            <div class="deep-dive-card h-100">
                <div class="card-title-head">Video Details</div>
                
                <div class="detail-row d-flex">
                    <i class="fas fa-heading d-icon"></i>
                    <div class="ml-3">
                        <div class="d-lbl">Title</div>
                        <div class="d-val"><?= character_limiter(esc($video->title), 35) ?></div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-folder d-icon"></i>
                    <div class="ml-3">
                        <div class="d-lbl">Category</div>
                        <div class="d-val"><?= esc($video->category) ?></div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-eye d-icon text-success-custom"></i>
                    <div class="ml-3">
                        <div class="d-lbl">Visibility</div>
                        <div class="d-val"><i class="fas fa-circle text-success-custom status-dot"></i> <?= ucfirst($video->visibility) ?></div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-tags d-icon text-primary-custom"></i>
                    <div class="ml-3 w-100">
                        <div class="d-lbl">Tags</div>
                        <div class="mt-1">
                            <?php if($video->tags): foreach(explode(',', $video->tags) as $tag): ?>
                                <span class="tag-pill"><?= trim($tag) ?></span>
                            <?php endforeach; else: echo '<span class="text-muted small">No tags</span>'; endif; ?>
                        </div>
                    </div>
                </div>
                <div class="detail-row d-flex">
                    <i class="fas fa-align-left d-icon text-muted"></i>
                    <div class="ml-3 w-100">
                        <div class="d-lbl">Description</div>
                        <div class="d-val desc-scroll-box">
                            <?= esc($video->description) ?: 'No description provided.' ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="deep-dive-card mt-4">
                <div class="card-title-head">Creator Information</div>
                <div class="p-3">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?= get_media_url($video->user_avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($video->username) ?>';" class="avatar-md">
                        <div class="ml-3">
                            <div class="text-strong text-dark"><?= esc($video->full_name) ?> <?php if($video->user_verified) echo '<i class="fas fa-check-circle text-primary-custom ml-1"></i>'; ?></div>
                            <div class="text-muted small">@<?= esc($video->username) ?></div>
                        </div>
                    </div>
                    <div class="creator-stats-grid">
                        <div class="c-stat">
                            <span class="s-lbl">Followers</span>
                            <span class="s-val"><?= format_number_k($video->followers_count ?? 0) ?></span>
                        </div>
                        <div class="c-stat">
                            <span class="s-lbl">Videos</span>
                            <span class="s-val"><?= $video->channel_videos_count ?? 0 ?></span>
                        </div>
                        <div class="c-stat">
                            <span class="s-lbl">Channel ID</span>
                            <span class="s-val small">CH-<?= substr($video->channel_id ?? '00', 0, 4) ?></span>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/channels/view/'.$video->channel_id) ?>" class="btn btn-light btn-block mt-3 font-weight-bold">View Creator Profile</a>
                </div>
            </div>

        </div>

        <div class="col-xl-6 col-lg-8 col-md-12 mb-4">
            
            <div class="deep-dive-card">
                <div class="stats-row-grid">
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-primary-soft"><i class="fas fa-eye"></i></div>
                        <div class="sb-lbl">Views</div>
                        <div class="sb-val"><?= format_number_k($video->views_count) ?></div>
                        <?php if($stats['recent_views'] > 0): ?>
                            <div class="sb-trend text-success-custom"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_views']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-danger-soft"><i class="fas fa-heart"></i></div>
                        <div class="sb-lbl">Likes</div>
                        <div class="sb-val"><?= format_number_k($video->likes_count) ?></div>
                        <?php if($stats['recent_likes'] > 0): ?>
                            <div class="sb-trend text-success-custom"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_likes']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-primary-soft"><i class="fas fa-comment"></i></div>
                        <div class="sb-lbl">Comments</div>
                        <div class="sb-val"><?= format_number_k($video->comments_count) ?></div>
                        <?php if($stats['recent_comments'] > 0): ?>
                            <div class="sb-trend text-success-custom"><i class="fas fa-arrow-up"></i> <?= format_number_k($stats['recent_comments']) ?> new</div>
                        <?php else: ?>
                            <div class="sb-trend text-muted">-</div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-muted-soft"><i class="fas fa-share"></i></div>
                        <div class="sb-lbl">Shares</div>
                        <div class="sb-val"><?= format_number_k($video->shares_count ?? 0) ?></div>
                        <div class="sb-trend text-muted">Total</div>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-primary-soft"><i class="fas fa-clock"></i></div>
                        <div class="sb-lbl">Watch Time</div>
                        <div class="sb-val"><?= $stats['watch_time_hrs'] ?>h</div>
                        <div class="sb-trend text-muted">Total hours</div>
                    </div>
                    <div class="stat-box-img">
                        <div class="icon-wrap bg-warning-soft"><i class="fas fa-stopwatch"></i></div>
                        <div class="sb-lbl">Avg Watch</div>
                        <div class="sb-val"><?= $stats['avg_watch_dur'] ?></div>
                        <div class="sb-trend text-muted"><?= $stats['avg_completion'] ?>% compl.</div>
                    </div>
                </div>
            </div>

            <div class="deep-dive-card">
                <div class="card-title-head">
                    Performance Analytics
                    <select class="form-control form-control-sm w-auto font-weight-bold">
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
                            $pct = ($geo->count / max(1, $video->views_count)) * 100;
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

                    <div class="text-center">
                        <div class="aud-title text-left">Device Type</div>
                        <?php if(!empty($device_stats)): ?>
                            <div class="device-chart-wrapper">
                                <canvas id="deviceChart"></canvas>
                            </div>
                            <div class="mt-3 text-left pl-2" id="deviceLegend"></div>
                        <?php else: echo '<div class="text-muted small text-left">No device data.</div>'; endif; ?>
                    </div>

                    <div>
                        <div class="aud-title">Age Group</div>
                        <?php if(!empty($age_stats)): foreach($age_stats as $age): 
                            $pct = ($age->count / max(1, $video->views_count)) * 100;
                        ?>
                            <div class="age-row">
                                <span class="geo-lbl w-25"><?= esc($age->age_group) ?></span>
                                <div class="age-bar-track"><div class="age-bar-fill" style="width: <?= $pct ?>%;"></div></div>
                                <span class="geo-val w-25 text-right"><?= round($pct, 1) ?>%</span>
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
                            <button class="qa-btn" onclick="updateStatus('blocked')"><i class="fas fa-ban text-danger-custom"></i> Block</button>
                            <button class="qa-btn" onclick="updateStatus('public')"><i class="fas fa-globe text-success-custom"></i> Public</button>
                            <button class="qa-btn" onclick="blacklistHash('<?= $video->id ?>')"><i class="fas fa-shield-alt text-primary-custom"></i> Blacklist</button>
                            <button class="qa-btn" onclick="openStrikeModal('VIDEO', '<?= $video->id ?>', '<?= $video->channel_id ?>')"><i class="fas fa-exclamation-triangle text-warning-custom"></i> Warn</button>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">
                            Monetization
                            <span class="monetize-badge <?= $video->monetization_enabled ? 'bg-success-soft' : 'bg-muted-soft' ?>">
                                <i class="fas fa-circle status-dot bg-dot-success"></i> 
                                <?= $video->monetization_enabled ? 'Monetized' : 'Disabled' ?>
                            </span>
                        </div>
                        <div class="p-3">
                            <div class="d-lbl text-muted">Real Earnings</div>
                            <div class="m-earnings">₹<?= number_format($creator_earnings->total_earnings ?? 0, 2) ?></div>
                            
                            <div class="d-flex justify-content-between mt-3 mb-3">
                                <div>
                                    <div class="d-lbl text-muted">Ad Impressions</div>
                                    <div class="text-strong text-md text-dark"><?= format_number_k($ad_stats->total_imps ?? 0) ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="d-lbl text-muted">Avg RPM</div>
                                    <div class="text-strong text-md text-dark">₹<?= number_format($ad_stats->avg_rpm ?? 0, 2) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">Copyright Status</div>
                        <div class="p-3">
                            <?php if(empty($video->copyright_status) || $video->copyright_status == 'NONE'): ?>
                                <div class="d-flex align-items-center mb-4">
                                    <div class="copyright-pass bg-success-soft"><i class="fas fa-check"></i></div>
                                    <div>
                                        <div class="text-strong text-dark text-md">No Copyright Issues</div>
                                        <div class="text-muted small">Content is safe</div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center mb-4">
                                    <div class="copyright-pass bg-danger-soft"><i class="fas fa-exclamation-triangle"></i></div>
                                    <div>
                                        <div class="text-strong text-dark text-md">Violation Found</div>
                                        <div class="text-danger-custom small font-weight-bold"><?= esc($video->copyright_status) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-end mb-1">
                                <span class="d-lbl text-muted mb-0">AI Scan Score</span>
                                <span class="text-strong text-dark text-md"><?= $video->ai_safety_score ?? 'N/A' ?>/100</span>
                            </div>
                            <?php if(isset($video->ai_safety_score)): ?>
                                <div class="ai-score-track"><div class="ai-score-fill" style="width: <?= $video->ai_safety_score ?>%;"></div></div>
                            <?php else: ?>
                                <div class="ai-score-track"><div class="ai-score-fill bg-muted-soft" style="width: 0%;"></div></div>
                            <?php endif; ?>

                            <div class="check-row"><span class="text-muted"><i class="fas fa-check-circle text-success-custom mr-2"></i> Audio Check</span><span class="text-dark">Passed</span></div>
                            <div class="check-row"><span class="text-muted"><i class="fas fa-check-circle text-success-custom mr-2"></i> Visual Match</span><span class="text-dark">Passed</span></div>
                            <div class="check-row"><span class="text-muted"><i class="fas fa-check-circle text-success-custom mr-2"></i> Policy Check</span><span class="text-dark">Passed</span></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-12 col-lg-6 col-md-6 mb-4">
                    <div class="deep-dive-card h-100 mb-0">
                        <div class="card-title-head">Reports & Violations</div>
                        <div class="p-3">
                            <div class="rep-row"><span class="text-muted"><i class="fas fa-flag text-danger-custom mr-2"></i> Total Reports</span><span class="text-dark"><?= count($reports) ?></span></div>
                            <div class="rep-row"><span class="text-muted"><i class="fas fa-exclamation-triangle text-warning-custom mr-2"></i> Active Strikes</span><span class="text-dark"><?= !empty($video_strike) ? '1' : '0' ?></span></div>
                            <div class="rep-row"><span class="text-muted"><i class="fas fa-clipboard-list text-primary-custom mr-2"></i> Last Report</span><span class="text-dark"><?= !empty($reports) ? date('M d, Y', strtotime($reports[0]->created_at)) : 'None' ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?= $this->include('admin/moderation/strikes/modal_snippet') ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dynamic Theme Color Fetcher for JS
    const styles = getComputedStyle(document.documentElement);
    const colorPrimary = styles.getPropertyValue('--primary-blue').trim() || '#4361ee';
    const colorGreen = styles.getPropertyValue('--accent-green').trim() || '#2ec4b6';
    const colorOrange = styles.getPropertyValue('--accent-orange').trim() || '#ff9f1c';
    const colorRed = styles.getPropertyValue('--accent-red').trim() || '#e63946';
    const colorPurple = '#7209b7'; // fallback
    const colorBorder = styles.getPropertyValue('--border-soft').trim() || '#eaedf2';
    const colorBg = styles.getPropertyValue('--bg-surface').trim() || '#ffffff';
    const colorText = styles.getPropertyValue('--text-dark').trim() || '#1a1c23';
    const colorPrimarySoft = 'rgba(67, 97, 238, 0.1)';

    // Graph 1: Performance Line Chart
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
                    { label: 'Views', data: viewData, borderColor: colorPrimary, backgroundColor: colorPrimarySoft, borderWidth: 2, tension: 0.4, fill: true, pointRadius: 3 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { grid: { borderDash: [5, 5], color: colorBorder }, ticks: { font: { size: 10 } } }
                }
            }
        });
    } else {
        document.getElementById('performanceChart').outerHTML = '<div class="text-center text-muted p-4 small">No trend data available for the last 7 days.</div>';
    }

    // Graph 2: Device Donut Chart
    const deviceStats = <?= json_encode($device_stats) ?>;
    if(deviceStats && deviceStats.length > 0) {
        const ctxDonut = document.getElementById('deviceChart').getContext('2d');
        let devLabels = []; let devData = []; let devColors = [colorPrimary, colorGreen, colorOrange, colorPurple];
        let legendHtml = '';

        deviceStats.forEach((item, index) => {
            let labelName = item.device_type ? item.device_type.charAt(0).toUpperCase() + item.device_type.slice(1) : 'Unknown';
            let pct = ((item.count / <?= max(1, $video->views_count) ?>) * 100).toFixed(1);
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
    window.updateStatus = function(s) {
        Swal.fire({
            title: 'Change Visibility?',
            text: "Are you sure you want to change status to: " + s.toUpperCase(),
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: colorPrimary,
            cancelButtonColor: colorBorder,
            confirmButtonText: 'Yes, update',
            background: colorBg,
            color: colorText
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= base_url('admin/videos/update_status') ?>", {id: "<?= $video->id ?>", status: s}, function() { location.reload(); });
            }
        });
    }

    window.deleteVideo = function(id) {
        Swal.fire({
            title: 'Permanently Delete?',
            text: "This action cannot be undone.",
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: colorRed,
            cancelButtonColor: colorBorder,
            confirmButtonText: 'Yes, Delete',
            background: colorBg,
            color: colorText
        }).then((result) => {
            if (result.isConfirmed) { window.location.href = "<?= base_url('admin/videos/delete/') ?>" + id; }
        });
    }

    window.blacklistHash = function(id) {
        Swal.fire({
            title: 'Blacklist Hash?',
            text: "Blocks future uploads of this file.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: colorRed,
            cancelButtonColor: colorBorder,
            confirmButtonText: 'Yes, Blacklist',
            background: colorBg,
            color: colorText
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("<?= base_url('admin/videos/blacklist/') ?>"+id, {reason: "Policy Violation"}, function() { location.reload(); });
            }
        });
    }

    window.openStrikeModal = function(t, id, c_id) {
        $('#strike_content_type').val(t); 
        $('#strike_content_id').val(id); 
        $('#strike_channel_id').val(c_id); 
        $('#addStrikeModal').modal('show');
    }
});
</script>

<?= $this->endSection() ?>
