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

        // Save / unsave a listing (bookmark).
        register_rest_route(self::NAMESPACE , '/listings/(?P<id>\d+)/save', array(
            array(
                'methods' => 'POST',
                'callback' => array($this, 'save_listing'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ),
            array(
                'methods' => 'DELETE',
                'callback' => array($this, 'unsave_listing'),
                'permission_callback' => function () {
                    return is_user_logged_in();
                },
            ),
        ));

        // Check saved status for current user.
        register_rest_route(self::NAMESPACE , '/listings/saved-ids', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_saved_ids'),
            'permission_callback' => function () {
                return is_user_logged_in();
            },
        ));
    }

    /**
     * Save (bookmark) a listing.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function save_listing($request)
    {
        global $wpdb;

        $listing_id = absint($request->get_param('id'));
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'rental_saved_listings';

        // Verify listing exists.
        $post = get_post($listing_id);
        if (!$post || 'cpt_listing' !== $post->post_type) {
            return new \WP_Error('not_found', 'Listing not found.', array('status' => 404));
        }

        // Check if already saved.
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table} WHERE student_id = %d AND listing_id = %d",
            $user_id,
            $listing_id
        ));

        if ($exists) {
            return new \WP_REST_Response(array('saved' => true, 'message' => 'Already saved.'), 200);
        }

        $wpdb->insert($table, array(
            'student_id' => $user_id,
            'listing_id' => $listing_id,
            'saved_at' => current_time('mysql'),
        ), array('%d', '%d', '%s'));

        return new \WP_REST_Response(array('saved' => true, 'message' => 'Listing saved.'), 201);
    }

    /**
     * Unsave (remove bookmark) a listing.
     *
     * @param \WP_REST_Request $request
     * @return \WP_REST_Response
     */
    public function unsave_listing($request)
    {
        global $wpdb;

        $listing_id = absint($request->get_param('id'));
        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'rental_saved_listings';

        $wpdb->delete($table, array(
            'student_id' => $user_id,
            'listing_id' => $listing_id,
        ), array('%d', '%d'));

        return new \WP_REST_Response(array('saved' => false, 'message' => 'Listing removed.'), 200);
    }

    /**
     * Get all saved listing IDs for the current user.
     *
     * @return \WP_REST_Response
     */
    public function get_saved_ids()
    {
        global $wpdb;

        $user_id = get_current_user_id();
        $table = $wpdb->prefix . 'rental_saved_listings';

        $ids = $wpdb->get_col($wpdb->prepare(
            "SELECT listing_id FROM {$table} WHERE student_id = %d",
            $user_id
        ));

        return new \WP_REST_Response(array_map('absint', $ids), 200);
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

        // ── Phase 1: Find qualifying unit IDs based on unit/property meta. ──
        // These filters target meta on cpt_unit / cpt_property, NOT on cpt_listing,
        // so we must resolve them first.

        $unit_args = array(
            'post_type' => 'cpt_unit',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => array('relation' => 'AND'),
        );

        // Only available units.
        $unit_args['meta_query'][] = array(
            'key' => '_availability_status',
            'value' => 'available',
            'compare' => '=',
        );

        // Price range filter (rent_price is on the unit).
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

            $unit_args['meta_query'][] = $price_query;
        }

        // Gender preference (on the unit).
        $gender = $request->get_param('gender');
        if ($gender) {
            $unit_args['meta_query'][] = array(
                'key' => '_gender_preference',
                'value' => sanitize_text_field($gender),
                'compare' => '=',
            );
        }

        // Furnished status (on the unit).
        $furnished = $request->get_param('furnished');
        if ($furnished) {
            $unit_args['meta_query'][] = array(
                'key' => '_furnished_status',
                'value' => sanitize_text_field($furnished),
                'compare' => '=',
            );
        }

        $qualifying_unit_ids = get_posts($unit_args);

        // City filter — lives on the parent property, so we may need to
        // further restrict units to those whose property matches the city.
        $city = $request->get_param('city');
        if ($city && !empty($qualifying_unit_ids)) {
            $property_args = array(
                'post_type' => 'cpt_property',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => array(
                    array(
                        'key' => '_property_city',
                        'value' => sanitize_text_field($city),
                        'compare' => 'LIKE',
                    ),
                ),
            );
            $matching_property_ids = get_posts($property_args);

            if (empty($matching_property_ids)) {
                // No properties in this city — return empty results.
                $response = new \WP_REST_Response(array(), 200);
                $response->header('X-WP-Total', 0);
                $response->header('X-WP-TotalPages', 0);
                return $response;
            }

            // Keep only units that belong to a matching property.
            $filtered_unit_ids = array();
            foreach ($qualifying_unit_ids as $uid) {
                $parent_property = get_post_meta($uid, '_unit_property_id', true);
                if (!$parent_property) {
                    $parent_property = wp_get_post_parent_id($uid);
                }
                if (in_array((int) $parent_property, $matching_property_ids, true)) {
                    $filtered_unit_ids[] = $uid;
                }
            }
            $qualifying_unit_ids = $filtered_unit_ids;
        }

        // If no qualifying units exist, return empty immediately.
        if (empty($qualifying_unit_ids)) {
            $response = new \WP_REST_Response(array(), 200);
            $response->header('X-WP-Total', 0);
            $response->header('X-WP-TotalPages', 0);
            return $response;
        }

        // ── Phase 2: Query listings that reference qualifying units. ──

        $args = array(
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page,
            'meta_query' => array(
                array(
                    'key' => '_listing_unit_id',
                    'value' => $qualifying_unit_ids,
                    'compare' => 'IN',
                    'type' => 'NUMERIC',
                ),
            ),
        );

        // Room type taxonomy (on the unit, but we filter listing-side as fallback).
        $room_type = $request->get_param('room_type');
        if ($room_type) {
            // Filter units by taxonomy first, then restrict listings.
            $tax_unit_ids = get_posts(array(
                'post_type' => 'cpt_unit',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'post__in' => $qualifying_unit_ids,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'room_type',
                        'field' => 'slug',
                        'terms' => array_map('sanitize_text_field', (array) $room_type),
                    ),
                ),
            ));

            if (empty($tax_unit_ids)) {
                $response = new \WP_REST_Response(array(), 200);
                $response->header('X-WP-Total', 0);
                $response->header('X-WP-TotalPages', 0);
                return $response;
            }

            $args['meta_query'] = array(
                array(
                    'key' => '_listing_unit_id',
                    'value' => $tax_unit_ids,
                    'compare' => 'IN',
                    'type' => 'NUMERIC',
                ),
            );
        }

        $query = new \WP_Query($args);
        $listings = array();

        if ($query->have_posts()) {
            // Pre-load meta cache for performance.
            \update_postmeta_cache(\wp_list_pluck($query->posts, 'ID'));

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
            'link' => get_permalink($post->ID),
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
