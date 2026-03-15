<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PROFESSIONAL FORM UI */
    .form-control-pro { 
        background: #fff !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        height: 48px; 
        border-radius: 8px; 
        transition: 0.3s;
    }
    .form-control-pro:focus { 
        border-color: var(--primary-blue) !important; 
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1) !important; 
    }
    
    .input-group-text-pro {
        background: #f8f9fa !important;
        border: 1px solid var(--border-soft) !important;
        border-right: none !important;
        color: var(--primary-blue) !important;
        border-radius: 8px 0 0 8px !important;
        font-weight: 700;
    }

    /* Pro Card Styling */
    .card-pro { 
        background: #fff; 
        border: none; 
        border-radius: 12px; 
        margin-bottom: 25px; 
        overflow: hidden; 
        box-shadow: var(--card-shadow); 
    }
    .card-header-pro { 
        background: #f8f9fa; 
        border-bottom: 1px solid var(--border-soft); 
        padding: 15px 25px; 
    }
    .card-header-pro h6 { 
        font-weight: 700; 
        text-transform: uppercase; 
        margin: 0; 
        font-size: 12px; 
        color: var(--text-dark);
        letter-spacing: 0.5px;
    }

    label { 
        font-weight: 700 !important; 
        font-size: 11px; 
        color: var(--text-muted); 
        text-transform: uppercase; 
        margin-bottom: 8px; 
        display: block; 
    }
    
    .save-btn {
        background: var(--primary-blue); 
        color: #fff; 
        border: none;
        border-radius: 10px; 
        padding: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 4px 12px rgba(93, 120, 255, 0.2);
        transition: 0.3s;
    }
    .save-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 15px rgba(93, 120, 255, 0.3); color: #fff; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-edit mr-2 text-primary"></i> Edit Channel
            </h1>
            <a href="<?= base_url('admin/channels/view/'.$channel->id) ?>" class="btn btn-light btn-sm shadow-sm" style="border-radius: 8px; font-weight: 600; border: 1px solid var(--border-soft);">
                <i class="fas fa-arrow-left mr-1"></i> BACK TO PROFILE
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <?php if(session()->has('error')): ?>
            <div class="alert alert-danger border-0 mb-4 shadow-sm" style="background: #fff; border-left: 4px solid var(--accent-red) !important; color: var(--accent-red); border-radius: 8px;">
                <i class="fas fa-exclamation-circle mr-2"></i> <?= session('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('admin/channels/update/'.$channel->id) ?>" method="post">
            <?= csrf_field() ?>
            
            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6><i class="fas fa-info-circle mr-2 text-primary"></i> Basic Information</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Channel Name</label>
                            <input type="text" name="channel_name" class="form-control form-control-pro" value="<?= esc($channel->name) ?>" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Handle</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text input-group-text-pro">@</span>
                                </div>
                                <input type="text" name="handle" class="form-control form-control-pro" style="border-radius: 0 8px 8px 0;" value="<?= esc($channel->handle) ?>" required>
                            </div>
                        </div>

                        <div class="col-12 mb-3">
                            <label>Short Description</label>
                            <input type="text" name="description" class="form-control form-control-pro" value="<?= esc($channel->description) ?>" placeholder="Tagline for the channel...">
                        </div>

                        <div class="col-12">
                            <label>About Bio</label>
                            <textarea name="about_text" class="form-control form-control-pro" rows="4" style="height: auto;" placeholder="Detailed channel description..."><?= esc($channel->about_text) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-pro">
                <div class="card-header-pro">
                    <h6><i class="fas fa-shield-alt mr-2 text-info"></i> Monetization & Security</h6>
                </div>
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Monetization Status</label>
                            <select name="monetization_status" class="form-control form-control-pro">
                                <option value="APPROVED" <?= ($channel->monetization_status == 'APPROVED') ? 'selected' : '' ?>>✅ APPROVED</option>
                                <option value="SUSPENDED" <?= ($channel->monetization_status == 'SUSPENDED') ? 'selected' : '' ?>>🚫 SUSPENDED</option>
                                <option value="PENDING" <?= ($channel->monetization_status == 'PENDING') ? 'selected' : '' ?>>⏳ PENDING</option>
                            </select>
                        </div>

                        <div class="col-md-8 mb-3">
                            <label>Reason / Admin Notes</label>
                            <input type="text" name="monetization_reason" class="form-control form-control-pro" value="<?= esc($channel->monetization_reason ?? '') ?>" placeholder="Why was the status changed?">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Verified Badge</label>
                            <select name="is_verified" class="form-control form-control-pro">
                                <option value="1" <?= $channel->is_verified ? 'selected' : '' ?>>YES (Verified Tick)</option>
                                <option value="0" <?= !$channel->is_verified ? 'selected' : '' ?>>NO (Standard)</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Trust Score (0-100)</label>
                            <input type="number" name="trust_score" class="form-control form-control-pro" value="<?= $channel->trust_score ?>" min="0" max="100">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Creator Level</label>
                            <select name="creator_level" class="form-control form-control-pro">
                                <option value="bronze" <?= ($channel->creator_level == 'bronze') ? 'selected' : '' ?>>BRONZE</option>
                                <option value="silver" <?= ($channel->creator_level == 'silver') ? 'selected' : '' ?>>SILVER</option>
                                <option value="gold" <?= ($channel->creator_level == 'gold') ? 'selected' : '' ?>>GOLD</option>
                                <option value="diamond" <?= ($channel->creator_level == 'diamond') ? 'selected' : '' ?>>DIAMOND</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pb-5 mt-2">
                <button type="submit" class="btn btn-block save-btn">
                    <i class="fas fa-save mr-2"></i> SAVE CHANNEL CHANGES
                </button>
            </div>

        </form>
    </div>
</section>

<?= $this->endSection() ?>
