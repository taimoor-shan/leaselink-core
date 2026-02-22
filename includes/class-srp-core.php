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
     * Define the core functionality of the plugin.
     */
    public function __construct()
    {
        $this->load_dependencies();
        $this->init_post_types();
        $this->init_workflows();
        $this->define_admin_hooks();
        $this->define_public_hooks();
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
        // Public hooks will be added in Phase 4+.
    }

    /**
     * Run the plugin.
     */
    public function run()
    {
        // Loader execution point.
    }
}
