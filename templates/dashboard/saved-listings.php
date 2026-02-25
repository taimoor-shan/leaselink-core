<?php
/**
 * Saved Listings — Student bookmarked listings
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
$saved_table = $wpdb->prefix . 'rental_saved_listings';
$saved_listings = $wpdb->get_results($wpdb->prepare(
    "SELECT s.listing_id, s.saved_at 
     FROM {$saved_table} s 
     INNER JOIN {$wpdb->posts} p ON s.listing_id = p.ID AND p.post_status = 'publish' 
     WHERE s.student_id = %d ORDER BY s.saved_at DESC",
    $user_id
));
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'student']); ?>

    <div class="ll-dashboard-content">
        <div class="mb-6">
            <h1 class="text-2xl font-bold mb-1">Saved Listings</h1>
            <p class="text-gray">Listings you've bookmarked for later.</p>
        </div>

        <?php if (!empty($saved_listings)): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                <?php foreach ($saved_listings as $saved): ?>
                    <div class="relative">
                        <?php SRP_Template_Loader::get_template('components/listing-card.php', ['listing_id' => $saved->listing_id]); ?>
                        <button onclick="if(confirm('Remove from saved?')) { 
                            fetch('<?php echo esc_url(rest_url('rental/v1/listings/' . $saved->listing_id . '/save')); ?>', {
                                method: 'DELETE', 
                                headers: {'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'}
                            }).then(() => this.closest('.relative').remove());
                        }"
                            class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-white shadow-md flex items-center justify-center text-danger hover:bg-danger hover:text-white transition-colors"
                            title="Remove from saved">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                            </svg>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No saved listings',
                    'description' => 'Save listings you like while browsing to compare them later.',
                    'icon' => 'heart',
                    'action_url' => home_url('/search-listings/'),
                    'action_text' => 'Browse Listings',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>