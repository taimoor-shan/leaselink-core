<?php
/**
 * Tests for SRP_Roles — Custom Roles and Capabilities.
 *
 * @package StudentRentalPlatform\Tests\Unit
 */

namespace StudentRentalPlatform\Tests\Unit;

use WP_UnitTestCase;
use StudentRentalPlatform\SRP_Roles;

/**
 * Class RolesTest
 *
 * Tests that install_roles() and remove_roles() correctly manage
 * the student and landlord roles and their capabilities.
 */
class RolesTest extends WP_UnitTestCase
{
    /**
     * Clean up before each test.
     */
    public function set_up(): void
    {
        parent::set_up();
        // Remove roles so we can test fresh installation.
        remove_role('student');
        remove_role('landlord');
    }

    /**
     * @test
     */
    public function install_roles_creates_landlord_role(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $this->assertNotNull($role, 'Landlord role should exist after install_roles().');
    }

    /**
     * @test
     */
    public function install_roles_creates_student_role(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('student');
        $this->assertNotNull($role, 'Student role should exist after install_roles().');
    }

    /**
     * @test
     */
    public function landlord_has_manage_properties(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $this->assertTrue($role->has_cap('manage_properties'), 'Landlord must have manage_properties capability.');
    }

    /**
     * @test
     */
    public function landlord_has_upload_files(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $this->assertTrue($role->has_cap('upload_files'), 'Landlord must have upload_files capability.');
    }

    /**
     * @test
     */
    public function landlord_has_all_property_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $property_caps = [
            'read_property',
            'edit_properties',
            'edit_published_properties',
            'delete_properties',
            'delete_published_properties',
            'publish_properties',
        ];

        foreach ($property_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Landlord should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function landlord_has_all_unit_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $unit_caps = [
            'read_unit',
            'edit_units',
            'edit_published_units',
            'delete_units',
            'delete_published_units',
            'publish_units',
        ];

        foreach ($unit_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Landlord should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function landlord_has_all_listing_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $listing_caps = [
            'read_listing',
            'read_listings',
            'edit_listings',
            'edit_published_listings',
            'delete_listings',
            'delete_published_listings',
            'publish_listings',
        ];

        foreach ($listing_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Landlord should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function landlord_has_platform_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('landlord');
        $platform_caps = [
            'manage_applications',
            'upload_verification_docs',
            'view_analytics',
            'send_messages',
        ];

        foreach ($platform_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Landlord should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function student_has_read_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('student');
        $read_caps = [
            'read',
            'upload_files',
            'read_listing',
            'read_listings',
            'read_property',
            'read_unit',
        ];

        foreach ($read_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Student should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function student_has_platform_capabilities(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('student');
        $platform_caps = [
            'submit_applications',
            'send_messages',
            'save_listings',
            'report_content',
        ];

        foreach ($platform_caps as $cap) {
            $this->assertTrue($role->has_cap($cap), "Student should have '{$cap}'.");
        }
    }

    /**
     * @test
     */
    public function student_cannot_manage_properties(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('student');
        $this->assertFalse(
            $role->has_cap('manage_properties'),
            'Student must NOT have manage_properties capability.'
        );
    }

    /**
     * @test
     */
    public function student_cannot_edit_listings(): void
    {
        SRP_Roles::install_roles();

        $role = get_role('student');
        $this->assertFalse(
            $role->has_cap('edit_listings'),
            'Student must NOT have edit_listings capability.'
        );
    }

    /**
     * @test
     */
    public function remove_roles_removes_both_roles(): void
    {
        SRP_Roles::install_roles();

        // Verify they exist.
        $this->assertNotNull(get_role('student'));
        $this->assertNotNull(get_role('landlord'));

        SRP_Roles::remove_roles();

        $this->assertNull(get_role('student'), 'Student role should be removed.');
        $this->assertNull(get_role('landlord'), 'Landlord role should be removed.');
    }

    /**
     * @test
     */
    public function install_roles_adds_admin_capabilities(): void
    {
        SRP_Roles::install_roles();

        $admin = get_role('administrator');
        $this->assertNotNull($admin);

        // Spot-check a few admin-specific caps.
        $this->assertTrue($admin->has_cap('moderate_listings'), 'Admin should have moderate_listings.');
        $this->assertTrue($admin->has_cap('verify_landlords'), 'Admin should have verify_landlords.');
        $this->assertTrue($admin->has_cap('edit_others_properties'), 'Admin should have edit_others_properties.');
    }

    /**
     * @test
     */
    public function install_roles_is_idempotent(): void
    {
        SRP_Roles::install_roles();
        SRP_Roles::install_roles(); // Run twice.

        $landlord = get_role('landlord');
        $student = get_role('student');

        $this->assertNotNull($landlord, 'Landlord role should still exist after double install.');
        $this->assertNotNull($student, 'Student role should still exist after double install.');
        $this->assertTrue($landlord->has_cap('manage_properties'));
    }
}
