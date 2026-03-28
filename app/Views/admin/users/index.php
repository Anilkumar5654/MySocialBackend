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

// Logic for stats cards
$totalUsers = count($users);
$activeUsers = 0;
$bannedUsers = 0;
foreach($users as $u) {
    if(!$u->is_banned) $activeUsers++;
    if($u->is_banned) $bannedUsers++;
}
?>

<style>
    /* 🎨 PAGE SPECIFIC LAYOUT (Powered completely by Global Variables) */
    .dashboard-container { padding: var(--space-lg); }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: var(--space-md);
        margin-bottom: var(--space-lg);
    }

    /* Modifiers for Stats Cards */
    .stat-card {
        padding: var(--space-lg);
        border-left-width: 4px;
        border-left-style: solid;
    }
    .border-left-primary { border-left-color: var(--primary-blue); }
    .border-left-success { border-left-color: var(--accent-green); }
    .border-left-warning { border-left-color: var(--accent-orange); } /* Fallback to orange if warning is missing */
    .border-left-dark { border-left-color: var(--text-dark); }

    .stat-value {
        color: var(--text-dark);
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-black);
    }

    /* Filter & Form Elements */
    .input-group-text-pro {
        background-color: transparent;
        border: 1px solid var(--border-soft);
        border-right: none;
        border-top-left-radius: var(--radius-md);
        border-bottom-left-radius: var(--radius-md);
        color: var(--text-muted);
    }

    .form-control-pro { 
        background-color: transparent; 
        border: 1px solid var(--border-soft); 
        color: var(--text-dark); 
        height: var(--btn-height-action); 
        font-size: var(--font-size-md);
    }
    
    .input-group .form-control-pro {
        border-left: none;
        border-top-right-radius: var(--radius-md);
        border-bottom-right-radius: var(--radius-md);
    }
    
    select.form-control-pro {
        border-radius: var(--radius-md);
    }

    /* Custom Table Styling */
    .premium-table thead th {
        background-color: var(--bg-light);
        border-bottom: 1px solid var(--border-soft);
        color: var(--text-dark);
        font-size: var(--font-size-xs);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: var(--font-weight-bold);
        padding: var(--space-md);
    }

    .premium-table tbody tr td {
        padding: var(--space-md);
        vertical-align: middle;
        border-bottom: 1px solid var(--border-soft);
    }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .dashboard-container { padding: var(--space-sm); }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <h1 class="page-title">
            User Directory
        </h1>
        <div class="header-actions">
            <button class="btn btn-light btn-sm mr-2">
                <i class="fas fa-download mr-1"></i> Export
            </button>
            <button class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Add User
            </button>
        </div>
    </div>
</div>

<section class="content dashboard-container">
    <div class="container-fluid">
        
        <div class="stats-grid">
            <div class="card stat-card border-left-primary mb-0">
                <div class="label">Total Users</div>
                <div class="stat-value"><?= number_format($totalUsers) ?></div>
            </div>
            <div class="card stat-card border-left-success mb-0">
                <div class="label">Active Users</div>
                <div class="stat-value"><?= number_format($activeUsers) ?></div>
            </div>
            <div class="card stat-card border-left-warning mb-0">
                <div class="label">Banned</div>
                <div class="stat-value"><?= number_format($bannedUsers) ?></div>
            </div>
            <div class="card stat-card border-left-dark mb-0">
                <div class="label">Growth</div>
                <div class="stat-value">+12%</div>
            </div>
        </div>

        <div class="card p-4">
            <form action="<?= base_url('admin/users') ?>" method="get">
                <div class="row align-items-end">
                    <div class="col-12 col-md-5">
                        <label class="label mb-2">SEARCH DIRECTORY</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text-pro px-3 d-flex align-items-center"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="UID, Name or Username..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="col-6 col-md-4">
                        <label class="label mb-2">ACCOUNT STATUS</label>
                        <select name="status" class="form-control form-control-pro">
                            <option value="">Show All Status</option>
                            <option value="active" <?= ($_GET['status'] ?? '') == 'active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="banned" <?= ($_GET['status'] ?? '') == 'banned' ? 'selected' : '' ?>>Banned Only</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <button type="submit" class="btn btn-primary w-100" style="height: var(--btn-height-action);">
                            <i class="fas fa-filter mr-1"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table premium-table mb-0">
                    <thead>
                        <tr>
                            <th class="pl-4">System ID</th>
                            <th>Identity & Profile</th>
                            <th>Engagement</th>
                            <th class="text-center">Account Status</th>
                            <th class="text-right pr-4">Management</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $user): ?>
                        <tr>
                            <td class="pl-4">
                                <div class="text-strong">#<?= $user->id ?></div>
                                <div class="label text-primary mt-1"><?= $user->unique_id ?: 'UNSET' ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="<?= get_media_url($user->avatar, 'profile') ?>" 
                                         onerror="this.src='https://ui-avatars.com/api/?name=<?= $user->username ?>&background=f4f7fa&color=5d78ff';" 
                                         class="avatar-sm mr-3">
                                    <div>
                                        <div class="text-strong text-md">
                                            <?= $user->name ?: 'Standard Member' ?>
                                            <?php if($user->is_verified): ?><i class="fas fa-check-circle text-primary ml-1 font-size-xs"></i><?php endif; ?>
                                        </div>
                                        <div class="label mt-1">@<?= strtoupper($user->username) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <span class="badge badge-neutral"><i class="fas fa-users text-primary mr-1"></i> <?= format_number_k($user->followers_count) ?></span>
                                    <span class="badge badge-neutral"><i class="fas fa-play-circle text-info mr-1"></i> <?= format_number_k($user->videos_count + $user->reels_count) ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?= $user->is_banned ? 'badge-danger' : 'badge-success' ?>">
                                    <?= $user->is_banned ? 'Banned' : 'Active' ?>
                                </span>
                                <?php if($user->kyc_status == 'APPROVED'): ?>
                                    <div class="mt-2"><span class="badge badge-success">KYC Verified</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-right pr-4">
                                <div class="d-inline-flex">
                                    <?= view('admin/common/action_buttons', ['id' => $user->id, 'type' => 'users', 'row' => $user]) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-muted font-weight-bold">No users found in the system.</div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-4 py-3 d-flex justify-content-between align-items-center border-top">
                <div class="label mb-0">
                    Showing <?= count($users) ?> results
                </div>
                <div class="pagination-ui">
                    <ul class="pagination pagination-sm m-0">
                        <li class="page-item disabled"><a class="page-link" href="#">Previous</a></li>
                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                        <li class="page-item"><a class="page-link" href="#">Next</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
<?= $this->endSection() ?>
