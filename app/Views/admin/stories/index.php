<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL THEME: STORY BUBBLES */
    .story-wrapper {
        position: relative;
        display: flex; flex-direction: column; align-items: center;
        margin-bottom: 25px; cursor: pointer; transition: 0.3s;
    }
    .story-wrapper:hover { transform: translateY(-5px); }

    .story-ring {
        width: 75px; height: 75px;
        padding: 3px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        box-shadow: var(--card-shadow);
    }
    /* Simple Blue Ring for Active */
    .ring-active {
        border: 3px solid var(--primary-blue);
    }
    /* Gray Ring for Expired */
    .ring-expired { border: 2px solid var(--border-soft); opacity: 0.6; }

    .user-circle {
        width: 100%; height: 100%; border-radius: 50%;
        background: #fff; overflow: hidden;
        border: 2px solid #fff;
    }
    .user-img { width: 100%; height: 100%; object-fit: cover; }

    /* Count Badge */
    .story-count-badge {
        position: absolute; top: -5px; right: 8px;
        background: var(--primary-blue); color: #fff;
        font-weight: 700; font-size: 10px;
        width: 22px; height: 22px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        border: 2px solid #fff; z-index: 10;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .story-username { 
        margin-top: 10px; font-size: 12px; font-weight: 600;
        color: var(--text-dark); max-width: 85px; 
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
    }
    .story-time { font-size: 10px; color: var(--text-muted); font-weight: 500; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 42px; border-radius: 8px; 
    }

    .card-pro { 
        background: #fff; border: none; border-radius: 12px; 
        box-shadow: var(--card-shadow); margin-bottom: 30px; 
    }
</style>

<div class="content-header pb-2">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-history mr-2 text-primary"></i> All Stories
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card card-pro">
            <div class="card-body py-3 px-3">
                <form action="<?= base_url('admin/stories') ?>" method="get">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-2">
                            <label class="small text-muted font-weight-bold uppercase">Search User</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Username..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted font-weight-bold uppercase">Status</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Active Stories</option>
                                <option value="expired" <?= $status == 'expired' ? 'selected' : '' ?>>Old History</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm" style="background: var(--primary-blue); height: 42px; border: none; border-radius: 8px;">
                                Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <?php if(empty($users)): ?>
                <div class="col-12 text-center text-muted py-5">
                    <i class="fas fa- ghost fa-3x mb-3 opacity-25"></i>
                    <p>No stories found in this section.</p>
                </div>
            <?php endif; ?>

            <?php foreach($users as $u): ?>
                <?php 
                    $avatar = get_media_url($u->user_avatar, 'profile'); 
                    $ringClass = ($status == 'active') ? 'ring-active' : 'ring-expired';
                ?>
                <div class="col-6 col-sm-4 col-md-3 col-lg-2 col-xl-1 d-flex justify-content-center">
                    <a href="<?= base_url('admin/stories/view/'.$u->user_id) ?>" class="text-decoration-none">
                        <div class="story-wrapper">
                            <div class="story-count-badge"><?= $u->story_count ?></div>

                            <div class="story-ring <?= $ringClass ?>">
                                <div class="user-circle">
                                    <img src="<?= $avatar ?>" class="user-img" onerror="this.src='https://ui-avatars.com/api/?name=<?= $u->username ?>&background=f4f7fa&color=5d78ff';">
                                </div>
                            </div>
                            
                            <div class="story-username">@<?= strtoupper($u->username) ?></div>
                            <div class="story-time"><?= time_ago($u->latest_activity) ?></div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
