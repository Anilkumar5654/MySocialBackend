<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* Professional Form & UI Consistency */
    .form-control-pro { 
        background: #fff !important; 
        border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; 
        height: 45px; 
        border-radius: 8px; 
    }
    .form-control-pro:focus {
        border-color: var(--primary-blue) !important;
        box-shadow: 0 0 0 0.2rem rgba(93, 120, 255, 0.1);
    }
    
    .user-avatar { 
        width: 45px; 
        height: 45px; 
        border-radius: 10px; 
        border: 1px solid var(--border-soft); 
        object-fit: cover; 
    }
    
    /* Global Status Badges (Screenshot Style) */
    .status-badge { 
        font-size: 10px; 
        padding: 5px 12px; 
        border-radius: 4px; 
        font-weight: 700; 
        text-transform: uppercase; 
        letter-spacing: 0.5px; 
        display: inline-block;
    }
    .status-PENDING { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    .status-APPROVED { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .status-REJECTED { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    
    .btn-review-pro { 
        background: #f4f7fa; 
        color: var(--primary-blue); 
        border: 1px solid var(--border-soft); 
        padding: 6px 15px; 
        border-radius: 6px; 
        font-size: 11px; 
        font-weight: 700; 
        transition: 0.3s;
    }
    .btn-review-pro:hover { background: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }

    .doc-pill-pro { 
        background: #f8f9fa; 
        border: 1px solid var(--border-soft); 
        padding: 4px 12px; 
        border-radius: 6px; 
        font-size: 11px; 
        color: var(--text-dark); 
        font-weight: 600;
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-id-card mr-2 text-primary"></i> KYC Requests
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-5 col-12 mb-2">
                            <label class="small text-muted font-weight-bold">SEARCH ENTITY</label>
                            <input type="text" name="search" class="form-control form-control-pro" placeholder="Name or Email..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>

                        <div class="col-md-4 col-12 mb-2">
                            <label class="small text-muted font-weight-bold">STATUS FILTER</label>
                            <select name="filter" class="form-control form-control-pro" onchange="this.form.submit()">
                                <option value="PENDING"  <?= ($current_filter == 'PENDING') ? 'selected' : '' ?>>⏳ Pending Only</option>
                                <option value="APPROVED" <?= ($current_filter == 'APPROVED') ? 'selected' : '' ?>>✅ Approved History</option>
                                <option value="REJECTED" <?= ($current_filter == 'REJECTED') ? 'selected' : '' ?>>❌ Rejected History</option>
                                <option value="ALL"      <?= ($current_filter == 'ALL') ? 'selected' : '' ?>>📂 Show All</option>
                            </select>
                        </div>

                        <div class="col-md-3 col-12 mb-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm text-white" style="background: var(--primary-blue); border-radius: 8px; height: 45px; border: none;">
                                <i class="fas fa-search mr-1"></i> APPLY MAP
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 900px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="py-3 px-4 border-0" width="30%">Applicant Profile</th>
                                <th class="py-3 border-0" width="20%">Document Type</th>
                                <th class="py-3 border-0" width="20%">Submission Date</th>
                                <th class="py-3 text-center border-0" width="15%">Status</th>
                                <th class="py-3 text-right px-4 border-0" width="15%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($requests)): ?>
                                <?php foreach($requests as $r): ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="align-middle px-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= get_media_url($r->avatar, 'profile') ?>" 
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= $r->username ?>&background=f4f7fa&color=5d78ff';" 
                                                 class="user-avatar">
                                            <div class="ml-3">
                                                <div style="font-weight: 600; color: var(--text-dark); font-size: 14px;">
                                                    <?= $r->profile_name ?? $r->name ?? $r->username ?>
                                                </div>
                                                <small class="text-muted">@<?= strtoupper($r->username) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="doc-pill-pro">
                                            <i class="far fa-id-card mr-1 text-primary"></i> <?= strtoupper(str_replace('_', ' ', $r->document_type)) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if(isset($r->submitted_at)): ?>
                                            <div class="text-dark font-weight-bold" style="font-size: 13px;"><?= date('d M Y', strtotime($r->submitted_at)) ?></div>
                                            <small class="text-muted"><?= date('h:i A', strtotime($r->submitted_at)) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted small">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle text-center">
                                        <?php 
                                            $status = $r->status ?? 'PENDING';
                                            $badgeClass = 'status-PENDING';
                                            if($status == 'APPROVED') $badgeClass = 'status-APPROVED';
                                            if($status == 'REJECTED') $badgeClass = 'status-REJECTED';
                                        ?>
                                        <span class="status-badge <?= $badgeClass ?>">
                                            <?= $status ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <a href="<?= base_url('admin/kyc/view/'.$r->id) ?>" class="btn btn-review-pro shadow-sm">
                                            <?= ($r->status == 'PENDING') ? 'REVIEW' : 'VIEW' ?> <i class="fas fa-chevron-right ml-2" style="font-size: 10px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div style="opacity: 0.6;">
                                            <i class="fas fa-clipboard-check fa-3x mb-3 text-muted"></i>
                                            <h5 class="text-dark font-weight-bold">Queue is Empty!</h5>
                                            <p class="text-muted small">No KYC requests match your current filters.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>
