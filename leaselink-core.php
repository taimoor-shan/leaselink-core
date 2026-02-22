<?php
/**
 * Plugin Name: LeaseLink Core
 * Description: Core plugin for LeaseLink housing platform, implementing custom database tables and functionalities.
 * Version: 1.0.0
 * Author: LeaseLink Team
 * Text Domain: leaselink-core
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-srp-activator.php
 */
function activate_leaselink_core()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-srp-roles.php';
	require_once plugin_dir_path(__FILE__) . 'includes/class-srp-activator.php';
	StudentRentalPlatform\SRP_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-srp-deactivator.php
 */
function deactivate_leaselink_core()
{
	require_once plugin_dir_path(__FILE__) . 'includes/class-srp-roles.php';
	require_once plugin_dir_path(__FILE__) . 'includes/class-srp-deactivator.php';
	StudentRentalPlatform\SRP_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_leaselink_core');
register_deactivation_hook(__FILE__, 'deactivate_leaselink_core');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-srp-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_leaselink_core()
{
	$plugin = StudentRentalPlatform\SRP_Core::get_instance();
	$plugin->run();
}
run_leaselink_core();
