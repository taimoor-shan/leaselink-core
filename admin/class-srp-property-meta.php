<?php
/**
 * Property Meta Boxes.
 *
 * Registers and saves admin meta boxes for the Property CPT.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Admin;

/**
 * Class SRP_Property_Meta
 */
class SRP_Property_Meta
{

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_cpt_property', array($this, 'save_meta'), 10, 2);
    }

    /**
     * Register meta boxes.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'srp_property_location',
            __('Property Location', 'leaselink-core'),
            array($this, 'render_location_meta_box'),
            'cpt_property',
            'normal',
            'high'
        );

        add_meta_box(
            'srp_property_details',
            __('Property Details', 'leaselink-core'),
            array($this, 'render_details_meta_box'),
            'cpt_property',
            'side',
            'default'
        );
    }

    /**
     * Render the Location meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_location_meta_box($post)
    {
        wp_nonce_field('srp_property_meta', 'srp_property_meta_nonce');

        $address = get_post_meta($post->ID, '_property_address', true);
        $city = get_post_meta($post->ID, '_property_city', true);
        $state = get_post_meta($post->ID, '_property_state', true);
        $country = get_post_meta($post->ID, '_property_country', true);
        $postal_code = get_post_meta($post->ID, '_property_postal_code', true);
        $latitude = get_post_meta($post->ID, '_property_latitude', true);
        $longitude = get_post_meta($post->ID, '_property_longitude', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="srp_property_address">
                        <?php esc_html_e('Street Address', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="text" id="srp_property_address" name="_property_address"
                        value="<?php echo esc_attr($address); ?>" class="large-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_city">
                        <?php esc_html_e('City', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="text" id="srp_property_city" name="_property_city" value="<?php echo esc_attr($city); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_state">
                        <?php esc_html_e('State / Province', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="text" id="srp_property_state" name="_property_state" value="<?php echo esc_attr($state); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_country">
                        <?php esc_html_e('Country (ISO Code)', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="text" id="srp_property_country" name="_property_country"
                        value="<?php echo esc_attr($country); ?>" class="small-text" maxlength="2" placeholder="US" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_postal_code">
                        <?php esc_html_e('Postal Code', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="text" id="srp_property_postal_code" name="_property_postal_code"
                        value="<?php echo esc_attr($postal_code); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_latitude">
                        <?php esc_html_e('Latitude', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="number" step="0.000001" id="srp_property_latitude" name="_property_latitude"
                        value="<?php echo esc_attr($latitude); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_property_longitude">
                        <?php esc_html_e('Longitude', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="number" step="0.000001" id="srp_property_longitude" name="_property_longitude"
                        value="<?php echo esc_attr($longitude); ?>" class="regular-text" /></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render the Details meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_details_meta_box($post)
    {
        $verification_status = get_post_meta($post->ID, '_verification_status', true);
        $verification_notes = get_post_meta($post->ID, '_verification_notes', true);

        $statuses = array(
            'unverified' => __('Unverified', 'leaselink-core'),
            'pending' => __('Pending', 'leaselink-core'),
            'verified' => __('Verified', 'leaselink-core'),
            'rejected' => __('Rejected', 'leaselink-core'),
        );

        // Count units for this property.
        $unit_count = $this->get_unit_count($post->ID);
        ?>
        <p>
            <strong>
                <?php esc_html_e('Units:', 'leaselink-core'); ?>
            </strong>
            <?php echo esc_html($unit_count); ?>
            <?php if ($unit_count > 0): ?>
                — <a href="<?php echo esc_url(admin_url('edit.php?post_type=cpt_unit&srp_property_id=' . $post->ID)); ?>">
                    <?php esc_html_e('View Units', 'leaselink-core'); ?>
                </a>
            <?php endif; ?>
        </p>
        <hr />
        <?php if (current_user_can('verify_landlords')): ?>
            <p>
                <label for="srp_verification_status"><strong>
                        <?php esc_html_e('Verification Status', 'leaselink-core'); ?>
                    </strong></label><br />
                <select id="srp_verification_status" name="_verification_status" style="width: 100%;">
                    <?php foreach ($statuses as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($verification_status, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="srp_verification_notes"><strong>
                        <?php esc_html_e('Verification Notes', 'leaselink-core'); ?>
                    </strong></label><br />
                <textarea id="srp_verification_notes" name="_verification_notes" rows="4"
                    style="width: 100%;"><?php echo esc_textarea($verification_notes); ?></textarea>
            </p>
        <?php else: ?>
            <p>
                <strong><?php esc_html_e('Verification:', 'leaselink-core'); ?></strong>
                <?php
                $display_status = isset($statuses[$verification_status]) ? $statuses[$verification_status] : __('Unverified', 'leaselink-core');
                echo esc_html($display_status);
                ?>
            </p>
        <?php endif; ?>
    <?php
    }

    /**
     * Save meta box data.
     *
     * @since 1.0.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save_meta($post_id, $post)
    {
        // Verify nonce.
        if (
            !isset($_POST['srp_property_meta_nonce']) ||
            !wp_verify_nonce($_POST['srp_property_meta_nonce'], 'srp_property_meta')
        ) {
            return;
        }

        // Check autosave.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permission.
        if (!current_user_can('edit_property', $post_id)) {
            return;
        }

        // Text fields.
        $text_fields = array(
            '_property_address',
            '_property_city',
            '_property_state',
            '_property_country',
            '_property_postal_code',
        );

        foreach ($text_fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
            }
        }

        // Float fields.
        if (isset($_POST['_property_latitude'])) {
            update_post_meta($post_id, '_property_latitude', floatval($_POST['_property_latitude']));
        }
        if (isset($_POST['_property_longitude'])) {
            update_post_meta($post_id, '_property_longitude', floatval($_POST['_property_longitude']));
        }

        // Verification status — admin only.
        if (current_user_can('verify_landlords')) {
            if (isset($_POST['_verification_status'])) {
                $allowed = array('unverified', 'pending', 'verified', 'rejected');
                $status = sanitize_text_field(wp_unslash($_POST['_verification_status']));
                if (in_array($status, $allowed, true)) {
                    update_post_meta($post_id, '_verification_status', $status);
                }
            }

            // Verification notes.
            if (isset($_POST['_verification_notes'])) {
                update_post_meta($post_id, '_verification_notes', sanitize_textarea_field(wp_unslash($_POST['_verification_notes'])));
            }
        }

        // Timestamps.
        update_post_meta($post_id, '_updated_at', current_time('mysql'));

        if (!get_post_meta($post_id, '_created_at', true)) {
            update_post_meta($post_id, '_created_at', current_time('mysql'));
        }
    }

    /**
     * Get the number of units belonging to a property.
     *
     * @since 1.0.0
     *
     * @param int $property_id The property post ID.
     * @return int
     */
    private function get_unit_count($property_id)
    {
        $units = get_posts(array(
            'post_type' => 'cpt_unit',
            'post_status' => 'any',
            'meta_key' => '_unit_property_id',
            'meta_value' => $property_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ));

        return count($units);
    }
}
