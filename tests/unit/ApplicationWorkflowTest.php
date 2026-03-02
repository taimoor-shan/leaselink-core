<?php
/**
 * Tests for SRP_Application_Workflow — Application Lifecycle.
 *
 * @package StudentRentalPlatform\Tests\Unit
 */

namespace StudentRentalPlatform\Tests\Unit;

use WP_UnitTestCase;
use StudentRentalPlatform\Workflows\SRP_Application_Workflow;

/**
 * Class ApplicationWorkflowTest
 *
 * Tests application submission, acceptance, rejection, and withdrawal
 * per REQUIREMENTS §3.2 and §12.1.
 */
class ApplicationWorkflowTest extends WP_UnitTestCase
{
    private SRP_Application_Workflow $workflow;
    private int $landlord_id;
    private int $student_id;

    public function set_up(): void
    {
        parent::set_up();
        global $wpdb;

        $this->workflow = new SRP_Application_Workflow();
        $this->landlord_id = self::factory()->user->create(['role' => 'landlord']);
        $this->student_id = self::factory()->user->create(['role' => 'student']);

        // Ensure the applications table exists.
        $table = $wpdb->prefix . 'rental_applications';
        $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        if (!$exists) {
            $this->markTestSkipped("Applications table '{$table}' does not exist. Run plugin activation first.");
        }
    }

    public function tear_down(): void
    {
        global $wpdb;
        $table = $wpdb->prefix . 'rental_applications';
        $wpdb->query("TRUNCATE TABLE {$table}");
        parent::tear_down();
    }

    /**
     * Helper: Create a published listing with a property and unit.
     */
    private function create_test_listing(int $owner_id): array
    {
        $property_id = self::factory()->post->create([
            'post_type' => 'cpt_property',
            'post_status' => 'publish',
            'post_author' => $owner_id,
        ]);

        $unit_id = self::factory()->post->create([
            'post_type' => 'cpt_unit',
            'post_status' => 'publish',
            'post_author' => $owner_id,
            'post_parent' => $property_id,
        ]);
        update_post_meta($unit_id, '_parent_property_id', $property_id);
        update_post_meta($unit_id, '_availability_status', 'available');

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'post_author' => $owner_id,
        ]);
        update_post_meta($listing_id, '_listing_unit_id', $unit_id);
        update_post_meta($listing_id, '_listing_property_id', $property_id);

        return compact('property_id', 'unit_id', 'listing_id');
    }

    /**
     * Helper: Default application data with required fields.
     */
    private function default_app_data(array $overrides = []): array
    {
        return array_merge([
            'move_in_date' => '2026-04-01',
            'lease_duration' => 6,
            'message' => 'I am interested in this unit.',
        ], $overrides);
    }

    // ─── Transition Validation Tests ───

    /** @test */
    public function submitted_to_under_review_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('submitted', 'under_review')
        );
    }

    /** @test */
    public function submitted_to_accepted_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('submitted', 'accepted')
        );
    }

    /** @test */
    public function submitted_to_rejected_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('submitted', 'rejected')
        );
    }

    /** @test */
    public function submitted_to_withdrawn_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('submitted', 'withdrawn')
        );
    }

    /** @test */
    public function accepted_to_submitted_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('accepted', 'submitted'),
            'Cannot go back from accepted to submitted.'
        );
    }

    /** @test */
    public function rejected_to_accepted_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('rejected', 'accepted'),
            'Cannot accept a rejected application.'
        );
    }

    // ─── Submission Tests ───

    /** @test */
    public function student_can_submit_application(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);

        $result = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($result)) {
            $this->fail('Application submission failed: ' . $result->get_error_message());
        }

        $this->assertIsInt($result, 'submit() should return an application ID.');
        $this->assertGreaterThan(0, $result);
    }

    /** @test */
    public function student_cannot_apply_to_unpublished_listing(): void
    {
        wp_set_current_user($this->student_id);

        $property_id = self::factory()->post->create([
            'post_type' => 'cpt_property',
            'post_status' => 'publish',
            'post_author' => $this->landlord_id,
        ]);
        $unit_id = self::factory()->post->create([
            'post_type' => 'cpt_unit',
            'post_status' => 'publish',
            'post_author' => $this->landlord_id,
            'post_parent' => $property_id,
        ]);
        update_post_meta($unit_id, '_parent_property_id', $property_id);
        update_post_meta($unit_id, '_availability_status', 'available');

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'draft', // Not published!
            'post_author' => $this->landlord_id,
        ]);
        update_post_meta($listing_id, '_listing_unit_id', $unit_id);

        $result = $this->workflow->submit(
            $this->student_id,
            $unit_id,
            $this->default_app_data()
        );

        $this->assertWPError($result, 'Should not be able to apply to unpublished listing.');
    }

    /** @test */
    public function submit_requires_move_in_date(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);

        $result = $this->workflow->submit($this->student_id, $listing['unit_id'], [
            'lease_duration' => 6,
            // No move_in_date!
        ]);

        $this->assertWPError($result, 'Should fail without move_in_date.');
    }

    /** @test */
    public function submit_requires_positive_lease_duration(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);

        $result = $this->workflow->submit($this->student_id, $listing['unit_id'], [
            'move_in_date' => '2026-04-01',
            // No lease_duration!
        ]);

        $this->assertWPError($result, 'Should fail without lease_duration.');
    }

    // ─── Acceptance Tests ───

    /** @test */
    public function accepting_application_books_unit(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);
        $app_id = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($app_id)) {
            $this->fail('Setup failed: ' . $app_id->get_error_message());
        }

        wp_set_current_user($this->landlord_id);
        $result = $this->workflow->accept($app_id, $this->landlord_id);

        if (is_wp_error($result)) {
            $this->fail('accept() failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);

        $app = $this->workflow->get_application($app_id);
        $this->assertEquals('accepted', $app->application_status);

        $unit_status = get_post_meta($listing['unit_id'], '_availability_status', true);
        $this->assertEquals('booked', $unit_status);
    }

    // ─── Rejection Tests ───

    /** @test */
    public function landlord_can_reject_application(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);
        $app_id = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($app_id)) {
            $this->fail('Setup failed: ' . $app_id->get_error_message());
        }

        wp_set_current_user($this->landlord_id);
        $result = $this->workflow->reject($app_id, $this->landlord_id, 'Not a good fit.');

        if (is_wp_error($result)) {
            $this->fail('reject() failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);

        $app = $this->workflow->get_application($app_id);
        $this->assertEquals('rejected', $app->application_status);
    }

    // ─── Withdrawal Tests ───

    /** @test */
    public function student_can_withdraw_application(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);
        $app_id = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($app_id)) {
            $this->fail('Setup failed: ' . $app_id->get_error_message());
        }

        $result = $this->workflow->withdraw($app_id, $this->student_id);

        if (is_wp_error($result)) {
            $this->fail('withdraw() failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);

        $app = $this->workflow->get_application($app_id);
        $this->assertEquals('withdrawn', $app->application_status);
    }

    // ─── Business Rule Tests ───

    /** @test */
    public function active_application_count_increments(): void
    {
        wp_set_current_user($this->student_id);

        $count_before = $this->workflow->get_active_application_count($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);
        $result = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($result)) {
            $this->fail('Setup failed: ' . $result->get_error_message());
        }

        $count_after = $this->workflow->get_active_application_count($this->student_id);

        $this->assertEquals($count_before + 1, $count_after);
    }

    /** @test */
    public function get_application_returns_row(): void
    {
        wp_set_current_user($this->student_id);

        $listing = $this->create_test_listing($this->landlord_id);
        $app_id = $this->workflow->submit(
            $this->student_id,
            $listing['unit_id'],
            $this->default_app_data()
        );

        if (is_wp_error($app_id)) {
            $this->fail('Setup failed: ' . $app_id->get_error_message());
        }

        $app = $this->workflow->get_application($app_id);

        $this->assertNotNull($app);
        $this->assertEquals($this->student_id, $app->student_id);
        $this->assertEquals('submitted', $app->application_status);
    }
}
