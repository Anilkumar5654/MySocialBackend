<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; padding: 20px; position: relative; }
    @media (min-width: 768px) { .card-pro { padding: 25px; } }

    .section-header-pro { 
        color: #000; font-size: 11px; font-weight: 800; text-transform: uppercase; 
        letter-spacing: 0.5px; margin-bottom: 20px; display: flex; align-items: center; 
        border-bottom: 1px solid var(--border-soft); padding-bottom: 12px;
        word-break: break-word; 
    }
    @media (min-width: 768px) { .section-header-pro { font-size: 13px; } }
    
    .section-icon-pro { margin-right: 10px; color: var(--primary-blue); font-size: 16px; flex-shrink: 0; }

    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: #000 !important; height: 45px; border-radius: 8px; font-weight: 600; font-size: 14px; 
        width: 100%;
    }
    .form-control-pro:focus { border-color: var(--primary-blue) !important; box-shadow: var(--card-shadow); }
    
    .label-pro { display: block; font-size: 9px; font-weight: 800; color: var(--text-muted); text-transform: uppercase; margin-bottom: 8px; letter-spacing: 0.5px; }
    @media (min-width: 768px) { .label-pro { font-size: 10px; } }
    
    .switch-row-pro { 
        background: #f8f9fa; border: 1px solid var(--border-soft); padding: 12px 15px; 
        border-radius: 10px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;
    }
    .custom-control-input:checked ~ .custom-control-label::before { background-color: var(--primary-blue); border-color: var(--primary-blue); }
    
    .info-tag-pro { font-size: 9px; color: var(--text-muted); font-weight: 600; margin-top: 5px; display: block; word-wrap: break-word; }
    
    .black-name { color: #000 !important; font-weight: 700; font-size: 12px; word-break: break-word; flex: 1; padding-right: 10px; }
    
    .status-badge-pro { 
        background: #f4f7fa; border: 1px solid var(--border-soft); border-radius: 8px; 
        color: var(--accent-green); font-size: 10px; font-weight: 700; white-space: nowrap;
    }
</style>

<div class="content-header pb-4">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -1px;">AD <span style="color: var(--primary-blue);">SETTINGS</span></h1>
            <p class="text-muted small mb-0 font-weight-bold">Configure ad placements and revenue distribution</p>
        </div>
        <div class="status-badge-pro px-3 py-2">
            <i class="fas fa-check-circle mr-2"></i> SYSTEM ONLINE
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert shadow-sm mb-4" style="background: rgba(10, 187, 135, 0.05); border: 1px solid var(--accent-green); color: var(--accent-green); border-radius: 10px;">
                <i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/ads/save_settings') ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="row">
                <div class="col-lg-6">
                    
                    <div class="card-pro">
                        <div class="section-header-pro"><i class="fas fa-hand-holding-usd section-icon-pro"></i> Bidding & Rates (Internal)</div>
                        
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="label-pro">Min Click (₹)</label>
                                <input type="number" step="0.01" name="min_cpc_bid" class="form-control-pro" value="<?= $settings['min_cpc_bid'] ?? '5.00' ?>">
                                <span class="info-tag-pro">*Per click</span>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="label-pro">Min 1k Views (₹)</label>
                                <input type="number" step="0.01" name="min_cpm_bid" class="form-control-pro" value="<?= $settings['min_cpm_bid'] ?? '6.00' ?>">
                                <span class="info-tag-pro">*Per 1,000 views</span>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="label-pro">Min View (₹)</label>
                                <input type="number" step="0.01" name="min_cpv_bid" class="form-control-pro" value="<?= $settings['min_cpv_bid'] ?? '1.00' ?>">
                                <span class="info-tag-pro">*Per single view</span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="label-pro">Creator Share (%)</label>
                                <input type="number" name="revenue_share_ads" class="form-control-pro" value="<?= $settings['revenue_share_ads'] ?? '80' ?>" max="100">
                                <span class="info-tag-pro">*Direct Ads Share</span>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="label-pro">Pool Share (%)</label>
                                <input type="number" name="revenue_share_pool" class="form-control-pro" value="<?= $settings['revenue_share_pool'] ?? '70' ?>" max="100">
                                <span class="info-tag-pro">*Meta Network Share</span>
                            </div>
                        </div>

                        <div class="pt-3 mt-2" style="border-top: 1px dashed var(--border-soft);">
                            <div class="label-pro mb-3" style="color: var(--primary-blue);">Copyright Claim Distribution</div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="label-pro">Original Owner (%)</label>
                                    <input type="number" name="revenue_share_original_creator" class="form-control-pro" value="<?= $settings['revenue_share_original_creator'] ?? '70' ?>" max="100">
                                    <span class="info-tag-pro">*Share for Right Holder</span>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="label-pro">Uploader Share (%)</label>
                                    <input type="number" name="revenue_share_uploader" class="form-control-pro" value="<?= $settings['revenue_share_uploader'] ?? '30' ?>" max="100">
                                    <span class="info-tag-pro">*Share for Re-uploader</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro">
                        <div class="section-header-pro"><i class="fab fa-facebook section-icon-pro"></i> Meta Audience Network</div>
                        
                        <div class="mb-3">
                            <label class="label-pro">Meta App ID</label>
                            <input type="text" name="fb_app_id" class="form-control-pro" value="<?= $settings['fb_app_id'] ?? '' ?>" placeholder="Enter FAN App ID">
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="label-pro">Reels Placement ID</label>
                                <input type="text" name="fb_placement_reels" class="form-control-pro" value="<?= $settings['fb_placement_reels'] ?? '' ?>">
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="label-pro">Video Placement ID</label>
                                <input type="text" name="fb_placement_video" class="form-control-pro" value="<?= $settings['fb_placement_video'] ?? '' ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label class="label-pro">Ad Provider</label>
                                <select name="active_ad_provider" class="form-control-pro">
                                    <option value="internal" <?= ($settings['active_ad_provider'] ?? '') == 'internal' ? 'selected' : '' ?>>Direct Ads Only</option>
                                    <option value="meta" <?= ($settings['active_ad_provider'] ?? '') == 'meta' ? 'selected' : '' ?>>Meta Network Only</option>
                                    <option value="both" <?= ($settings['active_ad_provider'] ?? 'both') == 'both' ? 'selected' : '' ?>>Hybrid (Mix)</option>
                                </select>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label class="label-pro">SDK Mode</label>
                                <select name="fb_test_mode" class="form-control-pro">
                                    <option value="1" <?= ($settings['fb_test_mode'] ?? '1') == '1' ? 'selected' : '' ?>>TESTING</option>
                                    <option value="0" <?= ($settings['fb_test_mode'] ?? '1') == '0' ? 'selected' : '' ?>>PRODUCTION</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    
                    <div class="card-pro">
                        <div class="section-header-pro"><i class="fas fa-toggle-on section-icon-pro"></i> Active Placements</div>
                        <?php 
                        $placements = [
                            'enable_home_ads'       => 'Home Feed (Posts)',
                            'enable_reels_ads'      => 'Reels Feed (Shorts)',
                            'enable_video_feed_ads' => 'Video Tab Feed',
                            'enable_instream_ads'   => 'In-Stream Video Ads'
                        ];
                        foreach($placements as $key => $label): ?>
                            <div class="switch-row-pro">
                                <span class="black-name"><?= $label ?></span>
                                <div class="custom-control custom-switch" style="flex-shrink: 0;">
                                    <input type="hidden" name="<?= $key ?>" value="0">
                                    <input type="checkbox" class="custom-control-input" id="sw_<?= $key ?>" name="<?= $key ?>" value="1" <?= ($settings[$key] ?? '1') == '1' ? 'checked' : '' ?>>
                                    <label class="custom-control-label" for="sw_<?= $key ?>"></label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="card-pro" style="border-left: 4px solid var(--primary-blue);">
                        <div class="section-header-pro"><i class="fas fa-user-lock section-icon-pro"></i> Daily View Limits (Capping)</div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="label-pro">Internal Ad Limit</label>
                                <input type="number" name="internal_ad_cap_limit" class="form-control-pro" value="<?= $settings['internal_ad_cap_limit'] ?? '3' ?>">
                                <span class="info-tag-pro">*Max views per user/day (Feed)</span>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="label-pro">In-Stream Limit</label>
                                <input type="number" name="instream_ad_cap_limit" class="form-control-pro" value="<?= $settings['instream_ad_cap_limit'] ?? '3' ?>">
                                <span class="info-tag-pro">*Max player ads per user/day</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro">
                        <div class="section-header-pro"><i class="fas fa-history section-icon-pro"></i> Ad Frequency (Gap)</div>
                        <div class="row">
                            <div class="col-4">
                                <label class="label-pro">Home Gap</label>
                                <input type="number" name="ad_frequency_home" class="form-control-pro" value="<?= $settings['ad_frequency_home'] ?? '7' ?>">
                                <span class="info-tag-pro">Every 7 posts</span>
                            </div>
                            <div class="col-4">
                                <label class="label-pro">Reels Gap</label>
                                <input type="number" name="ad_frequency_reels" class="form-control-pro" value="<?= $settings['ad_frequency_reels'] ?? '7' ?>">
                                <span class="info-tag-pro">Every 7 reels</span>
                            </div>
                            <div class="col-4">
                                <label class="label-pro">Video Gap</label>
                                <input type="number" name="ad_frequency_video" class="form-control-pro" value="<?= $settings['ad_frequency_video'] ?? '8' ?>">
                                <span class="info-tag-pro">Every 8 videos</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-pro" style="border-top: 3px solid #ff4757;">
                        <div class="section-header-pro" style="color: #ff4757; border: none;"><i class="fas fa-power-off section-icon-pro" style="color: #ff4757;"></i> Global Ad Switch</div>
                        <div class="switch-row-pro" style="background: rgba(255, 71, 87, 0.05); border-color: rgba(255, 71, 87, 0.2);">
                            <span class="black-name" style="color: #ff4757 !important;">Enable Ads System Globally</span>
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="enable_ads_globally" value="0">
                                <input type="checkbox" class="custom-control-input" id="sw_global" name="enable_ads_globally" value="1" <?= ($settings['enable_ads_globally'] ?? '1') == '1' ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="sw_global"></label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm" style="background: var(--primary-blue); border: none; border-radius: 10px; height: 55px; font-size: 15px; margin-bottom: 30px;">
                        <i class="fas fa-save mr-2"></i> SAVE ALL SETTINGS
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<?= $this->endSection() ?>
