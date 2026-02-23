<?php
/**
 * Listing Meta Boxes.
 *
 * Registers and saves admin meta boxes for the Listing CPT.
 * Includes the Unit/Property relationship selector.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Admin;

/**
 * Class SRP_Listing_Meta
 */
class SRP_Listing_Meta
{

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_cpt_listing', array($this, 'save_meta'), 10, 2);
    }

    /**
     * Register meta boxes.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'srp_listing_unit',
            __('Linked Unit & Property', 'leaselink-core'),
            array($this, 'render_unit_meta_box'),
            'cpt_listing',
            'side',
            'high'
        );

        // Admin-only: Listing Controls (featured, expiry, admin notes).
        if ( current_user_can( 'moderate_listings' ) ) {
            add_meta_box(
                'srp_listing_publishing',
                __( 'Listing Controls (Admin)', 'leaselink-core' ),
                array( $this, 'render_publishing_meta_box' ),
                'cpt_listing',
                'normal',
                'high'
            );
        }

        add_meta_box(
            'srp_listing_stats',
            __('Statistics', 'leaselink-core'),
            array($this, 'render_stats_meta_box'),
            'cpt_listing',
            'side',
            'default'
        );
    }

    /**
     * Render Unit & Property selector.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_unit_meta_box($post)
    {
        wp_nonce_field('srp_listing_meta', 'srp_listing_meta_nonce');

        $selected_unit = get_post_meta($post->ID, '_listing_unit_id', true);
        $selected_property = get_post_meta($post->ID, '_listing_property_id', true);

        // Get all units grouped by property.
        $properties = get_posts(array(
            'post_type' => 'cpt_property',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        <p>
            <label for="srp_listing_unit_id"><strong>
                    <?php esc_html_e('Select Unit', 'leaselink-core'); ?>
                </strong></label>
        </p>
        <select id="srp_listing_unit_id" name="_listing_unit_id" style="width: 100%;">
            <option value="">
                <?php esc_html_e('— Select Unit —', 'leaselink-core'); ?>
            </option>
            <?php foreach ($properties as $property): ?>
                <?php
                $units = get_posts(array(
                    'post_type' => 'cpt_unit',
                    'post_status' => array('publish', 'draft'),
                    'meta_key' => '_unit_property_id',
                    'meta_value' => $property->ID,
                    'posts_per_page' => -1,
                    'orderby' => 'title',
                    'order' => 'ASC',
                ));
                if (empty($units)) {
                    continue;
                }
                ?>
                <optgroup label="<?php echo esc_attr($property->post_title); ?>">
                    <?php foreach ($units as $unit): ?>
                        <?php
                        $rent = get_post_meta($unit->ID, '_rent_price', true);
                        $label = $unit->post_title;
                        if ($rent) {
                            $label .= ' — €' . number_format(floatval($rent), 0, ',', '.');
                        }
                        ?>
                        <option value="<?php echo esc_attr($unit->ID); ?>" data-property="<?php echo esc_attr($property->ID); ?>"
                            <?php selected($selected_unit, $unit->ID); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Units are grouped by their parent Property.', 'leaselink-core'); ?>
        </p>

        <?php if ($selected_property): ?>
            <?php $prop_post = get_post($selected_property); ?>
            <?php if ($prop_post): ?>
                <p>
                    <strong>
                        <?php esc_html_e('Property:', 'leaselink-core'); ?>
                    </strong>
                    <a href="<?php echo esc_url(get_edit_post_link($selected_property)); ?>">
                        <?php echo esc_html($prop_post->post_title); ?>
                    </a>
                </p>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Auto-set property_id from selected unit -->
        <input type="hidden" name="_listing_property_id" id="srp_listing_property_id"
            value="<?php echo esc_attr($selected_property); ?>" />

        <script>
            (function () {
                var unitSelect = document.getElementById('srp_listing_unit_id');
                var propInput = document.getElementById('srp_listing_property_id');
                if (unitSelect && propInput) {
                    unitSelect.addEventListener('change', function () {
                        var selected = this.options[this.selectedIndex];
                        propInput.value = selected.getAttribute('data-property') || '';
                    });
                }
            })();
        </script>
        <?php
    }

    /**
     * Render Listing Controls meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_publishing_meta_box($post)
    {
        $verification_status = get_post_meta($post->ID, '_verification_status', true);
        $featured_flag       = get_post_meta($post->ID, '_featured_flag', true);
        $featured_expires    = get_post_meta($post->ID, '_featured_expires_at', true);
        $expiry_date         = get_post_meta($post->ID, '_listing_expiry_date', true);
        $suspended_at        = get_post_meta($post->ID, '_suspended_at', true);
        $suspension_reason   = get_post_meta($post->ID, '_suspension_reason', true);
        $admin_notes         = get_post_meta($post->ID, '_admin_notes', true);

        $verification_statuses = array(
            'unverified' => __( 'Unverified', 'leaselink-core' ),
            'approved'   => __( 'Approved', 'leaselink-core' ),
            'rejected'   => __( 'Rejected', 'leaselink-core' ),
        );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="srp_verification_status">
                        <?php esc_html_e('Verification Status', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <select id="srp_verification_status" name="_verification_status">
                        <?php foreach ( $verification_statuses as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $verification_status, $value ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="srp_featured_flag">
                        <?php esc_html_e('Featured Listing', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <label>
                        <input type="checkbox" id="srp_featured_flag" name="_featured_flag" value="1" <?php checked($featured_flag, '1'); ?> />
                        <?php esc_html_e('Mark as featured (paid promotion)', 'leaselink-core'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><label for="srp_featured_expires_at">
                        <?php esc_html_e('Featured Expires', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="datetime-local" id="srp_featured_expires_at" name="_featured_expires_at"
                        value="<?php echo esc_attr($featured_expires); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="srp_listing_expiry_date">
                        <?php esc_html_e('Listing Expiry', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="datetime-local" id="srp_listing_expiry_date" name="_listing_expiry_date"
                        value="<?php echo esc_attr($expiry_date); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Auto-set to 90 days from publish. Override here.', 'leaselink-core'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="srp_suspended_at">
                        <?php esc_html_e('Suspended At', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="datetime-local" id="srp_suspended_at" name="_suspended_at"
                        value="<?php echo esc_attr( $suspended_at ); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="srp_suspension_reason">
                        <?php esc_html_e('Suspension Reason', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <textarea id="srp_suspension_reason" name="_suspension_reason" rows="2"
                        class="large-text"><?php echo esc_textarea( $suspension_reason ); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="srp_admin_notes">
                        <?php esc_html_e('Admin Notes', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <textarea id="srp_admin_notes" name="_admin_notes" rows="3"
                        class="large-text"><?php echo esc_textarea($admin_notes); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Internal only — not visible to landlords or students.', 'leaselink-core'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Statistics meta box (read-only).
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_stats_meta_box($post)
    {
        $view_count = get_post_meta($post->ID, '_view_count', true);
        $application_count = get_post_meta($post->ID, '_application_count', true);
        $published_at = get_post_meta($post->ID, '_published_at', true);
        ?>
        <p>
            <strong>
                <?php esc_html_e('Views:', 'leaselink-core'); ?>
            </strong>
            <?php echo esc_html($view_count ? number_format(intval($view_count)) : '0'); ?>
        </p>
        <p>
            <strong>
                <?php esc_html_e('Applications:', 'leaselink-core'); ?>
            </strong>
            <?php echo esc_html($application_count ? $application_count : '0'); ?>
        </p>
        <?php if ($published_at): ?>
            <p>
                <strong>
                    <?php esc_html_e('Published:', 'leaselink-core'); ?>
                </strong>
                <?php echo esc_html($published_at); ?>
            </p>
        <?php endif; ?>
    <?php
    }

    /**
     * Save Listing meta box data.
     *
     * @since 1.0.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save_meta($post_id, $post)
    {
        if (
            !isset($_POST['srp_listing_meta_nonce']) ||
            !wp_verify_nonce($_POST['srp_listing_meta_nonce'], 'srp_listing_meta')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_listing', $post_id)) {
            return;
        }

        // ---- Relationships ----
        if (isset($_POST['_listing_unit_id'])) {
            $unit_id = absint($_POST['_listing_unit_id']);
            if ($unit_id > 0) {
                update_post_meta($post_id, '_listing_unit_id', $unit_id);

                // Auto-resolve property from unit.
                $property_id = get_post_meta($unit_id, '_unit_property_id', true);
                if ($property_id) {
                    update_post_meta($post_id, '_listing_property_id', absint($property_id));
                }
            } else {
                delete_post_meta($post_id, '_listing_unit_id');
                delete_post_meta($post_id, '_listing_property_id');
            }
        }

        // Override property (hidden field, auto-set by JS).
        if (isset($_POST['_listing_property_id']) && absint($_POST['_listing_property_id']) > 0) {
            update_post_meta($post_id, '_listing_property_id', absint($_POST['_listing_property_id']));
        }

        // ---- Admin-only fields (require moderate_listings) ----
        if ( current_user_can( 'moderate_listings' ) ) {
            // Verification status.
            if ( isset( $_POST['_verification_status'] ) ) {
                $allowed_verify = array( 'unverified', 'approved', 'rejected' );
                $ver_status = sanitize_text_field( wp_unslash( $_POST['_verification_status'] ) );
                if ( in_array( $ver_status, $allowed_verify, true ) ) {
                    update_post_meta( $post_id, '_verification_status', $ver_status );
                }
            }

            // Featured flag.
            if ( isset( $_POST['_featured_flag'] ) ) {
                update_post_meta( $post_id, '_featured_flag', '1' );
            } else {
                update_post_meta( $post_id, '_featured_flag', '0' );
            }

            // Datetime fields.
            $datetime_fields = array( '_featured_expires_at', '_listing_expiry_date', '_suspended_at' );
            foreach ( $datetime_fields as $key ) {
                if ( isset( $_POST[ $key ] ) && ! empty( $_POST[ $key ] ) ) {
                    update_post_meta( $post_id, $key, sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) );
                } else {
                    delete_post_meta( $post_id, $key );
                }
            }

            // Suspension reason.
            if ( isset( $_POST['_suspension_reason'] ) ) {
                update_post_meta( $post_id, '_suspension_reason', sanitize_textarea_field( wp_unslash( $_POST['_suspension_reason'] ) ) );
            }

            // Admin notes.
            if ( isset( $_POST['_admin_notes'] ) ) {
                update_post_meta( $post_id, '_admin_notes', sanitize_textarea_field( wp_unslash( $_POST['_admin_notes'] ) ) );
            }
        }

        // ---- Timestamps ----
        update_post_meta($post_id, '_updated_at', current_time('mysql'));

        if (!get_post_meta($post_id, '_created_at', true)) {
            update_post_meta($post_id, '_created_at', current_time('mysql'));
        }
    }
}
