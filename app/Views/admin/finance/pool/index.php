<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* 🎨 GLOBAL PRO THEME SYNC */
    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 50px; border-radius: 8px; font-weight: 600; 
    }
    .form-control-pro:focus { border-color: var(--primary-blue) !important; box-shadow: var(--card-shadow); }
    .form-control-pro:read-only { background: #f8f9fa !important; color: #888 !important; cursor: not-allowed; }
    
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: var(--card-shadow); margin-bottom: 25px; overflow: hidden; }
    .card-header-pro { background: #f8f9fa; border-bottom: 1px solid var(--border-soft); padding: 20px 25px; }
    
    .input-addon-pro { background: #f4f7fa; border: 1px solid var(--border-soft); color: var(--text-muted); width: 50px; justify-content: center; display: flex; align-items: center; border-radius: 8px 0 0 8px; }
    .input-group .form-control-pro { border-radius: 0 8px 8px 0; }

    /* Calculation Preview Box */
    .preview-box-pro { background: #f4f7fa; border: 1px dashed var(--border-soft); border-radius: 12px; padding: 20px; margin-top: 25px; }
    .preview-label-pro { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 5px; letter-spacing: 0.5px; }
    .preview-value-pro { font-size: 22px; font-weight: 800; color: #000; }
    
    /* Table Sync */
    .table-pro thead th { background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px; font-weight: 800; border: none; padding: 15px; }
    .table-pro td { vertical-align: middle; padding: 15px; border-top: 1px solid var(--border-soft); }
    
    .pill-pro { background: #fff; border: 1px solid var(--border-soft); padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; color: var(--primary-blue); }
    .black-text { color: #000 !important; font-weight: 700; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
                    <i class="fas fa-hand-holding-usd mr-2 text-primary"></i> Earnings Distribution
                </h1>
                <p class="text-muted small mb-0">Calculate and distribute payouts to creators</p>
            </div>
        </div>
    </div>
</div>

<section class="content pb-5">
    <div class="container-fluid">
        
        <?php if($yesterday_points <= 0): ?>
            <div class="alert shadow-sm mb-4" role="alert" style="background: rgba(10, 187, 135, 0.05); border: 1px solid var(--accent-green); border-radius: 10px;">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle fa-2x mr-3" style="color: var(--accent-green);"></i>
                    <div>
                        <h6 class="mb-1 black-text">ALL CLEAR!</h6>
                        <span class="small text-muted">There are no pending points to settle today. All creators are up to date.</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card card-pro h-100">
                    <div class="card-header-pro">
                        <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-calculator mr-2 text-primary"></i> DISTRIBUTE PAYOUT</h6>
                    </div>
                    <form id="payoutForm">
                        <div class="card-body">
                            <div class="form-group mb-4">
                                <label class="small text-muted font-weight-bold text-uppercase mb-2">Unpaid Points (Total)</label>
                                <div class="input-group">
                                    <div class="input-addon-pro"><i class="fas fa-star text-warning"></i></div>
                                    <input type="text" class="form-control-pro" value="<?= number_format($yesterday_points) ?>" readonly id="total_points" data-raw="<?= $yesterday_points ?>">
                                </div>
                                <small class="text-muted">Total points earned by users that haven't been paid yet.</small>
                            </div>

                            <div class="form-group">
                                <label class="small text-muted font-weight-bold text-uppercase mb-2">Ad Network Revenue ($)</label>
                                <div class="input-group">
                                    <div class="input-addon-pro text-success font-weight-bold">$</div>
                                    <input type="number" step="0.01" class="form-control-pro" id="meta_revenue" name="meta_revenue" placeholder="0.00" required>
                                </div>
                                <small class="text-muted mt-2 d-block">Enter total revenue received from Ads to calculate shares.</small>
                            </div>

                            <div class="preview-box-pro">
                                <div class="row text-center">
                                    <div class="col-6 border-right">
                                        <div class="preview-label-pro">Creators' Share (55%)</div>
                                        <div class="preview-value-pro text-success">$<span id="preview_pool">0.00</span></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="preview-label-pro">Point Value</div>
                                        <div class="preview-value-pro" style="color: var(--primary-blue);">$<span id="preview_rate">0.0000</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <button type="submit" class="btn btn-primary btn-block font-weight-bold shadow-sm" id="btnProcess" 
                                style="background: var(--primary-blue); border: none; height: 50px; border-radius: 8px;"
                                <?= ($yesterday_points <= 0) ? 'disabled' : '' ?>>
                                <i class="fas fa-paper-plane mr-2"></i> START DISTRIBUTION
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card card-pro h-100">
                    <div class="card-header-pro">
                         <h6 class="text-dark m-0 font-weight-bold"><i class="fas fa-history mr-2 text-primary"></i> RECENT HISTORY</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-pro mb-0">
                                <thead>
                                    <tr>
                                        <th class="pl-4">Date</th>
                                        <th>Ad Revenue</th>
                                        <th>Creators Share</th>
                                        <th class="text-center">Rate</th>
                                        <th class="text-right pr-4">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($logs)): ?>
                                        <?php foreach($logs as $log): ?>
                                        <tr>
                                            <td class="pl-4 black-text">
                                                <?= date('d M, Y', strtotime($log['date'])) ?>
                                            </td>
                                            <td class="text-muted">$<?= number_format($log['meta_revenue'], 2) ?></td>
                                            <td class="text-success font-weight-bold">$<?= number_format($log['creator_pool_amount'], 2) ?></td>
                                            <td class="text-center">
                                                <span class="pill-pro">$<?= number_format($log['coin_rate'], 5) ?></span>
                                            </td>
                                            <td class="text-right pr-4">
                                                <span class="badge" style="background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); padding: 5px 12px;">PAID</span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5">
                                                <i class="fas fa-receipt fa-3x text-muted mb-3 opacity-25"></i>
                                                <p class="text-muted font-weight-bold">No distribution history found.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.jQuery) {
        (function($) {
            // 1. LIVE CALCULATION
            $('#meta_revenue').on('input', function() {
                let revenue = parseFloat($(this).val()) || 0;
                let points = parseFloat($('#total_points').data('raw')) || 1; 
                let pool = revenue * 0.55; 
                let rate = pool / points;
                $('#preview_pool').text(pool.toFixed(2));
                $('#preview_rate').text(rate.toFixed(5));
            });

            // 2. DISTRIBUTION PROCESS
            $('#payoutForm').submit(function(e) {
                e.preventDefault();
                let revenue = $('#meta_revenue').val();
                let btn = $('#btnProcess');

                Swal.fire({
                    title: 'Distribute Funds?',
                    html: `Confirm distribution of <b class="text-success">$${(revenue * 0.55).toFixed(2)}</b><br>among all pending point holders.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#5d78ff', 
                    cancelButtonColor: '#abb3ba',
                    confirmButtonText: 'YES, DISTRIBUTE',
                    background: '#fff', color: '#3d4465'
                }).then((result) => {
                    if (result.isConfirmed) {
                        btn.prop('disabled', true).html('<i class="fas fa-circle-notch fa-spin mr-2"></i> PROCESSING...');
                        $.ajax({
                            url: '<?= base_url("admin/finance/pool/process") ?>',
                            type: 'POST',
                            data: { meta_revenue: revenue, <?= csrf_token() ?>: '<?= csrf_hash() ?>' },
                            success: function(response) {
                                if(response.status === 'success') {
                                    Swal.fire({ title: 'Success!', text: response.msg, icon: 'success', confirmButtonColor: '#5d78ff' })
                                    .then(() => { location.reload(); });
                                } else {
                                    Swal.fire({ title: 'Error!', text: response.msg, icon: 'error' });
                                    btn.prop('disabled', false).text('START DISTRIBUTION');
                                }
                            }
                        });
                    }
                });
            });
        })(window.jQuery);
    }
});
</script>

<?= $this->endSection() ?>
