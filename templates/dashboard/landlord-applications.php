<?php
/**
 * Landlord Applications — Review and respond to applications
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

$properties = get_posts(['post_type' => 'cpt_property', 'author' => $user_id, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any']);
$listing_ids = [];
if (!empty($properties)) {
    $listing_ids = get_posts([
        'post_type' => 'cpt_listing',
        'post_status' => 'any',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [['key' => '_listing_property_id', 'value' => $properties, 'compare' => 'IN']],
    ]);
}

global $wpdb;
$table = $wpdb->prefix . 'rental_applications';
$applications = [];
if (!empty($listing_ids)) {
    $ids_str = implode(',', array_map('intval', $listing_ids));
    $where = "WHERE a.listing_id IN ({$ids_str})";
    if ($filter_status) {
        $where .= $wpdb->prepare(" AND a.application_status = %s", $filter_status);
    }
    $applications = $wpdb->get_results(
        "SELECT a.*, p.post_title as listing_title, u.display_name as student_name, u.user_email as student_email
         FROM {$table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID
         LEFT JOIN {$wpdb->users} u ON a.student_id = u.ID {$where} ORDER BY a.submitted_at DESC"
    );
}

$status_colors = [
    'submitted' => 'primary',
    'under_review' => 'warning',
    'accepted' => 'success',
    'rejected' => 'danger',
    'withdrawn' => 'gray',
    'expired' => 'gray',
];
$statuses = ['submitted', 'under_review', 'accepted', 'rejected', 'withdrawn'];
$nonce = wp_create_nonce('wp_rest');
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content">
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">Applications</h1>
            <p class="text-gray">Review and respond to student applications.</p>
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
                    <div class="p-5" x-data="{ expanded: false, processing: false }">
                        <div class="flex items-center justify-between gap-4 cursor-pointer" @click="expanded = !expanded">
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center shrink-0 text-sm font-semibold">
                                    <?php echo esc_html(strtoupper(substr($app->student_name ?: 'S', 0, 2))); ?>
                                </div>
                                <div class="min-w-0">
                                    <p class="font-semibold text-dark text-sm mb-0.5">
                                        <?php echo esc_html($app->student_name ?: 'Student'); ?></p>
                                    <p class="text-xs text-gray mb-0 truncate">
                                        Applied for <?php echo esc_html($app->listing_title ?: 'Listing'); ?>
                                        · <?php echo esc_html(human_time_diff(strtotime($app->submitted_at))); ?> ago
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php SRP_Template_Loader::get_template('components/badge.php', [
                                    'text' => ucfirst(str_replace('_', ' ', $app->application_status)),
                                    'color' => $status_colors[$app->application_status] ?? 'gray',
                                ]); ?>
                                <svg class="w-5 h-5 text-gray shrink-0 transition-transform" :class="{'rotate-180': expanded}"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                        <div x-show="expanded" x-transition class="mt-4 pt-4 border-t border-zinc-100">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <p class="text-xs text-gray mb-0.5">Move-in Date</p>
                                    <p class="text-sm font-medium text-dark mb-0">
                                        <?php echo esc_html($app->move_in_date ? date('M j, Y', strtotime($app->move_in_date)) : 'N/A'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray mb-0.5">Lease Duration</p>
                                    <p class="text-sm font-medium text-dark mb-0">
                                        <?php echo esc_html($app->lease_duration ?: '—'); ?> months</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray mb-0.5">Email</p>
                                    <p class="text-sm font-medium text-dark mb-0">
                                        <?php echo esc_html($app->student_email ?: 'N/A'); ?></p>
                                </div>
                            </div>
                            <?php if ($app->message): ?>
                                <div class="mb-4 p-3 bg-surface rounded-lg">
                                    <p class="text-xs text-gray mb-1">Student's Message</p>
                                    <p class="text-sm text-dark mb-0"><?php echo esc_html($app->message); ?></p>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($app->application_status, ['submitted', 'under_review'])): ?>
                                <div class="flex gap-2" x-show="!processing">
                                    <button
                                        @click="processing = true; updateApplication(<?php echo $app->application_id; ?>, 'accepted')"
                                        class="ll-btn ll-btn-primary ll-btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7" />
                                        </svg> Accept
                                    </button>
                                    <button
                                        @click="processing = true; updateApplication(<?php echo $app->application_id; ?>, 'rejected')"
                                        class="ll-btn ll-btn-danger ll-btn-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg> Reject
                                    </button>
                                </div>
                                <div x-show="processing" class="flex items-center gap-2 text-sm text-gray">
                                    <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                        </circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg> Processing...
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => empty($listing_ids) ? 'No listings yet' : 'No applications found',
                    'description' => empty($listing_ids) ? 'Add a property and create listings to start receiving applications.' : ($filter_status ? 'No applications with this status.' : 'Applications will appear here once students apply.'),
                    'icon' => 'inbox',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    async function updateApplication(appId, status) {
        try {
            const response = await fetch(`<?php echo esc_url(rest_url('rental/v1/applications/')); ?>${appId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                body: JSON.stringify({ status, notes: '' }),
            });
            if (response.ok) { location.reload(); }
            else { alert('Failed to update application. Please try again.'); }
        } catch (error) { alert('Something went wrong. Please try again.'); }
    }
</script>

<?php get_footer(); ?>