<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: #000 !important; height: 45px; border-radius: 8px; font-weight: 600; 
    }
    .form-control-pro:focus { border-color: var(--primary-blue) !important; box-shadow: var(--card-shadow); }
    
    .label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 6px; display: block; letter-spacing: 0.5px; }
    
    /* Config Layout Helpers */
    .config-box { background: #f4f7fa; padding: 20px; border-radius: 12px; border: 1px solid var(--border-soft); height: 100%; }
    .config-title { font-size: 13px; font-weight: 800; color: #000; margin-bottom: 15px; border-bottom: 2px solid #fff; padding-bottom: 8px; display: flex; align-items: center; }

    /* Floating Save Bar */
    .save-bar-pro { 
        position: fixed; bottom: 0; right: 0; left: 250px; background: rgba(255, 255, 255, 0.9); 
        backdrop-filter: blur(10px); border-top: 1px solid var(--border-soft); z-index: 1000; 
        padding: 15px 40px; display: flex; align-items: center; justify-content: flex-end;
    }
    .btn-save-pro { background: var(--primary-blue); color: #fff; border-radius: 10px; height: 48px; padding: 0 35px; font-weight: 800; border: none; box-shadow: 0 4px 15px rgba(93, 120, 255, 0.2); }
    
    @media (max-width: 992px) { .save-bar-pro { left: 0 !important; justify-content: center; } }
</style>

<div class="content-header pt-4">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -1px;"><i class="fas fa-sliders-h mr-2 text-primary"></i> System Configuration</h1>
        <p class="text-muted small font-weight-bold">MANAGE ALGORITHM WEIGHTS, REWARDS AND SAFETY SCORE</p>
    </div>
</div>

<section class="content pb-5">
    <div class="container-fluid">
        <form action="<?= base_url('admin/settings/points/update') ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                
                <div class="col-12">
                    <div class="card card-pro">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-eye text-primary mr-2"></i> VIEW COUNTING LOGIC</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="label-pro">Min Seconds to Count as View</label>
                                    <div class="input-group">
                                        <input type="number" name="min_view_sec" class="form-control-pro" value="<?= $settings['min_view_sec'] ?? 5 ?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text bg-light font-weight-bold" style="border: 1px solid var(--border-soft); border-left:0; border-radius: 0 8px 8px 0;">Sec</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card card-pro">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-coins text-success mr-2"></i> MONETIZATION & REWARDS</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4 mb-md-0">
                                    <div class="config-box">
                                        <div class="config-title"><i class="fas fa-video mr-2 text-primary"></i> Long Form Videos</div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="label-pro">Min. Seconds for Payout</label>
                                                <input type="number" name="min_qualified_sec_video" class="form-control-pro" value="<?= $settings['min_qualified_sec_video'] ?? 15 ?>">
                                            </div>
                                            <div class="col-6 mb-3"><label class="label-pro">Qualified View (Pts)</label><input type="number" step="0.0001" name="point_view_qualified_video" class="form-control-pro" value="<?= $settings['point_view_qualified_video'] ?? 0.50 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Like Reward (Pts)</label><input type="number" step="0.0001" name="point_like_video" class="form-control-pro" value="<?= $settings['point_like_video'] ?? 0.20 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Comment (Pts)</label><input type="number" step="0.0001" name="point_comment_video" class="form-control-pro" value="<?= $settings['point_comment_video'] ?? 0.30 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Share (Pts)</label><input type="number" step="0.0001" name="point_share_video" class="form-control-pro" value="<?= $settings['point_share_video'] ?? 0.50 ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="config-box">
                                        <div class="config-title"><i class="fas fa-bolt mr-2 text-warning"></i> Short Reels</div>
                                        <div class="row">
                                            <div class="col-12 mb-3">
                                                <label class="label-pro">Min. Seconds for Payout</label>
                                                <input type="number" name="min_qualified_sec_reel" class="form-control-pro" value="<?= $settings['min_qualified_sec_reel'] ?? 5 ?>">
                                            </div>
                                            <div class="col-6 mb-3"><label class="label-pro">Qualified View (Pts)</label><input type="number" step="0.0001" name="point_view_qualified_reel" class="form-control-pro" value="<?= $settings['point_view_qualified_reel'] ?? 0.10 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Like Reward (Pts)</label><input type="number" step="0.0001" name="point_like_reel" class="form-control-pro" value="<?= $settings['point_like_reel'] ?? 0.05 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Comment (Pts)</label><input type="number" step="0.0001" name="point_comment_reel" class="form-control-pro" value="<?= $settings['point_comment_reel'] ?? 0.10 ?>"></div>
                                            <div class="col-6 mb-3"><label class="label-pro">Share (Pts)</label><input type="number" step="0.0001" name="point_share_reel" class="form-control-pro" value="<?= $settings['point_share_reel'] ?? 0.20 ?>"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-pro">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-chart-line text-info mr-2"></i> ALGORITHM RANKING</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3"><label class="label-pro">Basic View Weight</label><input type="number" step="0.01" name="viral_weight_view" class="form-control-pro" value="<?= $settings['viral_weight_view'] ?? 0.01 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">Retention Weight (15s+)</label><input type="number" step="0.01" name="viral_weight_qualified_view" class="form-control-pro" value="<?= $settings['viral_weight_qualified_view'] ?? 5.00 ?>"></div>
                                <div class="col-4 mb-3"><label class="label-pro">Like</label><input type="number" step="0.01" name="viral_weight_like" class="form-control-pro" value="<?= $settings['viral_weight_like'] ?? 1.00 ?>"></div>
                                <div class="col-4 mb-3"><label class="label-pro">Comment</label><input type="number" step="0.01" name="viral_weight_comment" class="form-control-pro" value="<?= $settings['viral_weight_comment'] ?? 2.00 ?>"></div>
                                <div class="col-4 mb-3"><label class="label-pro">Share</label><input type="number" step="0.01" name="viral_weight_share" class="form-control-pro" value="<?= $settings['viral_weight_share'] ?? 5.00 ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card card-pro">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-shield-alt text-danger mr-2"></i> SAFETY & TRUST SCORE</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3"><label class="label-pro">Min. Score to Apply</label><input type="number" name="trust_min_to_apply" class="form-control-pro" value="<?= $settings['trust_min_to_apply'] ?? 80 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">Min. Score to Keep</label><input type="number" name="trust_min_to_keep" class="form-control-pro" value="<?= $settings['trust_min_to_keep'] ?? 50 ?>"></div>

                                <div class="col-12 my-2"><div class="label-pro text-primary" style="margin-bottom: 10px; border-bottom: 1px solid #eee;">Recovery Rewards (+)</div></div>
                                
                                <div class="col-6 mb-3"><label class="label-pro">Safety Quiz Bonus</label><input type="number" name="points_recovery_quiz" class="form-control-pro" value="<?= $settings['points_recovery_quiz'] ?? 5 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">15D Clean Streak</label><input type="number" name="points_clean_streak_15d" class="form-control-pro" value="<?= $settings['points_clean_streak_15d'] ?? 15 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">Audience Feedback</label><input type="number" name="points_positive_feedback_ratio" class="form-control-pro" value="<?= $settings['points_positive_feedback_ratio'] ?? 8 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">Genuine Engagement</label><input type="number" name="points_genuine_engagement" class="form-control-pro" value="<?= $settings['points_genuine_engagement'] ?? 4 ?>"></div>

                                <div class="col-12 my-2"><div class="label-pro text-danger" style="margin-bottom: 10px; border-bottom: 1px solid #eee;">System Penalties (-)</div></div>
                                
                                <div class="col-12 mb-3"><label class="label-pro text-danger">Major Channel Strike</label><input type="number" name="trust_penalty_channel_strike" class="form-control-pro" style="border-color: #ff4444 !important;" value="<?= $settings['trust_penalty_channel_strike'] ?? 35 ?>"></div>
                                
                                <div class="col-6 mb-3"><label class="label-pro text-danger">Video Strike</label><input type="number" name="trust_penalty_video_strike" class="form-control-pro" value="<?= $settings['trust_penalty_video_strike'] ?? 5 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro text-danger">Reel Strike</label><input type="number" name="trust_penalty_reel_strike" class="form-control-pro" value="<?= $settings['trust_penalty_reel_strike'] ?? 3 ?>"></div>

                                <div class="col-12 my-2"><hr style="border-top: 1px dashed var(--border-soft);"></div>

                                <div class="col-6 mb-3"><label class="label-pro">Strike Expiry (Days)</label><input type="number" name="trust_strike_expiry_days" class="form-control-pro" value="<?= $settings['trust_strike_expiry_days'] ?? 45 ?>"></div>
                                <div class="col-6 mb-3"><label class="label-pro">Fake Report Penalty</label><input type="number" name="trust_penalty_fake_report" class="form-control-pro" value="<?= $settings['trust_penalty_fake_report'] ?? 3 ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="save-bar-pro shadow-sm">
                <button type="submit" class="btn-save-pro">
                    <i class="fas fa-save mr-2"></i> UPDATE SYSTEM CONFIGURATION
                </button>
            </div>
        </form>
    </div>
</section>

<div style="height: 100px;"></div>
<?= $this->endSection() ?>
