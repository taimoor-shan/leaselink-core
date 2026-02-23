<?php
/**
 * Shortcode Template: Student Dashboard
 *
 * Rendered by [leaselink_student_dashboard] shortcode.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();

global $wpdb;
$table = $wpdb->prefix . 'rental_applications';

$active_apps = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE student_id = %d AND application_status IN ('submitted', 'under_review')",
    $user_id
));
$accepted_apps = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table} WHERE student_id = %d AND application_status = 'accepted'",
    $user_id
));

$saved_table = $wpdb->prefix . 'rental_saved_listings';
$saved_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$saved_table} WHERE student_id = %d", $user_id));

$recent_apps = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, p.post_title as listing_title FROM {$table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID WHERE a.student_id = %d ORDER BY a.submitted_at DESC LIMIT 5",
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
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Welcome back,
            <?php echo esc_html(wp_get_current_user()->display_name); ?>!
        </h1>
        <p style="color:var(--ll-gray);margin-bottom:2rem;">Here's what's happening with your rental search.</p>

        <div class="ll-stats-grid" style="margin-bottom:2rem;">
            <?php
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Active Applications', 'value' => $active_apps ?: 0, 'color' => 'primary']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Accepted', 'value' => $accepted_apps ?: 0, 'color' => 'success']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Saved Listings', 'value' => $saved_count ?: 0, 'color' => 'danger']);
            ?>
        </div>

        <!-- Quick Actions -->
        <div
            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;margin-bottom:2rem;">
            <a href="<?php echo home_url('/listings/'); ?>" class="ll-card"
                style="padding:1.25rem;text-decoration:none;color:inherit;display:flex;align-items:center;gap:1rem;">
                <div
                    style="width:48px;height:48px;border-radius:var(--ll-radius-lg);background:rgba(79,70,229,0.1);color:var(--ll-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div>
                    <p style="font-weight:600;margin:0;color:var(--ll-dark);">Browse Listings</p>
                    <p style="font-size:0.8125rem;color:var(--ll-gray);margin:0;">Find your next home</p>
                </div>
            </a>
        </div>

        <!-- Recent Applications -->
        <div class="ll-card">
            <div
                style="padding:1.25rem;border-bottom:1px solid var(--ll-border);display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.125rem;font-weight:600;margin:0;">Recent Applications</h2>
                <a href="<?php echo home_url('/my-applications/'); ?>"
                    style="font-size:0.875rem;color:var(--ll-primary);">View All</a>
            </div>
            <?php if (!empty($recent_apps)): ?>
                <?php foreach ($recent_apps as $app): ?>
                    <div
                        style="padding:1rem 1.25rem;border-bottom:1px solid var(--ll-border);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <p style="font-weight:500;font-size:0.875rem;margin:0 0 0.25rem;color:var(--ll-dark);">
                                <?php echo esc_html($app->listing_title ?: 'Listing #' . $app->listing_id); ?>
                            </p>
                            <p style="font-size:0.75rem;color:var(--ll-gray);margin:0;">Applied
                                <?php echo esc_html(human_time_diff(strtotime($app->submitted_at))); ?> ago
                            </p>
                        </div>
                        <?php SRP_Template_Loader::get_template('components/badge.php', [
                            'text' => ucfirst(str_replace('_', ' ', $app->application_status)),
                            'color' => $status_colors[$app->application_status] ?? 'gray',
                        ]); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No applications yet',
                    'description' => 'Start browsing to find your perfect home.',
                    'icon' => 'inbox',
                    'action_url' => home_url('/listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>