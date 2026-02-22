<?php
/**
 * Property Post Type Registration
 *
 * @package           StudentRentalPlatform
 * @author            LeaseLink Team
 * @license           GPL-2.0+
 * @link              https://leaselink.com
 * @since             1.0.0
 */

namespace StudentRentalPlatform\PostTypes;

/**
 * Register Property Custom Post Type and Taxonomies.
 */
class SRP_Property
{

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
    }

    /**
     * Register the custom post type.
     *
     * @since    1.0.0
     */
    public function register_post_type()
    {
        $labels = array(
            'name' => _x('Properties', 'Post Type General Name', 'leaselink-core'),
            'singular_name' => _x('Property', 'Post Type Singular Name', 'leaselink-core'),
            'menu_name' => __('Properties', 'leaselink-core'),
            'name_admin_bar' => __('Property', 'leaselink-core'),
            'archives' => __('Property Archives', 'leaselink-core'),
            'attributes' => __('Property Attributes', 'leaselink-core'),
            'parent_item_colon' => __('Parent Property:', 'leaselink-core'),
            'all_items' => __('All Properties', 'leaselink-core'),
            'add_new_item' => __('Add New Property', 'leaselink-core'),
            'add_new' => __('Add New', 'leaselink-core'),
            'new_item' => __('New Property', 'leaselink-core'),
            'edit_item' => __('Edit Property', 'leaselink-core'),
            'update_item' => __('Update Property', 'leaselink-core'),
            'view_item' => __('View Property', 'leaselink-core'),
            'view_items' => __('View Properties', 'leaselink-core'),
            'search_items' => __('Search Property', 'leaselink-core'),
            'not_found' => __('Not found', 'leaselink-core'),
            'not_found_in_trash' => __('Not found in Trash', 'leaselink-core'),
            'featured_image' => __('Featured Image', 'leaselink-core'),
            'set_featured_image' => __('Set featured image', 'leaselink-core'),
            'remove_featured_image' => __('Remove featured image', 'leaselink-core'),
            'use_featured_image' => __('Use as featured image', 'leaselink-core'),
            'insert_into_item' => __('Insert into property', 'leaselink-core'),
            'uploaded_to_this_item' => __('Uploaded to this property', 'leaselink-core'),
            'items_list' => __('Properties list', 'leaselink-core'),
            'items_list_navigation' => __('Properties list navigation', 'leaselink-core'),
            'filter_items_list' => __('Filter properties list', 'leaselink-core'),
        );
        $args = array(
            'label' => __('Property', 'leaselink-core'),
            'description' => __('Physical building or residence', 'leaselink-core'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'author', 'thumbnail', 'custom-fields'), // ID is inherent
            'taxonomies' => array('property_type'),
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 5,
            'menu_icon' => 'dashicons-building',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => array('property', 'properties'),
            'map_meta_cap' => true,
            'show_in_rest' => true,
        );
        register_post_type('cpt_property', $args);
    }

    /**
     * Register the taxonomies.
     *
     * @since    1.0.0
     */
    public function register_taxonomies()
    {
        // Property Type
        $labels = array(
            'name' => _x('Property Types', 'Taxonomy General Name', 'leaselink-core'),
            'singular_name' => _x('Property Type', 'Taxonomy Singular Name', 'leaselink-core'),
            'menu_name' => __('Property Type', 'leaselink-core'),
            'all_items' => __('All Property Types', 'leaselink-core'),
            'parent_item' => __('Parent Property Type', 'leaselink-core'),
            'parent_item_colon' => __('Parent Property Type:', 'leaselink-core'),
            'new_item_name' => __('New Property Type Name', 'leaselink-core'),
            'add_new_item' => __('Add New Property Type', 'leaselink-core'),
            'edit_item' => __('Edit Property Type', 'leaselink-core'),
            'update_item' => __('Update Property Type', 'leaselink-core'),
            'view_item' => __('View Property Type', 'leaselink-core'),
            'separate_items_with_commas' => __('Separate property types with commas', 'leaselink-core'),
            'add_or_remove_items' => __('Add or remove property types', 'leaselink-core'),
            'choose_from_most_used' => __('Choose from the most used', 'leaselink-core'),
            'popular_items' => __('Popular Property Types', 'leaselink-core'),
            'search_items' => __('Search Property Types', 'leaselink-core'),
            'not_found' => __('Not Found', 'leaselink-core'),
            'no_terms' => __('No property types', 'leaselink-core'),
            'items_list' => __('Property types list', 'leaselink-core'),
            'items_list_navigation' => __('Property types list navigation', 'leaselink-core'),
        );
        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
        );
        register_taxonomy('property_type', array('cpt_property'), $args);
    }
}
