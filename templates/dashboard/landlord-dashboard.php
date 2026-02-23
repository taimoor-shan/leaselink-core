<?php
/**
 * Shortcode Template: Landlord Dashboard
 * @package LeaseLink
 * @since   1.1.0
 */
use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();

$properties = get_posts(['post_type' => 'cpt_property', 'author' => $user_id, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids']);
$property_count = count($properties);

$listing_ids = [];
if (!empty($properties)) {
    $listing_ids = get_posts(['post_type' => 'cpt_listing', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => [['key' => '_listing_property_id', 'value' => $properties, 'compare' => 'IN']]]);
}

$total_views = 0;
foreach ($listing_ids as $lid) {
    $total_views += absint(get_post_meta($lid, '_view_count', true));
}

global $wpdb;
$app_table = $wpdb->prefix . 'rental_applications';
$pending_apps = 0;
$recent_apps = [];

if (!empty($listing_ids)) {
    $ids_str = implode(',', array_map('intval', $listing_ids));
    $pending_apps = $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE listing_id IN ({$ids_str}) AND application_status IN ('submitted','under_review')");
    $recent_apps = $wpdb->get_results("SELECT a.*, p.post_title as listing_title, u.display_name as student_name FROM {$app_table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID LEFT JOIN {$wpdb->users} u ON a.student_id = u.ID WHERE a.listing_id IN ({$ids_str}) ORDER BY a.submitted_at DESC LIMIT 5");
}

$status_colors = ['submitted' => 'primary', 'under_review' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'withdrawn' => 'gray'];
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content">
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Landlord Dashboard</h1>
        <p style="color:var(--ll-gray);margin-bottom:2rem;">Manage your properties and track applications.</p>

        <div class="ll-stats-grid" style="margin-bottom:2rem;">
            <?php
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Properties', 'value' => $property_count, 'color' => 'primary']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Total Views', 'value' => number_format($total_views), 'color' => 'accent']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Pending Review', 'value' => $pending_apps ?: 0, 'color' => 'danger']);
            ?>
        </div>

        <!-- Quick Actions -->
        <div
            style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:1rem;margin-bottom:2rem;">
            <a href="<?php echo home_url('/add-property/'); ?>" class="ll-card"
                style="padding:1.25rem;text-decoration:none;color:inherit;display:flex;align-items:center;gap:1rem;">
                <div
                    style="width:48px;height:48px;border-radius:var(--ll-radius-lg);background:rgba(79,70,229,0.1);color:var(--ll-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <p style="font-weight:600;margin:0;color:var(--ll-dark);">Add Property</p>
                    <p style="font-size:0.8125rem;color:var(--ll-gray);margin:0;">List a new property</p>
                </div>
            </a>
            <a href="<?php echo home_url('/landlord-applications/'); ?>" class="ll-card"
                style="padding:1.25rem;text-decoration:none;color:inherit;display:flex;align-items:center;gap:1rem;">
                <div
                    style="width:48px;height:48px;border-radius:var(--ll-radius-lg);background:rgba(245,158,11,0.1);color:var(--ll-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div>
                    <p style="font-weight:600;margin:0;color:var(--ll-dark);">Review Applications</p>
                    <p style="font-size:0.8125rem;color:var(--ll-gray);margin:0;">
                        <?php echo esc_html($pending_apps); ?> pending
                    </p>
                </div>
            </a>
        </div>

        <!-- Recent Applications -->
        <div class="ll-card">
            <div
                style="padding:1.25rem;border-bottom:1px solid var(--ll-border);display:flex;justify-content:space-between;align-items:center;">
                <h2 style="font-size:1.125rem;font-weight:600;margin:0;">Recent Applications</h2>
                <a href="<?php echo home_url('/landlord-applications/'); ?>"
                    style="font-size:0.875rem;color:var(--ll-primary);">View All</a>
            </div>
            <?php if (!empty($recent_apps)): ?>
                <?php foreach ($recent_apps as $app): ?>
                    <div
                        style="padding:1rem 1.25rem;border-bottom:1px solid var(--ll-border);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <p style="font-weight:500;font-size:0.875rem;margin:0 0 0.25rem;color:var(--ll-dark);">
                                <?php echo esc_html($app->student_name ?: 'Student'); ?> →
                                <?php echo esc_html($app->listing_title ?: 'Listing'); ?>
                            </p>
                            <p style="font-size:0.75rem;color:var(--ll-gray);margin:0;">
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
                    'description' => 'Applications appear here once students apply.',
                    'icon' => 'inbox',
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>