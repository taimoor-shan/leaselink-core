<?php
/**
 * Unit Post Type Registration
 *
 * @package           StudentRentalPlatform
 * @author            LeaseLink Team
 * @license           GPL-2.0+
 * @link              https://leaselink.com
 * @since             1.0.0
 */

namespace StudentRentalPlatform\PostTypes;

/**
 * Register Unit Custom Post Type and Taxonomies.
 */
class SRP_Unit
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
            'name' => _x('Units', 'Post Type General Name', 'leaselink-core'),
            'singular_name' => _x('Unit', 'Post Type Singular Name', 'leaselink-core'),
            'menu_name' => __('Units', 'leaselink-core'),
            'name_admin_bar' => __('Unit', 'leaselink-core'),
            'archives' => __('Unit Archives', 'leaselink-core'),
            'attributes' => __('Unit Attributes', 'leaselink-core'),
            'parent_item_colon' => __('Parent Unit:', 'leaselink-core'),
            'all_items' => __('All Units', 'leaselink-core'),
            'add_new_item' => __('Add New Unit', 'leaselink-core'),
            'add_new' => __('Add New', 'leaselink-core'),
            'new_item' => __('New Unit', 'leaselink-core'),
            'edit_item' => __('Edit Unit', 'leaselink-core'),
            'update_item' => __('Update Unit', 'leaselink-core'),
            'view_item' => __('View Unit', 'leaselink-core'),
            'view_items' => __('View Units', 'leaselink-core'),
            'search_items' => __('Search Unit', 'leaselink-core'),
            'not_found' => __('Not found', 'leaselink-core'),
            'not_found_in_trash' => __('Not found in Trash', 'leaselink-core'),
            'featured_image' => __('Featured Image', 'leaselink-core'),
            'set_featured_image' => __('Set featured image', 'leaselink-core'),
            'remove_featured_image' => __('Remove featured image', 'leaselink-core'),
            'use_featured_image' => __('Use as featured image', 'leaselink-core'),
            'insert_into_item' => __('Insert into unit', 'leaselink-core'),
            'uploaded_to_this_item' => __('Uploaded to this unit', 'leaselink-core'),
            'items_list' => __('Units list', 'leaselink-core'),
            'items_list_navigation' => __('Units list navigation', 'leaselink-core'),
            'filter_items_list' => __('Filter units list', 'leaselink-core'),
        );
        $args = array(
            'label' => __('Unit', 'leaselink-core'),
            'description' => __('Individual rentable space within a property', 'leaselink-core'),
            'labels' => $labels,
            'supports' => array('title', 'editor', 'author', 'custom-fields', 'page-attributes'), // page-attributes for hierarchy/parent
            'taxonomies' => array('room_type', 'amenity'),
            'hierarchical' => true, // Can have parents (Properties - though often handle via meta)
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => true, // Maybe submenu of Properties?
            'menu_position' => 5,
            'menu_icon' => 'dashicons-layout',
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => true,
            'can_export' => true,
            'has_archive' => true,
            'exclude_from_search' => false,
            'publicly_queryable' => true,
            'capability_type' => array('unit', 'units'),
            'map_meta_cap' => true,
            'show_in_rest' => true,
        );
        register_post_type('cpt_unit', $args);
    }

    /**
     * Register the taxonomies.
     *
     * @since    1.0.0
     */
    public function register_taxonomies()
    {
        // Room Type
        $labels_room = array(
            'name' => _x('Room Types', 'Taxonomy General Name', 'leaselink-core'),
            'singular_name' => _x('Room Type', 'Taxonomy Singular Name', 'leaselink-core'),
            'menu_name' => __('Room Type', 'leaselink-core'),
            'all_items' => __('All Room Types', 'leaselink-core'),
            'parent_item' => __('Parent Room Type', 'leaselink-core'),
            'parent_item_colon' => __('Parent Room Type:', 'leaselink-core'),
            'new_item_name' => __('New Room Type Name', 'leaselink-core'),
            'add_new_item' => __('Add New Room Type', 'leaselink-core'),
            'edit_item' => __('Edit Room Type', 'leaselink-core'),
            'update_item' => __('Update Room Type', 'leaselink-core'),
            'view_item' => __('View Room Type', 'leaselink-core'),
            'separate_items_with_commas' => __('Separate room types with commas', 'leaselink-core'),
            'add_or_remove_items' => __('Add or remove room types', 'leaselink-core'),
            'choose_from_most_used' => __('Choose from the most used', 'leaselink-core'),
            'popular_items' => __('Popular Room Types', 'leaselink-core'),
            'search_items' => __('Search Room Types', 'leaselink-core'),
            'not_found' => __('Not Found', 'leaselink-core'),
            'no_terms' => __('No room types', 'leaselink-core'),
            'items_list' => __('Room types list', 'leaselink-core'),
            'items_list_navigation' => __('Room types list navigation', 'leaselink-core'),
        );
        $args_room = array(
            'labels' => $labels_room,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
        );
        register_taxonomy('room_type', array('cpt_unit'), $args_room);

        // Amenities
        $labels_amenity = array(
            'name' => _x('Amenities', 'Taxonomy General Name', 'leaselink-core'),
            'singular_name' => _x('Amenity', 'Taxonomy Singular Name', 'leaselink-core'),
            'menu_name' => __('Amenities', 'leaselink-core'),
            'all_items' => __('All Amenities', 'leaselink-core'),
            'parent_item' => __('Parent Amenity', 'leaselink-core'),
            'parent_item_colon' => __('Parent Amenity:', 'leaselink-core'),
            'new_item_name' => __('New Amenity Name', 'leaselink-core'),
            'add_new_item' => __('Add New Amenity', 'leaselink-core'),
            'edit_item' => __('Edit Amenity', 'leaselink-core'),
            'update_item' => __('Update Amenity', 'leaselink-core'),
            'view_item' => __('View Amenity', 'leaselink-core'),
            'separate_items_with_commas' => __('Separate amenities with commas', 'leaselink-core'),
            'add_or_remove_items' => __('Add or remove amenities', 'leaselink-core'),
            'choose_from_most_used' => __('Choose from the most used', 'leaselink-core'),
            'popular_items' => __('Popular Amenities', 'leaselink-core'),
            'search_items' => __('Search Amenities', 'leaselink-core'),
            'not_found' => __('Not Found', 'leaselink-core'),
            'no_terms' => __('No amenities', 'leaselink-core'),
            'items_list' => __('Amenities list', 'leaselink-core'),
            'items_list_navigation' => __('Amenities list navigation', 'leaselink-core'),
        );
        $args_amenity = array(
            'labels' => $labels_amenity,
            'hierarchical' => false, // Checkbox style usually, but 'amenity' often hierarchical for categories
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
        );
        register_taxonomy('amenity', array('cpt_unit'), $args_amenity);
    }
}
