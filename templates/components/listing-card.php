<?php
/**
 * Component: Listing Card
 *
 * Plugin default template — any theme can override
 * by placing a copy in yourtheme/leaselink/components/listing-card.php
 *
 * @param int $listing_id Listing post ID.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

$listing_id = $listing_id ?? ($args['listing_id'] ?? get_the_ID());
if (!$listing_id)
    return;

$unit_id = get_post_meta($listing_id, '_listing_unit_id', true);
$property_id = get_post_meta($listing_id, '_listing_property_id', true);
$rent_price = get_post_meta($unit_id, '_rent_price', true);
$city = get_post_meta($property_id, '_property_city', true);
$room_type = get_post_meta($unit_id, '_room_type', true);
$featured = get_post_meta($listing_id, '_featured_flag', true);
$thumbnail = get_the_post_thumbnail_url($listing_id, 'medium') ?: get_the_post_thumbnail_url($property_id, 'medium');
$link = get_permalink($listing_id);

$room_labels = [
    'private_room' => 'Private Room',
    'shared_room' => 'Shared Room',
    'entire_unit' => 'Entire Unit',
    'studio' => 'Studio',
];
?>
<a href="<?php echo esc_url($link); ?>" class="ll-listing-card">
    <div class="ll-listing-card-image">
        <?php if ($thumbnail): ?>
            <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr(get_the_title($listing_id)); ?>"
                loading="lazy">
        <?php else: ?>
            <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--ll-border);">
                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
        <?php endif; ?>
        <?php if ($featured): ?>
            <span class="ll-badge ll-badge-warning" style="position:absolute;top:0.75rem;left:0.75rem;">Featured</span>
        <?php endif; ?>
    </div>
    <div class="ll-listing-card-body">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.25rem;">
            <p class="ll-listing-card-title">
                <?php echo esc_html(get_the_title($listing_id)); ?>
            </p>
            <span class="ll-listing-card-price">€
                <?php echo esc_html(number_format((float) $rent_price, 0, ',', '.')); ?>
            </span>
        </div>
        <?php if ($city): ?>
            <p class="ll-listing-card-location">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
                <?php echo esc_html($city); ?>
            </p>
        <?php endif; ?>
        <div class="ll-listing-card-meta">
            <?php if ($room_type): ?>
                <span>
                    <?php echo esc_html($room_labels[$room_type] ?? ucfirst($room_type)); ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
</a>