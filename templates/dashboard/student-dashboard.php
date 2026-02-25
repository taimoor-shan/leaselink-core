<?php
/**
 * Student Dashboard
 *
 * @package LeaseLink
 */

use StudentRentalPlatform\SRP_Template_Loader;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();

global $wpdb;
$table = $wpdb->prefix . 'rental_applications';

$total_apps = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE student_id = %d", $user_id));
$active_apps = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE student_id = %d AND application_status IN ('submitted', 'under_review')", $user_id));
$accepted_apps = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE student_id = %d AND application_status = 'accepted'", $user_id));

$saved_table = $wpdb->prefix . 'rental_saved_listings';
$saved_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$saved_table} WHERE student_id = %d", $user_id));

$msg_table = $wpdb->prefix . 'rental_messages';
$unread_msgs = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$msg_table} WHERE recipient_id = %d AND is_read = 0", $user_id));

$recent_apps = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, p.post_title as listing_title 
     FROM {$table} a 
     LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID 
     WHERE a.student_id = %d 
     ORDER BY a.submitted_at DESC LIMIT 5",
    $user_id
));

$status_colors = [
    'submitted' => 'primary',
    'under_review' => 'warning',
    'accepted' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'gray',
    'expired' => 'gray',
];
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'student']); ?>

    <div class="ll-dashboard-content">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-1">Welcome back,
                <?php echo esc_html(wp_get_current_user()->display_name); ?>!</h1>
            <p class="text-gray">Here's what's happening with your rental search.</p>
        </div>

        <div class="ll-stats-grid mb-8">
            <?php
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Active Applications', 'value' => $active_apps ?: 0, 'icon' => 'inbox', 'color' => 'primary']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Accepted', 'value' => $accepted_apps ?: 0, 'icon' => 'check', 'color' => 'success']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Saved Listings', 'value' => $saved_count ?: 0, 'icon' => 'heart', 'color' => 'danger']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Unread Messages', 'value' => $unread_msgs ?: 0, 'icon' => 'inbox', 'color' => 'warning']);
            ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <a href="<?php echo home_url('/search-listings/'); ?>"
                class="flex items-center gap-4 p-5 bg-white rounded-xl border border-zinc-100 shadow-sm hover:shadow-md transition-shadow no-underline group">
                <div
                    class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-dark mb-0.5 text-base">Browse Listings</h3>
                    <p class="text-sm text-gray mb-0">Search for your next home</p>
                </div>
            </a>
            <a href="<?php echo home_url('/my-applications/'); ?>"
                class="flex items-center gap-4 p-5 bg-white rounded-xl border border-zinc-100 shadow-sm hover:shadow-md transition-shadow no-underline group">
                <div
                    class="w-12 h-12 rounded-xl bg-accent/10 text-accent flex items-center justify-center shrink-0 group-hover:bg-accent group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-dark mb-0.5 text-base">My Applications</h3>
                    <p class="text-sm text-gray mb-0"><?php echo esc_html($total_apps); ?> total applications</p>
                </div>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
            <div class="p-5 border-b border-zinc-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold mb-0">Recent Applications</h2>
                    <a href="<?php echo home_url('/my-applications/'); ?>"
                        class="text-sm text-primary no-underline hover:underline">View All</a>
                </div>
            </div>
            <?php if (!empty($recent_apps)): ?>
                <div class="divide-y divide-zinc-100">
                    <?php foreach ($recent_apps as $app): ?>
                        <div class="p-5 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium text-dark text-sm mb-1 truncate">
                                    <?php echo esc_html($app->listing_title ?: 'Listing #' . $app->listing_id); ?>
                                </p>
                                <p class="text-xs text-gray mb-0">Applied
                                    <?php echo esc_html(human_time_diff(strtotime($app->submitted_at))); ?> ago</p>
                            </div>
                            <?php SRP_Template_Loader::get_template('components/badge.php', [
                                'text' => ucfirst(str_replace('_', ' ', $app->application_status)),
                                'color' => $status_colors[$app->application_status] ?? 'gray',
                            ]); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No applications yet',
                    'description' => 'Start browsing listings to find your perfect home.',
                    'icon' => 'inbox',
                    'action_url' => home_url('/search-listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>