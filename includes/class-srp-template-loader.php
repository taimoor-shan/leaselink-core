<?php
/**
 * Template Loader — WooCommerce-style template override system.
 *
 * Lookup order:
 *   1. yourtheme/leaselink/{template_name}
 *   2. leaselink-core/templates/{template_name}
 *
 * @package    StudentRentalPlatform
 * @since      1.1.0
 */

namespace StudentRentalPlatform;

class SRP_Template_Loader
{

    /**
     * The subdirectory in the theme to look for overrides.
     *
     * @var string
     */
    const THEME_DIR = 'leaselink';

    /**
     * Hook into WordPress template system.
     */
    public function __construct()
    {
        add_filter('template_include', [$this, 'template_include']);
    }

    /**
     * Page-slug → plugin template mapping.
     *
     * Key:   WordPress page slug.
     * Value: relative template path inside templates/.
     */
    const PAGE_MAP = [
        'landlord-dashboard' => 'dashboard/landlord-dashboard.php',
        'student-dashboard' => 'dashboard/student-dashboard.php',
        'my-properties' => 'dashboard/my-properties.php',
        'add-property' => 'dashboard/add-property.php',
        'my-applications' => 'dashboard/my-applications.php',
        'landlord-applications' => 'dashboard/landlord-applications.php',
        'saved-listings' => 'dashboard/saved-listings.php',
        'messages' => 'dashboard/messages.php',
        'profile-settings' => 'dashboard/profile-settings.php',
        'verification' => 'dashboard/verification.php',
        'search-listings' => 'shortcodes/search.php',
    ];

    /**
     * Override templates for our CPTs and dashboard pages.
     *
     * @param string $template The resolved template path.
     * @return string
     */
    public function template_include($template)
    {
        // Single listing
        if (is_singular('cpt_listing')) {
            $custom = self::locate('single-cpt_listing.php');
            if ($custom) {
                return $custom;
            }
        }

        // Listing archive
        if (is_post_type_archive('cpt_listing')) {
            $custom = self::locate('archive-cpt_listing.php');
            if ($custom) {
                return $custom;
            }
        }

        // Single property (optional)
        if (is_singular('cpt_property')) {
            $custom = self::locate('single-cpt_property.php');
            if ($custom) {
                return $custom;
            }
        }

        // Dashboard / internal pages — match by page slug.
        if (is_page()) {
            $slug = get_post_field('post_name', get_queried_object_id());
            if (isset(self::PAGE_MAP[$slug])) {
                $custom = self::locate(self::PAGE_MAP[$slug]);
                if ($custom) {
                    return $custom;
                }
            }
        }

        return $template;
    }

    /**
     * Load a template, passing $args to it.
     *
     * Equivalent of WooCommerce's wc_get_template().
     *
     * Usage:
     *   SRP_Template_Loader::get_template('components/listing-card.php', ['listing_id' => 123]);
     *
     * @param string $template_name Relative template name (e.g. 'components/badge.php').
     * @param array  $args          Variables to pass to the template.
     */
    public static function get_template($template_name, $args = [])
    {
        $located = self::locate($template_name);

        if (!$located) {
            return;
        }

        /**
         * Fires before a LeaseLink template is loaded.
         *
         * @param string $template_name Template name.
         * @param string $located       Full path to the template.
         * @param array  $args          Template arguments.
         */
        do_action('leaselink_before_template', $template_name, $located, $args);

        // Extract args into scope for the template
        if (!empty($args) && is_array($args)) {
            extract($args); // phpcs:ignore WordPress.PHP.DontExtract
        }

        include $located;

        do_action('leaselink_after_template', $template_name, $located, $args);
    }

    /**
     * Like get_template_part() but with plugin fallback.
     *
     * Usage:
     *   SRP_Template_Loader::get_template_part('components/listing', 'card', ['listing_id' => 123]);
     *
     * @param string $slug Template slug.
     * @param string $name Optional. Template variation name.
     * @param array  $args Variables to pass to the template.
     */
    public static function get_template_part($slug, $name = '', $args = [])
    {
        $template_name = $name ? "{$slug}-{$name}.php" : "{$slug}.php";
        self::get_template($template_name, $args);
    }

    /**
     * Locate a template file.
     *
     * Checks theme first, then plugin default.
     *
     * @param string $template_name Relative template name.
     * @return string|false Full path to template, or false if not found.
     */
    public static function locate($template_name)
    {
        $template = false;

        // 1. Check child theme: child-theme/leaselink/{template}
        if (get_stylesheet_directory() !== get_template_directory()) {
            $path = get_stylesheet_directory() . '/' . self::THEME_DIR . '/' . $template_name;
            if (file_exists($path)) {
                $template = $path;
            }
        }

        // 2. Check parent theme: theme/leaselink/{template}
        if (!$template) {
            $path = get_template_directory() . '/' . self::THEME_DIR . '/' . $template_name;
            if (file_exists($path)) {
                $template = $path;
            }
        }

        // 3. Fallback to plugin: leaselink-core/templates/{template}
        if (!$template) {
            $path = self::plugin_templates_path() . $template_name;
            if (file_exists($path)) {
                $template = $path;
            }
        }

        /**
         * Filter the located template path.
         *
         * @param string|false $template      The located template path, or false.
         * @param string       $template_name The template name being looked up.
         */
        return apply_filters('leaselink_locate_template', $template, $template_name);
    }

    /**
     * Get the plugin's templates directory path.
     *
     * @return string
     */
    public static function plugin_templates_path()
    {
        return plugin_dir_path(__DIR__) . 'templates/';
    }

    /**
     * Get the theme override directory name.
     *
     * @return string
     */
    public static function theme_path()
    {
        return self::THEME_DIR . '/';
    }
}
