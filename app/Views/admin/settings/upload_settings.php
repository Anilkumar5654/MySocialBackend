<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 20px 25px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: #000 !important; height: 48px; border-radius: 8px; font-weight: 600; 
    }
    .form-control-pro:focus { border-color: var(--primary-blue) !important; box-shadow: var(--card-shadow); }
    
    .label-pro { font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; margin-bottom: 8px; display: block; letter-spacing: 0.5px; }
    .black-text { color: #000 !important; font-weight: 700; }

    /* Professional Toggle Switch */
    .switch-pro { position: relative; display: inline-block; width: 46px; height: 24px; vertical-align: middle; }
    .switch-pro input { opacity: 0; width: 0; height: 0; }
    .slider-pro { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #dee2e6; transition: .4s; border-radius: 34px; }
    .slider-pro:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    input:checked + .slider-pro { background-color: var(--primary-blue); }
    input:checked + .slider-pro:before { transform: translateX(22px); }

    /* Sticky Save Bar Pro */
    .save-bar-pro { 
        position: fixed; bottom: 0; right: 0; left: 250px; background: rgba(255, 255, 255, 0.9); 
        backdrop-filter: blur(10px); border-top: 1px solid var(--border-soft); z-index: 1000; 
        padding: 15px 40px; display: flex; align-items: center; justify-content: flex-end;
    }
    .btn-save-pro { background: var(--primary-blue); color: #fff; border-radius: 10px; height: 48px; padding: 0 40px; font-weight: 800; border: none; box-shadow: 0 4px 15px rgba(93, 120, 255, 0.2); }
    
    @media (max-width: 992px) { .save-bar-pro { left: 0 !important; justify-content: center; } }
</style>

<div class="content-header pt-4">
    <div class="container-fluid">
        <span class="text-primary font-weight-bold text-uppercase small" style="letter-spacing: 1px;">System Configuration</span>
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -1px; margin-top: 5px;">
            <i class="fas fa-server mr-2 text-primary"></i> Media Processing Engine
        </h1>
    </div>
</div>

<section class="content pb-5 mt-3">
    <div class="container-fluid">
        
        <?php if(session()->getFlashdata('success')): ?>
            <div class="alert shadow-sm mb-4" style="background: rgba(10, 187, 135, 0.05); border: 1px solid var(--accent-green); color: var(--accent-green); border-radius: 10px;">
                <i class="fas fa-check-circle mr-2"></i> <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/settings/upload/update') ?>" method="post">
            <?= csrf_field() ?>

            <div class="row">
                <div class="col-12">
                    <div class="card card-pro" style="border-top: 4px solid var(--primary-blue);">
                        <div class="card-header-pro d-flex justify-content-between align-items-center">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-microchip mr-2 text-primary"></i> VIDEO PROCESSING (FFMPEG)</h6>
                            <div class="d-flex align-items-center">
                                <span class="black-text small mr-2">ENABLE ENGINE</span>
                                <label class="switch-pro">
                                    <input type="hidden" name="ffmpeg_enabled" value="false">
                                    <input type="checkbox" name="ffmpeg_enabled" value="true" <?= ($settings['ffmpeg_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                    <span class="slider-pro"></span>
                                </label>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="label-pro">CPU Threads</label>
                                    <input type="number" name="ffmpeg_threads" class="form-control-pro" value="<?= $settings['ffmpeg_threads'] ?? 2 ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="label-pro">Speed Preset</label>
                                    <select name="ffmpeg_preset" class="form-control-pro">
                                        <option value="ultrafast" <?= ($settings['ffmpeg_preset'] ?? '') == 'ultrafast' ? 'selected' : '' ?>>Ultrafast (Low CPU)</option>
                                        <option value="superfast" <?= ($settings['ffmpeg_preset'] ?? '') == 'superfast' ? 'selected' : '' ?>>Superfast</option>
                                        <option value="medium" <?= ($settings['ffmpeg_preset'] ?? '') == 'medium' ? 'selected' : '' ?>>Medium (Better Quality)</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="label-pro">Video Bitrate</label>
                                    <input type="text" name="ffmpeg_video_bitrate" class="form-control-pro" value="<?= $settings['ffmpeg_video_bitrate'] ?? '1000k' ?>">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="label-pro">Max Resolution (Pixels)</label>
                                    <input type="number" name="ffmpeg_resolution" class="form-control-pro" value="<?= $settings['ffmpeg_resolution'] ?? 720 ?>">
                                </div>
                                <div class="col-12 mt-2">
                                    <label class="label-pro">System Binary Path</label>
                                    <input type="text" name="ffmpeg_path" class="form-control-pro" value="<?= $settings['ffmpeg_path'] ?? '/usr/bin/ffmpeg' ?>">
                                    <small class="text-muted font-weight-bold">Default path is usually <code>/usr/bin/ffmpeg</code>. Change only if required.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-pro" style="border-top: 4px solid var(--accent-orange);">
                        <div class="card-header-pro d-flex justify-content-between align-items-center">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-cloud mr-2 text-warning"></i> CLOUD STORAGE (S3/SPACES)</h6>
                            <div class="d-flex align-items-center">
                                <div class="mr-4 d-flex align-items-center">
                                    <span class="black-text small mr-2">ENABLE CLOUD</span>
                                    <label class="switch-pro">
                                        <input type="hidden" name="cloud_storage_enabled" value="false">
                                        <input type="checkbox" name="cloud_storage_enabled" value="true" <?= ($settings['cloud_storage_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                        <span class="slider-pro"></span>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="text-muted small font-weight-bold mr-2">CDN</span>
                                    <label class="switch-pro">
                                        <input type="hidden" name="do_cdn_enabled" value="false">
                                        <input type="checkbox" name="do_cdn_enabled" value="true" <?= ($settings['do_cdn_enabled'] ?? 'false') === 'true' ? 'checked' : '' ?>>
                                        <span class="slider-pro"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-4 mb-3"><label class="label-pro">Bucket Name</label><input type="text" name="do_bucket_name" class="form-control-pro" value="<?= $settings['do_bucket_name'] ?? '' ?>"></div>
                                <div class="col-md-4 mb-3"><label class="label-pro">Region</label><input type="text" name="do_region" class="form-control-pro" value="<?= $settings['do_region'] ?? '' ?>"></div>
                                <div class="col-md-4 mb-3"><label class="label-pro">Endpoint URL</label><input type="text" name="do_endpoint" class="form-control-pro" value="<?= $settings['do_endpoint'] ?? '' ?>"></div>
                                <div class="col-md-6 mb-3"><label class="label-pro">Access Key ID</label><input type="text" name="do_access_key" class="form-control-pro" value="<?= $settings['do_access_key'] ?? '' ?>"></div>
                                <div class="col-md-6 mb-3"><label class="label-pro">Secret Key</label><input type="password" name="do_secret_key" class="form-control-pro" value="<?= $settings['do_secret_key'] ?? '' ?>"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card card-pro h-100">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-file-upload mr-2 text-primary"></i> GLOBAL UPLOAD LIMITS</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="label-pro">Max File Size (MB)</label>
                                <input type="number" name="max_upload_size_mb" class="form-control-pro" value="<?= $settings['max_upload_size_mb'] ?? '500' ?>">
                            </div>
                            <div class="mb-0">
                                <label class="label-pro">Allowed Formats</label>
                                <input type="text" name="allowed_video_formats" class="form-control-pro" value="<?= $settings['allowed_video_formats'] ?? 'mp4,mov' ?>">
                                <small class="text-muted font-weight-bold">Comma separated (e.g. mp4,mov,avi)</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card card-pro h-100" style="border-right: 4px solid var(--accent-red);">
                        <div class="card-header-pro">
                            <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-bolt mr-2 text-danger"></i> REELS & SHORTS LIMITS</h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="mb-3">
                                <label class="label-pro">Max Reel Size (MB)</label>
                                <input type="number" name="reel_max_size_mb" class="form-control-pro" value="<?= $settings['reel_max_size_mb'] ?? '50' ?>">
                            </div>
                            <div class="mb-0">
                                <label class="label-pro">Max Duration (Seconds)</label>
                                <input type="number" name="reel_max_duration_sec" class="form-control-pro" value="<?= $settings['reel_max_duration_sec'] ?? '60' ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="save-bar-pro">
                <button type="submit" class="btn-save-pro shadow-sm">
                    <i class="fas fa-save mr-2"></i> UPDATE ENGINE CONFIGURATION
                </button>
            </div>
        </form>
    </div>
</section>

<div style="height: 100px;"></div>

<?= $this->endSection() ?>
