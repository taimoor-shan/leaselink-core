<?php
/**
 * Frontend — Public-facing hooks, shortcodes, asset enqueue, and dashboard protection.
 *
 * Handles all presentation logic that was previously in the theme.
 * Any theme can work with this plugin — the theme just provides styling overrides.
 *
 * @package    StudentRentalPlatform
 * @since      1.1.0
 */

namespace StudentRentalPlatform;

class SRP_Frontend
{

    /**
     * Initialize public hooks.
     */
    public function __construct()
    {
        // Enqueue plugin assets.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // Register shortcodes.
        add_shortcode('leaselink_search', [$this, 'shortcode_search']);
        add_shortcode('leaselink_listing', [$this, 'shortcode_listing']);
        add_shortcode('leaselink_student_dashboard', [$this, 'shortcode_student_dashboard']);
        add_shortcode('leaselink_my_applications', [$this, 'shortcode_my_applications']);
        add_shortcode('leaselink_saved_listings', [$this, 'shortcode_saved_listings']);
        add_shortcode('leaselink_landlord_dashboard', [$this, 'shortcode_landlord_dashboard']);
        add_shortcode('leaselink_my_properties', [$this, 'shortcode_my_properties']);
        add_shortcode('leaselink_add_property', [$this, 'shortcode_add_property']);
        add_shortcode('leaselink_landlord_applications', [$this, 'shortcode_landlord_applications']);

        // Role-based body classes.
        add_filter('body_class', [$this, 'body_classes']);

        // Login redirect.
        add_filter('login_redirect', [$this, 'login_redirect'], 10, 3);

        // Dashboard page protection.
        add_action('template_redirect', [$this, 'protect_dashboard_pages']);
    }

    /**
     * Enqueue plugin's own CSS and JS.
     * These are minimal styles that work with ANY theme.
     * Themes override by providing their own leaselink/ templates with theme-specific styles.
     */
    public function enqueue_assets()
    {
        $plugin_url = plugin_dir_url(__DIR__);
        $version = '1.1.0';

        // Core plugin styles — always loaded.
        wp_enqueue_style(
            'leaselink-core',
            $plugin_url . 'assets/css/leaselink.css',
            [],
            $version
        );

        // HTMX — loaded everywhere (lightweight).
        wp_enqueue_script(
            'htmx',
            'https://unpkg.com/htmx.org@2.0.4',
            [],
            '2.0.4',
            true
        );

        // Alpine.js — if not already enqueued by the theme.
        if (!wp_script_is('alpine', 'enqueued') && !wp_script_is('alpinejs', 'enqueued')) {
            wp_enqueue_script(
                'alpinejs',
                'https://unpkg.com/alpinejs@3.14.9/dist/cdn.min.js',
                [],
                '3.14.9',
                ['in_footer' => true, 'strategy' => 'defer']
            );
        }

        // Leaflet + Fancybox — only on single listings.
        if (is_singular('cpt_listing')) {
            wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4');
            wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true);
            wp_enqueue_style('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css', [], '5.0');
            wp_enqueue_script('fancybox', 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js', [], '5.0', true);
        }

        // Plugin frontend JS — search, dashboard, application forms.
        wp_enqueue_script(
            'leaselink-frontend',
            $plugin_url . 'assets/js/leaselink-frontend.js',
            ['alpinejs'],
            $version,
            true
        );

        // Pass data to JS.
        wp_localize_script('leaselink-frontend', 'leaselinkData', [
            'restUrl' => esc_url_raw(rest_url('rental/v1/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'homeUrl' => esc_url_raw(home_url('/')),
            'isLoggedIn' => is_user_logged_in(),
            'userRole' => is_user_logged_in() ? wp_get_current_user()->roles[0] ?? '' : '',
        ]);
    }

    /**
     * Add role-based body classes.
     *
     * @param array $classes Existing body classes.
     * @return array
     */
    public function body_classes($classes)
    {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            if (in_array('student', $user->roles)) {
                $classes[] = 'role-student';
            } elseif (in_array('landlord', $user->roles)) {
                $classes[] = 'role-landlord';
            } elseif (in_array('administrator', $user->roles)) {
                $classes[] = 'role-admin';
            }
            $classes[] = 'is-logged-in';
        } else {
            $classes[] = 'is-guest';
        }
        $classes[] = 'leaselink-active';
        return $classes;
    }

    /**
     * Redirect users to role-based dashboard after login.
     *
     * @param string   $redirect_to The redirect URL.
     * @param string   $request     The requested redirect URL.
     * @param \WP_User $user        The user object.
     * @return string
     */
    public function login_redirect($redirect_to, $request, $user)
    {
        if (isset($user->roles) && is_array($user->roles)) {
            if (in_array('landlord', $user->roles)) {
                return home_url('/landlord-dashboard/');
            } elseif (in_array('student', $user->roles)) {
                return home_url('/student-dashboard/');
            }
        }
        return $redirect_to;
    }

    /**
     * Protect dashboard pages — redirect guests to login.
     */
    public function protect_dashboard_pages()
    {
        if (is_user_logged_in()) {
            return;
        }

        $protected_slugs = apply_filters('leaselink_protected_slugs', [
            'student-dashboard',
            'my-applications',
            'saved-listings',
            'messages',
            'profile-settings',
            'landlord-dashboard',
            'my-properties',
            'add-property',
            'landlord-applications',
            'verification',
        ]);

        $current_slug = get_post_field('post_name', get_queried_object_id());
        if (in_array($current_slug, $protected_slugs)) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }

    /**
     * Get the appropriate dashboard URL for the current user role.
     *
     * @return string
     */
    public static function get_dashboard_url()
    {
        if (!is_user_logged_in()) {
            return wp_login_url();
        }

        $user = wp_get_current_user();
        if (in_array('landlord', $user->roles)) {
            return home_url('/landlord-dashboard/');
        } elseif (in_array('student', $user->roles)) {
            return home_url('/student-dashboard/');
        }
        return admin_url();
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Helper: load a template for a shortcode, capturing output.
     *
     * @param string $template Template name.
     * @param array  $args     Arguments.
     * @return string
     */
    private function render_template($template, $args = [])
    {
        ob_start();
        SRP_Template_Loader::get_template($template, $args);
        return ob_get_clean();
    }

    /**
     * [leaselink_search] — Listing search with filters.
     */
    public function shortcode_search($atts)
    {
        $atts = shortcode_atts([
            'per_page' => 12,
            'city' => '',
        ], $atts, 'leaselink_search');

        return $this->render_template('shortcodes/search.php', $atts);
    }

    /**
     * [leaselink_listing id="123"] — Single listing embed.
     */
    public function shortcode_listing($atts)
    {
        $atts = shortcode_atts(['id' => 0], $atts, 'leaselink_listing');
        if (!$atts['id']) {
            return '';
        }
        return $this->render_template('components/listing-card.php', ['listing_id' => $atts['id']]);
    }

    /**
     * [leaselink_student_dashboard] — Student dashboard.
     */
    public function shortcode_student_dashboard()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/student-dashboard.php');
    }

    /**
     * [leaselink_my_applications] — Student applications list.
     */
    public function shortcode_my_applications()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your applications.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/my-applications.php');
    }

    /**
     * [leaselink_saved_listings] — Saved listings.
     */
    public function shortcode_saved_listings()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view saved listings.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/saved-listings.php');
    }

    /**
     * [leaselink_landlord_dashboard] — Landlord dashboard.
     */
    public function shortcode_landlord_dashboard()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your dashboard.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/landlord-dashboard.php');
    }

    /**
     * [leaselink_my_properties] — Landlord properties list.
     */
    public function shortcode_my_properties()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view your properties.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/my-properties.php');
    }

    /**
     * [leaselink_add_property] — Add property wizard.
     */
    public function shortcode_add_property()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to add a property.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/add-property.php');
    }

    /**
     * [leaselink_landlord_applications] — Landlord applications manager.
     */
    public function shortcode_landlord_applications()
    {
        if (!is_user_logged_in()) {
            return '<p>' . __('Please log in to view applications.', 'leaselink') . '</p>';
        }
        return $this->render_template('dashboard/landlord-applications.php');
    }
}
