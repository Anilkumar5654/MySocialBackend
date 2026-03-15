<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>
<?php 
if (!function_exists('format_number_k')) {
    function format_number_k($number) {
        $number = (float)$number;
        if ($number >= 1000000) { return round($number / 1000000, 1) . 'M'; }
        if ($number >= 1000) { return round($number / 1000, 1) . 'K'; }
        return $number;
    }
}
?>

<style>
    /* ⚡ Global Variables ko follow karne ke liye pro styles */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
    .user-avatar { 
        width: 45px; height: 45px; border-radius: 10px; border: 1px solid var(--border-soft); object-fit: cover; 
    }
    .status-badge { 
        font-size: 9px; padding: 4px 10px; border-radius: 4px; font-weight: 700; 
        text-transform: uppercase; display: inline-block; 
    }
    .badge-kyc-approved { 
        background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); 
    }
    .stat-pill { 
        background: #f8f9fa; border: 1px solid var(--border-soft); padding: 3px 10px; 
        border-radius: 6px; font-size: 11px; color: var(--text-dark); display: flex; 
        align-items: center; gap: 6px; margin-bottom: 4px; font-weight: 500;
    }

    @media (max-width: 768px) {
        .table-responsive { border: 0; }
        .user-avatar { width: 35px; height: 35px; }
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-database mr-2 text-primary"></i> All Users
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="<?= base_url('admin/users') ?>" method="get">
                    <div class="row">
                        <div class="col-12 col-md-5 mb-2">
                            <label class="small text-muted font-weight-bold">SEARCH ENTITY</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="UID / Name / Username" value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <label class="small text-muted font-weight-bold">ACCOUNT STATUS</label>
                            <select name="status" class="form-control form-control-pro">
                                <option value="">ALL</option>
                                <option value="active" <?= ($_GET['status'] ?? '') == 'active' ? 'selected' : '' ?>>ACTIVE</option>
                                <option value="banned" <?= ($_GET['status'] ?? '') == 'banned' ? 'selected' : '' ?>>BANNED</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-4 d-flex align-items-end mb-2">
                            <button type="submit" class="btn btn-primary font-weight-bold w-100 shadow-sm" style="background: var(--primary-blue); height: 45px; border-radius: 8px; border: none;">
                                <i class="fas fa-sync-alt mr-1"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8f9fa; color: var(--text-dark); font-size: 11px; text-transform: uppercase;">
                            <tr>
                                <th class="py-3 px-4 border-0">System ID</th>
                                <th class="py-3 border-0">Profile Info</th>
                                <th class="py-3 border-0">Stats</th>
                                <th class="py-3 text-center border-0">Verification</th>
                                <th class="py-3 text-right px-4 border-0">Action Map</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): ?>
                            <tr style="border-bottom: 1px solid var(--border-soft);">
                                <td class="align-middle px-4">
                                    <span class="text-dark font-weight-bold">#<?= $user->id ?></span>
                                    <small class="d-block text-primary font-weight-bold" style="font-size: 10px; font-family: monospace;"><?= $user->unique_id ?: 'NO_UID' ?></small>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center">
                                        <img src="<?= get_media_url($user->avatar, 'profile') ?>" 
                                             onerror="this.src='https://ui-avatars.com/api/?name=<?= $user->username ?>&background=f4f7fa&color=5d78ff';" 
                                             class="user-avatar mr-3">
                                        <div>
                                            <div class="text-dark font-weight-bold" style="font-size: 14px; line-height: 1.2;">
                                                <?= $user->name ?: 'Member' ?>
                                                <?php if($user->is_verified): ?><i class="fas fa-check-circle text-primary ml-1" style="font-size: 11px;"></i><?php endif; ?>
                                            </div>
                                            <small class="text-muted">@<?= strtoupper($user->username) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle">
                                    <div style="min-width: 130px;">
                                        <span class="stat-pill"><i class="fas fa-users text-primary"></i> <?= format_number_k($user->followers_count) ?></span>
                                        <span class="stat-pill"><i class="fas fa-video text-info"></i> <?= format_number_k($user->videos_count + $user->reels_count) ?></span>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <span class="status-badge <?= $user->is_banned ? 'badge-danger' : 'badge-success' ?> mb-1">
                                        <?= $user->is_banned ? 'BANNED' : 'ACTIVE' ?>
                                    </span>
                                    <br>
                                    <?php if($user->kyc_status == 'APPROVED'): ?>
                                        <span class="status-badge badge-kyc-approved">Kyc Done</span>
                                    <?php endif; ?>
                                </td>
                                <td class="align-middle text-right px-4">
                                    <?= view('admin/common/action_buttons', ['id' => $user->id, 'type' => 'users', 'row' => $user]) ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
