<?php
/**
 * Listing Card Component
 *
 * @package LeaseLink
 */

$listing_id = $listing_id ?? get_the_ID();
$unit_id = get_post_meta($listing_id, '_listing_unit_id', true);
$property_id = get_post_meta($listing_id, '_listing_property_id', true);

$rent_price = get_post_meta($unit_id, '_rent_price', true);
$room_type = get_post_meta($unit_id, '_room_type', true);
$furnished = get_post_meta($unit_id, '_furnished_status', true);
$available = get_post_meta($unit_id, '_available_from', true);
$sqft = get_post_meta($unit_id, '_square_footage', true);

$city = get_post_meta($property_id, '_property_city', true);
$address = get_post_meta($property_id, '_property_address', true);

$views = absint(get_post_meta($listing_id, '_view_count', true));
$featured = get_post_meta($listing_id, '_featured_flag', true);

$thumbnail = get_the_post_thumbnail_url($listing_id, 'medium_large') ?: get_the_post_thumbnail_url($property_id, 'medium_large');
$permalink = get_permalink($listing_id);

$room_labels = [
    'private_room' => 'Private Room',
    'shared_room' => 'Shared Room',
    'entire_unit' => 'Entire Unit',
    'studio' => 'Studio',
];
$room_label = $room_labels[$room_type] ?? ucfirst(str_replace('_', ' ', $room_type ?? 'Unit'));

$furnished_labels = [
    'furnished' => 'Furnished',
    'unfurnished' => 'Unfurnished',
    'partially_furnished' => 'Partially',
];
$furnished_label = $furnished_labels[$furnished] ?? '';
?>

<a href="<?php echo esc_url($permalink); ?>"
    class="group block bg-white rounded-xl border border-zinc-100 overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300 no-underline">
    <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
        <?php if ($thumbnail): ?>
            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($listing_id)); ?>"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
        <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-zinc-50">
                <svg class="w-12 h-12 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
            </div>
        <?php endif; ?>

        <?php if ($featured): ?>
            <span
                class="absolute top-3 left-3 inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-warning text-white text-xs font-semibold">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
                Featured
            </span>
        <?php endif; ?>

        <span
            class="absolute top-3 right-3 inline-flex items-center px-2.5 py-1 rounded-full bg-dark/60 backdrop-blur-sm text-white text-xs font-medium">
            <?php echo esc_html($room_label); ?>
        </span>
    </div>

    <div class="p-4">
        <div class="flex items-start justify-between gap-2 mb-2">
            <h3 class="text-base font-semibold text-dark leading-snug line-clamp-1 mb-0">
                <?php echo esc_html(get_the_title($listing_id)); ?>
            </h3>
            <span
                class="text-lg font-bold text-primary whitespace-nowrap">€<?php echo esc_html(number_format((float) $rent_price, 0, ',', '.')); ?></span>
        </div>

        <?php if ($city || $address): ?>
            <p class="text-sm text-gray flex items-center gap-1 mb-3">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <?php echo esc_html($city ?: $address); ?>
            </p>
        <?php endif; ?>

        <div class="flex items-center gap-3 text-xs text-zinc-500">
            <?php if ($sqft): ?>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                    </svg>
                    <?php echo esc_html($sqft); ?> m²
                </span>
            <?php endif; ?>
            <?php if ($furnished_label): ?>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <?php echo esc_html($furnished_label); ?>
                </span>
            <?php endif; ?>
            <?php if ($available): ?>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <?php echo esc_html(date('M j', strtotime($available))); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</a>