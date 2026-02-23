<?php
/**
 * Shortcode Template: Landlord Applications
 * @package LeaseLink
 * @since   1.1.0
 */
use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();
$filter_status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

$properties = get_posts(['post_type' => 'cpt_property', 'author' => $user_id, 'posts_per_page' => -1, 'fields' => 'ids', 'post_status' => 'any']);
$listing_ids = [];
if (!empty($properties)) {
    $listing_ids = get_posts(['post_type' => 'cpt_listing', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => [['key' => '_listing_property_id', 'value' => $properties, 'compare' => 'IN']]]);
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
    $applications = $wpdb->get_results("SELECT a.*, p.post_title as listing_title, u.display_name as student_name, u.user_email as student_email FROM {$table} a LEFT JOIN {$wpdb->posts} p ON a.listing_id = p.ID LEFT JOIN {$wpdb->users} u ON a.student_id = u.ID {$where} ORDER BY a.submitted_at DESC");
}

$status_colors = ['submitted' => 'primary', 'under_review' => 'warning', 'accepted' => 'success', 'rejected' => 'danger', 'withdrawn' => 'gray'];
$statuses = ['submitted', 'under_review', 'accepted', 'rejected', 'withdrawn'];
?>
<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>
    <div class="ll-dashboard-content">
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem;">Applications</h1>
        <p style="color:var(--ll-gray);margin-bottom:1.5rem;">Review and respond to student applications.</p>

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
                    <div style="padding:1.25rem;border-bottom:1px solid var(--ll-border);"
                        x-data="{ expanded: false, processing: false }">
                        <div style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;"
                            @click="expanded = !expanded">
                            <div style="display:flex;align-items:center;gap:0.75rem;">
                                <div
                                    style="width:40px;height:40px;border-radius:50%;background:rgba(79,70,229,0.1);color:var(--ll-primary);display:flex;align-items:center;justify-content:center;font-size:0.8125rem;font-weight:600;flex-shrink:0;">
                                    <?php echo esc_html(strtoupper(substr($app->student_name ?: 'S', 0, 2))); ?>
                                </div>
                                <div>
                                    <p style="font-weight:600;font-size:0.875rem;margin:0;color:var(--ll-dark);">
                                        <?php echo esc_html($app->student_name ?: 'Student'); ?>
                                    </p>
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0;">→
                                        <?php echo esc_html($app->listing_title ?: 'Listing'); ?> ·
                                        <?php echo esc_html(human_time_diff(strtotime($app->submitted_at))); ?> ago
                                    </p>
                                </div>
                            </div>
                            <div style="display:flex;align-items:center;gap:0.5rem;">
                                <?php SRP_Template_Loader::get_template('components/badge.php', ['text' => ucfirst(str_replace('_', ' ', $app->application_status)), 'color' => $status_colors[$app->application_status] ?? 'gray']); ?>
                                <svg width="20" height="20" fill="none" stroke="var(--ll-gray)" viewBox="0 0 24 24"
                                    :style="expanded && 'transform:rotate(180deg)'">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                        </div>

                        <div x-show="expanded" x-transition
                            style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--ll-border);">
                            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;margin-bottom:1rem;">
                                <div>
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.125rem;">Move-in</p>
                                    <p style="font-size:0.875rem;font-weight:500;margin:0;">
                                        <?php echo esc_html($app->move_in_date ? date('M j, Y', strtotime($app->move_in_date)) : 'N/A'); ?>
                                    </p>
                                </div>
                                <div>
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.125rem;">Duration</p>
                                    <p style="font-size:0.875rem;font-weight:500;margin:0;">
                                        <?php echo esc_html($app->lease_duration ?: '—'); ?> months
                                    </p>
                                </div>
                                <div>
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.125rem;">Email</p>
                                    <p style="font-size:0.875rem;font-weight:500;margin:0;">
                                        <?php echo esc_html($app->student_email ?: 'N/A'); ?>
                                    </p>
                                </div>
                            </div>
                            <?php if ($app->message): ?>
                                <div
                                    style="padding:0.75rem;background:var(--ll-surface);border-radius:var(--ll-radius);margin-bottom:1rem;">
                                    <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.25rem;">Student's Message</p>
                                    <p style="font-size:0.875rem;margin:0;color:var(--ll-dark);">
                                        <?php echo esc_html($app->message); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                            <?php if (in_array($app->application_status, ['submitted', 'under_review'])): ?>
                                <div x-show="!processing" style="display:flex;gap:0.5rem;">
                                    <button
                                        @click="processing = true; leaselinkUpdateApplication(<?php echo $app->application_id; ?>, 'accepted')"
                                        class="ll-btn ll-btn-primary ll-btn-sm">✓ Accept</button>
                                    <button
                                        @click="processing = true; leaselinkUpdateApplication(<?php echo $app->application_id; ?>, 'rejected')"
                                        class="ll-btn ll-btn-danger ll-btn-sm">✕ Reject</button>
                                </div>
                                <div x-show="processing" style="font-size:0.875rem;color:var(--ll-gray);">Processing...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ll-card">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => empty($listing_ids) ? 'No listings yet' : 'No applications found',
                    'description' => empty($listing_ids) ? 'Add a property first.' : 'Applications will appear here.',
                    'icon' => 'inbox',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>