<?php
/**
 * Unit Meta Boxes.
 *
 * Registers and saves admin meta boxes for the Unit CPT.
 * Includes the critical Property relationship dropdown.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Admin;

/**
 * Class SRP_Unit_Meta
 */
class SRP_Unit_Meta
{

    /**
     * Initialize hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_cpt_unit', array($this, 'save_meta'), 10, 2);
    }

    /**
     * Register meta boxes.
     *
     * @since 1.0.0
     */
    public function add_meta_boxes()
    {
        add_meta_box(
            'srp_unit_property',
            __('Parent Property', 'leaselink-core'),
            array($this, 'render_property_meta_box'),
            'cpt_unit',
            'side',
            'high'
        );

        add_meta_box(
            'srp_unit_pricing',
            __('Pricing & Lease', 'leaselink-core'),
            array($this, 'render_pricing_meta_box'),
            'cpt_unit',
            'normal',
            'high'
        );

        add_meta_box(
            'srp_unit_details',
            __('Unit Details', 'leaselink-core'),
            array($this, 'render_details_meta_box'),
            'cpt_unit',
            'normal',
            'default'
        );

        add_meta_box(
            'srp_unit_policies',
            __('Policies & Preferences', 'leaselink-core'),
            array($this, 'render_policies_meta_box'),
            'cpt_unit',
            'normal',
            'default'
        );

        add_meta_box(
            'srp_unit_availability',
            __('Availability', 'leaselink-core'),
            array($this, 'render_availability_meta_box'),
            'cpt_unit',
            'side',
            'default'
        );
    }

    /**
     * Render Parent Property selector.
     *
     * This is the key relationship field — assigns a Unit to a Property.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_property_meta_box($post)
    {
        wp_nonce_field('srp_unit_meta', 'srp_unit_meta_nonce');

        $selected_property = get_post_meta($post->ID, '_unit_property_id', true);

        // Get all properties the current user can see.
        $properties = get_posts(array(
            'post_type' => 'cpt_property',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        ?>
        <p>
            <label for="srp_unit_property_id"><strong>
                    <?php esc_html_e('Assign to Property', 'leaselink-core'); ?>
                </strong></label>
        </p>
        <select id="srp_unit_property_id" name="_unit_property_id" style="width: 100%;">
            <option value="">
                <?php esc_html_e('— Select Property —', 'leaselink-core'); ?>
            </option>
            <?php foreach ($properties as $property): ?>
                <option value="<?php echo esc_attr($property->ID); ?>" <?php selected($selected_property, $property->ID); ?>>
                    <?php echo esc_html($property->post_title); ?>
                    <?php
                    $city = get_post_meta($property->ID, '_property_city', true);
                    if ($city) {
                        echo ' — ' . esc_html($city);
                    }
                    ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (empty($properties)): ?>
            <p class="description" style="color: #d63638;">
                <?php esc_html_e('No properties found. Please create a Property first.', 'leaselink-core'); ?>
            </p>
        <?php else: ?>
            <p class="description">
                <?php esc_html_e('Select which property this unit belongs to.', 'leaselink-core'); ?>
            </p>
        <?php endif; ?>
    <?php
    }

    /**
     * Render Pricing & Lease meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_pricing_meta_box($post)
    {
        $rent_price = get_post_meta($post->ID, '_rent_price', true);
        $currency = get_post_meta($post->ID, '_currency', true);
        $deposit = get_post_meta($post->ID, '_deposit_amount', true);
        $min_duration = get_post_meta($post->ID, '_lease_duration_min', true);
        $max_duration = get_post_meta($post->ID, '_lease_duration_max', true);

        if (empty($currency)) {
            $currency = 'USD';
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="srp_rent_price">
                        <?php esc_html_e('Monthly Rent', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="number" step="0.01" min="0" id="srp_rent_price" name="_rent_price"
                        value="<?php echo esc_attr($rent_price); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="srp_currency">
                        <?php esc_html_e('Currency', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="text" id="srp_currency" name="_currency" value="<?php echo esc_attr($currency); ?>"
                        class="small-text" maxlength="3" placeholder="USD" />
                </td>
            </tr>
            <tr>
                <th><label for="srp_deposit_amount">
                        <?php esc_html_e('Security Deposit', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="number" step="0.01" min="0" id="srp_deposit_amount" name="_deposit_amount"
                        value="<?php echo esc_attr($deposit); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="srp_lease_duration_min">
                        <?php esc_html_e('Lease Duration (months)', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <input type="number" min="1" id="srp_lease_duration_min" name="_lease_duration_min"
                        value="<?php echo esc_attr($min_duration); ?>" class="small-text" placeholder="Min" />
                    <?php esc_html_e('to', 'leaselink-core'); ?>
                    <input type="number" min="1" id="srp_lease_duration_max" name="_lease_duration_max"
                        value="<?php echo esc_attr($max_duration); ?>" class="small-text" placeholder="Max" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Unit Details meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_details_meta_box($post)
    {
        $max_occupancy = get_post_meta($post->ID, '_max_occupancy', true);
        $furnished = get_post_meta($post->ID, '_furnished_status', true);
        $square_footage = get_post_meta($post->ID, '_square_footage', true);
        $floor_number = get_post_meta($post->ID, '_floor_number', true);
        $utilities = get_post_meta($post->ID, '_utilities_included', true);

        if (!is_array($utilities)) {
            $utilities = array();
        }

        $furnished_options = array(
            'furnished' => __('Furnished', 'leaselink-core'),
            'unfurnished' => __('Unfurnished', 'leaselink-core'),
            'partially_furnished' => __('Partially Furnished', 'leaselink-core'),
        );

        $utility_options = array(
            'electricity' => __('Electricity', 'leaselink-core'),
            'water' => __('Water', 'leaselink-core'),
            'internet' => __('Internet', 'leaselink-core'),
            'heating' => __('Heating', 'leaselink-core'),
            'gas' => __('Gas', 'leaselink-core'),
        );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="srp_max_occupancy">
                        <?php esc_html_e('Max Occupancy', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="number" min="1" id="srp_max_occupancy" name="_max_occupancy"
                        value="<?php echo esc_attr($max_occupancy); ?>" class="small-text" /></td>
            </tr>
            <tr>
                <th><label for="srp_furnished_status">
                        <?php esc_html_e('Furnished Status', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <select id="srp_furnished_status" name="_furnished_status">
                        <option value="">
                            <?php esc_html_e('— Select —', 'leaselink-core'); ?>
                        </option>
                        <?php foreach ($furnished_options as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($furnished, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="srp_square_footage">
                        <?php esc_html_e('Square Footage', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="number" min="0" id="srp_square_footage" name="_square_footage"
                        value="<?php echo esc_attr($square_footage); ?>" class="small-text" /> sqft</td>
            </tr>
            <tr>
                <th><label for="srp_floor_number">
                        <?php esc_html_e('Floor Number', 'leaselink-core'); ?>
                    </label></th>
                <td><input type="number" id="srp_floor_number" name="_floor_number"
                        value="<?php echo esc_attr($floor_number); ?>" class="small-text" /></td>
            </tr>
            <tr>
                <th>
                    <?php esc_html_e('Utilities Included', 'leaselink-core'); ?>
                </th>
                <td>
                    <?php foreach ($utility_options as $value => $label): ?>
                        <label style="display: block; margin-bottom: 4px;">
                            <input type="checkbox" name="_utilities_included[]" value="<?php echo esc_attr($value); ?>" <?php checked(in_array($value, $utilities, true)); ?> />
                            <?php echo esc_html($label); ?>
                        </label>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Policies & Preferences meta box.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_policies_meta_box($post)
    {
        $gender_pref = get_post_meta($post->ID, '_gender_preference', true);
        $pet_policy = get_post_meta($post->ID, '_pet_policy', true);
        $smoking_policy = get_post_meta($post->ID, '_smoking_policy', true);

        $gender_options = array(
            'any' => __('Any', 'leaselink-core'),
            'male' => __('Male Only', 'leaselink-core'),
            'female' => __('Female Only', 'leaselink-core'),
            'female_only' => __('Female Strictly', 'leaselink-core'),
        );

        $pet_options = array(
            'allowed' => __('Allowed', 'leaselink-core'),
            'not_allowed' => __('Not Allowed', 'leaselink-core'),
            'negotiable' => __('Negotiable', 'leaselink-core'),
        );

        $smoking_options = array(
            'allowed' => __('Allowed', 'leaselink-core'),
            'not_allowed' => __('Not Allowed', 'leaselink-core'),
            'outside_only' => __('Outside Only', 'leaselink-core'),
        );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="srp_gender_preference">
                        <?php esc_html_e('Gender Preference', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <select id="srp_gender_preference" name="_gender_preference">
                        <?php foreach ($gender_options as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($gender_pref, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="srp_pet_policy">
                        <?php esc_html_e('Pet Policy', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <select id="srp_pet_policy" name="_pet_policy">
                        <?php foreach ($pet_options as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($pet_policy, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="srp_smoking_policy">
                        <?php esc_html_e('Smoking Policy', 'leaselink-core'); ?>
                    </label></th>
                <td>
                    <select id="srp_smoking_policy" name="_smoking_policy">
                        <?php foreach ($smoking_options as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($smoking_policy, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render Availability meta box (sidebar).
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_availability_meta_box($post)
    {
        $available_from = get_post_meta($post->ID, '_available_from', true);
        $available_to = get_post_meta($post->ID, '_available_to', true);
        $status = get_post_meta($post->ID, '_availability_status', true);

        if (empty($status)) {
            $status = 'available';
        }

        $status_options = array(
            'available' => __('Available', 'leaselink-core'),
            'booked' => __('Booked', 'leaselink-core'),
            'hold' => __('On Hold', 'leaselink-core'),
            'unavailable' => __('Unavailable', 'leaselink-core'),
        );
        ?>
        <p>
            <label for="srp_availability_status"><strong>
                    <?php esc_html_e('Status', 'leaselink-core'); ?>
                </strong></label><br />
            <select id="srp_availability_status" name="_availability_status" style="width: 100%;">
                <?php foreach ($status_options as $value => $label): ?>
                    <option value="<?php echo esc_attr($value); ?>" <?php selected($status, $value); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="srp_available_from"><strong>
                    <?php esc_html_e('Available From', 'leaselink-core'); ?>
                </strong></label><br />
            <input type="date" id="srp_available_from" name="_available_from" value="<?php echo esc_attr($available_from); ?>"
                style="width: 100%;" />
        </p>
        <p>
            <label for="srp_available_to"><strong>
                    <?php esc_html_e('Available To', 'leaselink-core'); ?>
                </strong></label><br />
            <input type="date" id="srp_available_to" name="_available_to" value="<?php echo esc_attr($available_to); ?>"
                style="width: 100%;" />
            <span class="description">
                <?php esc_html_e('Leave blank for open-ended.', 'leaselink-core'); ?>
            </span>
        </p>
        <?php
    }

    /**
     * Save all Unit meta box data.
     *
     * @since 1.0.0
     *
     * @param int      $post_id Post ID.
     * @param \WP_Post $post    Post object.
     */
    public function save_meta($post_id, $post)
    {
        if (
            !isset($_POST['srp_unit_meta_nonce']) ||
            !wp_verify_nonce($_POST['srp_unit_meta_nonce'], 'srp_unit_meta')
        ) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_unit', $post_id)) {
            return;
        }

        // ---- Relationship: Property ----
        if (isset($_POST['_unit_property_id'])) {
            $property_id = absint($_POST['_unit_property_id']);
            if ($property_id > 0) {
                update_post_meta($post_id, '_unit_property_id', $property_id);
            } else {
                delete_post_meta($post_id, '_unit_property_id');
            }
        }

        // ---- Pricing ----
        $decimal_fields = array('_rent_price', '_deposit_amount');
        foreach ($decimal_fields as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, floatval($_POST[$key]));
            }
        }

        if (isset($_POST['_currency'])) {
            update_post_meta($post_id, '_currency', sanitize_text_field(wp_unslash($_POST['_currency'])));
        }

        // ---- Integer fields ----
        $int_fields = array('_lease_duration_min', '_lease_duration_max', '_max_occupancy', '_square_footage', '_floor_number');
        foreach ($int_fields as $key) {
            if (isset($_POST[$key]) && '' !== $_POST[$key]) {
                update_post_meta($post_id, $key, absint($_POST[$key]));
            } else {
                delete_post_meta($post_id, $key);
            }
        }

        // ---- Enum select fields ----
        $enum_fields = array(
            '_furnished_status' => array('furnished', 'unfurnished', 'partially_furnished'),
            '_gender_preference' => array('any', 'male', 'female', 'female_only'),
            '_pet_policy' => array('allowed', 'not_allowed', 'negotiable'),
            '_smoking_policy' => array('allowed', 'not_allowed', 'outside_only'),
            '_availability_status' => array('available', 'booked', 'hold', 'unavailable'),
        );

        foreach ($enum_fields as $key => $allowed) {
            if (isset($_POST[$key])) {
                $value = sanitize_text_field(wp_unslash($_POST[$key]));
                if (in_array($value, $allowed, true)) {
                    update_post_meta($post_id, $key, $value);
                }
            }
        }

        // ---- Date fields ----
        $date_fields = array('_available_from', '_available_to');
        foreach ($date_fields as $key) {
            if (isset($_POST[$key]) && !empty($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
            } else {
                delete_post_meta($post_id, $key);
            }
        }

        // ---- Utilities (array) ----
        if (isset($_POST['_utilities_included']) && is_array($_POST['_utilities_included'])) {
            $utilities = array_map('sanitize_text_field', wp_unslash($_POST['_utilities_included']));
            update_post_meta($post_id, '_utilities_included', $utilities);
        } else {
            update_post_meta($post_id, '_utilities_included', array());
        }

        // ---- Timestamps ----
        update_post_meta($post_id, '_updated_at', current_time('mysql'));

        if (!get_post_meta($post_id, '_created_at', true)) {
            update_post_meta($post_id, '_created_at', current_time('mysql'));
        }
    }
}
