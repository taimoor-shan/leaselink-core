<?php
/**
 * REST API — Listings Endpoints.
 *
 * Public listing search and detail endpoints.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\RestApi;

/**
 * Class SRP_REST_Listings
 *
 * Registers REST routes for public listing access per REQUIREMENTS §7.1.
 */
class SRP_REST_Listings
{

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'rental/v1';

    /**
     * Register hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register the REST routes.
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        register_rest_route(self::NAMESPACE , '/listings', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_listings'),
            'permission_callback' => '__return_true',
            'args' => $this->get_collection_params(),
        ));

        register_rest_route(self::NAMESPACE , '/listings/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_listing'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    },
                ),
            ),
        ));
    }

    /**
     * Get paginated, filtered listings.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_listings($request)
    {
        $page = absint($request->get_param('page')) ?: 1;
        $per_page = absint($request->get_param('per_page')) ?: 24;

        if ($per_page > 100) {
            $per_page = 100;
        }

        $args = array(
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array('relation' => 'AND'),
            'tax_query' => array('relation' => 'AND'),
        );

        // Price range filter.
        $min_price = $request->get_param('min_price');
        $max_price = $request->get_param('max_price');

        if ($min_price !== null || $max_price !== null) {
            $price_query = array(
                'key' => '_rent_price',
                'type' => 'NUMERIC',
            );

            if ($min_price !== null && $max_price !== null) {
                $price_query['value'] = array(floatval($min_price), floatval($max_price));
                $price_query['compare'] = 'BETWEEN';
            } elseif ($min_price !== null) {
                $price_query['value'] = floatval($min_price);
                $price_query['compare'] = '>=';
            } else {
                $price_query['value'] = floatval($max_price);
                $price_query['compare'] = '<=';
            }

            $args['meta_query'][] = $price_query;
        }

        // City filter.
        $city = $request->get_param('city');
        if ($city) {
            $args['meta_query'][] = array(
                'key' => '_property_city',
                'value' => sanitize_text_field($city),
                'compare' => '=',
            );
        }

        // Gender preference.
        $gender = $request->get_param('gender');
        if ($gender) {
            $args['meta_query'][] = array(
                'key' => '_gender_preference',
                'value' => sanitize_text_field($gender),
                'compare' => '=',
            );
        }

        // Furnished status.
        $furnished = $request->get_param('furnished');
        if ($furnished) {
            $args['meta_query'][] = array(
                'key' => '_furnished_status',
                'value' => sanitize_text_field($furnished),
                'compare' => '=',
            );
        }

        // Room type taxonomy.
        $room_type = $request->get_param('room_type');
        if ($room_type) {
            $args['tax_query'][] = array(
                'taxonomy' => 'room_type',
                'field' => 'slug',
                'terms' => array_map('sanitize_text_field', (array) $room_type),
            );
        }

        // Amenities taxonomy.
        $amenities = $request->get_param('amenities');
        if ($amenities) {
            $args['tax_query'][] = array(
                'taxonomy' => 'amenity',
                'field' => 'slug',
                'terms' => array_map('sanitize_text_field', (array) $amenities),
                'operator' => 'AND',
            );
        }

        // Only available units.
        $args['meta_query'][] = array(
            'key' => '_availability_status',
            'value' => 'available',
            'compare' => '=',
        );

        $query = new \WP_Query($args);
        $listings = array();

        if ($query->have_posts()) {
            // Pre-load meta cache for performance.
            update_post_meta_cache(wp_list_pluck($query->posts, 'ID'));

            foreach ($query->posts as $post) {
                $listings[] = $this->prepare_listing($post);
            }
        }

        $response = new \WP_REST_Response($listings, 200);
        $response->header('X-WP-Total', $query->found_posts);
        $response->header('X-WP-TotalPages', $query->max_num_pages);

        return $response;
    }

    /**
     * Get a single listing.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_listing($request)
    {
        $post = get_post($request->get_param('id'));

        if (!$post || 'cpt_listing' !== $post->post_type || 'publish' !== $post->post_status) {
            return new \WP_Error('not_found', __('Listing not found.', 'leaselink-core'), array('status' => 404));
        }

        // Increment view count.
        $count = absint(get_post_meta($post->ID, '_view_count', true));
        update_post_meta($post->ID, '_view_count', $count + 1);

        return new \WP_REST_Response($this->prepare_listing($post, true), 200);
    }

    /**
     * Prepare a listing for API response.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post   The listing post.
     * @param bool     $detail Whether to include full detail (address, etc.).
     * @return array
     */
    private function prepare_listing($post, $detail = false)
    {
        $unit_id = get_post_meta($post->ID, '_listing_unit_id', true);
        $property_id = get_post_meta($post->ID, '_listing_property_id', true);

        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'description' => $post->post_content,
            'rent_price' => floatval(get_post_meta($unit_id, '_rent_price', true)),
            'currency' => get_post_meta($unit_id, '_currency', true) ?: 'EUR',
            'deposit_amount' => floatval(get_post_meta($unit_id, '_deposit_amount', true)),
            'city' => get_post_meta($property_id, '_property_city', true),
            'furnished' => get_post_meta($unit_id, '_furnished_status', true),
            'gender' => get_post_meta($unit_id, '_gender_preference', true),
            'max_occupancy' => absint(get_post_meta($unit_id, '_max_occupancy', true)),
            'square_footage' => absint(get_post_meta($unit_id, '_square_footage', true)),
            'available_from' => get_post_meta($unit_id, '_available_from', true),
            'available_to' => get_post_meta($unit_id, '_available_to', true),
            'pet_policy' => get_post_meta($unit_id, '_pet_policy', true),
            'smoking_policy' => get_post_meta($unit_id, '_smoking_policy', true),
            'utilities' => get_post_meta($unit_id, '_utilities_included', true),
            'amenities' => wp_get_post_terms($unit_id, 'amenity', array('fields' => 'names')),
            'room_type' => wp_get_post_terms($unit_id, 'room_type', array('fields' => 'names')),
            'property_type' => wp_get_post_terms($property_id, 'property_type', array('fields' => 'names')),
            'featured' => (bool) get_post_meta($post->ID, '_featured_flag', true),
            'view_count' => absint(get_post_meta($post->ID, '_view_count', true)),
            'published_at' => get_post_meta($post->ID, '_published_at', true),
            'permalink' => get_permalink($post->ID),
            'thumbnail' => get_the_post_thumbnail_url($post->ID, 'large'),
        );

        // Full detail includes address (shown after inquiry per §5.1).
        if ($detail) {
            $data['address'] = get_post_meta($property_id, '_property_address', true);
            $data['state'] = get_post_meta($property_id, '_property_state', true);
            $data['country'] = get_post_meta($property_id, '_property_country', true);
            $data['postal_code'] = get_post_meta($property_id, '_property_postal_code', true);
            $data['latitude'] = floatval(get_post_meta($property_id, '_property_latitude', true));
            $data['longitude'] = floatval(get_post_meta($property_id, '_property_longitude', true));
            $data['floor'] = absint(get_post_meta($unit_id, '_floor_number', true));
            $data['lease_min'] = absint(get_post_meta($unit_id, '_lease_duration_min', true));
            $data['lease_max'] = absint(get_post_meta($unit_id, '_lease_duration_max', true));
        }

        return $data;
    }

    /**
     * Get collection parameters for the listings endpoint.
     *
     * @since 1.0.0
     *
     * @return array
     */
    private function get_collection_params()
    {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default' => 24,
                'sanitize_callback' => 'absint',
            ),
            'city' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'min_price' => array(
                'sanitize_callback' => 'floatval',
            ),
            'max_price' => array(
                'sanitize_callback' => 'floatval',
            ),
            'gender' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'furnished' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'room_type' => array(
                'sanitize_callback' => 'sanitize_text_field',
            ),
            'amenities' => array(
                'type' => 'array',
                'items' => array('type' => 'string'),
            ),
        );
    }
}
