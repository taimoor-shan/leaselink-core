<?php
/**
 * Template: Single Listing
 *
 * Plugin default — used when no theme override exists.
 * Theme can override at: yourtheme/leaselink/single-cpt_listing.php
 *
 * @package LeaseLink
 * @since   1.1.0
 */

use StudentRentalPlatform\SRP_Template_Loader;

get_header();

$listing_id = get_the_ID();
$unit_id = get_post_meta($listing_id, '_listing_unit_id', true);
$property_id = get_post_meta($listing_id, '_listing_property_id', true);

// Unit data
$rent_price = get_post_meta($unit_id, '_rent_price', true);
$deposit = get_post_meta($unit_id, '_deposit_amount', true);
$room_type = get_post_meta($unit_id, '_room_type', true);
$furnished = get_post_meta($unit_id, '_furnished_status', true);
$available_from = get_post_meta($unit_id, '_available_from', true);
$gender_pref = get_post_meta($unit_id, '_gender_preference', true);
$lease_min = get_post_meta($unit_id, '_lease_duration_min', true);
$lease_max = get_post_meta($unit_id, '_lease_duration_max', true);
$max_occ = get_post_meta($unit_id, '_max_occupancy', true);
$sqft = get_post_meta($unit_id, '_square_footage', true);
$amenities_raw = get_post_meta($unit_id, '_amenities', true);

// Property data
$address = get_post_meta($property_id, '_property_address', true);
$city = get_post_meta($property_id, '_property_city', true);
$state = get_post_meta($property_id, '_property_state', true);
$lat = get_post_meta($property_id, '_property_latitude', true);
$lng = get_post_meta($property_id, '_property_longitude', true);

// Listing data
$views = absint(get_post_meta($listing_id, '_view_count', true));
$featured = get_post_meta($listing_id, '_featured_flag', true);
$thumbnail = get_the_post_thumbnail_url($listing_id, 'large');

$room_labels = [
    'private_room' => 'Private Room',
    'shared_room' => 'Shared Room',
    'entire_unit' => 'Entire Unit',
    'studio' => 'Studio',
];
$furnished_labels = [
    'furnished' => 'Furnished',
    'unfurnished' => 'Unfurnished',
    'partially_furnished' => 'Partially Furnished',
];
$gender_labels = [
    'any' => 'No Preference',
    'male' => 'Male Only',
    'female' => 'Female Only',
];

$amenities_list = is_array($amenities_raw) ? $amenities_raw : (is_string($amenities_raw) && !empty($amenities_raw) ? array_map('trim', explode(',', $amenities_raw)) : []);
?>

<div style="max-width:1100px;margin:0 auto;padding:2rem 1rem;">
    <div style="display:flex;gap:2rem;flex-wrap:wrap;">
        <!-- Main Content -->
        <div style="flex:1;min-width:0;">
            <!-- Image -->
            <?php if ($thumbnail): ?>
                <div style="border-radius:var(--ll-radius-xl);overflow:hidden;margin-bottom:1.5rem;max-height:480px;">
                    <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php the_title_attribute(); ?>"
                        style="width:100%;height:480px;object-fit:cover;" data-fancybox="gallery">
                </div>
            <?php endif; ?>

            <!-- Title -->
            <div class="ll-card" style="padding:1.5rem;margin-bottom:1.25rem;">
                <div style="display:flex;gap:0.5rem;margin-bottom:0.75rem;flex-wrap:wrap;">
                    <?php if ($featured):
                        SRP_Template_Loader::get_template('components/badge.php', ['text' => 'Featured', 'color' => 'warning']); endif; ?>
                    <?php if ($room_type):
                        SRP_Template_Loader::get_template('components/badge.php', ['text' => $room_labels[$room_type] ?? ucfirst($room_type), 'color' => 'primary']); endif; ?>
                </div>
                <h1 style="font-size:1.75rem;font-weight:700;margin-bottom:0.5rem;color:var(--ll-dark);">
                    <?php the_title(); ?>
                </h1>
                <?php if ($city): ?>
                    <p style="color:var(--ll-gray);margin-bottom:1rem;font-size:0.875rem;">
                        <?php echo esc_html(implode(', ', array_filter([$address, $city, $state]))); ?>
                    </p>
                <?php endif; ?>
                <p style="margin:0;"><span style="font-size:1.75rem;font-weight:700;color:var(--ll-primary);">€
                        <?php echo esc_html(number_format((float) $rent_price, 0, ',', '.')); ?>
                    </span> <span style="color:var(--ll-gray);font-size:0.875rem;">/ month</span></p>
            </div>

            <!-- Details Grid -->
            <div class="ll-card" style="padding:1.5rem;margin-bottom:1.25rem;">
                <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;color:var(--ll-dark);">Property Details
                </h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:0.75rem;">
                    <?php
                    $details = array_filter([
                        $sqft ? ['Area', $sqft . ' m²'] : null,
                        $furnished ? ['Furnishing', $furnished_labels[$furnished] ?? ucfirst($furnished)] : null,
                        $available_from ? ['Available From', date('M j, Y', strtotime($available_from))] : null,
                        $deposit ? ['Deposit', '€' . number_format((float) $deposit, 0, ',', '.')] : null,
                        ($lease_min || $lease_max) ? ['Lease Duration', ($lease_min ?: '1') . '–' . ($lease_max ?: '12') . ' months'] : null,
                        $max_occ ? ['Max Occupancy', $max_occ . ' person(s)'] : null,
                        $gender_pref ? ['Gender Pref.', $gender_labels[$gender_pref] ?? ucfirst($gender_pref)] : null,
                    ]);
                    foreach ($details as $d): ?>
                        <div style="padding:0.75rem;background:var(--ll-surface);border-radius:var(--ll-radius);">
                            <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.125rem;">
                                <?php echo esc_html($d[0]); ?>
                            </p>
                            <p style="font-size:0.875rem;font-weight:600;color:var(--ll-dark);margin:0;">
                                <?php echo esc_html($d[1]); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="ll-card" style="padding:1.5rem;margin-bottom:1.25rem;">
                <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;color:var(--ll-dark);">Description</h2>
                <div style="color:var(--ll-gray);line-height:1.7;font-size:0.875rem;">
                    <?php the_content(); ?>
                </div>
            </div>

            <!-- Amenities -->
            <?php if (!empty($amenities_list)): ?>
                <div class="ll-card" style="padding:1.5rem;margin-bottom:1.25rem;">
                    <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;color:var(--ll-dark);">Amenities</h2>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:0.5rem;">
                        <?php foreach ($amenities_list as $amenity): ?>
                            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;color:var(--ll-dark);">
                                <svg width="16" height="16" fill="none" stroke="var(--ll-success)" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $amenity))); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Map -->
            <?php if ($lat && $lng): ?>
                <div class="ll-card" style="padding:1.5rem;margin-bottom:1.25rem;">
                    <h2 style="font-size:1.125rem;font-weight:600;margin-bottom:1rem;color:var(--ll-dark);">Location</h2>
                    <div id="listing-map"
                        style="width:100%;height:300px;border-radius:var(--ll-radius-lg);overflow:hidden;"></div>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        if (typeof L !== 'undefined') {
                            const map = L.map('listing-map').setView([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>], 15);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
                    L.marker([<?php echo esc_js($lat); ?>, <?php echo esc_js($lng); ?>]).addTo(map);
                            }
                        });
                </script>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <aside style="width:300px;flex-shrink:0;">
            <div style="position:sticky;top:5rem;">
                <!-- Apply / Login -->
                <?php if (is_user_logged_in() && current_user_can('submit_applications')): ?>
                    <div class="ll-card" style="padding:1.5rem;margin-bottom:1rem;" x-data="leaselinkApplication()">
                        <div x-show="!showForm && !submitted">
                            <h3 style="font-size:1.125rem;font-weight:600;margin-bottom:0.5rem;">Interested?</h3>
                            <p style="font-size:0.875rem;color:var(--ll-gray);margin-bottom:1rem;">Apply now and the
                                landlord will review your application.</p>
                            <button @click="showForm = true" class="ll-btn ll-btn-primary" style="width:100%;">Apply
                                Now</button>
                        </div>

                        <form x-show="showForm" @submit.prevent="submitApplication($event)"
                            style="display:flex;flex-direction:column;gap:0.75rem;">
                            <h3 style="font-size:1rem;font-weight:600;">Submit Application</h3>
                            <div>
                                <label
                                    style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Move-in
                                    Date</label>
                                <input type="date" name="move_in_date" required class="ll-input"
                                    min="<?php echo esc_attr($available_from ?: date('Y-m-d')); ?>">
                            </div>
                            <div>
                                <label style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Lease
                                    Duration (months)</label>
                                <input type="number" name="lease_duration" required class="ll-input"
                                    min="<?php echo esc_attr($lease_min ?: 1); ?>"
                                    max="<?php echo esc_attr($lease_max ?: 24); ?>"
                                    value="<?php echo esc_attr($lease_min ?: 6); ?>">
                            </div>
                            <div>
                                <label
                                    style="display:block;font-size:0.875rem;font-weight:500;margin-bottom:0.25rem;">Message</label>
                                <textarea name="message" class="ll-textarea" style="height:80px;"
                                    placeholder="Introduce yourself..."></textarea>
                            </div>
                            <input type="hidden" name="unit_id" value="<?php echo esc_attr($unit_id); ?>">
                            <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
                            <button type="submit" class="ll-btn ll-btn-primary" :disabled="submitting">
                                <span x-show="!submitting">Submit</span><span x-show="submitting">Sending...</span>
                            </button>
                            <button type="button" @click="showForm = false" class="ll-btn ll-btn-ghost"
                                style="font-size:0.8125rem;">Cancel</button>
                        </form>

                        <div x-show="submitted" style="text-align:center;padding:1rem 0;">
                            <p style="font-weight:600;font-size:1.125rem;color:var(--ll-success);margin-bottom:0.5rem;">
                                Application Sent!</p>
                            <p style="font-size:0.875rem;color:var(--ll-gray);">The landlord will review your application
                                shortly.</p>
                        </div>
                    </div>
                <?php elseif (!is_user_logged_in()): ?>
                    <div class="ll-card" style="padding:1.5rem;margin-bottom:1rem;">
                        <h3 style="font-size:1.125rem;font-weight:600;margin-bottom:0.5rem;">Interested?</h3>
                        <p style="font-size:0.875rem;color:var(--ll-gray);margin-bottom:1rem;">Log in or create an account
                            to apply.</p>
                        <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="ll-btn ll-btn-primary"
                            style="width:100%;text-align:center;text-decoration:none;">Log In to Apply</a>
                    </div>
                <?php endif; ?>

                <!-- Views -->
                <div class="ll-card" style="padding:1rem;">
                    <p style="font-size:0.875rem;color:var(--ll-gray);margin:0;">
                        <?php echo esc_html($views); ?> views
                    </p>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php get_footer(); ?>