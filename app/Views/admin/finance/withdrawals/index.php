<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO THEME SYNC */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 15px 20px; }
    
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }

    /* Status Badges Pro */
    .st-badge-pro { font-weight: 800; font-size: 10px; padding: 5px 12px; border-radius: 4px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border: 1px solid transparent; }
    .st-completed { color: var(--accent-green) !important; background: rgba(10, 187, 135, 0.1) !important; border-color: var(--accent-green) !important; }
    .st-pending, .st-processing { color: var(--accent-orange) !important; background: rgba(249, 155, 45, 0.1) !important; border-color: var(--accent-orange) !important; }
    .st-failed, .st-rejected { color: var(--accent-red) !important; background: rgba(253, 57, 122, 0.1) !important; border-color: var(--accent-red) !important; }
    
    /* Text Styles */
    .black-name { color: #000 !important; font-weight: 700; font-size: 14px; }
    .amount-text-pro { font-size: 16px; font-weight: 800; color: #000; }
    .method-tag-pro { font-size: 10px; padding: 2px 8px; border-radius: 4px; background: #f4f7fa; color: var(--primary-blue); font-weight: 800; border: 1px solid var(--border-soft); text-transform: uppercase; }
    .utr-text-pro { font-size: 11px; color: var(--text-muted); font-family: 'Courier New', Courier, monospace; margin-top: 4px; display: block; font-weight: 600; }
    
    .action-btn-pro { 
        width: 36px; height: 36px; display: inline-flex; align-items: center; 
        justify-content: center; border-radius: 8px; transition: 0.2s; 
        border: 1px solid var(--border-soft); background: #fff; color: var(--primary-blue);
    }
    .action-btn-pro:hover { transform: translateY(-2px); box-shadow: var(--card-shadow); background: var(--primary-blue); color: #fff; border-color: var(--primary-blue); }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                <i class="fas fa-university mr-2 text-primary"></i> Withdrawal Requests
            </h1>
            <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-light btn-sm shadow-sm" style="font-weight: 600; border: 1px solid var(--border-soft); border-radius: 8px;">
                <i class="fas fa-arrow-left mr-1"></i> BACK
            </a>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card card-pro">
            <div class="card-body">
                <form action="" method="get">
                    <div class="row align-items-end">
                        <div class="col-md-9 col-12 mb-2">
                            <label class="small text-muted font-weight-bold text-uppercase">Search Records</label>
                            <input type="text" name="search" class="form-control form-control-pro" 
                                   placeholder="Channel name, handle, request ID or UTR..." 
                                   value="<?= esc($_GET['search'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 col-12 mb-2">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm" style="background: var(--primary-blue); border: none; height: 45px; border-radius: 8px;">
                                <i class="fas fa-filter mr-1"></i> FILTER
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-pro">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 900px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Request ID</th>
                                <th class="py-4 border-0">Creator</th>
                                <th class="py-4 border-0">Amount</th>
                                <th class="py-4 border-0">Payment Method / ID</th>
                                <th class="py-4 border-0">Status</th>
                                <th class="py-4 text-right px-4 border-0">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($requests)): ?>
                                <?php foreach($requests as $r): ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-4 align-middle font-weight-bold" style="color: var(--primary-blue);">#<?= $r->id ?></td>
                                    <td class="align-middle">
                                        <div class="black-name"><?= esc($r->channel_name) ?></div>
                                        <small class="text-primary font-weight-bold">@<?= strtoupper(esc($r->handle)) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <div class="amount-text-pro">₹<?= number_format($r->amount, 2) ?></div>
                                        <small class="text-muted font-weight-bold" style="font-size: 10px;"><?= date('d M Y', strtotime($r->created_at)) ?></small>
                                    </td>
                                    <td class="align-middle">
                                        <span class="method-tag-pro"><?= strtoupper(str_replace('_', ' ', $r->payment_method)) ?></span>
                                        <?php if(!empty($r->transaction_id)): ?>
                                            <span class="utr-text-pro" title="Transaction ID">
                                                <i class="fas fa-receipt mr-1"></i> <?= esc($r->transaction_id) ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="align-middle">
                                        <?php 
                                            $currentStatus = strtolower($r->status);
                                            $displayLabel = ($currentStatus == 'failed' || $currentStatus == 'rejected') ? 'REJECTED' : $currentStatus;
                                            $stClass = "st-" . $currentStatus;
                                        ?>
                                        <span class="st-badge-pro <?= $stClass ?>">
                                            <?= strtoupper($displayLabel) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <a href="<?= base_url('admin/finance/withdrawal/view/'.$r->id) ?>" 
                                           class="action-btn-pro" title="View Case Details">
                                           <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3 opacity-25"></i>
                                        <p class="text-muted font-weight-bold">No withdrawal requests found.</p>
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
