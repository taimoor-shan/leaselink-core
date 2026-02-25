<?php
/**
 * Landlord Dashboard
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
$user = wp_get_current_user();

$properties = get_posts([
    'post_type' => 'cpt_property',
    'post_status' => 'any',
    'author' => $user_id,
    'posts_per_page' => -1,
    'fields' => 'ids',
]);
$property_count = count($properties);

$listings = [];
if (!empty($properties)) {
    $listings = get_posts([
        'post_type' => 'cpt_listing',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [['key' => '_listing_property_id', 'value' => $properties, 'compare' => 'IN']],
    ]);
}
$listing_count = count($listings);

$total_views = 0;
foreach ($listings as $lid) {
    $total_views += absint(get_post_meta($lid, '_view_count', true));
}

global $wpdb;
$app_table = $wpdb->prefix . 'rental_applications';
$total_apps = 0;
$pending_apps = 0;
$recent_apps = [];

if (!empty($listings)) {
    $listing_ids = implode(',', array_map('intval', $listings));
    $total_apps = $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE listing_id IN ({$listing_ids})");
    $pending_apps = $wpdb->get_var("SELECT COUNT(*) FROM {$app_table} WHERE listing_id IN ({$listing_ids}) AND application_status IN ('submitted', 'under_review')");
    $recent_apps = $wpdb->get_results(
        "SELECT a.*, p.post_title as listing_title, u.display_name as student_name
         FROM {$app_table} a 
         LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID
         LEFT JOIN {$wpdb->users} u ON a.student_id = u.ID
         WHERE a.listing_id IN ({$listing_ids}) 
         ORDER BY a.submitted_at DESC LIMIT 5"
    );
}

$verification_table = $wpdb->prefix . 'landlord_verification';
$verification = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$verification_table} WHERE landlord_id = %d ORDER BY verification_level DESC LIMIT 1",
    $user_id
));
$verification_level = $verification ? absint($verification->verification_level) : 0;
$level_labels = [
    0 => ['label' => 'Unverified', 'desc' => 'Complete verification to publish listings.'],
    1 => ['label' => 'Email Verified', 'desc' => 'Verify your phone to submit listings for review.'],
    2 => ['label' => 'Phone Verified', 'desc' => 'Submit ID documents to get fully verified.'],
    3 => ['label' => 'Documents Pending', 'desc' => 'Your documents are under review.'],
    4 => ['label' => 'Fully Verified', 'desc' => 'Your listings are auto-approved.'],
    5 => ['label' => 'Premium Verified', 'desc' => 'You have premium verification status.'],
];
$level_info = $level_labels[$verification_level] ?? $level_labels[0];
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
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-1">Landlord Dashboard</h1>
            <p class="text-gray">Manage your properties and track applications.</p>
        </div>

        <?php if ($verification_level < 4): ?>
            <div
                class="mb-6 p-4 rounded-xl border flex items-start gap-3 
                <?php echo $verification_level < 2 ? 'bg-danger/5 border-danger/20' : 'bg-warning/5 border-warning/20'; ?>">
                <svg class="w-5 h-5 shrink-0 mt-0.5 <?php echo $verification_level < 2 ? 'text-danger' : 'text-warning'; ?>"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <div>
                    <p class="font-semibold text-sm mb-0.5">Verification: <?php echo esc_html($level_info['label']); ?></p>
                    <p class="text-sm text-gray mb-2"><?php echo esc_html($level_info['desc']); ?></p>
                    <a href="<?php echo home_url('/verification/'); ?>"
                        class="ll-btn ll-btn-primary ll-btn-sm no-underline">Complete Verification</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="ll-stats-grid mb-8">
            <?php
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Properties', 'value' => $property_count, 'icon' => 'building', 'color' => 'primary']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Total Views', 'value' => number_format($total_views), 'icon' => 'eye', 'color' => 'accent']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Total Applications', 'value' => $total_apps ?: 0, 'icon' => 'users', 'color' => 'warning']);
            SRP_Template_Loader::get_template('components/stat-card.php', ['label' => 'Pending Review', 'value' => $pending_apps ?: 0, 'icon' => 'clock', 'color' => 'danger']);
            ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <a href="<?php echo home_url('/add-property/'); ?>"
                class="flex items-center gap-4 p-5 bg-primary/5 rounded-xl border border-primary/10 hover:border-primary/30 transition-colors no-underline group">
                <div
                    class="w-12 h-12 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 group-hover:bg-primary group-hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-dark mb-0.5 text-base">Add Property</h3>
                    <p class="text-sm text-gray mb-0">List a new property for students</p>
                </div>
            </a>
            <a href="<?php echo home_url('/landlord-applications/'); ?>"
                class="flex items-center gap-4 p-5 bg-white rounded-xl border border-zinc-100 shadow-sm hover:shadow-md transition-shadow no-underline group">
                <div class="w-12 h-12 rounded-xl bg-warning/10 text-warning flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-dark mb-0.5 text-base">Review Applications</h3>
                    <p class="text-sm text-gray mb-0"><?php echo esc_html($pending_apps); ?> pending review</p>
                </div>
            </a>
        </div>

        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
            <div class="p-5 border-b border-zinc-100">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold mb-0">Recent Applications</h2>
                    <a href="<?php echo home_url('/landlord-applications/'); ?>"
                        class="text-sm text-primary no-underline hover:underline">View All</a>
                </div>
            </div>
            <?php if (!empty($recent_apps)): ?>
                <div class="divide-y divide-zinc-100">
                    <?php foreach ($recent_apps as $app): ?>
                        <div class="p-5 flex items-center justify-between gap-4">
                            <div class="min-w-0">
                                <p class="font-medium text-dark text-sm mb-1">
                                    <?php echo esc_html($app->student_name ?: 'Student'); ?> →
                                    <?php echo esc_html($app->listing_title ?: 'Listing'); ?>
                                </p>
                                <p class="text-xs text-gray mb-0">
                                    <?php echo esc_html(human_time_diff(strtotime($app->submitted_at))); ?> ago · Move-in:
                                    <?php echo esc_html($app->move_in_date ? date('M j', strtotime($app->move_in_date)) : 'N/A'); ?>
                                </p>
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
                    'description' => 'Applications will appear here once students apply to your listings.',
                    'icon' => 'inbox',
                ]); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php get_footer(); ?>