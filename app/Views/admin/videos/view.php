<?= $this->extend('admin/layout/main') ?>
<?= $this->section('content') ?>

<style>
    /* 🎭 THEATER HEADER (Responsive Fix) */
    .theater-header { position: relative; background: #000; width: 100%; padding: 40px 0; overflow: hidden; }
    .theater-background {
        position: absolute; top: 0; left: 0; width: 100%; height: 100%;
        background: url('<?= get_media_url($video->thumbnail_url, 'video') ?>') center center / cover;
        filter: blur(40px) brightness(0.3); opacity: 0.7; transform: scale(1.1);
    }
    .theater-container { position: relative; max-width: 1000px; margin: 0 auto; z-index: 2; padding: 0 15px; }
    .theater-player-frame { 
        background: #000; border-radius: 12px; overflow: hidden; 
        box-shadow: 0 25px 60px rgba(0,0,0,0.6); border: 2px solid #333; 
        aspect-ratio: 16/9; width: 100%;
    }

    /* 🏷️ META STRIP & ACTIONS (No Sticky Header) */
    .meta-data-strip { background: #fff; padding: 15px 30px; border-bottom: 1px solid var(--border-soft); box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
    .video-title-heavy { font-size: 1.1rem; font-weight: 800; color: #111; margin-bottom: 6px; text-transform: uppercase; line-height: 1.3; }

    /* 🔥 HORIZONTAL BADGES (Never wraps, horizontal scroll on mobile) */
    .top-badges-scroll {
        display: flex; gap: 8px; flex-wrap: nowrap; overflow-x: auto; white-space: nowrap; padding-bottom: 4px; scrollbar-width: none; align-items: center;
    }
    .top-badges-scroll::-webkit-scrollbar { display: none; }

    /* 📊 HEAVY ANALYTICS CARDS */
    .heavy-card { background: #fff; border-radius: 12px; margin-bottom: 20px; box-shadow: var(--card-shadow); height: auto !important; overflow: hidden; border: 1px solid var(--border-soft); }
    .heavy-header { background: #f8f9fa; padding: 14px 20px; border-bottom: 1px solid var(--border-soft); font-size: 11px; font-weight: 800; letter-spacing: 0.5px; text-transform: uppercase; color: var(--text-dark); }
    
    .data-row-heavy { background: #fdfdfd; border: 1px solid #f6f6f6; padding: 12px; border-radius: 8px; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
    .stat-label-heavy { font-size: 10px; color: var(--text-muted); font-weight: 800; text-transform: uppercase; }
    .stat-value-heavy { font-size: 14px; font-weight: 800; color: #333; }

    .gauge-track { background: #eee; height: 6px; border-radius: 10px; overflow: hidden; width: 100%; margin-top: 5px; }
    .gauge-fill { height: 100%; border-radius: 10px; transition: 1.5s ease-in-out; }

    /* 💬 COMMENTS */
    .comment-item { padding: 12px 20px; border-bottom: 1px solid #f4f4f4; display: flex; align-items: flex-start; gap: 12px; }
    .comment-item img { width: 36px; height: 36px; border-radius: 8px; object-fit: cover; }

    /* 🔥 INTERACTION PILLS (Mobile Horizontal Scroll) */
    .interaction-grid {
        display: flex; gap: 10px; flex-wrap: nowrap; overflow-x: auto; padding-bottom: 5px; scrollbar-width: none; 
    }
    .interaction-grid::-webkit-scrollbar { display: none; } 
    .interaction-pill {
        flex: 1 1 auto; min-width: 100px; background: #f8f9fa; border: 1px solid var(--border-soft); border-radius: 8px; padding: 10px; text-align: center; display: flex; flex-direction: column; justify-content: center;
    }
    
    /* 📱 MOBILE RESPONSIVE FIXES */
    @media (max-width: 768px) {
        .theater-header { padding: 15px 0; }
        .meta-data-strip { padding: 15px; }
        .meta-data-strip .d-flex.flex-wrap { flex-direction: column; align-items: flex-start !important; gap: 15px; }
        
        /* Action buttons mobile tweaks */
        .meta-data-strip .btn-strike-mobile { width: 100%; display: block; padding: 12px 0; font-size: 12px !important; }
        .action-buttons-mobile { flex-direction: column !important; gap: 10px !important; margin-top: 15px; }
        .action-buttons-mobile button { width: 100%; }

        .video-title-heavy { font-size: 1rem; }
        .stat-value-heavy { font-size: 12px; }
        .heavy-header { font-size: 10px; padding: 10px 15px; }
        .desktop-border-right { border-right: none !important; border-bottom: 1px solid var(--border-soft); padding-bottom: 15px; margin-bottom: 15px; }
        .interaction-grid { padding: 5px; }
        .interaction-pill { min-width: 80px; padding: 8px 5px; }
        .interaction-pill .stat-label-heavy { font-size: 9px; }
        .interaction-pill h5 { font-size: 14px !important; margin-top: 3px; }
        .claim-box-mobile-col { border-right: none !important; border-bottom: 1px solid #e9ecef; padding-bottom: 10px; margin-bottom: 10px; }
    }
    @media (min-width: 769px) {
        .desktop-border-right { border-right: 1px solid var(--border-soft); }
        .claim-box-mobile-col { border-right: 1px solid #e9ecef; }
        .action-buttons-mobile { flex-direction: row !important; gap: 10px; }
    }
</style>

<div class="theater-header">
    <div class="theater-background"></div>
    <div class="theater-container">
        <div class="theater-player-frame">
            <video width="100%" height="100%" controls poster="<?= get_media_url($video->thumbnail_url, 'video') ?>">
                <source src="<?= get_media_url($video->video_url, 'video') ?>" type="video/mp4">
            </video>
        </div>
    </div>
</div>

<div class="meta-data-strip">
    <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center">
        <div class="mb-2 mb-md-0 w-100 w-md-auto" style="overflow: hidden;">
            <h3 class="video-title-heavy text-truncate"><?= esc($video->title) ?></h3>
            
            <div class="top-badges-scroll">
                <span class="badge badge-<?= $video->status == 'published' ? 'success' : 'warning' ?>" style="font-size: 10px; padding: 5px 8px;"><?= strtoupper($video->status) ?></span>
                <span class="badge badge-<?= $video->visibility == 'public' ? 'info' : 'dark' ?>" style="font-size: 10px; padding: 5px 8px;"><?= strtoupper($video->visibility) ?></span>
                <span class="badge" style="background: rgba(93, 120, 255, 0.1); color: var(--primary-blue); font-size: 10px; border: 1px solid var(--primary-blue); padding: 5px 10px;"><?= strtoupper($video->category) ?></span>
                <span class="small text-muted font-weight-bold" style="font-size: 11px;">ID: #<?= $video->unique_id ?></span>
                <span class="small text-muted font-weight-bold" style="font-size: 11px;"><i class="far fa-eye ml-1 mr-1"></i> <?= number_format($video->views_count) ?> Views</span>
            </div>
        </div>
        
        <div class="w-100 w-md-auto mt-2 mt-md-0">
            <button class="btn btn-danger font-weight-bold px-4 btn-strike-mobile shadow-sm" style="font-size: 11px; border-radius: 8px;" onclick="openStrikeModal('VIDEO', '<?= $video->id ?>', '<?= $video->channel_id ?>')"><i class="fas fa-bolt mr-1"></i> ISSUE STRIKE</button>
        </div>
    </div>
</div>

<div class="container-fluid px-2 px-md-4 mt-4">
    <div class="row">
        
        <div class="col-lg-8">
            
            <?php if ($video->copyright_status === 'CLAIMED' || (!empty($video_strike) && $video_strike->type === 'CLAIM')): ?>
                <div class="heavy-card" style="border: 1px solid #ffc107; background: #fffcf5;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                            <div class="d-none d-md-block mt-1">
                                <i class="fas fa-exclamation-triangle text-warning" style="font-size: 28px;"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-exclamation-triangle text-warning d-inline-block d-md-none mr-2" style="font-size: 18px;"></i>
                                    <h6 class="font-weight-bold text-dark mb-0" style="font-size: 15px;">Copyright Claim: Revenue Sharing Active</h6>
                                </div>
                                <p class="small text-muted mb-3" style="line-height: 1.5;">
                                    This video contains copyrighted material. Monetization is restricted for the uploader, and estimated ad revenue is being routed to the original owner.
                                </p>
                                
                                <div class="bg-white border rounded p-3">
                                    <div class="row align-items-center text-center text-md-left">
                                        <div class="col-md-4 claim-box-mobile-col">
                                            <span class="stat-label-heavy d-block text-muted mb-1">Claimant / Owner</span>
                                            <span class="font-weight-bold text-dark" style="font-size: 12px;">
                                                <i class="fas fa-music mr-1 text-primary"></i> <?= !empty($rev_share) ? esc($rev_share->claimant_name) : 'Content ID System' ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 claim-box-mobile-col pl-md-3">
                                            <span class="stat-label-heavy d-block text-muted mb-1">Revenue Split</span>
                                            <span class="font-weight-bold text-success" style="font-size: 12px;">
                                                <?= !empty($rev_share) ? "Active Split" : 'Pending Verification' ?>
                                            </span>
                                        </div>
                                        <div class="col-md-4 pl-md-3">
                                            <span class="stat-label-heavy d-block text-muted mb-1">Visibility</span>
                                            <span class="badge badge-success px-2 py-1">Visible</span> 
                                            <span class="badge badge-warning text-dark px-2 py-1">Revenue Diverted</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            
            <?php elseif ($video->copyright_status === 'STRIKED' || (!empty($video_strike) && $video_strike->type === 'STRIKE')): ?>
                <div class="heavy-card" style="border: 1px solid #dc3545; background: #fff5f5;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                            <div class="d-none d-md-block mt-1">
                                <i class="fas fa-ban text-danger" style="font-size: 28px;"></i>
                            </div>
                            <div class="w-100">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-ban text-danger d-inline-block d-md-none mr-2" style="font-size: 18px;"></i>
                                    <h6 class="font-weight-bold text-danger mb-0" style="font-size: 15px;">Content Strike Issued</h6>
                                </div>
                                <p class="small text-danger mb-3" style="line-height: 1.5;">This video violates community guidelines or severe copyright policies. The channel has been penalized.</p>
                                <div class="bg-white border border-danger rounded p-3">
                                    <div class="row align-items-center text-center text-md-left">
                                        <div class="col-md-6 claim-box-mobile-col">
                                            <span class="stat-label-heavy d-block text-muted mb-1">Violation Reason</span>
                                            <span class="font-weight-bold text-dark" style="font-size: 12px;"><?= !empty($video_strike) ? esc($video_strike->reason) : 'Policy Violation' ?></span>
                                        </div>
                                        <div class="col-md-6 pl-md-3">
                                            <span class="stat-label-heavy d-block text-muted mb-1">Trust Points Deducted</span>
                                            <span class="badge badge-danger px-2 py-1">-<?= !empty($video_strike) ? $video_strike->severity_points : '10' ?> Points</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif (!empty($video_strike) && $video_strike->type === 'WARNING'): ?>
                <div class="heavy-card" style="border: 1px solid #fd7e14; background: #fff9f4;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle text-warning mr-3" style="font-size: 24px;"></i>
                            <div>
                                <h6 class="font-weight-bold text-dark mb-1">Community Guidelines Warning</h6>
                                <p class="small text-muted mb-0">Reason: <?= esc($video_strike->reason) ?> (No points deducted yet).</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="heavy-card">
                <div class="heavy-header"><i class="fas fa-chart-line mr-2 text-primary"></i> Real-time Analytics (Section D)</div>
                <div class="card-body p-3 p-md-4">
                    <div class="row mb-4">
                        <div class="col-md-4 mb-4 mb-md-0 desktop-border-right">
                            <span class="stat-label-heavy">Watch Completion</span>
                            <div class="stat-value-heavy" style="font-size: 20px;"><?= $stats['avg_completion'] ?>%</div>
                            <div class="gauge-track"><div class="gauge-fill" style="background: var(--primary-blue); width: <?= $stats['avg_completion'] ?>%"></div></div>
                        </div>
                        <div class="col-md-4 mb-4 mb-md-0 desktop-border-right px-md-4">
                            <span class="stat-label-heavy">Interaction Rate</span>
                            <div class="stat-value-heavy" style="font-size: 20px;"><?= $stats['engagement_rate'] ?>%</div>
                            <div class="gauge-track"><div class="gauge-fill" style="background: var(--accent-green); width: <?= min(100, $stats['engagement_rate']*5) ?>%"></div></div>
                        </div>
                        <div class="col-md-4 pl-md-4">
                            <span class="stat-label-heavy">Viral Potential</span>
                            <div class="stat-value-heavy" style="font-size: 20px;"><?= $stats['viral_percent'] ?>%</div>
                            <div class="gauge-track"><div class="gauge-fill" style="background: var(--accent-orange); width: <?= $stats['viral_percent'] ?>%"></div></div>
                        </div>
                    </div>
                    
                    <div class="interaction-grid mt-2 pt-3 border-top">
                        <div class="interaction-pill">
                            <span class="stat-label-heavy">Likes</span>
                            <h5 class="font-weight-bold text-success mb-0"><?= format_number_k($video->likes_count) ?></h5>
                        </div>
                        <div class="interaction-pill">
                            <span class="stat-label-heavy">Dislikes</span>
                            <h5 class="font-weight-bold text-danger mb-0"><?= format_number_k($stats['dislikes']) ?></h5>
                        </div>
                        <div class="interaction-pill">
                            <span class="stat-label-heavy">Comments</span>
                            <h5 class="font-weight-bold text-dark mb-0"><?= format_number_k($video->comments_count) ?></h5>
                        </div>
                        <div class="interaction-pill">
                            <span class="stat-label-heavy">Shares</span>
                            <h5 class="font-weight-bold text-primary mb-0"><?= format_number_k($video->shares_count) ?></h5>
                        </div>
                    </div>
                </div>
            </div>

            <div class="heavy-card">
                <div class="heavy-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-hand-holding-usd mr-2 text-success"></i> Earnings & Revenue (Section C)</span>
                    <span class="badge badge-<?= $video->monetization_enabled ? 'success' : 'secondary' ?>"><?= $video->monetization_enabled ? 'MONETIZED' : 'ADS OFF' ?></span>
                </div>
                <div class="card-body p-3 p-md-4">
                    <div class="row">
                        <div class="col-md-6 desktop-border-right">
                            <div class="data-row-heavy"><span class="stat-label-heavy">Ad Impressions</span><span class="stat-value-heavy"><?= number_format($ad_stats->total_imps ?? 0) ?></span></div>
                            <div class="data-row-heavy"><span class="stat-label-heavy">Ad Views</span><span class="stat-value-heavy"><?= number_format($ad_views_count ?? 0) ?></span></div>
                            <div class="data-row-heavy"><span class="stat-label-heavy">Ad Clicks (CTR)</span><span class="stat-value-heavy"><?= $ad_clicks ?> (<?= ($ad_stats->total_imps > 0) ? round(($ad_clicks/$ad_stats->total_imps)*100, 2) : 0 ?>%)</span></div>
                        </div>
                        <div class="col-md-6 pl-md-4">
                            <div class="data-row-heavy" style="background: rgba(40, 167, 69, 0.05); border: 1px dashed #28a745;">
                                <span class="stat-label-heavy" style="color: #28a745;">Total Real Earnings</span>
                                <span class="stat-value-heavy" style="color: #28a745; font-size: 18px;">₹<?= number_format($creator_earnings->total_earnings ?? 0, 2) ?></span>
                            </div>
                            <?php if($rev_share): ?>
                                <div class="alert alert-info py-2 small mt-2 mb-0"><i class="fas fa-handshake mr-1"></i> Revenue split active with original creator.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="heavy-card">
                <div class="heavy-header"><i class="fas fa-search mr-2 text-info"></i> Discovery & SEO Engine (Section E)</div>
                <div class="card-body p-3 p-md-4">
                    <span class="stat-label-heavy mb-2 d-block">Video Description</span>
                    <div class="p-3 bg-light rounded small text-dark mb-3" style="line-height: 1.6; max-height: 150px; overflow-y: auto;">
                        <?= nl2br(esc($video->description)) ?: '<span class="text-muted">No description provided.</span>' ?>
                    </div>
                    
                    <span class="stat-label-heavy mb-2 d-block">Hashtags & Keywords</span>
                    <div class="d-flex flex-wrap gap-2">
                        <?php 
                            if($video->tags): 
                                foreach(explode(',', $video->tags) as $tag): 
                        ?>
                            <span class="badge" style="background: #eef2f7; color: var(--primary-blue); border: 1px solid #dce4ee; font-size: 11px; padding: 5px 10px;">#<?= trim($tag) ?></span>
                        <?php endforeach; else: ?>
                            <span class="small text-muted">No tags indexed.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="heavy-card">
                <div class="heavy-header"><i class="fas fa-comments mr-2 text-warning"></i> Audience Interaction Stream (Section F)</div>
                <div class="card-body p-0">
                    <?php if($comments): foreach($comments as $c): ?>
                    <div class="comment-item">
                        <img src="<?= get_media_url($c->avatar, 'profile') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= $c->username ?>&background=f4f7fa&color=5d78ff';">
                        <div class="w-100">
                            <div class="d-flex justify-content-between">
                                <span class="font-weight-bold text-dark" style="font-size: 11px;">@<?= strtoupper($c->username) ?></span>
                                <span class="text-muted" style="font-size: 9px;"><?= date('d M, Y', strtotime($c->created_at)) ?></span>
                            </div>
                            <p class="mb-0 mt-1 text-dark" style="font-size: 12px; line-height: 1.4;"><?= esc($c->content) ?></p>
                        </div>
                    </div>
                    <?php endforeach; else: ?>
                        <div class="p-4 text-center text-muted small font-weight-bold">No comments yet.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="heavy-card" style="border: 2px solid var(--accent-red); margin-top: 30px;">
                <div class="heavy-header bg-light text-danger" style="font-size: 12px;"><i class="fas fa-shield-alt mr-2"></i> Admin Action Center</div>
                <div class="card-body p-3 p-md-4">
                    <div class="row align-items-center">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="input-group shadow-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold bg-white text-muted" style="font-size: 11px; border-radius: 8px 0 0 8px;">VISIBILITY</span>
                                </div>
                                <select id="visibility_selector" class="form-control font-weight-bold" style="height: 45px; font-size: 13px;">
                                    <option value="public" <?= $video->visibility == 'public' ? 'selected' : '' ?>>PUBLIC</option>
                                    <option value="private" <?= $video->visibility == 'private' ? 'selected' : '' ?>>PRIVATE</option>
                                    <option value="unlisted" <?= $video->visibility == 'unlisted' ? 'selected' : '' ?>>UNLISTED</option>
                                    <option value="blocked" <?= $video->visibility == 'blocked' ? 'selected' : '' ?>>BLOCKED</option>
                                </select>
                                <div class="input-group-append">
                                    <button onclick="updateVisibilityFromSelect()" class="btn btn-primary font-weight-bold px-3" style="height: 45px; border-radius: 0 8px 8px 0;"><i class="fas fa-save mr-1"></i> UPDATE</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 d-flex justify-content-md-end action-buttons-mobile">
                            <button onclick="updateStatus('blocked')" class="btn btn-dark font-weight-bold flex-grow-1" style="height: 45px; border-radius: 8px;"><i class="fas fa-ban mr-1"></i> Quick Block</button>
                            <button onclick="deleteVideo('<?= $video->id ?>')" class="btn btn-danger font-weight-bold flex-grow-1" style="height: 45px; border-radius: 8px;"><i class="fas fa-trash-alt mr-1"></i> Delete Video</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="heavy-card" style="border: 2px solid rgba(93, 120, 255, 0.2);">
                <div class="heavy-header">Creator Health</div>
                <div class="card-body text-center p-3 p-md-4">
                    <img src="<?= get_media_url($video->user_avatar, 'profile') ?>" class="rounded-circle mb-3 border p-1" style="width: 80px; height: 80px; object-fit: cover;">
                    <h5 class="font-weight-bold mb-0" style="font-size: 15px;">@<?= strtoupper($video->username) ?></h5>
                    <p class="small text-muted font-weight-bold mb-3"><?= strtoupper($video->channel_name) ?></p>
                    <div class="row border-top pt-3">
                        <div class="col-6 border-right">
                            <span class="stat-label-heavy">Trust Score</span>
                            <div class="h4 font-weight-bold mb-0 <?= ($video->trust_score < 40) ? 'text-danger' : 'text-success' ?>"><?= $video->trust_score ?></div>
                        </div>
                        <div class="col-6">
                            <span class="stat-label-heavy">Strikes</span>
                            <div class="h4 font-weight-bold mb-0 text-danger"><?= $video->strikes_count ?></div>
                        </div>
                    </div>
                    <a href="<?= base_url('admin/channels/view/'.$video->channel_id) ?>" class="btn btn-light border btn-block btn-sm mt-3 font-weight-bold text-dark">VIEW FULL CHANNEL</a>
                </div>
            </div>

            <div class="heavy-card" style="border-top: 3px solid var(--primary-blue);">
                <div class="heavy-header bg-light"><i class="fas fa-fingerprint mr-2 text-primary"></i> Copyright Engine (Section G)</div>
                <div class="card-body p-3">
                    <div class="data-row-heavy"><span class="stat-label-heavy">Video Hash</span><code class="small text-primary text-truncate" style="max-width: 100px;"><?= $video->video_hash ?: 'N/A' ?></code></div>
                    <div class="data-row-heavy"><span class="stat-label-heavy">Copyright Status</span><span class="badge badge-<?= ($video->copyright_status == 'NONE') ? 'success' : 'danger' ?>"><?= $video->copyright_status ?></span></div>
                    
                    <?php if(!empty($video->original_video_url)): ?>
                        <div class="mt-3 p-2 bg-light border rounded">
                            <span class="stat-label-heavy d-block mb-2 text-danger">Matched Original Content</span>
                            <video width="100%" class="rounded mb-2" controls poster="<?= get_media_url($video->original_thumbnail, 'video') ?>"><source src="<?= get_media_url($video->original_video_url, 'video') ?>" type="video/mp4"></video>
                            <div class="small mb-1">Owner: <b><?= $video->original_owner_name ?></b></div>
                        </div>
                    <?php endif; ?>
                    
                    <button class="btn btn-outline-danger btn-block btn-xs font-weight-bold mt-3 py-2" onclick="blacklistHash('<?= $video->id ?>')"><i class="fas fa-ban mr-1"></i> BLACKLIST CONTENT HASH</button>
                </div>
            </div>

            <div class="heavy-card" style="border-top: 3px solid var(--accent-red);">
                <div class="heavy-header d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-flag mr-1 text-danger"></i> User Reports (Violation Queue)</span> 
                    <span class="badge badge-danger"><?= count($reports) ?> Reports</span>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 small">
                        <tbody>
                            <?php foreach($reports as $r): ?>
                            <tr>
                                <td class="p-2 pl-3"><b><?= $r->reason ?></b><br><span class="text-muted" style="font-size: 9px;">BY: @<?= strtoupper($r->reporter_name) ?></span></td>
                                <td class="p-2 pr-3 text-right align-middle"><span class="badge" style="background: rgba(253,57,122,0.1); color: var(--accent-red); font-size: 9px;"><?= strtoupper($r->status) ?></span></td>
                            </tr>
                            <?php endforeach; if(empty($reports)) echo "<tr><td colspan='2' class='text-center py-4 text-muted font-weight-bold'>Clean Security Record.</td></tr>"; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="heavy-card">
                <div class="heavy-header">Machine Logs & Sentiment</div>
                <div class="card-body p-3">
                    <div class="data-row-heavy border-0 py-1 mb-2"><span class="stat-label-heavy text-dark">FFmpeg Processing</span><span class="badge <?= ($process_log?->status == 'completed') ? 'badge-success' : 'badge-warning' ?>" style="font-size: 9px;"><?= strtoupper($process_log?->status ?? 'N/A') ?></span></div>
                    <div class="data-row-heavy border-0 py-1 mb-2"><span class="stat-label-heavy text-dark">Duration / Scheduled</span><span class="stat-value-heavy" style="font-size: 11px;"><?= $video->duration ?>s / <?= $video->scheduled_at ?: 'Now' ?></span></div>
                    <hr class="my-2">
                    <div class="data-row-heavy border-0 py-1 mb-0"><span class="stat-label-heavy text-danger">Not Interested</span><span class="font-weight-bold"><?= $feedback['Not Interested'] ?? 0 ?></span></div>
                    <div class="data-row-heavy border-0 py-1 mb-0"><span class="stat-label-heavy text-danger">Hide Channel</span><span class="font-weight-bold"><?= $feedback['Hide Creator'] ?? 0 ?></span></div>
                </div>
            </div>

        </div>
    </div>
</div>

<?= $this->include('admin/moderation/strikes/modal_snippet') ?>

<script>
function updateStatus(s) {
    Swal.fire({
        title: 'Confirm Visibility Change',
        text: "Are you sure you want to change this video's visibility to: " + s.toUpperCase() + "?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#343a40', 
        cancelButtonColor: '#abb3ba',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("<?= base_url('admin/videos/update_status') ?>", {id: "<?= $video->id ?>", status: s}, function() { 
                location.reload(); 
            });
        }
    });
}

function updateVisibilityFromSelect() {
    let vis = document.getElementById('visibility_selector').value;
    updateStatus(vis);
}

function deleteVideo(id) {
    Swal.fire({
        title: 'DANGER: Permanently Delete Video?',
        text: "This will completely wipe the video, views, likes, and all associated data. This action CANNOT be undone!",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Wipe Everything!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?= base_url('admin/videos/delete/') ?>" + id;
        }
    });
}

function blacklistHash(id) {
    Swal.fire({
        title: 'DANGER: Blacklist Hash?',
        text: "This blocks future uploads of this exact video file. This action cannot be undone.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonColor: '#dc3545', 
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, Blacklist it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.post("<?= base_url('admin/videos/blacklist/') ?>"+id, {reason: "Copyright / Policy Violation"}, function() { 
                Swal.fire(
                    'Banned!',
                    'Content Hash Banned Successfully.',
                    'success'
                ).then(() => {
                    location.reload(); 
                });
            });
        }
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
