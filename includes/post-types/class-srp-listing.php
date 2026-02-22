<?php
/**
 * Listing Post Type Registration
 *
 * @package           StudentRentalPlatform
 * @author            LeaseLink Team
 * @license           GPL-2.0+
 * @link              https://leaselink.com
 * @since             1.0.0
 */

namespace StudentRentalPlatform\PostTypes;

/**
 * Register Listing Custom Post Type.
 */
class SRP_Listing
{

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
    }

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Listings', 'Post Type General Name', 'leaselink-core'),
            'singular_name' => _x('Listing', 'Post Type Singular Name', 'leaselink-core'),
            'menu_name' => __('Listings', 'leaselink-core'),
            'name_admin_bar' => __('Listing', 'leaselink-core'),
            'archives' => __('Listing Archives', 'leaselink-core'),
            'attributes' => __('Listing Attributes', 'leaselink-core'),
            'parent_item_colon' => __('Parent Listing:', 'leaselink-core'),
            'all_items' => __('All Listings', 'leaselink-core'),
            'add_new_item' => __('Add New Listing', 'leaselink-core'),
            'add_new' => __('Add New', 'leaselink-core'),
            'new_item' => __('New Listing', 'leaselink-core'),
            'edit_item' => __('Edit Listing', 'leaselink-core'),
            'update_item' => __('Update Listing', 'leaselink-core'),
            'view_item' => __('View Listing', 'leaselink-core'),
            'view_items' => __('View Listings', 'leaselink-core'),
            'search_items' => __('Search Listing', 'leaselink-core'),
            'not_found' => __('Not found', 'leaselink-core'),
            'not_found_in_trash' => __('Not found in Trash', 'leaselink-core'),
            'featured_image' => __('Featured Image', 'leaselink-core'),
            'set_featured_image' => __('Set featured image', 'leaselink-core'),
            'remove_featured_image' => __('Remove featured image', 'leaselink-core'),
            'use_featured_image' => __('Use as featured image', 'leaselink-core'),
            'insert_into_item' => __('Insert into listing', 'leaselink-core'),
            'uploaded_to_this_item' => __('Uploaded to this listing', 'leaselink-core'),
            'items_list' => __('Listings list', 'leaselink-core'),
            'items_list_navigation' => __('Listings list navigation', 'leaselink-core'),
            'filter_items_list' => __('Filter listings list', 'leaselink-core'),
        );
        $args = array(
            'label' => __('Listing', 'leaselink-core'),
            'description' => __('Public facing representation of a unit', 'leaselink-core'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields', 'comments'), // Comments for Q&A maybe
            'taxonomies' => array(), // Inherits from Unit usually
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-megaphone',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => array('listing', 'listings'),
            'map_meta_cap' => true,
            'show_in_rest' => true,
        );
        register_post_type('cpt_listing', $args);
    }
}
