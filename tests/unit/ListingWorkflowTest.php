<?php
/**
 * Tests for SRP_Listing_Workflow — State Machine Transitions.
 *
 * @package StudentRentalPlatform\Tests\Unit
 */

namespace StudentRentalPlatform\Tests\Unit;

use WP_UnitTestCase;
use StudentRentalPlatform\Workflows\SRP_Listing_Workflow;

/**
 * Class ListingWorkflowTest
 *
 * Tests listing state machine transitions per REQUIREMENTS §3.1.
 * Actual transition map:
 *   draft         → pending_review
 *   pending_review → publish, draft
 *   publish       → booked, expired, reported, suspended, archived
 *   booked        → archived, publish
 *   expired       → archived, draft
 *   reported      → publish, suspended
 *   suspended     → draft
 *   archived      → draft
 */
class ListingWorkflowTest extends WP_UnitTestCase
{
    private SRP_Listing_Workflow $workflow;

    public function set_up(): void
    {
        parent::set_up();
        $this->workflow = new SRP_Listing_Workflow();
    }

    // ─── Valid Transition Tests ───

    /** @test */
    public function draft_to_pending_review_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('draft', 'pending_review'),
            'Draft → Pending Review should be valid.'
        );
    }

    /** @test */
    public function pending_review_to_publish_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('pending_review', 'publish'),
            'Pending Review → Publish should be valid (admin approval).'
        );
    }

    /** @test */
    public function pending_review_to_draft_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('pending_review', 'draft'),
            'Pending Review → Draft should be valid (admin rejection).'
        );
    }

    /** @test */
    public function publish_to_booked_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('publish', 'booked'),
            'Publish → Booked should be valid.'
        );
    }

    /** @test */
    public function publish_to_expired_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('publish', 'expired'),
            'Publish → Expired should be valid.'
        );
    }

    /** @test */
    public function publish_to_suspended_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('publish', 'suspended'),
            'Publish → Suspended should be valid (admin action).'
        );
    }

    /** @test */
    public function publish_to_reported_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('publish', 'reported'),
            'Publish → Reported should be valid.'
        );
    }

    /** @test */
    public function publish_to_archived_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('publish', 'archived'),
            'Publish → Archived should be valid.'
        );
    }

    /** @test */
    public function booked_to_archived_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('booked', 'archived'),
            'Booked → Archived should be valid.'
        );
    }

    /** @test */
    public function expired_to_draft_is_valid(): void
    {
        $this->assertTrue(
            $this->workflow->is_valid_transition('expired', 'draft'),
            'Expired → Draft should be valid (re-submit).'
        );
    }

    // ─── Invalid Transition Tests ───

    /** @test */
    public function draft_to_booked_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('draft', 'booked'),
            'Draft → Booked should NOT be valid.'
        );
    }

    /** @test */
    public function expired_to_publish_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('expired', 'publish'),
            'Expired → Publish should NOT be valid (must go through draft → pending_review).'
        );
    }

    /** @test */
    public function draft_to_publish_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('draft', 'publish'),
            'Draft → Publish should NOT be valid (must go through pending_review).'
        );
    }

    /** @test */
    public function suspended_to_publish_is_invalid(): void
    {
        $this->assertFalse(
            $this->workflow->is_valid_transition('suspended', 'publish'),
            'Suspended → Publish should NOT be valid (must go back to draft).'
        );
    }

    // ─── get_allowed_transitions Tests ───

    /** @test */
    public function allowed_transitions_from_draft(): void
    {
        $allowed = $this->workflow->get_allowed_transitions('draft');
        $this->assertContains('pending_review', $allowed);
        $this->assertCount(1, $allowed, 'Draft should only allow pending_review.');
    }

    /** @test */
    public function allowed_transitions_from_publish(): void
    {
        $allowed = $this->workflow->get_allowed_transitions('publish');
        $this->assertContains('booked', $allowed);
        $this->assertContains('expired', $allowed);
        $this->assertContains('suspended', $allowed);
        $this->assertContains('reported', $allowed);
        $this->assertContains('archived', $allowed);
        $this->assertCount(5, $allowed);
    }

    /** @test */
    public function allowed_transitions_from_unknown_status_is_empty(): void
    {
        $allowed = $this->workflow->get_allowed_transitions('nonexistent_status');
        $this->assertEmpty($allowed);
    }

    // ─── Convenience Method Tests ───

    /** @test */
    public function submit_for_review_transitions_draft_to_pending_review(): void
    {
        $landlord_id = self::factory()->user->create(['role' => 'landlord']);
        wp_set_current_user($landlord_id);
        // Set verification level ≥ 2 (required by transition method).
        update_user_meta($landlord_id, '_srp_verification_level', 2);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'draft',
            'post_author' => $landlord_id,
        ]);

        $result = $this->workflow->submit_for_review($listing_id);

        if (is_wp_error($result)) {
            $this->fail('submit_for_review failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);
        $this->assertEquals('pending_review', get_post_status($listing_id));
    }

    /** @test */
    public function submit_for_review_requires_verification_level_2(): void
    {
        $landlord_id = self::factory()->user->create(['role' => 'landlord']);
        wp_set_current_user($landlord_id);
        // Level 1 — insufficient
        update_user_meta($landlord_id, '_srp_verification_level', 1);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'draft',
            'post_author' => $landlord_id,
        ]);

        $result = $this->workflow->submit_for_review($listing_id);

        $this->assertWPError($result, 'Should require verification level ≥ 2.');
    }

    /** @test */
    public function approve_transitions_pending_review_to_publish(): void
    {
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'pending_review',
            'post_author' => $admin_id,
        ]);

        $result = $this->workflow->approve($listing_id);

        if (is_wp_error($result)) {
            $this->fail('approve failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);
        $this->assertEquals('publish', get_post_status($listing_id));
    }

    /** @test */
    public function reject_transitions_pending_review_to_draft(): void
    {
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'pending_review',
            'post_author' => $admin_id,
        ]);

        $result = $this->workflow->reject($listing_id, 'Incomplete information');

        if (is_wp_error($result)) {
            $this->fail('reject failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);
        $this->assertEquals('draft', get_post_status($listing_id));
    }

    /** @test */
    public function suspend_transitions_published_to_suspended(): void
    {
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'post_author' => $admin_id,
        ]);

        $result = $this->workflow->suspend($listing_id, 'Policy violation');

        if (is_wp_error($result)) {
            $this->fail('suspend failed: ' . $result->get_error_message());
        }

        $this->assertTrue($result);
        $this->assertEquals('suspended', get_post_status($listing_id));
    }

    /** @test */
    public function transition_with_invalid_status_returns_wp_error(): void
    {
        $landlord_id = self::factory()->user->create(['role' => 'landlord']);
        wp_set_current_user($landlord_id);

        $listing_id = self::factory()->post->create([
            'post_type' => 'cpt_listing',
            'post_status' => 'draft',
            'post_author' => $landlord_id,
        ]);

        $result = $this->workflow->transition($listing_id, 'publish');

        $this->assertWPError($result, 'Direct draft → publish should return WP_Error.');
    }
}
