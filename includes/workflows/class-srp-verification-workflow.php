<?php
/**
 * Verification Workflow — Landlord Verification Levels.
 *
 * Manages the landlord verification progression from
 * unverified through fully verified, gating publishing capabilities.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Workflows;

/**
 * Class SRP_Verification_Workflow
 *
 * Manages landlord verification levels per REQUIREMENTS §3.3.
 */
class SRP_Verification_Workflow
{

    /**
     * Verification level definitions.
     *
     * @var array<int, string>
     */
    const LEVELS = array(
        0 => 'Unverified',
        1 => 'Email Verified',
        2 => 'Phone Verified',
        3 => 'Document Pending',
        4 => 'Fully Verified',
        5 => 'Premium Verified',
    );

    /**
     * Valid verification types.
     *
     * @var string[]
     */
    const VERIFICATION_TYPES = array(
        'email',
        'phone',
        'government_id',
        'proof_of_ownership',
        'business_license',
    );

    /**
     * Valid verification statuses.
     *
     * @var string[]
     */
    const STATUSES = array(
        'unverified',
        'pending',
        'approved',
        'rejected',
        'expired',
        'suspended',
    );

    /**
     * Get the current verification level for a landlord.
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @return int The verification level (0–5).
     */
    public function get_level($landlord_id)
    {
        return absint(get_user_meta($landlord_id, '_srp_verification_level', true));
    }

    /**
     * Set the verification level for a landlord.
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @param int $level       The new verification level (0–5).
     * @return bool True on success.
     */
    public function set_level($landlord_id, $level)
    {
        $level = absint($level);
        if ($level > 5) {
            $level = 5;
        }
        return (bool) update_user_meta($landlord_id, '_srp_verification_level', $level);
    }

    /**
     * Check if a landlord can create listings.
     *
     * Levels 0–1 cannot create listings.
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @return bool
     */
    public function can_create_listing($landlord_id)
    {
        return $this->get_level($landlord_id) >= 2;
    }

    /**
     * Check if a landlord can auto-publish (skip admin review).
     *
     * Level 4+ can auto-publish.
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @return bool
     */
    public function can_auto_publish($landlord_id)
    {
        return $this->get_level($landlord_id) >= 4;
    }

    /**
     * Submit a verification document.
     *
     * @since 1.0.0
     *
     * @param int    $landlord_id       The landlord's user ID.
     * @param string $verification_type Type of verification.
     * @param string $document_path     Path to the uploaded document.
     * @param string $document_type     MIME type of the document.
     * @return int|\WP_Error Verification record ID or WP_Error.
     */
    public function submit_verification($landlord_id, $verification_type, $document_path = '', $document_type = '')
    {
        global $wpdb;

        if (!in_array($verification_type, self::VERIFICATION_TYPES, true)) {
            return new \WP_Error(
                'invalid_verification_type',
                __('Invalid verification type.', 'leaselink-core')
            );
        }

        // Check for existing pending verification of same type.
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT verification_id FROM {$wpdb->prefix}landlord_verification
			 WHERE landlord_id = %d AND verification_type = %s AND verification_status = 'pending'",
            $landlord_id,
            $verification_type
        ));

        if ($existing) {
            return new \WP_Error(
                'verification_pending',
                __('You already have a pending verification of this type.', 'leaselink-core')
            );
        }

        $now = current_time('mysql');

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'landlord_verification',
            array(
                'landlord_id' => absint($landlord_id),
                'verification_type' => $verification_type,
                'document_path' => sanitize_text_field($document_path),
                'document_type' => sanitize_text_field($document_type),
                'verification_status' => 'pending',
                'verification_level' => $this->get_level($landlord_id),
                'submitted_at' => $now,
                'created_at' => $now,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error(
                'insert_failed',
                __('Failed to create verification record.', 'leaselink-core')
            );
        }

        $verification_id = $wpdb->insert_id;

        // For email and phone, we can auto-approve (in a real implementation,
        // this would trigger a confirmation code workflow).
        if (in_array($verification_type, array('email', 'phone'), true)) {
            $this->approve_verification($verification_id, 0); // 0 = system
        }

        do_action('srp_verification_submitted', $verification_id, $landlord_id, $verification_type);

        return $verification_id;
    }

    /**
     * Approve a verification (admin action).
     *
     * Recalculates the landlord's verification level after approval.
     *
     * @since 1.0.0
     *
     * @param int    $verification_id The verification record ID.
     * @param int    $admin_id        The admin's user ID (0 = system).
     * @param string $notes           Optional admin notes.
     * @return true|\WP_Error
     */
    public function approve_verification($verification_id, $admin_id = 0, $notes = '')
    {
        global $wpdb;

        $verification = $this->get_verification($verification_id);
        if (!$verification) {
            return new \WP_Error('invalid_verification', __('Verification not found.', 'leaselink-core'));
        }

        if ('pending' !== $verification->verification_status) {
            return new \WP_Error('not_pending', __('Verification is not in pending state.', 'leaselink-core'));
        }

        $now = current_time('mysql');

        // Set expiry for document-level verifications (annual renewal for level 4+).
        $expires_at = null;
        if (in_array($verification->verification_type, array('government_id', 'proof_of_ownership'), true)) {
            $expires_at = gmdate('Y-m-d H:i:s', strtotime('+1 year'));
        }

        $wpdb->update(
            $wpdb->prefix . 'landlord_verification',
            array(
                'verification_status' => 'approved',
                'reviewed_by' => absint($admin_id),
                'admin_notes' => sanitize_textarea_field($notes),
                'reviewed_at' => $now,
                'expires_at' => $expires_at,
            ),
            array('verification_id' => $verification_id),
            array('%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );

        // Recalculate the landlord's verification level.
        $new_level = $this->calculate_level($verification->landlord_id);
        $this->set_level($verification->landlord_id, $new_level);

        do_action('srp_verification_approved', $verification_id, $verification->landlord_id, $new_level);

        return true;
    }

    /**
     * Reject a verification (admin action).
     *
     * @since 1.0.0
     *
     * @param int    $verification_id The verification record ID.
     * @param int    $admin_id        The admin's user ID.
     * @param string $notes           Rejection reason.
     * @return true|\WP_Error
     */
    public function reject_verification($verification_id, $admin_id, $notes = '')
    {
        global $wpdb;

        $verification = $this->get_verification($verification_id);
        if (!$verification) {
            return new \WP_Error('invalid_verification', __('Verification not found.', 'leaselink-core'));
        }

        if ('pending' !== $verification->verification_status) {
            return new \WP_Error('not_pending', __('Verification is not in pending state.', 'leaselink-core'));
        }

        $wpdb->update(
            $wpdb->prefix . 'landlord_verification',
            array(
                'verification_status' => 'rejected',
                'reviewed_by' => absint($admin_id),
                'admin_notes' => sanitize_textarea_field($notes),
                'reviewed_at' => current_time('mysql'),
            ),
            array('verification_id' => $verification_id),
            array('%s', '%d', '%s', '%s'),
            array('%d')
        );

        do_action('srp_verification_rejected', $verification_id, $verification->landlord_id, $notes);

        return true;
    }

    /**
     * Calculate the verification level based on approved verifications.
     *
     * Level progression:
     *   0 → 1: email approved
     *   1 → 2: phone approved
     *   2 → 3: government_id pending (handled by submit)
     *   3 → 4: government_id approved
     *   4 → 5: proof_of_ownership approved
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @return int The calculated verification level (0–5).
     */
    public function calculate_level($landlord_id)
    {
        global $wpdb;

        $approved = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT verification_type FROM {$wpdb->prefix}landlord_verification
			 WHERE landlord_id = %d AND verification_status = 'approved'
			 AND (expires_at IS NULL OR expires_at > %s)",
            $landlord_id,
            current_time('mysql')
        ));

        $level = 0;

        if (in_array('email', $approved, true)) {
            $level = 1;
        }

        if ($level >= 1 && in_array('phone', $approved, true)) {
            $level = 2;
        }

        // Check for pending government ID (level 3).
        if ($level >= 2) {
            $has_pending_id = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}landlord_verification
				 WHERE landlord_id = %d AND verification_type = 'government_id'
				 AND verification_status = 'pending'",
                $landlord_id
            ));

            if ($has_pending_id) {
                $level = 3;
            }

            if (in_array('government_id', $approved, true)) {
                $level = 4;
            }
        }

        if ($level >= 4 && in_array('proof_of_ownership', $approved, true)) {
            $level = 5;
        }

        return $level;
    }

    /**
     * Check if a landlord needs reverification (annual renewal for level 4+).
     *
     * @since 1.0.0
     *
     * @param int $landlord_id The landlord's user ID.
     * @return bool True if reverification is needed.
     */
    public function needs_reverification($landlord_id)
    {
        global $wpdb;

        if ($this->get_level($landlord_id) < 4) {
            return false;
        }

        $expired_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}landlord_verification
			 WHERE landlord_id = %d AND verification_status = 'approved'
			 AND expires_at IS NOT NULL AND expires_at < %s",
            $landlord_id,
            current_time('mysql')
        ));

        return $expired_count > 0;
    }

    /**
     * Get a verification record by ID.
     *
     * @since 1.0.0
     *
     * @param int $verification_id The verification record ID.
     * @return object|null
     */
    public function get_verification($verification_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}landlord_verification WHERE verification_id = %d",
            $verification_id
        ));
    }
}
