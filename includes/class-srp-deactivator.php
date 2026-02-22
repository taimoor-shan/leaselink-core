<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform;

/**
 * Class SRP_Deactivator
 *
 * Handles cleanup when the plugin is deactivated.
 */
class SRP_Deactivator
{

    /**
     * Plugin deactivation callback.
     *
     * Removes custom roles/capabilities and flushes rewrite rules.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        SRP_Roles::remove_roles();
        flush_rewrite_rules();
    }
}
