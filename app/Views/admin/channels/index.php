<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<style>
    /* Professional Channel Styling */
    .channel-avatar { 
        width: 45px; height: 45px; border-radius: 10px; 
        object-fit: cover; border: 1px solid var(--border-soft); background: #f8f9fa; 
    }
    
    /* Global Score Badges */
    .score-badge { font-weight: 700; font-size: 11px; padding: 5px 12px; border-radius: 6px; letter-spacing: 0.5px; display: inline-block; }
    .score-high { color: var(--accent-green); background: rgba(10, 187, 135, 0.1); border: 1px solid var(--accent-green); }
    .score-mid { color: var(--accent-orange); background: rgba(249, 155, 45, 0.1); border: 1px solid var(--accent-orange); }
    .score-low { color: var(--accent-red); background: rgba(253, 57, 122, 0.1); border: 1px solid var(--accent-red); }
    
    /* 🟢 DYNAMIC STATUS ENGINE 🟢 */
    .status-pill-pro { 
        font-size: 10px; padding: 4px 10px; border-radius: 4px; font-weight: 800; 
        text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; 
    }

    /* Approved / Active */
    .st-approved, .st-active { 
        background: rgba(10, 187, 135, 0.1); color: var(--accent-green); border: 1px solid var(--accent-green); 
    }

    /* Pending / Review */
    .st-pending, .st-review { 
        background: rgba(249, 155, 45, 0.1); color: var(--accent-orange); border: 1px solid var(--accent-orange); 
    }

    /* Suspended / Rejected */
    .st-suspended, .st-rejected, .st-banned { 
        background: rgba(253, 57, 122, 0.1); color: var(--accent-red); border: 1px solid var(--accent-red); 
    }

    /* Not Applied / Default */
    .st-not_applied, .st-off { 
        background: #f4f7fa; color: #888; border: 1px solid var(--border-soft); 
    }

    .stat-pill-mini { display: block; font-size: 14px; color: var(--text-dark); font-weight: 700; line-height: 1; }
    .stat-label-mini { font-size: 10px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; }

    .form-control-pro { 
        background: #fff !important; border: 1px solid var(--border-soft) !important; 
        color: var(--text-dark) !important; height: 45px; border-radius: 8px; 
    }
</style>

<div class="content-header">
    <div class="container-fluid">
        <h1 style="color: var(--text-dark); font-weight: 700; letter-spacing: -0.5px;">
            <i class="fas fa-tv mr-2 text-primary"></i> All Channels
        </h1>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        
        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
            <div class="card-body">
                <form action="" method="get">
                    <div class="row">
                        <div class="col-md-9 col-12 mb-2">
                            <input type="text" name="search" class="form-control form-control-pro" 
                                   placeholder="Search Channel Name, Handle or Owner..." value="<?= $_GET['search'] ?? '' ?>">
                        </div>
                        <div class="col-md-3 col-12 mb-2">
                            <button type="submit" class="btn btn-block font-weight-bold shadow-sm" 
                                    style="background: var(--primary-blue); color: white; height: 45px; border-radius: 8px; border: none;">
                                <i class="fas fa-search mr-1"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0" style="min-width: 950px;">
                        <thead style="background: #f8f9fa; color: var(--text-dark); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px;">
                            <tr>
                                <th class="py-4 px-4 border-0">Channel Entity</th>
                                <th class="py-4 border-0">Trust Standing</th>
                                <th class="py-4 border-0">Engagement Stats</th>
                                <th class="py-4 border-0">Monetization Status</th>
                                <th class="py-4 text-right px-4 border-0">Administrative Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(!empty($channels)): ?>
                                <?php foreach($channels as $c): ?>
                                <tr style="border-bottom: 1px solid var(--border-soft);">
                                    <td class="px-4 align-middle">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= get_media_url($c->avatar, 'channel') ?>" 
                                                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($c->name) ?>&background=f4f7fa&color=5d78ff';" 
                                                 class="channel-avatar">
                                            <div class="ml-3">
                                                <div class="text-dark font-weight-bold" style="font-size: 14px;"><?= esc($c->name) ?></div>
                                                <div class="small text-muted">@<?= strtoupper(esc($c->handle)) ?> • <span class="text-primary font-weight-600"><?= esc($c->owner_name ?? 'Creator') ?></span></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <?php 
                                            $score = $c->trust_score ?? 0;
                                            $sClass = ($score >= 80) ? 'score-high' : (($score >= 50) ? 'score-mid' : 'score-low');
                                        ?>
                                        <span class="score-badge <?= $sClass ?>">
                                            <i class="fas fa-shield-alt mr-1"></i> <?= $score ?>%
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <span class="stat-pill-mini"><?= number_format($c->subscribers_count ?? 0) ?></span>
                                        <span class="stat-label-mini">Subscribers</span>
                                    </td>
                                    <td class="align-middle">
                                        <?php 
                                            // Status and Color Logic
                                            $mStatus = $c->monetization_status ?? 'NOT_APPLIED';
                                            $mClass = 'st-' . strtolower($mStatus);
                                            
                                            // If manually enabled, force Active Green
                                            if($c->is_monetization_enabled == 1) {
                                                $mStatus = 'ACTIVE';
                                                $mClass = 'st-active';
                                            }
                                        ?>
                                        <span class="status-pill-pro <?= $mClass ?>">
                                            <i class="fas <?= ($mStatus == 'ACTIVE' || $mStatus == 'APPROVED') ? 'fa-check-circle' : 'fa-info-circle' ?> mr-1"></i> 
                                            <?= str_replace('_', ' ', $mStatus) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle text-right px-4">
                                        <div class="btn-group">
                                            <?php if (has_permission('channels.view')): ?>
                                                <a href="<?= base_url("admin/channels/view/{$c->id}") ?>" 
                                                   class="btn btn-sm" 
                                                   style="color: var(--primary-blue); border: 1px solid var(--primary-blue); border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (has_permission('channels.edit')): ?>
                                                <a href="<?= base_url("admin/channels/edit/{$c->id}") ?>" 
                                                   class="btn btn-sm" 
                                                   style="color: var(--accent-orange); border: 1px solid var(--accent-orange); border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; margin-right: 5px;">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if (has_permission('channels.delete')): ?>
                                                <button type="button" 
                                                        class="btn btn-sm delete-channel-btn" 
                                                        data-url="<?= base_url("admin/channels/delete/{$c->id}") ?>" 
                                                        data-name="<?= esc($c->name) ?>"
                                                        style="color: var(--accent-red); border: 1px solid var(--accent-red); border-radius: 6px; width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-5 text-muted font-weight-bold">No channels discovered in the directory.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
/**
 * 🔥 Native JS Logic taaki pop-up jQuery ke bina bhi chale
 */
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-channel-btn');
    if (btn) {
        e.preventDefault();
        const url = btn.getAttribute('data-url');
        const channelName = btn.getAttribute('data-name');

        Swal.fire({
            title: 'Delete this Channel?',
            text: "Target: @" + channelName + "\nAll linked videos and monetization settings will be removed!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#fd397a', 
            cancelButtonColor: '#abb3ba',
            confirmButtonText: 'Yes, Delete Channel',
            background: '#fff',
            color: '#3d4465'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
});
</script>

<?= $this->endSection() ?>
