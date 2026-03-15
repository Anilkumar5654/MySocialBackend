<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* 🎨 PROFESSIONAL ANALYTICS UI */
    .card-pro { background: #fff; border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 20px; transition: all 0.3s ease; }
    .stat-title { font-size: 13px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
    .stat-value { font-size: 28px; font-weight: 900; color: var(--text-dark); margin-bottom: 0; transition: opacity 0.3s ease; }
    .text-green { color: #0abb87 !important; }
    .text-red { color: #fd397a !important; }
    
    .card-header-custom { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .card-title-custom { font-size: 16px; font-weight: 800; color: #3d4465; margin: 0; }
    
    .table-pro th { border-top: none; font-size: 12px; text-transform: uppercase; color: #a1a8c3; font-weight: 700; }
    .table-pro td { font-size: 14px; font-weight: 600; color: #5d6c8a; vertical-align: middle; }
    
    .status-dot { height: 12px; width: 12px; border-radius: 50%; display: inline-block; margin-right: 8px; }
    .dot-green { background-color: #0abb87; box-shadow: 0 0 5px rgba(10, 187, 135, 0.5); }
    .dot-yellow { background-color: #ffb822; box-shadow: 0 0 5px rgba(255, 184, 34, 0.5); }
    .dot-red { background-color: #fd397a; box-shadow: 0 0 5px rgba(253, 57, 122, 0.5); }
    
    .is-loading { opacity: 0.5; pointer-events: none; }
</style>

<div class="content-header">
    <div class="container-fluid">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h1 style="color: var(--text-dark); font-weight: 800; letter-spacing: -0.5px; display: flex; align-items: center;">
                <i class="fas fa-chart-line text-primary mr-2"></i> Plateform Analytics
                <div id="miniLoader" class="spinner-border spinner-border-sm text-primary ml-3" role="status" style="display: none;"></div>
            </h1>
            
            <select id="dateFilter" class="form-control" style="width: 200px; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <option value="last_hour">Last Hour</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="this_month">This Month</option>
                <option value="this_year">This Year</option>
                <option value="lifetime" selected>All Time (Lifetime)</option>
            </select>
        </div>
    </div>
</div>

<section class="content" id="dashboardContent">
    <div class="container-fluid">
        
        <div class="row">
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #ffb822;">
                    <div class="stat-title text-warning"><i class="fas fa-chart-pie mr-1"></i> Platform Net Profit</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valProfit">...</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #0abb87;">
                    <div class="stat-title text-success"><i class="fas fa-money-check-alt mr-1"></i> Total Creator Payouts</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valPayouts">...</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #17a2b8;">
                    <div class="stat-title text-info"><i class="fas fa-wallet mr-1"></i> Advertiser Wallets (Unspent)</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valSpendingWallet">...</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #6f42c1;">
                    <div class="stat-title" style="color: #6f42c1;"><i class="fas fa-piggy-bank mr-1"></i> Creator Wallets (Unwithdrawn)</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valCreatorWallet">...</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #ffc107;">
                    <div class="stat-title text-warning"><i class="fas fa-hourglass-half mr-1"></i> Pending Deposits</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valPendingDeposits">...</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-pro border-top-0 border-left-0 border-right-0" style="border-bottom: 4px solid #dc3545;">
                    <div class="stat-title text-danger"><i class="fas fa-hand-holding-usd mr-1"></i> Pending Withdrawals</div>
                    <div class="d-flex align-items-baseline">
                        <h3 class="stat-value" id="valPendingWithdrawals">...</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-8">
                <div class="card-pro h-100">
                    <div class="card-header-custom">
                        <h4 class="card-title-custom">Revenue vs. Payout Trend</h4>
                    </div>
                    <div style="height: 300px; position: relative;">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card-pro mb-3" style="border-bottom: 4px solid #fd397a; padding: 15px 20px;">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="stat-title" style="color: #fd397a;"><i class="fas fa-bullhorn mr-1"></i> Active Ad Campaigns</div>
                            <h3 class="stat-value" id="valCampaigns">...</h3>
                        </div>
                        <div class="text-muted opacity-50">
                            <i class="fas fa-ad fa-2x" style="opacity: 0.2;"></i>
                        </div>
                    </div>
                </div>

                <div class="card-pro" style="height: calc(100% - 105px);">
                    <div class="card-header-custom mb-1">
                        <h4 class="card-title-custom">P&L By Ad Model</h4>
                    </div>
                    <div style="height: 220px; position: relative;">
                        <canvas id="modelChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-4">
                <div class="card-pro h-100">
                    <div class="card-header-custom">
                        <h4 class="card-title-custom"><i class="fas fa-fire text-warning mr-2"></i> Top 5 Viral Hashtags</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-pro mb-0">
                            <thead><tr><th>Hashtag</th><th class="text-right">Posts/Views</th></tr></thead>
                            <tbody id="tblHashtags"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-pro h-100">
                    <div class="card-header-custom">
                        <h4 class="card-title-custom"><i class="fas fa-server text-info mr-2"></i> Infrastructure Health</h4>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-pro mb-0">
                            <thead><tr><th>Component</th><th class="text-right">Status</th></tr></thead>
                            <tbody>
                                <tr>
                                    <td>Video Processing Queue</td>
                                    <td class="text-right" id="infQueue">
                                        <span class="status-dot dot-green"></span> <span id="queueText">Healthy</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Platform Strikes (Active)</td>
                                    <td class="text-right" id="infStrikes">
                                        <span class="status-dot dot-yellow"></span> <span id="strikeText">0 Active</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Server Cron Jobs</td>
                                    <td class="text-right">
                                        <span class="status-dot dot-green"></span> Online
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card-pro h-100">
                    <div class="card-header-custom">
                        <h4 class="card-title-custom"><i class="fas fa-id-card text-success mr-2"></i> KYC Bottleneck</h4>
                    </div>
                    <div class="d-flex flex-column justify-content-center h-75 align-items-center text-center">
                        <h1 class="display-3 font-weight-bold text-dark" id="kycPending">...</h1>
                        <p class="text-muted font-weight-bold text-uppercase">Requests Pending Approval</p>
                        <a href="<?= base_url('admin/kyc/requests') ?>" class="btn btn-light border btn-sm mt-3 font-weight-bold shadow-sm">Review Now</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const miniLoader = document.getElementById('miniLoader');
    const dashboardContent = document.getElementById('dashboardContent');
    const dateFilter = document.getElementById('dateFilter');
    
    let trendChartObj = null;
    let modelChartObj = null;

    // Format Number to INR
    const formatMoney = (amount) => {
        return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR' }).format(amount || 0);
    };

    function loadAnalytics(filterValue) {
        miniLoader.style.display = 'inline-block';
        dashboardContent.classList.add('is-loading');

        const apiUrl = "<?= base_url('admin/analytics/dashboard-stats') ?>?filter=" + filterValue;

        fetch(apiUrl)
            .then(async response => {
                const text = await response.text(); 
                try {
                    return { ok: response.ok, status: response.status, data: JSON.parse(text) };
                } catch (e) {
                    throw new Error("Invalid Response. Check API route.");
                }
            })
            .then(res => {
                if (!res.ok) throw new Error(`Server Error: ${res.status}`);
                
                const dataObj = res.data;
                if(dataObj.status === 'success') {
                    const data = dataObj.data;

                    // 1. Update Top Row (Core Financials)
                    document.getElementById('valProfit').innerText = formatMoney(data.cards.net_profit);
                    document.getElementById('valPayouts').innerText = formatMoney(data.cards.total_payouts);

                    // 2. Update Row 2 (Wallets & Pending) & Advertiser Wallet
                    if(data.wallet_balances) {
                        document.getElementById('valSpendingWallet').innerText = formatMoney(data.wallet_balances.total_spending);
                        document.getElementById('valCreatorWallet').innerText = formatMoney(data.wallet_balances.total_creator);
                        document.getElementById('valPendingDeposits').innerText = formatMoney(data.wallet_balances.pending_deposits);
                        document.getElementById('valPendingWithdrawals').innerText = formatMoney(data.wallet_balances.pending_withdrawals);
                    }

                    // 3. Update Active Ads Card (Moved to right side)
                    document.getElementById('valCampaigns').innerText = data.cards.active_campaigns;

                    // 4. Update Hashtags
                    let hashHtml = '';
                    if(data.summary && data.summary.viral_hashtags && data.summary.viral_hashtags.length > 0) {
                        data.summary.viral_hashtags.forEach(h => {
                            hashHtml += `<tr>
                                            <td class="text-dark">#${h.tag}</td>
                                            <td class="text-right text-primary">${Number(h.posts_count).toLocaleString()}</td>
                                         </tr>`;
                        });
                    } else {
                        hashHtml = `<tr><td colspan="2" class="text-center text-muted py-3">No active hashtags</td></tr>`;
                    }
                    document.getElementById('tblHashtags').innerHTML = hashHtml;

                    // 5. Update Infrastructure & KYC
                    document.getElementById('kycPending').innerText = data.summary.pending_kyc;
                    let qPending = (data.summary.ffmpeg_queue && data.summary.ffmpeg_queue.pending) ? data.summary.ffmpeg_queue.pending : 0;
                    let qFailed = (data.summary.ffmpeg_queue && data.summary.ffmpeg_queue.failed) ? data.summary.ffmpeg_queue.failed : 0;
                    let qText = 'Healthy'; let qClass = 'dot-green';
                    
                    if(qFailed > 10) { qText = 'Critical Load'; qClass = 'dot-red'; }
                    else if(qPending > 50) { qText = 'High Load'; qClass = 'dot-yellow'; }
                    document.getElementById('infQueue').innerHTML = `<span class="status-dot ${qClass}"></span> ${qText} (${qPending})`;
                    
                    document.getElementById('strikeText').innerText = `${data.summary.active_strikes} Active`;

                    // 6. Update Trend Chart
                    if (trendChartObj) trendChartObj.destroy();
                    if(data.charts && data.charts.revenue_trend) {
                        const trendLabels = data.charts.revenue_trend.map(item => item.date);
                        const trendRevenue = data.charts.revenue_trend.map(item => item.daily_revenue);
                        const trendPayout = data.charts.revenue_trend.map(item => item.daily_payout);

                        trendChartObj = new Chart(document.getElementById('trendChart'), {
                            type: 'line',
                            data: {
                                labels: trendLabels.length ? trendLabels : ['No Data'],
                                datasets: [
                                    { label: 'Gross Revenue', data: trendRevenue.length ? trendRevenue : [0], borderColor: '#0abb87', backgroundColor: 'rgba(10, 187, 135, 0.1)', tension: 0.4, fill: true, borderWidth: 3 },
                                    { label: 'Creator Payouts', data: trendPayout.length ? trendPayout : [0], borderColor: '#5d78ff', backgroundColor: 'transparent', tension: 0.4, borderWidth: 3 }
                                ]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } } }
                        });
                    }

                    // 7. Update Bar Chart
                    if (modelChartObj) modelChartObj.destroy();
                    if(data.charts && data.charts.ad_models) {
                        const modelLabels = data.charts.ad_models.map(item => (item.bid_type || 'Unknown').toUpperCase());
                        const modelData = data.charts.ad_models.map(item => item.total_spent);

                        modelChartObj = new Chart(document.getElementById('modelChart'), {
                            type: 'bar',
                            data: {
                                labels: modelLabels.length ? modelLabels : ['CPC', 'CPM', 'CPV'],
                                datasets: [{
                                    label: 'Total Spent',
                                    data: modelData.length ? modelData : [0, 0, 0],
                                    backgroundColor: ['#5d78ff', '#0abb87', '#ffb822'],
                                    borderRadius: 6
                                }]
                            },
                            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
                        });
                    }

                    miniLoader.style.display = 'none';
                    dashboardContent.classList.remove('is-loading');
                }
            })
            .catch(error => {
                console.error("Dashboard Analytics Error:", error);
                miniLoader.style.display = 'none';
                dashboardContent.classList.remove('is-loading');
                alert("Failed to load analytics data. Please refresh.");
            });
    }

    dateFilter.addEventListener('change', function() {
        loadAnalytics(this.value);
    });

    if(dateFilter) {
        loadAnalytics(dateFilter.value);
    }
});
</script>

<?= $this->endSection() ?>
