<?php
/**
 * Shortcode Template: Saved Listings
 * @package LeaseLink
 * @since   1.1.0
 */
use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();
global $wpdb;
$saved_table = $wpdb->prefix . 'rental_saved_listings';
$saved_listings = $wpdb->get_results($wpdb->prepare(
    "SELECT s.listing_id FROM {$saved_table} s INNER JOIN {$wpdb->posts} p ON s.listing_id = p.ID AND p.post_status = 'publish' WHERE s.student_id = %d ORDER BY s.saved_at DESC",
    $user_id
));
?>
<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'student']); ?>
    <div class="ll-dashboard-content">
        <h1 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem;">Saved Listings</h1>
        <?php if (!empty($saved_listings)): ?>
            <div class="ll-search-grid">
                <?php foreach ($saved_listings as $saved): ?>
                    <div class="ll-saved-item" style="position:relative;">
                        <?php SRP_Template_Loader::get_template('components/listing-card.php', ['listing_id' => $saved->listing_id]); ?>
                        <button onclick="leaselinkUnsave(<?php echo $saved->listing_id; ?>, this)"
                            style="position:absolute;top:0.75rem;right:0.75rem;z-index:10;width:32px;height:32px;border-radius:50%;background:#fff;border:none;box-shadow:var(--ll-shadow);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--ll-danger);">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ll-card">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No saved listings',
                    'description' => 'Save listings while browsing to compare them later.',
                    'icon' => 'heart',
                    'action_url' => home_url('/listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>