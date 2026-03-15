<?php

namespace Config;

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

// =====================================================================
// 🏠 DEFAULT ROUTE
// =====================================================================
$routes->get('/', 'Index::index');

// =====================================================================
// 🛠️ ADMIN PANEL ROUTES
// =====================================================================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function ($routes) {
    // --- Auth ---
    $routes->get('/', 'Auth::loginView');
    $routes->get('login', 'Auth::loginView');
    $routes->post('login', 'Auth::login');
    $routes->get('logout', 'Auth::logout');

    // --- Protected Admin Area ---
    $routes->group('', ['filter' => 'rbac'], function ($routes) {
        $routes->get('dashboard', 'Dashboard::index');

        // 1. Users Management
        $routes->group('users', function ($routes) {
            $routes->get('/', 'Users::index');
            $routes->get('view/(:num)', 'Users::view/$1');
            $routes->get('edit/(:num)', 'Users::edit/$1');
            $routes->post('update/(:num)', 'Users::update/$1');
            $routes->get('toggle_ban/(:num)', 'Users::toggle_ban/$1');
            $routes->get('delete/(:num)', 'Users::delete/$1');
        });

        // ✅ KYC MANAGEMENT
        $routes->group('kyc', function ($routes) {
            $routes->get('requests', 'Users::kyc_requests');
            $routes->get('view/(:num)', 'Users::kyc_view/$1');
            $routes->post('action', 'Users::kyc_action');
        });

        // ✅ CHANNEL MANAGEMENT
        $routes->group('channels', function ($routes) {
            $routes->get('/', 'Channels::index');
            $routes->get('view/(:num)', 'Channels::view/$1');
            $routes->get('edit/(:num)', 'Channels::edit/$1');
            $routes->post('update/(:num)', 'Channels::update/$1');
            $routes->get('delete/(:num)', 'Channels::delete/$1');
            $routes->get('monetization', 'Channels::monetization_requests');
            $routes->get('monetization/view/(:num)', 'Channels::monetization_view/$1');
            $routes->post('monetization/process', 'Channels::monetization_process');
            $routes->post('monetization_toggle_status/(:num)', 'Channels::monetization_toggle_status/$1');
            $routes->post('toggle_monetization', 'Channels::toggle_monetization');
            $routes->post('issue_strike', 'Channels::issue_strike');
        });

        // 2. Videos
        $routes->group('videos', function ($routes) {
            $routes->get('/', 'Videos::index');
            $routes->get('view/(:num)', 'Videos::view/$1');
            $routes->get('edit/(:num)', 'Videos::edit/$1');
            $routes->post('update/(:num)', 'Videos::update/$1');
            $routes->post('update_properties/(:num)', 'Videos::update_properties/$1');
            $routes->get('toggle_status/(:num)', 'Videos::toggle_status/$1');
            $routes->get('delete/(:num)', 'Videos::delete/$1');
            $routes->post('blacklist/(:num)', 'Videos::blacklist/$1');
        });

        // 3. Reels
        $routes->group('reels', function ($routes) {
            $routes->get('/', 'Reels::index');
            $routes->get('view/(:num)', 'Reels::view/$1');
            $routes->get('edit/(:num)', 'Reels::edit/$1');
            $routes->post('update_properties/(:num)', 'Reels::update_properties/$1');
            $routes->post('update/(:num)', 'Reels::update/$1');
            $routes->get('delete/(:num)', 'Reels::delete/$1');
        });

        // 4. Stories
        $routes->group('stories', function ($routes) {
            $routes->get('/', 'Stories::index');
            $routes->get('view/(:num)', 'Stories::view/$1');
            $routes->get('delete/(:num)', 'Stories::delete/$1');
        });

        // 5. Posts
        $routes->group('posts', function ($routes) {
            $routes->get('/', 'Posts::index');
            $routes->get('view/(:num)', 'Posts::view/$1');
            $routes->get('edit/(:num)', 'Roles::edit/$1');
            $routes->post('update/(:num)', 'Posts::update/$1');
            $routes->get('delete/(:num)', 'Posts::delete/$1');
            $routes->get('delete_comment/(:num)', 'Posts::delete_comment/$1');
        });

        // 6. Reports
        $routes->group('reports', function ($routes) {
            $routes->get('/', 'Reports::index');
            $routes->get('videos', 'Reports::videos');
            $routes->get('reels', 'Reports::reels');
            $routes->get('posts', 'Reports::posts');
            $routes->get('comments', 'Reports::comments');
            $routes->get('users', 'Reports::users');
            $routes->get('channels', 'Reports::channels');
            $routes->get('view/(:num)', 'Reports::view/$1');
            $routes->post('action', 'Reports::take_action');
            $routes->get('delete/(:num)', 'Reports::delete/$1');
        });

        // 🔥 COPYRIGHT & MODERATION
        $routes->group('moderation', function ($routes) {
            $routes->group('strikes', function ($routes) {
                $routes->get('/', 'Moderation\Strikes::index');
                $routes->get('claims', 'Moderation\Strikes::claims');
                $routes->get('appeals', 'Moderation\Strikes::appeals');
                $routes->get('view/(:num)', 'Moderation\Strikes::view/$1');
                $routes->post('store', 'Moderation\Strikes::store');
                $routes->post('report_decision', 'Moderation\Strikes::report_decision');
                $routes->post('appeal_action', 'Moderation\Strikes::appeal_action');
                $routes->get('remove/(:num)', 'Moderation\Strikes::remove/$1');
                $routes->post('verify_content', 'Moderation\Strikes::verify_content');
            });
        });

        // 7. Staff & Roles
        $routes->group('staff', function ($routes) {
            $routes->get('/', 'Staff::index');
            $routes->get('add', 'Staff::add');
            $routes->post('store', 'Staff::store');
            $routes->get('remove_admin/(:num)', 'Staff::remove_admin/$1');
        });
        $routes->group('roles', function ($routes) {
            $routes->get('/', 'Roles::index');
            $routes->get('create', 'Roles::create');
            $routes->post('store', 'Roles::store');
            $routes->get('edit/(:num)', 'Roles::edit/$1');
            $routes->post('update/(:num)', 'Roles::update/$1');
            $routes->get('delete/(:num)', 'Roles::delete/$1');
        });

        // 💰 FINANCE & PAYOUTS
        $routes->group('finance', function ($routes) {
            $routes->get('pool', 'Finance\Pool::index');
            $routes->post('pool/process', 'Finance\Pool::process_daily_payout');
            $routes->group('withdrawal', function ($routes) {
                $routes->get('/', 'Finance\Withdrawals::index');
                $routes->get('view/(:num)', 'Finance\Withdrawals::view/$1');
                $routes->post('approve', 'Finance\Withdrawals::approve');
                $routes->post('reject', 'Finance\Withdrawals::reject');
            });
        });

        // 📢 ADS MANAGER
        $routes->group('ads', function ($routes) {
            $routes->get('/', 'Ads::index');
            $routes->get('requests', 'Ads::requests');
            $routes->get('campaigns', 'Ads::campaigns');
            $routes->get('view/(:num)', 'Ads::view/$1');
            $routes->post('update_status', 'Ads::update_status');
            $routes->get('settings', 'Ads::settings');
            $routes->post('save_settings', 'Ads::save_settings');
            $routes->get('delete/(:num)', 'Ads::delete/$1');
        });

        // ⚙️ SYSTEM CONFIGURATION
        $routes->group('settings', function ($routes) {
            $routes->get('points', 'Settings\PointsController::index');
            $routes->post('points/update', 'Settings\PointsController::update');
            $routes->get('smtp', 'Settings\SmtpController::index');
            $routes->post('smtp/update', 'Settings\SmtpController::update');
            $routes->get('upload', 'Settings\UploadController::index');
            $routes->post('upload/update', 'Settings\UploadController::update');
        });
        
        $routes->get('logs', 'AdminLogs::index');

        // 📊 SUPER ADMIN ANALYTICS (NEWLY ADDED)
        $routes->group('analytics', function ($routes) {
            $routes->get('/', 'Analytics::index');
            $routes->get('dashboard-stats', 'Analytics::getDashboardData'); // Protected data API route
        });
    });
});

// =====================================================================
// 🚀 API ROUTES
// =====================================================================
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {

    // 1. Auth Public Routes
    $routes->group('auth', function ($routes) {
        $routes->post('login', 'AuthController::login');
        $routes->post('register', 'AuthController::register');
        $routes->post('check-username', 'AuthController::checkUsername');
        $routes->post('verify_otp', 'AuthController::verifyOtp');
        $routes->post('forgot_password', 'AuthController::forgotPassword');
        $routes->post('reset_password', 'AuthController::resetPassword');
        $routes->post('recover_account', 'AuthController::recoverAccount');
        $routes->post('logout', 'AuthController::logout');
        $routes->post('update_fcm_token', 'AuthController::updateFcmToken');

        $routes->group('', ['filter' => 'authFilter'], function ($routes) {
            $routes->get('me', 'AuthController::me');
        });
    });

    // 2. Public Settle Route
    $routes->get('ads/settle', 'Ads\SettlementController::settle_earnings');

    // ✅ SYSTEM CONFIG ROUTE
    $routes->group('system', ['namespace' => 'App\Controllers\Api\System'], function ($routes) {
        $routes->get('config', 'ConfigController::index');
    });

    // 🛡️ COPYRIGHT ROUTES - PUBLIC 🛡️
    $routes->group('creator', ['namespace' => 'App\Controllers\Api\Creator'], function ($routes) {
        // 1. Original videos ki list lane ke liye (app/creator/trust/index.tsx)
        $routes->get('copyright/original-videos', 'CopyrightController::getOriginalVideos');
        
        // 2. Specific video ke matched clips lane ke liye (app/creator/trust/matches/[id].tsx)
        $routes->get('copyright/matches/(:num)', 'CopyrightController::getMatchedClips/$1');
        
        // 3. Strike ya Claim action submit karne ke liye
        $routes->post('copyright/take-action', 'CopyrightController::takeAction');
    });

    // 🔒 AUTHENTICATED ROUTES
    $routes->group('', ['filter' => 'authFilter'], function ($routes) {

        // 🟢 NAYA GLOBAL SEARCH ROUTE
        $routes->get('global-search', 'SearchController::index');

        // 👤 USER CONTROLLER
        $routes->group('users', function ($routes) {
            $routes->get('fetch_profile', 'UserController::fetchProfile');
            $routes->post('edit_profile', 'UserController::editProfile');
            $routes->get('posts', 'UserController::getUserPosts');
            $routes->get('videos', 'UserController::getUserVideos');
            $routes->get('reels', 'ReelsController::getByUser');
            $routes->get('me/posts', 'UserController::getMyPosts');
            $routes->get('me/videos', 'UserController::getMyVideos');
            $routes->get('me/reels', 'UserController::getMyReels');
            $routes->get('saved-posts', 'UserController::getSavedPosts');
            $routes->get('chat-list', 'UserController::getChatList');
            $routes->post('heartbeat', 'UserController::heartbeat');
            $routes->post('submit_kyc', 'UserController::submitKYC');
            $routes->get('kyc_status', 'UserController::getKYCStatus');
            $routes->get('followers', 'UserController::getFollowers');
            $routes->get('following', 'UserController::getFollowing');
        });

        // 🔔 NOTIFICATIONS ROUTES
        $routes->group('notifications', function ($routes) {
            $routes->get('/', 'NotificationController::index');
            $routes->post('mark-read/all', 'NotificationController::markRead/all');
            $routes->post('mark-read/(:any)', 'NotificationController::markRead/$1');
        });

        $routes->group('users/action', ['namespace' => 'App\Controllers\Api\Actions'], function ($routes) {
            $routes->post('follow', 'SocialController::toggleFollow');
            $routes->post('unfollow', 'SocialController::toggleFollow');
            $routes->post('toggle_follow', 'UserController::toggleFollow');
            $routes->post('block', 'SocialController::toggleBlock');
        });

        $routes->group('interactions', ['namespace' => 'App\Controllers\Api\Actions'], function ($routes) {
            $routes->post('like', 'InteractionController::toggleLike');
            $routes->post('save', 'InteractionController::toggleSave');
            $routes->post('report', 'InteractionController::report');
            $routes->post('feedback', 'InteractionController::feedback');
            $routes->post('dislike', 'InteractionController::toggleDislike');
            $routes->post('share', 'InteractionController::share');
            // 🔥 NAYA ROUTE FOR BATCH IMPRESSIONS
            $routes->post('trackImpressions', 'InteractionController::trackImpressions');
        });

        $routes->get('home/feed', 'PostController::getFeed');

        $routes->group('posts', function ($routes) {
            $routes->get('details', 'PostController::getDetails');
            $routes->get('explore-user-feed', 'PostController::getExploreFeed');
            $routes->post('create', 'PostController::create');
            $routes->delete('delete/(:num)', 'PostController::delete/$1');
        });

        $routes->group('reels', function ($routes) {
            $routes->get('/', 'ReelsController::getFeed');
            $routes->get('details', 'ReelsController::getDetails');
            $routes->get('explore-user-feed', 'ReelsController::getByUser');
            $routes->post('upload', 'ReelsController::upload');
            $routes->post('track-watch', 'ReelsController::trackWatch');
            $routes->delete('delete/(:num)', 'ReelsController::delete/$1');
        });

        $routes->group('videos', function ($routes) {
            $routes->get('/', 'VideoController::getVideos');
            $routes->get('details', 'VideoController::getDetails');
            $routes->get('recommended', 'VideoController::getRecommended');
            $routes->post('track-watch', 'VideoController::trackWatch');
            $routes->post('view', 'VideoController::incrementView');
            $routes->post('upload', 'VideoController::upload');
            $routes->post('update/(:num)', 'VideoController::update/$1');
            $routes->delete('delete/(:num)', 'VideoController::delete/$1');
        });

        $routes->group('stories', function ($routes) {
            $routes->get('/', 'StoryController::getFeed');
            $routes->post('upload', 'StoryController::upload');
            $routes->get('viewers', 'StoryController::getViewers');
            $routes->post('action/view', 'StoryController::markViewed');
            $routes->post('action/react', 'StoryController::toggleReaction');
            $routes->post('action/reply', 'StoryController::quickReply');
            $routes->post('action/delete', 'StoryController::deleteStory');
        });

        $routes->group('channels', function ($routes) {
            $routes->get('details', 'ChannelController::getDetails');
            $routes->get('videos', 'ChannelController::getVideos');
            $routes->get('reels', 'ChannelController::getReels');
            $routes->get('check_user_channel', 'ChannelController::checkUserChannel');
            $routes->post('create', 'ChannelController::create');
            $routes->post('update', 'ChannelController::updateChannel');
            $routes->post('action/subscribe', 'Actions\SocialController::toggleFollow');
            $routes->post('action/unsubscribe', 'Actions\SocialController::toggleFollow');
        });

        $routes->group('creator', ['namespace' => 'App\Controllers\Api\Creator'], function ($routes) {
            $routes->get('dashboard', 'DashboardController::index');
            $routes->get('earnings', 'EarningsController::getDashboard');
            $routes->get('analytics/history', 'DashboardController::analytics');
            $routes->get('strikes', 'TrustController::strikeDetails');
            $routes->post('submit-strike-appeal/(:num)', 'TrustController::submitStrikeAppeal/$1');
            $routes->post('monetization/apply', 'TrustController::applyForMonetization');

            $routes->group('finance', function ($routes) {
                $routes->post('withdraw', 'WalletController::withdraw');
                $routes->get('history', 'WalletController::history');
                $routes->get('settings', 'PayoutSettingsController::index');
                $routes->post('save-settings', 'PayoutSettingsController::save');
            });

            $routes->get('content', 'ContentController::index');
            $routes->post('content/delete', 'ContentController::delete');
            $routes->post('content/update', 'ContentController::update');
            $routes->get('content/analytics/(:any)', 'ContentController::analytics/$1');
            $routes->get('content/details/(:any)', 'ContentController::details/$1');
        });

        $routes->group('rewards', ['namespace' => 'App\Controllers\Api\User'], function ($routes) {
            $routes->get('status', 'RewardsController::getStatus');
            $routes->post('claim-daily', 'RewardsController::claimDaily');
        });

        $routes->group('comments', function ($routes) {
            $routes->get('list', 'CommentController::list');
            $routes->post('add', 'CommentController::add');
            $routes->delete('delete', 'CommentController::delete');
        });

        // 🔥 STREAM VIDEO & CHAT ROUTES
        $routes->get('stream/token', 'StreamController::getToken');
        $routes->post('stream/create-call', 'StreamController::createCall');

        $routes->group('settings', ['namespace' => 'App\Controllers\Api\Settings'], function ($routes) {
            $routes->get('blocked-list', 'PrivacyController::getBlockedList');
            $routes->match(['GET', 'POST'], 'privacy', 'PrivacyController::handlePrivacy');
            $routes->match(['GET', 'POST'], 'notifications', 'NotificationController::handleNotifications');
            $routes->post('delete_account', 'AccountController::deleteAccount');

            $routes->group('security', function ($routes) {
                $routes->post('change-password', 'SecurityController::changePassword');
                $routes->group('2fa', function ($routes) {
                    $routes->get('status', 'TwoFactorController::getStatus');
                    $routes->post('enable', 'TwoFactorController::enable');
                    $routes->post('disable', 'TwoFactorController::disable');
                });
            });
        });

        $routes->group('wallet', ['namespace' => 'App\Controllers\Api\Creator'], function ($routes) {
            $routes->get('spending-balance', 'WalletController::get_spending_balance');
            $routes->post('add-money', 'WalletController::add_money');
            $routes->post('verify-recharge', 'WalletController::verify_recharge');
            $routes->get('transactions', 'WalletController::get_transactions');
        });

        $routes->group('ads', ['namespace' => 'App\Controllers\Api\Ads'], function ($routes) {
            $routes->post('create', 'CampaignController::create');
            $routes->get('my-ads', 'CampaignController::my_ads');
            $routes->post('toggle-status', 'CampaignController::toggle_status');
            $routes->get('settings-summary', 'SettingsController::summary');
            $routes->get('eligible', 'EngineController::get_instream_ad');
            $routes->match(['GET', 'POST'], 'track-view', 'TrackingController::track_view');
            $routes->match(['GET', 'POST'], 'track-impression', 'TrackingController::track_impression');
            $routes->post('track-click', 'TrackingController::track_click');
            $routes->get('analytics', 'CampaignController::get_analytics');
            // 🔥 NEW ROUTE FOR PROMOTESCREEN SETTINGS
            $routes->get('settings', 'CampaignController::get_settings');
        });

        $routes->group('hashtags', ['namespace' => 'App\Controllers\Api\System'], function ($routes) {
            $routes->get('search', 'HashtagController::search');
            $routes->get('trending', 'HashtagController::trending');
            $routes->get('(:segment)', 'HashtagController::feed/$1');
        });

    });
});

$routes->match(['GET', 'POST'], 'apiads/(:any)', function ($path) {
    return redirect()->to(base_url('api/ads/' . $path));
});
