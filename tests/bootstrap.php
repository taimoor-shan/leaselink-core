<?php
/**
 * PHPUnit Bootstrap — LeaseLink Core Plugin Tests.
 *
 * Loads the WordPress test suite and activates the plugin
 * so all tests run in a fully-initialized WordPress environment.
 *
 * @package StudentRentalPlatform\Tests
 */

// Composer autoloader.
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
    require_once $autoloader;
}

// Tell WP test suite where to find PHPUnit Polyfills.
define('WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname(__DIR__) . '/vendor/yoast/phpunit-polyfills');

// Find the WP tests directory.
$_tests_dir = getenv('WP_TESTS_DIR');

if (!$_tests_dir) {
    $_tests_dir = rtrim(sys_get_temp_dir(), '/\\') . '/wordpress-tests-lib';
}

// Check it exists.
if (!file_exists("{$_tests_dir}/includes/functions.php")) {
    echo "Could not find {$_tests_dir}/includes/functions.php.\n";
    echo "Run: bash bin/install-wp-tests.sh local_tests root root '127.0.0.1:10234' latest\n";
    exit(1);
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin for testing.
 */
function _manually_load_plugin()
{
    // Load the plugin.
    require dirname(__DIR__) . '/leaselink-core.php';
}
tests_add_filter('muplugins_loaded', '_manually_load_plugin');

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
