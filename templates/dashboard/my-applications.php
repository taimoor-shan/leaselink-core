<?php
/**
 * Shortcode Template: My Applications (Student)
 *
 * Rendered by [leaselink_my_applications] shortcode.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

global $wpdb;
$table = $wpdb->prefix . 'rental_applications';

$where = $wpdb->prepare("WHERE a.student_id = %d", $user_id);
if ($filter_status) {
    $where .= $wpdb->prepare(" AND a.application_status = %s", $filter_status);
}

$applications = $wpdb->get_results(
    "SELECT a.*, p.post_title as listing_title FROM {$table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID {$where} ORDER BY a.submitted_at DESC"
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
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">My Applications</h1>
        <p style="color:var(--ll-gray);margin-bottom:1.5rem;">Track and manage your rental applications.</p>

        <!-- Status Filters -->
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
            <a href="<?php echo esc_url(remove_query_arg('status')); ?>"
                style="padding:0.375rem 0.75rem;border-radius:var(--ll-radius);font-size:0.8125rem;font-weight:500;text-decoration:none;<?php echo !$filter_status ? 'background:var(--ll-primary);color:#fff;' : 'background:var(--ll-surface);color:var(--ll-gray);'; ?>">All</a>
            <?php foreach ($statuses as $s): ?>
                <a href="<?php echo esc_url(add_query_arg('status', $s)); ?>"
                    style="padding:0.375rem 0.75rem;border-radius:var(--ll-radius);font-size:0.8125rem;font-weight:500;text-decoration:none;<?php echo $filter_status === $s ? 'background:var(--ll-primary);color:#fff;' : 'background:var(--ll-surface);color:var(--ll-gray);'; ?>">
                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $s))); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($applications)): ?>
            <div class="ll-card" style="overflow:hidden;">
                <?php foreach ($applications as $app): ?>
                    <div style="padding:1.25rem;border-bottom:1px solid var(--ll-border);" x-data="{ expanded: false }">
                        <div style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;"
                            @click="expanded = !expanded">
                            <div>
                                <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.25rem;">
                                    <p style="font-weight:600;font-size:0.875rem;margin:0;color:var(--ll-dark);">
                                        <?php echo esc_html($app->listing_title ?: 'Listing #' . $app->listing_id); ?>
                                    </p>
                                    <?php SRP_Template_Loader::get_template('components/badge.php', [
                                        'text' => ucfirst(str_replace('_', ' ', $app->application_status)),
                                        'color' => $status_colors[$app->application_status] ?? 'gray',
                                    ]); ?>
                                </div>
                                <p style="font-size:0.75rem;color:var(--ll-gray);margin:0;">Applied
                                    <?php echo esc_html(date('M j, Y', strtotime($app->submitted_at))); ?>
                                </p>
                            </div>
                            <svg width="20" height="20" fill="none" stroke="var(--ll-gray)" viewBox="0 0 24 24"
                                :style="expanded && 'transform:rotate(180deg)'">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <div x-show="expanded" x-transition
                            style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--ll-border);">
                            <?php if ($app->message): ?>
                                <div style="margin-bottom:0.75rem;">
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.25rem;">Your Message</p>
                                    <p style="font-size:0.875rem;color:var(--ll-dark);margin:0;">
                                        <?php echo esc_html($app->message); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <div style="display:flex;gap:0.5rem;">
                                <a href="<?php echo get_permalink($app->listing_id); ?>"
                                    class="ll-btn ll-btn-secondary ll-btn-sm" style="text-decoration:none;">View Listing</a>
                                <?php if (in_array($app->application_status, ['submitted', 'under_review'])): ?>
                                    <button onclick="leaselinkWithdraw(<?php echo $app->application_id; ?>)"
                                        class="ll-btn ll-btn-ghost ll-btn-sm" style="color:var(--ll-danger);">Withdraw</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ll-card">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No applications found',
                    'description' => 'Browse listings to start applying.',
                    'icon' => 'inbox',
                    'action_url' => home_url('/listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>