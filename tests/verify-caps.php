<?php
/**
 * Diagnostic script — Verify Landlord Capabilities in the Database.
 *
 * Usage:
 *   1. Via WP-CLI: wp eval-file tests/verify-caps.php
 *   2. Via browser: Navigate to the plugin directory and run from WP context
 *
 * This script reads the stored role capabilities from wp_options and compares
 * them against what class-srp-roles.php defines. If stale, it can
 * optionally reset them.
 *
 * @package StudentRentalPlatform
 */

// Load WordPress if not already loaded.
if (!defined('ABSPATH')) {
    // Walk up to find wp-load.php
    $wp_load = dirname(__DIR__, 4) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    } else {
        die("Cannot find wp-load.php. Run via: wp eval-file tests/verify-caps.php\n");
    }
}

echo "=== LeaseLink Capability Verification ===\n\n";

// ─── 1. Read stored roles from DB ───
$roles_option = get_option('wp_user_roles');
if (!$roles_option) {
    // Some installs use the table prefix
    global $wpdb;
    $roles_option = get_option($wpdb->prefix . 'user_roles');
}

if (!$roles_option) {
    echo "❌ ERROR: Could not read wp_user_roles from the database.\n";
    exit(1);
}

// ─── 2. Expected capabilities ───
$expected_landlord_caps = [
    'read' => true,
    'upload_files' => true,
    'manage_properties' => true,
    'read_property' => true,
    'edit_properties' => true,
    'edit_published_properties' => true,
    'delete_properties' => true,
    'delete_published_properties' => true,
    'publish_properties' => true,
    'read_unit' => true,
    'edit_units' => true,
    'edit_published_units' => true,
    'delete_units' => true,
    'delete_published_units' => true,
    'publish_units' => true,
    'read_listing' => true,
    'read_listings' => true,
    'edit_listings' => true,
    'edit_published_listings' => true,
    'delete_listings' => true,
    'delete_published_listings' => true,
    'publish_listings' => true,
    'manage_applications' => true,
    'upload_verification_docs' => true,
    'view_analytics' => true,
    'send_messages' => true,
];

$expected_student_caps = [
    'read' => true,
    'upload_files' => true,
    'read_listing' => true,
    'read_listings' => true,
    'read_property' => true,
    'read_unit' => true,
    'submit_applications' => true,
    'send_messages' => true,
    'save_listings' => true,
    'report_content' => true,
];

// ─── 3. Check Landlord Role ───
echo "── Landlord Role ──\n";
$stale = false;

if (!isset($roles_option['landlord'])) {
    echo "❌ Landlord role NOT FOUND in database!\n";
    $stale = true;
} else {
    $stored_caps = $roles_option['landlord']['capabilities'] ?? [];
    echo "Stored capabilities: " . count($stored_caps) . "\n";
    echo "Expected capabilities: " . count($expected_landlord_caps) . "\n\n";

    // Check for missing caps
    $missing = [];
    foreach ($expected_landlord_caps as $cap => $val) {
        if (!isset($stored_caps[$cap]) || $stored_caps[$cap] !== $val) {
            $missing[] = $cap;
        }
    }

    if (empty($missing)) {
        echo "✅ All expected landlord capabilities are present.\n";
    } else {
        echo "❌ Missing/incorrect capabilities:\n";
        foreach ($missing as $cap) {
            echo "   - {$cap}\n";
        }
        $stale = true;
    }

    // Check for extra caps (informational)
    $extra = array_diff_key($stored_caps, $expected_landlord_caps);
    if (!empty($extra)) {
        echo "\nℹ️  Extra capabilities (not in expected list):\n";
        foreach ($extra as $cap => $val) {
            echo "   + {$cap} = " . ($val ? 'true' : 'false') . "\n";
        }
    }
}

// ─── 4. Check Student Role ───
echo "\n── Student Role ──\n";
if (!isset($roles_option['student'])) {
    echo "❌ Student role NOT FOUND in database!\n";
    $stale = true;
} else {
    $stored_caps = $roles_option['student']['capabilities'] ?? [];
    $missing = [];
    foreach ($expected_student_caps as $cap => $val) {
        if (!isset($stored_caps[$cap]) || $stored_caps[$cap] !== $val) {
            $missing[] = $cap;
        }
    }

    if (empty($missing)) {
        echo "✅ All expected student capabilities are present.\n";
    } else {
        echo "❌ Missing/incorrect capabilities:\n";
        foreach ($missing as $cap) {
            echo "   - {$cap}\n";
        }
        $stale = true;
    }
}

// ─── 5. Reset if stale ───
echo "\n── Summary ──\n";
if ($stale) {
    echo "⚠️  Stale capabilities detected. Resetting roles...\n";
    require_once dirname(__DIR__) . '/includes/class-srp-roles.php';
    \StudentRentalPlatform\SRP_Roles::install_roles();
    echo "✅ Roles have been reset via SRP_Roles::install_roles().\n";
    echo "   Run this script again to confirm.\n";
} else {
    echo "✅ All capabilities are up-to-date. No action needed.\n";
}

echo "\nDone.\n";
