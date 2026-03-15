<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 PROFESSIONAL UI ENGINE */
    .status-badge-pro { 
        font-size: 10px; padding: 5px 12px; border-radius: 4px; 
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; 
        display: inline-block;
    }
    
    /* Global Status Colors */
    .status-PENDING { background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); }
    .status-APPROVED { background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
    .status-REJECTED, .status-SUSPENDED { background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); }
    
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); overflow: hidden; }
    
    .channel-img { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; border: 1px solid var(--border-soft); }

    .btn-review-pro {
        background: #f4f7fa; color: var(--primary-blue); border: 1px solid var(--border-soft);
        font-weight: 700; padding: 5px 15px; border-radius: 6px; font-size: 11px; transition: 0.3s;
    }
    .btn-review-pro:hover { background: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-file-invoice-dollar mr-2 text-primary"></i> Monetization Requests
        </h1>
        <p class="text-muted small">Review applications for channel earnings activation</p>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background: #f8f9fa; font-size: 11px; color: var(--text-dark); text-transform: uppercase;">
                            <tr>
                                <th class="py-3 px-4 border-0">Channel</th>
                                <th class="py-3 border-0">Owner</th>
                                <th class="py-3 border-0">Subscribers</th>
                                <th class="py-3 border-0">Trust Score</th>
                                <th class="py-3 border-0">KYC Status</th>
                                <th class="py-3 border-0">Request Status</th>
                                <th class="py-3 text-right px-4 border-0">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($requests)): ?>
                                <?php foreach($requests as $r): ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-4 align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= get_media_url($r->avatar, 'channel') ?>" 
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($r->name) ?>&background=f4f7fa&color=5d78ff';" 
                                                 class="channel-img">
                                            <div class="ml-3">
                                                <div class="text-dark font-weight-bold" style="font-size: 14px;"><?= esc($r->name) ?></div>
                                                <small class="text-muted">@<?= esc($r->handle) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-600">@<?= esc($r->username) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-dark font-weight-bold"><?= number_format($r->subscribers_count ?? 0) ?></span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-primary font-weight-bold"><?= $r->trust_score ?>%</span>
                                    </td>
                                    <td class="align-middle">
                                        <?php if($r->kyc_status == 'APPROVED'): ?>
                                            <span class="badge" style="background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); font-size: 9px;">VERIFIED</span>
                                        <?php else: ?>
                                            <span class="badge" style="background: #f4f7fa; color: #888; font-size: 9px;">PENDING</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="status-badge-pro status-<?= $r->monetization_status ?>">
                                            <?= str_replace('_', ' ', $r->monetization_status) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <a href="<?= base_url('admin/channels/monetization/view/'.$r->id) ?>" class="btn btn-review-pro shadow-sm">
                                            REVIEW <i class="fas fa-chevron-right ml-1" style="font-size: 9px;"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-clipboard-check fa-3x text-muted mb-3 d-block opacity-50"></i>
                                        <p class="text-muted font-weight-bold">No pending requests found.</p>
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
