<?php
/**
 * REST API — Properties Endpoints.
 *
 * Authenticated endpoints for creating, viewing,
 * and managing properties and their units.
 *
 * @package    StudentRentalPlatform
 * @since      1.1.0
 */

namespace StudentRentalPlatform\RestApi;

/**
 * Class SRP_REST_Properties
 *
 * Registers REST routes for property and unit management.
 */
class SRP_REST_Properties
{

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'rental/v1';

    /**
     * Initialize and register routes.
     *
     * @since 1.1.0
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register the REST routes.
     *
     * @since 1.1.0
     */
    public function register_routes()
    {
        // Create property.
        register_rest_route(self::NAMESPACE , '/properties', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_property'),
            'permission_callback' => array($this, 'check_landlord_permission'),
        ));

        // Create unit for a property.
        register_rest_route(self::NAMESPACE , '/properties/(?P<id>\d+)/units', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_unit'),
            'permission_callback' => array($this, 'check_landlord_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                )
            ),
        ));

        // Get landlord's properties.
        register_rest_route(self::NAMESPACE , '/properties', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_properties'),
            'permission_callback' => array($this, 'check_landlord_permission'),
        ));
    }

    /**
     * Handle featured image upload.
     */
    private function handle_featured_image($post_id, $file_array)
    {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_sideload($file_array, $post_id);
        if (!is_wp_error($attachment_id)) {
            set_post_thumbnail($post_id, $attachment_id);
        }
        return $attachment_id;
    }

    /**
     * Create a new property.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_property($request)
    {
        $title = $request->get_param('title');
        $description = $request->get_param('description') ?? '';

        $post_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($title),
            'post_content' => wp_kses_post($description),
            'post_type' => 'cpt_property',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ), true);

        if (is_wp_error($post_id)) {
            return new \WP_Error(
                'create_failed',
                __('Failed to create property.', 'leaselink-core'),
                array('status' => 500)
            );
        }

        // Handle image upload
        $files = $request->get_file_params();
        if (isset($files['featured_image']) && !empty($files['featured_image']['name'])) {
            $this->handle_featured_image($post_id, $files['featured_image']);
        }

        // Save meta fields.
        $meta_fields = array(
            '_property_address' => sanitize_text_field($request->get_param('address')),
            '_property_city' => sanitize_text_field($request->get_param('city')),
            '_property_state' => sanitize_text_field($request->get_param('state')),
            '_property_country' => sanitize_text_field($request->get_param('country')),
            '_property_postal_code' => sanitize_text_field($request->get_param('postal_code')),
            '_property_latitude' => floatval($request->get_param('latitude')),
            '_property_longitude' => floatval($request->get_param('longitude')),
        );

        foreach ($meta_fields as $key => $value) {
            if (!empty($value)) {
                update_post_meta($post_id, $key, $value);
            }
        }

        // Save taxonomy
        $property_type = sanitize_text_field($request->get_param('property_type'));
        if (!empty($property_type)) {
            wp_set_object_terms($post_id, $property_type, 'property_type');
        }

        return new \WP_REST_Response(
            array(
                'id' => $post_id,
                'title' => sanitize_text_field($title),
                'message' => __('Property created successfully.', 'leaselink-core'),
            ),
            201
        );
    }

    /**
     * Create a unit for an existing property.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_unit($request)
    {
        $property_id = absint($request->get_param('id'));

        // Verify the property exists and belongs to the user (or user is admin).
        $property = get_post($property_id);
        if (!$property || 'cpt_property' !== $property->post_type) {
            return new \WP_Error(
                'invalid_property',
                __('Property not found.', 'leaselink-core'),
                array('status' => 404)
            );
        }

        if (absint($property->post_author) !== get_current_user_id() && !current_user_can('manage_options')) {
            return new \WP_Error(
                'unauthorized',
                __('You do not own this property.', 'leaselink-core'),
                array('status' => 403)
            );
        }

        $title = $request->get_param('title');
        $description = $request->get_param('description') ?? '';

        $unit_id = wp_insert_post(array(
            'post_title' => sanitize_text_field($title),
            'post_content' => wp_kses_post($description),
            'post_type' => 'cpt_unit',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ), true);

        if (is_wp_error($unit_id)) {
            return new \WP_Error(
                'create_failed',
                __('Failed to create unit.', 'leaselink-core'),
                array('status' => 500)
            );
        }

        // Handle image upload
        $files = $request->get_file_params();
        if (isset($files['featured_image']) && !empty($files['featured_image']['name'])) {
            $this->handle_featured_image($unit_id, $files['featured_image']);
        }

        // Link unit to property.
        update_post_meta($unit_id, '_unit_property_id', $property_id);

        // Utilities
        $utilities = $request->get_param('utilities');
        if (!empty($utilities)) {
            if (!is_array($utilities)) {
                $utilities = explode(',', $utilities);
            }
            $utilities = array_map('sanitize_text_field', $utilities);
        } else {
            $utilities = array();
        }

        // Save unit meta fields.
        $meta_fields = array(
            '_rent_price' => floatval($request->get_param('rent_price')),
            '_deposit_amount' => floatval($request->get_param('deposit')),
            '_currency' => sanitize_text_field($request->get_param('currency')) ?: 'USD',
            '_lease_duration_min' => absint($request->get_param('lease_duration_min')),
            '_lease_duration_max' => absint($request->get_param('lease_duration_max')),
            '_room_type' => sanitize_text_field($request->get_param('room_type')),
            '_furnished_status' => sanitize_text_field($request->get_param('furnished')),
            '_available_from' => sanitize_text_field($request->get_param('available_from')),
            '_gender_preference' => sanitize_text_field($request->get_param('gender_preference')),
            '_max_occupancy' => absint($request->get_param('max_occupancy')),
            '_square_footage' => absint($request->get_param('square_footage')),
            '_floor_number' => absint($request->get_param('floor_number')),
            '_pet_policy' => sanitize_text_field($request->get_param('pet_policy')),
            '_smoking_policy' => sanitize_text_field($request->get_param('smoking_policy')),
            '_utilities_included' => $utilities,
            '_availability_status' => 'available',
        );

        foreach ($meta_fields as $key => $value) {
            if (!empty($value) || $value === 0 || $value === 0.0) {
                update_post_meta($unit_id, $key, $value);
            }
        }

        return new \WP_REST_Response(
            array(
                'id' => $unit_id,
                'property_id' => $property_id,
                'title' => sanitize_text_field($title),
                'message' => __('Unit created successfully.', 'leaselink-core'),
            ),
            201
        );
    }

    /**
     * Get properties for the current landlord.
     *
     * @since 1.1.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_my_properties($request)
    {
        $properties = get_posts(array(
            'post_type' => 'cpt_property',
            'author' => get_current_user_id(),
            'posts_per_page' => -1,
            'post_status' => 'any',
        ));

        $data = array();
        foreach ($properties as $property) {
            $data[] = array(
                'id' => $property->ID,
                'title' => $property->post_title,
                'address' => get_post_meta($property->ID, '_property_address', true),
                'city' => get_post_meta($property->ID, '_property_city', true),
                'status' => $property->post_status,
            );
        }

        return new \WP_REST_Response($data, 200);
    }

    /**
     * Check if the current user can manage properties.
     *
     * @since 1.1.0
     *
     * @return bool
     */
    public function check_landlord_permission()
    {
        return is_user_logged_in() && (current_user_can('manage_properties') || current_user_can('manage_options'));
    }
}
