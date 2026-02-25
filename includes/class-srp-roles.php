<?php
/**
 * Custom Roles and Capabilities.
 *
 * Registers the Student and Landlord roles, and adds
 * custom capabilities to all three platform roles.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform;

/**
 * Class SRP_Roles
 *
 * Manages custom WordPress roles and capabilities for the platform.
 */
class SRP_Roles
{

    /**
     * Install custom roles and capabilities.
     *
     * Called during plugin activation.
     *
     * @since 1.0.0
     */
    public static function install_roles()
    {
        // Remove existing roles first to clear stale capabilities.
        // add_role() silently does nothing if the role already exists,
        // so we must remove first to ensure updated caps are applied.
        remove_role('student');
        remove_role('landlord');

        self::add_student_role();
        self::add_landlord_role();
        self::add_admin_capabilities();
    }

    /**
     * Remove custom roles and capabilities.
     *
     * Called during plugin deactivation.
     *
     * @since 1.0.0
     */
    public static function remove_roles()
    {
        remove_role('student');
        remove_role('landlord');
        self::remove_admin_capabilities();
    }

    /**
     * Register the Student role.
     *
     * @since 1.0.0
     */
    private static function add_student_role()
    {
        add_role(
            'student',
            __('Student', 'leaselink-core'),
            array(
                // Core WordPress.
                'read' => true,
                'upload_files' => true,

                // Listings (read-only).
                'read_listing' => true,
                'read_listings' => true,

                // Properties (read-only).
                'read_property' => true,

                // Units (read-only).
                'read_unit' => true,

                // Platform-specific.
                'submit_applications' => true,
                'send_messages' => true,
                'save_listings' => true,
                'report_content' => true,
            )
        );
    }

    /**
     * Register the Landlord role.
     *
     * @since 1.0.0
     */
    private static function add_landlord_role()
    {
        add_role(
            'landlord',
            __('Landlord', 'leaselink-core'),
            array(
                // Core WordPress.
                'read' => true,
                'upload_files' => true,

                // Properties.
                'manage_properties' => true,
                'read_property' => true,
                'edit_properties' => true,
                'edit_published_properties' => true,
                'delete_properties' => true,
                'delete_published_properties' => true,
                'publish_properties' => true,

                // Units.
                'read_unit' => true,
                'edit_units' => true,
                'edit_published_units' => true,
                'delete_units' => true,
                'delete_published_units' => true,
                'publish_units' => true,

                // Listings.
                'read_listing' => true,
                'read_listings' => true,
                'edit_listings' => true,
                'edit_published_listings' => true,
                'delete_listings' => true,
                'delete_published_listings' => true,
                'publish_listings' => true,

                // Platform-specific.
                'manage_applications' => true,
                'upload_verification_docs' => true,
                'view_analytics' => true,
                'send_messages' => true,
            )
        );
    }

    /**
     * Add platform-specific capabilities to the Administrator role.
     *
     * @since 1.0.0
     */
    private static function add_admin_capabilities()
    {
        $admin = get_role('administrator');
        if (!$admin) {
            return;
        }

        // Property capabilities.
        $admin->add_cap('read_property');
        $admin->add_cap('edit_properties');
        $admin->add_cap('edit_others_properties');
        $admin->add_cap('edit_published_properties');
        $admin->add_cap('delete_properties');
        $admin->add_cap('delete_others_properties');
        $admin->add_cap('delete_published_properties');
        $admin->add_cap('publish_properties');

        // Unit capabilities.
        $admin->add_cap('read_unit');
        $admin->add_cap('edit_units');
        $admin->add_cap('edit_others_units');
        $admin->add_cap('edit_published_units');
        $admin->add_cap('delete_units');
        $admin->add_cap('delete_others_units');
        $admin->add_cap('delete_published_units');
        $admin->add_cap('publish_units');

        // Listing capabilities.
        $admin->add_cap('read_listing');
        $admin->add_cap('read_listings');
        $admin->add_cap('edit_listings');
        $admin->add_cap('edit_others_listings');
        $admin->add_cap('edit_published_listings');
        $admin->add_cap('delete_listings');
        $admin->add_cap('delete_others_listings');
        $admin->add_cap('delete_published_listings');
        $admin->add_cap('publish_listings');

        // Platform moderation capabilities.
        $admin->add_cap('moderate_listings');
        $admin->add_cap('verify_landlords');
        $admin->add_cap('suspend_users');
        $admin->add_cap('manage_reports');
        $admin->add_cap('manage_subscriptions');
        $admin->add_cap('view_all_analytics');
        $admin->add_cap('manage_applications');
    }

    /**
     * Remove platform-specific capabilities from the Administrator role.
     *
     * @since 1.0.0
     */
    private static function remove_admin_capabilities()
    {
        $admin = get_role('administrator');
        if (!$admin) {
            return;
        }

        $caps = array(
            'read_property',
            'edit_properties',
            'edit_others_properties',
            'edit_published_properties',
            'delete_properties',
            'delete_others_properties',
            'delete_published_properties',
            'publish_properties',
            'read_unit',
            'edit_units',
            'edit_others_units',
            'edit_published_units',
            'delete_units',
            'delete_others_units',
            'delete_published_units',
            'publish_units',
            'read_listing',
            'read_listings',
            'edit_listings',
            'edit_others_listings',
            'edit_published_listings',
            'delete_listings',
            'delete_others_listings',
            'delete_published_listings',
            'publish_listings',
            'moderate_listings',
            'verify_landlords',
            'suspend_users',
            'manage_reports',
            'manage_subscriptions',
            'view_all_analytics',
            'manage_applications',
        );

        foreach ($caps as $cap) {
            $admin->remove_cap($cap);
        }
    }
}
