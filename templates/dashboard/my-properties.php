<?php
/**
 * My Properties — Landlord property management
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
$properties = get_posts([
    'post_type' => 'cpt_property',
    'post_status' => 'any',
    'author' => $user_id,
    'posts_per_page' => -1,
    'orderby' => 'date',
    'order' => 'DESC',
]);
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold mb-1">My Properties</h1>
                <p class="text-gray"><?php echo count($properties); ?> properties registered</p>
            </div>
            <a href="<?php echo home_url('/add-property/'); ?>" class="ll-btn ll-btn-primary no-underline">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Property
            </a>
        </div>

        <?php if (!empty($properties)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <?php foreach ($properties as $property):
                    $prop_id = $property->ID;
                    $city = get_post_meta($prop_id, '_property_city', true);
                    $thumbnail = get_the_post_thumbnail_url($prop_id, 'medium');

                    $units = get_posts(['post_type' => 'cpt_unit', 'post_parent' => $prop_id, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']);
                    $unit_count = count($units);
                    $listing_count = 0;
                    if (!empty($units)) {
                        $listing_count = count(get_posts([
                            'post_type' => 'cpt_listing',
                            'post_status' => 'publish',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'meta_query' => [['key' => '_listing_unit_id', 'value' => $units, 'compare' => 'IN']],
                        ]));
                    }
                    $status_badge = [
                        'draft' => ['text' => 'Draft', 'color' => 'gray'],
                        'publish' => ['text' => 'Published', 'color' => 'success'],
                        'suspended' => ['text' => 'Suspended', 'color' => 'danger'],
                    ];
                    $badge = $status_badge[$property->post_status] ?? ['text' => ucfirst($property->post_status), 'color' => 'gray'];
                    ?>
                    <div
                        class="bg-white rounded-xl border border-zinc-100 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                        <div class="flex">
                            <div class="w-32 bg-zinc-100 shrink-0">
                                <?php if ($thumbnail): ?>
                                    <img src="<?php echo esc_url($thumbnail); ?>"
                                        alt="<?php echo esc_attr($property->post_title); ?>" class="w-full h-full object-cover"
                                        loading="lazy">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center">
                                        <svg class="w-8 h-8 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1 p-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h3 class="text-base font-semibold text-dark mb-0 line-clamp-1">
                                        <?php echo esc_html($property->post_title); ?></h3>
                                    <?php SRP_Template_Loader::get_template('components/badge.php', $badge); ?>
                                </div>
                                <?php if ($city): ?>
                                    <p class="text-sm text-gray flex items-center gap-1 mb-3">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        <?php echo esc_html($city); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="flex items-center gap-4 text-xs text-zinc-500">
                                    <span><?php echo esc_html($unit_count); ?> unit(s)</span>
                                    <span><?php echo esc_html($listing_count); ?> active listing(s)</span>
                                </div>
                                <div class="mt-3 flex gap-2">
                                    <a href="<?php echo get_edit_post_link($prop_id); ?>"
                                        class="ll-btn ll-btn-secondary ll-btn-sm no-underline">Edit</a>
                                    <a href="<?php echo get_permalink($prop_id); ?>"
                                        class="ll-btn ll-btn-ghost ll-btn-sm no-underline">View</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No properties yet',
                    'description' => 'Add your first property to start listing units for students.',
                    'icon' => 'building',
                    'action_url' => home_url('/add-property/'),
                    'action_text' => 'Add Property',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php get_footer(); ?>