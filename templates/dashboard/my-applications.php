<?php
/**
 * My Applications — Student application tracking
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
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

global $wpdb;
$table = $wpdb->prefix . 'rental_applications';
$where = $wpdb->prepare("WHERE a.student_id = %d", $user_id);
if ($filter_status) {
    $where .= $wpdb->prepare(" AND a.application_status = %s", $filter_status);
}

$applications = $wpdb->get_results(
    "SELECT a.*, p.post_title as listing_title 
     FROM {$table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID {$where} ORDER BY a.submitted_at DESC"
);

$status_colors = [
    'submitted' => 'primary',
    'under_review' => 'warning',
    'accepted' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'gray',
    'expired' => 'gray',
];
$statuses = ['submitted', 'under_review', 'accepted', 'rejected', 'withdrawn', 'expired'];
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'student']); ?>

    <div class="ll-dashboard-content">
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">My Applications</h1>
            <p class="text-gray">Track and manage your rental applications.</p>
        </div>

        <div class="flex flex-wrap gap-2 mb-6">
            <a href="<?php echo esc_url(remove_query_arg('status')); ?>"
                class="px-3 py-1.5 rounded-lg text-sm font-medium no-underline transition-colors <?php echo !$filter_status ? 'bg-primary text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'; ?>">All</a>
            <?php foreach ($statuses as $s): ?>
                <a href="<?php echo esc_url(add_query_arg('status', $s)); ?>"
                    class="px-3 py-1.5 rounded-lg text-sm font-medium no-underline transition-colors <?php echo $filter_status === $s ? 'bg-primary text-white' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200'; ?>">
                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $s))); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($applications)): ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm divide-y divide-zinc-100">
                <?php foreach ($applications as $app): ?>
                    <div class="p-5" x-data="{ expanded: false }">
                        <div class="flex items-center justify-between gap-4 cursor-pointer" @click="expanded = !expanded">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-3 mb-1">
                                    <p class="font-semibold text-dark text-sm mb-0 truncate">
                                        <?php echo esc_html($app->listing_title ?: 'Listing #' . $app->listing_id); ?>
                                    </p>
                                    <?php SRP_Template_Loader::get_template('components/badge.php', [
                                        'text' => ucfirst(str_replace('_', ' ', $app->application_status)),
                                        'color' => $status_colors[$app->application_status] ?? 'gray',
                                    ]); ?>
                                </div>
                                <p class="text-xs text-gray mb-0">
                                    Applied <?php echo esc_html(date('M j, Y', strtotime($app->submitted_at))); ?>
                                    · Move-in:
                                    <?php echo esc_html($app->move_in_date ? date('M j, Y', strtotime($app->move_in_date)) : 'N/A'); ?>
                                    · <?php echo esc_html($app->lease_duration ?: '—'); ?> months
                                </p>
                            </div>
                            <svg class="w-5 h-5 text-gray shrink-0 transition-transform" :class="{'rotate-180': expanded}"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>
                        <div x-show="expanded" x-transition class="mt-4 pt-4 border-t border-zinc-100">
                            <?php if ($app->message): ?>
                                <div class="mb-3">
                                    <p class="text-xs font-medium text-gray mb-1">Your Message</p>
                                    <p class="text-sm text-dark mb-0"><?php echo esc_html($app->message); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if ($app->landlord_notes): ?>
                                <div class="mb-3">
                                    <p class="text-xs font-medium text-gray mb-1">Landlord Notes</p>
                                    <p class="text-sm text-dark mb-0"><?php echo esc_html($app->landlord_notes); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="flex gap-2 mt-3">
                                <a href="<?php echo get_permalink($app->listing_id); ?>"
                                    class="ll-btn ll-btn-secondary ll-btn-sm no-underline">View Listing</a>
                                <?php if (in_array($app->application_status, ['submitted', 'under_review'])): ?>
                                    <button class="ll-btn ll-btn-ghost ll-btn-sm text-danger" onclick="if(confirm('Withdraw this application?')) { 
                                        fetch('<?php echo esc_url(rest_url('rental/v1/applications/' . $app->application_id)); ?>', {
                                            method: 'PATCH', 
                                            headers: {'Content-Type':'application/json','X-WP-Nonce':'<?php echo wp_create_nonce('wp_rest'); ?>'},
                                            body: JSON.stringify({status:'withdrawn'})
                                        }).then(() => location.reload());
                                    }">Withdraw</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No applications found',
                    'description' => $filter_status ? 'No applications with this status.' : 'Browse listings to start applying.',
                    'icon' => 'inbox',
                    'action_url' => home_url('/search-listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>