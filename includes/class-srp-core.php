<?php
/**
 * The core plugin class.
 *
 * @package    StudentRentalPlatform
 * @author     LeaseLink Team
 * @license    GPL-2.0+
 * @link       https://leaselink.com
 * @since      1.0.0
 */

namespace StudentRentalPlatform;

use StudentRentalPlatform\PostTypes\SRP_Property;
use StudentRentalPlatform\PostTypes\SRP_Unit;
use StudentRentalPlatform\PostTypes\SRP_Listing;
use StudentRentalPlatform\Workflows\SRP_Listing_Workflow;
use StudentRentalPlatform\Workflows\SRP_Application_Workflow;
use StudentRentalPlatform\Workflows\SRP_Verification_Workflow;
use StudentRentalPlatform\Admin\SRP_Property_Meta;
use StudentRentalPlatform\Admin\SRP_Unit_Meta;
use StudentRentalPlatform\Admin\SRP_Listing_Meta;

/**
 * The core plugin class.
 *
 * Loads dependencies, initialises post types, workflows,
 * and hooks into admin / public lifecycle.
 */
class SRP_Core
{

    /**
     * The singleton instance.
     *
     * @var SRP_Core
     */
    private static $instance;

    /**
     * Listing workflow instance.
     *
     * @var SRP_Listing_Workflow
     */
    public $listing_workflow;

    /**
     * Application workflow instance.
     *
     * @var SRP_Application_Workflow
     */
    public $application_workflow;

    /**
     * Verification workflow instance.
     *
     * @var SRP_Verification_Workflow
     */
    public $verification_workflow;

    /**
     * Cron scheduler instance.
     *
     * @var SRP_Cron
     */
    public $cron;

    /**
     * Notifications instance.
     *
     * @var SRP_Notifications
     */
    public $notifications;

    /**
     * Template loader instance.
     *
     * @var SRP_Template_Loader
     * @since 1.1.0
     */
    public $template_loader;

    /**
     * Frontend instance.
     *
     * @var SRP_Frontend
     * @since 1.1.0
     */
    public $frontend;

    /**
     * Get the singleton instance.
     *
     * @return SRP_Core
     */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Store the plugin reference. Initialization happens in run().
     */
    public function __construct()
    {
        // Intentionally empty — run() is the entry point.
    }

    /**
     * Run the plugin.
     *
     * Loads dependencies, initialises post types, workflows,
     * cron, notifications, REST API, and admin/public hooks.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->load_dependencies();
        $this->init_post_types();
        $this->init_workflows();
        $this->init_cron();
        $this->init_notifications();
        $this->init_rest_api();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        // Ensure core pages exist (runs once, then sets a flag).
        add_action('init', [$this, 'maybe_create_pages'], 20);
    }

    /**
     * Create pages if they haven't been created yet.
     * Runs on every init but short-circuits via an option flag.
     */
    public function maybe_create_pages()
    {
        $pages_version = get_option('leaselink_pages_version', '0');
        if (version_compare($pages_version, '1.1.2', '>=')) {
            return;
        }
        require_once plugin_dir_path(__FILE__) . 'class-srp-activator.php';
        SRP_Activator::activate();
        update_option('leaselink_pages_version', '1.1.2');
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies()
    {
        $path = plugin_dir_path(__FILE__);

        // Post Types.
        require_once $path . 'post-types/class-srp-property.php';
        require_once $path . 'post-types/class-srp-unit.php';
        require_once $path . 'post-types/class-srp-listing.php';

        // Workflows.
        require_once $path . 'workflows/class-srp-listing-workflow.php';
        require_once $path . 'workflows/class-srp-application-workflow.php';
        require_once $path . 'workflows/class-srp-verification-workflow.php';

        // Cron & Notifications.
        require_once $path . 'class-srp-cron.php';
        require_once $path . 'class-srp-notifications.php';

        // REST API.
        require_once $path . 'rest-api/class-srp-rest-listings.php';
        require_once $path . 'rest-api/class-srp-rest-applications.php';

        // Template Loader & Frontend.
        require_once $path . 'class-srp-template-loader.php';
        require_once $path . 'class-srp-frontend.php';
        require_once $path . 'class-srp-auth.php';

        // Admin Meta Boxes.
        $admin_path = plugin_dir_path(__DIR__) . 'admin/';
        require_once $admin_path . 'class-srp-property-meta.php';
        require_once $admin_path . 'class-srp-unit-meta.php';
        require_once $admin_path . 'class-srp-listing-meta.php';
    }

    /**
     * Initialize Custom Post Types.
     */
    private function init_post_types()
    {
        new SRP_Property();
        new SRP_Unit();
        new SRP_Listing();
    }

    /**
     * Initialize Workflow engines.
     */
    private function init_workflows()
    {
        $this->listing_workflow = new SRP_Listing_Workflow();
        $this->application_workflow = new SRP_Application_Workflow();
        $this->verification_workflow = new SRP_Verification_Workflow();
    }

    /**
     * Initialize the cron scheduler.
     *
     * @since 1.0.0
     */
    private function init_cron()
    {
        $this->cron = new SRP_Cron($this->listing_workflow, $this->application_workflow);
    }

    /**
     * Initialize email notifications.
     *
     * @since 1.0.0
     */
    private function init_notifications()
    {
        $this->notifications = new SRP_Notifications();
    }

    /**
     * Initialize REST API endpoints.
     *
     * @since 1.0.0
     */
    private function init_rest_api()
    {
        new RestApi\SRP_REST_Listings();
        new RestApi\SRP_REST_Applications($this->application_workflow);
    }

    /**
     * Register admin-area hooks.
     */
    private function define_admin_hooks()
    {
        if (is_admin()) {
            new SRP_Property_Meta();
            new SRP_Unit_Meta();
            new SRP_Listing_Meta();
        }
    }

    /**
     * Register public-facing hooks.
     */
    private function define_public_hooks()
    {
        // Listing view counter.
        add_action('template_redirect', array($this, 'track_listing_view'));

        // Template loader — overrides CPT templates.
        $this->template_loader = new SRP_Template_Loader();

        // Frontend — shortcodes, assets, dashboard protection.
        $this->frontend = new SRP_Frontend();

        // Auth — custom login/signup pages, wp-login redirects.
        new SRP_Auth();
    }

    /**
     * Increment the view count for a listing when viewed on the frontend.
     *
     * @since 1.0.0
     */
    public function track_listing_view()
    {
        if (!is_singular('cpt_listing')) {
            return;
        }

        // Don't count admin/author views.
        $post = get_queried_object();
        if (!$post || (is_user_logged_in() && absint($post->post_author) === get_current_user_id())) {
            return;
        }

        $count = absint(get_post_meta($post->ID, '_view_count', true));
        update_post_meta($post->ID, '_view_count', $count + 1);
    }
}
