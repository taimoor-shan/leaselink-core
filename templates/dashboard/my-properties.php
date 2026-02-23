<?php
/**
 * Shortcode Template: My Properties (Landlord)
 * @package LeaseLink
 * @since   1.1.0
 */
use StudentRentalPlatform\SRP_Template_Loader;

$user_id = get_current_user_id();
$properties = get_posts(['post_type' => 'cpt_property', 'post_status' => 'any', 'author' => $user_id, 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC']);
?>
<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>
    <div class="ll-dashboard-content">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;">
            <h1 style="font-size:1.5rem;font-weight:700;margin:0;">My Properties</h1>
            <a href="<?php echo home_url('/add-property/'); ?>" class="ll-btn ll-btn-primary"
                style="text-decoration:none;">+ Add Property</a>
        </div>

        <?php if (!empty($properties)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(320px,1fr));gap:1.25rem;">
                <?php foreach ($properties as $property):
                    $prop_id = $property->ID;
                    $city = get_post_meta($prop_id, '_property_city', true);
                    $thumbnail = get_the_post_thumbnail_url($prop_id, 'medium');
                    $units = get_posts(['post_type' => 'cpt_unit', 'post_parent' => $prop_id, 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids']);
                    $unit_count = count($units);
                    $status_map = ['draft' => ['Draft', 'gray'], 'publish' => ['Published', 'success'], 'suspended' => ['Suspended', 'danger']];
                    $sb = $status_map[$property->post_status] ?? [ucfirst($property->post_status), 'gray'];
                    ?>
                    <div class="ll-card" style="display:flex;overflow:hidden;">
                        <div style="width:120px;background:var(--ll-surface);flex-shrink:0;">
                            <?php if ($thumbnail): ?>
                                <img src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($property->post_title); ?>"
                                    style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <div
                                    style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--ll-border);">
                                    <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="padding:1rem;flex:1;">
                            <div
                                style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0.5rem;">
                                <p style="font-weight:600;font-size:0.9375rem;margin:0;color:var(--ll-dark);">
                                    <?php echo esc_html($property->post_title); ?>
                                </p>
                                <?php SRP_Template_Loader::get_template('components/badge.php', ['text' => $sb[0], 'color' => $sb[1]]); ?>
                            </div>
                            <?php if ($city): ?>
                                <p style="font-size:0.8125rem;color:var(--ll-gray);margin:0 0 0.75rem;">
                                    <?php echo esc_html($city); ?>
                                </p>
                            <?php endif; ?>
                            <p style="font-size:0.75rem;color:var(--ll-gray);margin:0 0 0.75rem;">
                                <?php echo esc_html($unit_count); ?> unit(s)
                            </p>
                            <div style="display:flex;gap:0.5rem;">
                                <a href="<?php echo get_edit_post_link($prop_id); ?>" class="ll-btn ll-btn-secondary ll-btn-sm"
                                    style="text-decoration:none;">Edit</a>
                                <a href="<?php echo get_permalink($prop_id); ?>" class="ll-btn ll-btn-ghost ll-btn-sm"
                                    style="text-decoration:none;">View</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="ll-card">
                <?php SRP_Template_Loader::get_template('components/empty-state.php', [
                    'title' => 'No properties yet',
                    'description' => 'Add your first property to start listing.',
                    'icon' => 'building',
                    'action_url' => home_url('/add-property/'),
                    'action_text' => 'Add Property',
                ]); ?>
            </div>
        <?php endif; ?>
    </div>
</div>