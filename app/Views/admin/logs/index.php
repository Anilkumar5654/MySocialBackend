<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* 🎨 GLOBAL PRO UI SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; font-weight: 600;
    }

    /* Log Specific Avatar */
    .staff-avatar-pro { width: 42px; height: 42px; border-radius: 10px; object-fit: cover; border: 1px solid var(--border-soft); margin-right: 12px; background: #f8f9fa; }
    
    /* Professional Action Badges */
    .st-badge-pro { font-size: 10px; padding: 4px 10px; border-radius: 4px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid transparent; }
    .st-danger  { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border-color: var(--accent-red); }
    .st-blue    { background: rgba(93, 120, 255, 0.1); color: var(--primary-blue); border-color: var(--primary-blue); }
    .st-success { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border-color: var(--accent-green); }
    .st-warning { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border-color: var(--accent-orange); }
    .st-muted   { background: #f4f7fa; color: #888; border-color: var(--border-soft); }

    /* Action Button (Eye) */
    .action-btn-pro { 
        width: 34px; height: 34px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 8px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff; color: var(--primary-blue);
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); background: var(--primary-blue); color: #fff; }

    /* Pure Black Text */
    .black-text { color: #000 !important; font-weight: 700; }

    /* Select2 Pro Override */
    .select2-container .select2-selection--single { background-color: #fff !important; border: 1px solid var(--border-soft) !important; height: 45px !important; border-radius: 8px !important; }
    .select2-container--default .select2-selection--single .select2-selection__rendered { color: #000 !important; line-height: 43px !important; font-weight: 600; padding-left: 15px !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 43px !important; }
    .select2-dropdown { border: 1px solid var(--border-soft) !important; box-shadow: var(--card-shadow) !important; border-radius: 8px !important; }
</style>

<div class="content-header pt-4">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-history mr-2 text-primary"></i> Activity Audit
        </h1>
        <p class="text-muted small mb-0 font-weight-bold">Track all administrative actions and system events</p>
    </div>
</div>

<section class="content mt-3">
    <div class="container-fluid">
        
        <div class="card card-pro p-3">
            <form method="get">
                <div class="row align-items-end">
                    <div class="col-md-4 col-12 mb-2">
                        <label class="small text-muted font-weight-bold text-uppercase">Staff Member</label>
                        <select name="user_id" class="form-control select2">
                            <option value="">Search Administrator</option>
                            <?php foreach($admins as $admin): ?>
                                <option value="<?= $admin->id ?>" <?= ($request->getGet('user_id') == $admin->id) ? 'selected' : '' ?>>
                                    <?= esc($admin->name) ?> (<?= esc($admin->role_name) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 col-12 mb-2">
                        <label class="small text-muted font-weight-bold text-uppercase">Action Type</label>
                        <select name="action" class="form-control form-control-pro">
                            <option value="">All Activities</option>
                            <option value="LOGIN" <?= ($request->getGet('action') == 'LOGIN') ? 'selected' : '' ?>>Logins</option>
                            <option value="BAN_USER" <?= ($request->getGet('action') == 'BAN_USER') ? 'selected' : '' ?>>User Bans</option>
                            <option value="DELETE_CONTENT" <?= ($request->getGet('action') == 'DELETE_CONTENT') ? 'selected' : '' ?>>Deletions</option>
                            <option value="DISMISS_REPORT" <?= ($request->getGet('action') == 'DISMISS_REPORT') ? 'selected' : '' ?>>Reports</option>
                            <option value="UPDATE_SETTINGS" <?= ($request->getGet('action') == 'UPDATE_SETTINGS') ? 'selected' : '' ?>>System Config</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-12 mb-2">
                        <label class="small text-muted font-weight-bold text-uppercase">Date Range</label>
                        <input type="date" name="date" class="form-control form-control-pro" value="<?= esc($request->getGet('date')) ?>">
                    </div>

                    <div class="col-md-2 col-12 mb-2 d-flex">
                        <button type="submit" class="btn btn-primary btn-block font-weight-bold mr-2 shadow-sm" style="background: var(--primary-blue); border: none; height: 45px; border-radius: 8px;">
                            FILTER
                        </button>
                        <a href="<?= base_url('admin/logs') ?>" class="btn btn-light d-flex align-items-center justify-content-center shadow-sm" style="height: 45px; width: 45px; border-radius: 8px; border: 1px solid var(--border-soft);">
                            <i class="fas fa-undo text-muted"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 1000px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Administrator</th>
                                <th class="py-4 text-center border-0">Action</th>
                                <th class="py-4 border-0">Entity</th>
                                <th class="py-4 border-0" style="width: 30%;">Activity Detail</th>
                                <th class="py-4 border-0">Date & IP</th>
                                <th class="py-4 text-right px-4 border-0">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($logs)): ?>
                                <?php foreach($logs as $log): ?>
                                    <?php 
                                        $act = strtoupper($log->action);
                                        $stClass = 'st-muted';
                                        if(strpos($act, 'DELETE') !== false || strpos($act, 'BAN') !== false) $stClass = 'st-danger';
                                        elseif(strpos($act, 'UPDATE') !== false || strpos($act, 'EDIT') !== false) $stClass = 'st-blue';
                                        elseif(strpos($act, 'CREATE') !== false || strpos($act, 'LOGIN') !== false) $stClass = 'st-success';
                                        elseif(strpos($act, 'DISMISS') !== false) $stClass = 'st-warning';

                                        // Link Logic
                                        $viewUrl = '#';
                                        $isDisabled = '';
                                        $targetType = strtolower($log->target_type ?? '');
                                        if ($targetType == 'video') $viewUrl = base_url('admin/videos/view/' . $log->target_id);
                                        elseif ($targetType == 'reel') $viewUrl = base_url('admin/reels/view/' . $log->target_id);
                                        elseif ($targetType == 'post') $viewUrl = base_url('admin/posts/view/' . $log->target_id);
                                        elseif ($targetType == 'user') $viewUrl = base_url('admin/users/view/' . $log->target_id);
                                        elseif ($targetType == 'report') $viewUrl = base_url('admin/reports/view/' . $log->target_id);
                                        elseif ($targetType == 'channel') $viewUrl = base_url('admin/channels/details?id=' . $log->target_id);
                                        else $isDisabled = 'style="opacity:0.2; pointer-events:none;"';

                                        if(strpos($act, 'DELETE') !== false) {
                                            $isDisabled = 'style="opacity:0.3; pointer-events:none;" title="Content Removed"';
                                        }
                                    ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="align-middle px-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= get_media_url($log->avatar, 'profile') ?>" class="staff-avatar-pro" onerror="this.src='https://ui-avatars.com/api/?name=Admin&background=f4f7fa&color=5d78ff'">
                                            <div>
                                                <div class="black-text" style="font-size: 14px;"><?= esc($log->name ?? 'System') ?></div>
                                                <small class="text-primary font-weight-bold">@<?= strtoupper(esc($log->username ?? 'SYSTEM')) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="st-badge-pro <?= $stClass ?>"><?= str_replace('_', ' ', esc($log->action)) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if($log->target_type): ?>
                                            <div class="black-text text-uppercase" style="font-size: 11px;"><?= esc($log->target_type) ?></div>
                                            <span class="badge" style="background: #f4f7fa; color: var(--text-dark); font-size: 9px; border: 1px solid var(--border-soft);">ID: #<?= esc($log->target_id) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <div style="font-size: 13px; color: #444; line-height: 1.4; font-weight: 500;">
                                            <?= esc($log->note) ?>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <div class="black-text" style="font-size: 12px;">
                                            <?= date('d M Y', strtotime($log->created_at)) ?>
                                        </div>
                                        <small class="text-muted font-weight-bold"><?= date('h:i A', strtotime($log->created_at)) ?></small>
                                        <div style="font-size: 10px; color: var(--primary-blue); margin-top: 2px; font-weight: 700;">
                                            <?= esc($log->ip_address) ?>
                                        </div>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <a href="<?= $viewUrl ?>" class="action-btn-pro" <?= $isDisabled ?> target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-history fa-3x text-muted mb-3 opacity-25"></i>
                                        <p class="text-muted font-weight-bold">No activity logs found matching the filters.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <?php if($pager->getPageCount() > 1): ?>
            <div class="p-3 border-top bg-light">
                <div class="d-flex justify-content-center">
                    <?= $pager->links() ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('.select2').select2({
            width: '100%',
            placeholder: "Search Administrator",
            allowClear: true
        });
    });
</script>
<?= $this->endSection() ?>
