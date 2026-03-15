<aside class="main-sidebar elevation-4">
    <a href="<?= base_url('admin/dashboard') ?>" class="brand-link text-center border-bottom-0">
        <span class="brand-text font-weight-bold" style="color: var(--primary-accent); letter-spacing: 2px;">MySocial</span>
    </a>

    <div class="sidebar mt-3">
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu">
                
                <li class="nav-item">
                    <a href="<?= base_url('admin/dashboard') ?>" class="nav-link <?= (url_is('admin/dashboard*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tachometer-alt"></i> 
                        <p>Dashboard</p>
                    </a>
                </li>

                <?php if(has_permission('roles.view') || has_permission('staff.view') || session()->get('role_id') == 1): ?>
                <li class="nav-header">STAFF CONTROL</li>
                
                <?php if(has_permission('roles.view') || session()->get('role_id') == 1): ?>
                <li class="nav-item">
                    <a href="<?= base_url('admin/roles') ?>" class="nav-link <?= (url_is('admin/roles*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-user-shield"></i> <p>Roles</p>
                    </a>
                </li>
                <?php endif; ?>

                <?php if(has_permission('staff.view') || session()->get('role_id') == 1): ?>
                <li class="nav-item">
                    <a href="<?= base_url('admin/staff') ?>" class="nav-link <?= (url_is('admin/staff*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users-cog"></i> <p>Staff List</p>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <li class="nav-header">APP MANAGEMENT</li>

                <?php if(has_permission('users.view') || session()->get('role_id') == 1): ?>
                <li class="nav-item <?= (url_is('admin/users*') || url_is('admin/kyc*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/users*') || url_is('admin/kyc*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-users"></i>
                        <p>Users <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('admin/users') ?>" class="nav-link <?= (url_is('admin/users') && !url_is('admin/kyc*')) ? 'active' : '' ?>">
                                <i class="fas fa-list nav-icon"></i> <p>All Users</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/kyc/requests') ?>" class="nav-link <?= (url_is('admin/kyc*')) ? 'active' : '' ?>">
                                <i class="fas fa-id-card nav-icon"></i> <p>KYC Requests</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(has_permission('channels.view') || session()->get('role_id') == 1): ?>
                <li class="nav-item <?= (url_is('admin/channels*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/channels*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-tv"></i>
                        <p>Channels <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="<?= base_url('admin/channels') ?>" class="nav-link <?= (url_is('admin/channels') && !url_is('admin/channels/monetization*')) ? 'active' : '' ?>">
                                <i class="fas fa-layer-group nav-icon"></i> <p>All Channels</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="<?= base_url('admin/channels/monetization') ?>" class="nav-link <?= (url_is('admin/channels/monetization*')) ? 'active' : '' ?>">
                                <i class="fas fa-hand-holding-usd nav-icon"></i> <p>Monetization Req.</p>
                            </a>
                        </li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item"><a href="<?= base_url('admin/videos') ?>" class="nav-link <?= (url_is('admin/videos*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-video"></i> <p>Videos</p></a></li>
                <li class="nav-item"><a href="<?= base_url('admin/reels') ?>" class="nav-link <?= (url_is('admin/reels*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-bolt"></i> <p>Reels</p></a></li>
                <li class="nav-item"><a href="<?= base_url('admin/stories') ?>" class="nav-link <?= (url_is('admin/stories*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-history"></i> <p>Stories</p></a></li>
                <li class="nav-item"><a href="<?= base_url('admin/posts') ?>" class="nav-link <?= (url_is('admin/posts*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-th-large"></i> <p>Posts</p></a></li>

                <?php if(has_permission('reports.manage') || session()->get('role_id') == 1): ?>
                <li class="nav-item <?= (url_is('admin/reports*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/reports*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-flag"></i>
                        <p>Reports <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="<?= base_url('admin/reports/videos') ?>" class="nav-link <?= (url_is('admin/reports/videos*')) ? 'active' : '' ?>"><i class="fas fa-video nav-icon"></i> <p>Videos</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/reports/reels') ?>" class="nav-link <?= (url_is('admin/reports/reels*')) ? 'active' : '' ?>"><i class="fas fa-bolt nav-icon"></i> <p>Reels</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/reports/posts') ?>" class="nav-link <?= (url_is('admin/reports/posts*')) ? 'active' : '' ?>"><i class="fas fa-th-large nav-icon"></i> <p>Posts</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/reports/comments') ?>" class="nav-link <?= (url_is('admin/reports/comments*')) ? 'active' : '' ?>"><i class="fas fa-comments nav-icon"></i> <p>Comments</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/reports/users') ?>" class="nav-link <?= (url_is('admin/reports/users*')) ? 'active' : '' ?>"><i class="fas fa-user-circle nav-icon"></i> <p>Users</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/reports/channels') ?>" class="nav-link <?= (url_is('admin/reports/channels*')) ? 'active' : '' ?>"><i class="fas fa-tv nav-icon"></i> <p>Channels</p></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(has_permission('strikes.manage') || session()->get('role_id') == 1): ?>
                <li class="nav-item <?= (url_is('admin/moderation*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/moderation*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-copyright"></i>
                        <p>Copyright <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="<?= base_url('admin/moderation/strikes') ?>" class="nav-link <?= (url_is('admin/moderation/strikes*')) ? 'active' : '' ?>"><i class="fas fa-list-ul nav-icon"></i> <p>All Strikes</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/moderation/strikes/claims') ?>" class="nav-link <?= (url_is('admin/moderation/strikes/claims*')) ? 'active' : '' ?>"><i class="fas fa-exclamation-circle nav-icon"></i> <p>Claims</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/moderation/strikes/appeals') ?>" class="nav-link <?= (url_is('admin/moderation/strikes/appeals*')) ? 'active' : '' ?>"><i class="fas fa-balance-scale nav-icon"></i> <p>Appeals</p></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-header">FINANCE & PAYOUTS</li>
                <li class="nav-item"><a href="<?= base_url('admin/finance/pool') ?>" class="nav-link <?= (url_is('admin/finance/pool*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-hand-holding-usd"></i> <p>Daily Pool</p></a></li>
                <?php if(has_permission('withdrawals.view') || session()->get('role_id') == 1): ?>
                <li class="nav-item"><a href="<?= base_url('admin/finance/withdrawal') ?>" class="nav-link <?= (url_is('admin/finance/withdrawal*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-university"></i> <p>Withdrawals</p></a></li>
                <?php endif; ?>

                <?php if(has_permission('ads.view') || has_permission('ads.approve') || session()->get('role_id') == 1): ?>
                <li class="nav-item <?= (url_is('admin/ads*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/ads*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-ad"></i>
                        <p>Ads Manager <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="<?= base_url('admin/ads/requests') ?>" class="nav-link <?= (url_is('admin/ads/requests*')) ? 'active' : '' ?>"><i class="fas fa-check-circle nav-icon"></i> <p>Ad Requests</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/ads/campaigns') ?>" class="nav-link <?= (url_is('admin/ads/campaigns*')) ? 'active' : '' ?>"><i class="fas fa-bullhorn nav-icon"></i> <p>Campaigns</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/ads/settings') ?>" class="nav-link <?= (url_is('admin/ads/settings*')) ? 'active' : '' ?>"><i class="fas fa-cogs nav-icon"></i> <p>Settings</p></a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if(session()->get('role_id') == 1): ?>
                <li class="nav-header">SYSTEM</li>
                
                <li class="nav-item"><a href="<?= base_url('admin/analytics') ?>" class="nav-link <?= (url_is('admin/analytics*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-chart-line"></i> <p>Analytics</p></a></li>

                <li class="nav-item <?= (url_is('admin/settings*')) ? 'menu-open' : '' ?>">
                    <a href="#" class="nav-link <?= (url_is('admin/settings*')) ? 'active' : '' ?>">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>Settings <i class="right fas fa-angle-left"></i></p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item"><a href="<?= base_url('admin/settings/points') ?>" class="nav-link <?= (url_is('admin/settings/points*')) ? 'active' : '' ?>"><i class="fas fa-sliders-h nav-icon"></i> <p>Points Config</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/settings/smtp') ?>" class="nav-link <?= (url_is('admin/settings/smtp*')) ? 'active' : '' ?>"><i class="fas fa-envelope-open-text nav-icon"></i> <p>SMTP Settings</p></a></li>
                        <li class="nav-item"><a href="<?= base_url('admin/settings/upload') ?>" class="nav-link <?= (url_is('admin/settings/upload*')) ? 'active' : '' ?>"><i class="fas fa-cloud-upload-alt nav-icon"></i> <p>Upload Config</p></a></li>
                    </ul>
                </li>
                <li class="nav-item"><a href="<?= base_url('admin/logs') ?>" class="nav-link <?= (url_is('admin/logs*')) ? 'active' : '' ?>"><i class="nav-icon fas fa-history"></i> <p>Activity Logs</p></a></li>
                <?php endif; ?>

                <li class="nav-item mt-4">
                    <a href="<?= base_url('admin/logout') ?>" class="nav-link text-danger">
                        <i class="nav-icon fas fa-sign-out-alt"></i> <p>Logout</p>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</aside>
