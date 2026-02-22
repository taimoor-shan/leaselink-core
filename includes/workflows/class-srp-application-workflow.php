<?php
/**
 * Application Workflow — State Machine.
 *
 * Handles the full application lifecycle from submission
 * through acceptance/rejection with business rule enforcement.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Workflows;

/**
 * Class SRP_Application_Workflow
 *
 * Manages application state transitions per REQUIREMENTS §3.2.
 */
class SRP_Application_Workflow
{

    /**
     * Maximum active applications per student.
     *
     * @var int
     */
    const MAX_ACTIVE_APPLICATIONS = 10;

    /**
     * Days before an unreviewed application auto-expires.
     *
     * @var int
     */
    const AUTO_EXPIRE_DAYS = 14;

    /**
     * Valid state transitions.
     *
     * @var array<string, string[]>
     */
    private $allowed_transitions = array(
        'submitted' => array('under_review', 'withdrawn', 'expired'),
        'under_review' => array('accepted', 'rejected', 'withdrawn', 'expired'),
    );

    /**
     * Active (non-terminal) statuses.
     *
     * @var string[]
     */
    private $active_statuses = array('submitted', 'under_review');

    /**
     * Check if a transition is valid.
     *
     * @since 1.0.0
     *
     * @param string $current_status Current application status.
     * @param string $new_status     Target application status.
     * @return bool
     */
    public function is_valid_transition($current_status, $new_status)
    {
        if (!isset($this->allowed_transitions[$current_status])) {
            return false;
        }
        return in_array($new_status, $this->allowed_transitions[$current_status], true);
    }

    /**
     * Submit a new application.
     *
     * Enforces all business rules from REQUIREMENTS §4.1:
     * - Student can only apply to Published listings
     * - Cannot apply to own listings
     * - Maximum 10 active applications per student
     * - Only one application per student per unit
     * - Move-in date must be within unit's available window
     *
     * @since 1.0.0
     *
     * @param int   $student_id The student's user ID.
     * @param int   $unit_id    The unit post ID.
     * @param array $data       Application data {
     *     @type string $message        Optional cover message.
     *     @type string $move_in_date   Required move-in date (Y-m-d).
     *     @type int    $lease_duration Required lease duration in months.
     *     @type array  $student_info   Optional student details.
     * }
     * @return int|\WP_Error Application ID on success, WP_Error on failure.
     */
    public function submit($student_id, $unit_id, $data = array())
    {
        global $wpdb;

        // 1. Validate the unit exists and is a unit CPT.
        $unit = get_post($unit_id);
        if (!$unit || 'cpt_unit' !== $unit->post_type) {
            return new \WP_Error(
                'invalid_unit',
                __('Invalid unit ID.', 'leaselink-core')
            );
        }

        // 2. Find the associated listing.
        $listing_id = $this->get_listing_for_unit($unit_id);
        if (!$listing_id) {
            return new \WP_Error(
                'no_listing',
                __('No active listing found for this unit.', 'leaselink-core')
            );
        }

        $listing = get_post($listing_id);

        // 3. Listing must be published.
        if ('publish' !== $listing->post_status) {
            return new \WP_Error(
                'listing_not_published',
                __('Applications can only be submitted to published listings.', 'leaselink-core')
            );
        }

        // 4. Cannot apply to own listing.
        if (absint($listing->post_author) === absint($student_id)) {
            return new \WP_Error(
                'cannot_apply_own_listing',
                __('You cannot apply to your own listing.', 'leaselink-core')
            );
        }

        // 5. Unit must be available.
        $availability_status = get_post_meta($unit_id, '_availability_status', true);
        if ('available' !== $availability_status) {
            return new \WP_Error(
                'unit_unavailable',
                __('This unit is not currently available.', 'leaselink-core')
            );
        }

        // 6. Check maximum active applications.
        $active_count = $this->get_active_application_count($student_id);
        if ($active_count >= self::MAX_ACTIVE_APPLICATIONS) {
            return new \WP_Error(
                'max_applications_reached',
                sprintf(
                    /* translators: %d: maximum number of applications */
                    __('You have reached the maximum of %d active applications.', 'leaselink-core'),
                    self::MAX_ACTIVE_APPLICATIONS
                )
            );
        }

        // 7. Only one application per student per unit.
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT application_id FROM {$wpdb->prefix}rental_applications
			 WHERE student_id = %d AND unit_id = %d AND application_status IN ('submitted', 'under_review')",
            $student_id,
            $unit_id
        ));

        if ($existing) {
            return new \WP_Error(
                'duplicate_application',
                __('You already have an active application for this unit.', 'leaselink-core')
            );
        }

        // 8. Validate move-in date within unit's available window.
        $move_in_date = isset($data['move_in_date']) ? sanitize_text_field($data['move_in_date']) : '';
        if (empty($move_in_date)) {
            return new \WP_Error(
                'missing_move_in_date',
                __('Move-in date is required.', 'leaselink-core')
            );
        }

        $available_from = get_post_meta($unit_id, '_available_from', true);
        $available_to = get_post_meta($unit_id, '_available_to', true);

        if ($available_from && $move_in_date < $available_from) {
            return new \WP_Error(
                'move_in_too_early',
                __('Move-in date is before the unit availability window.', 'leaselink-core')
            );
        }

        if ($available_to && $move_in_date > $available_to) {
            return new \WP_Error(
                'move_in_too_late',
                __('Move-in date is after the unit availability window.', 'leaselink-core')
            );
        }

        // 9. Validate lease duration.
        $lease_duration = isset($data['lease_duration']) ? absint($data['lease_duration']) : 0;
        if ($lease_duration <= 0) {
            return new \WP_Error(
                'invalid_lease_duration',
                __('Lease duration is required and must be positive.', 'leaselink-core')
            );
        }

        $min_duration = absint(get_post_meta($unit_id, '_lease_duration_min', true));
        $max_duration = absint(get_post_meta($unit_id, '_lease_duration_max', true));

        if ($min_duration && $lease_duration < $min_duration) {
            return new \WP_Error(
                'lease_too_short',
                sprintf(
                    /* translators: %d: minimum lease duration in months */
                    __('Minimum lease duration is %d months.', 'leaselink-core'),
                    $min_duration
                )
            );
        }

        if ($max_duration && $lease_duration > $max_duration) {
            return new \WP_Error(
                'lease_too_long',
                sprintf(
                    /* translators: %d: maximum lease duration in months */
                    __('Maximum lease duration is %d months.', 'leaselink-core'),
                    $max_duration
                )
            );
        }

        // 10. Insert the application.
        $now = current_time('mysql');

        $student_info = isset($data['student_info']) ? wp_json_encode($data['student_info']) : null;
        $message = isset($data['message']) ? sanitize_textarea_field($data['message']) : '';

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'rental_applications',
            array(
                'student_id' => absint($student_id),
                'unit_id' => absint($unit_id),
                'listing_id' => absint($listing_id),
                'application_status' => 'submitted',
                'message' => $message,
                'move_in_date' => $move_in_date,
                'lease_duration' => $lease_duration,
                'student_info' => $student_info,
                'submitted_at' => $now,
                'created_at' => $now,
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        if (false === $inserted) {
            return new \WP_Error(
                'insert_failed',
                __('Failed to create application. Please try again.', 'leaselink-core')
            );
        }

        $application_id = $wpdb->insert_id;

        /**
         * Fires after a new application is submitted.
         *
         * @since 1.0.0
         *
         * @param int $application_id The new application ID.
         * @param int $student_id     The student's user ID.
         * @param int $unit_id        The unit post ID.
         * @param int $listing_id     The listing post ID.
         */
        do_action('srp_application_submitted', $application_id, $student_id, $unit_id, $listing_id);

        return $application_id;
    }

    /**
     * Accept an application (landlord action).
     *
     * When a landlord accepts an application:
     * 1. Update application status to 'accepted'
     * 2. Update unit status to 'booked'
     * 3. Reject all other pending applications for that unit
     * 4. Fire events for notifications
     *
     * @since 1.0.0
     *
     * @param int    $application_id The application ID.
     * @param int    $landlord_id    The landlord's user ID.
     * @param string $notes          Optional landlord notes.
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public function accept($application_id, $landlord_id, $notes = '')
    {
        global $wpdb;

        $application = $this->get_application($application_id);
        if (!$application) {
            return new \WP_Error('invalid_application', __('Application not found.', 'leaselink-core'));
        }

        // Verify the landlord owns this listing.
        $listing = get_post($application->listing_id);
        if (!$listing || absint($listing->post_author) !== absint($landlord_id)) {
            return new \WP_Error('unauthorized', __('You do not own this listing.', 'leaselink-core'));
        }

        // Validate transition.
        if (!$this->is_valid_transition($application->application_status, 'accepted')) {
            return new \WP_Error(
                'invalid_transition',
                __('This application cannot be accepted in its current state.', 'leaselink-core')
            );
        }

        $now = current_time('mysql');

        // 1. Accept this application.
        $wpdb->update(
            $wpdb->prefix . 'rental_applications',
            array(
                'application_status' => 'accepted',
                'landlord_notes' => sanitize_textarea_field($notes),
                'decision_at' => $now,
            ),
            array('application_id' => $application_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        // 2. Book the unit.
        update_post_meta($application->unit_id, '_availability_status', 'booked');

        // 3. Auto-reject all other pending applications for this unit.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rental_applications
			 SET application_status = 'rejected', decision_at = %s, landlord_notes = %s
			 WHERE unit_id = %d AND application_id != %d
			 AND application_status IN ('submitted', 'under_review')",
            $now,
            __('Another application was accepted for this unit.', 'leaselink-core'),
            $application->unit_id,
            $application_id
        ));

        /**
         * Fires after an application is accepted.
         *
         * @since 1.0.0
         *
         * @param int $application_id The application ID.
         * @param int $landlord_id    The landlord's user ID.
         */
        do_action('srp_application_accepted', $application_id, $landlord_id);

        /**
         * Fires after a unit is booked.
         *
         * @since 1.0.0
         *
         * @param int $unit_id        The unit post ID.
         * @param int $application_id The application ID.
         */
        do_action('srp_unit_booked', $application->unit_id, $application_id);

        return true;
    }

    /**
     * Reject an application (landlord action).
     *
     * @since 1.0.0
     *
     * @param int    $application_id The application ID.
     * @param int    $landlord_id    The landlord's user ID.
     * @param string $notes          Optional rejection reason.
     * @return true|\WP_Error
     */
    public function reject($application_id, $landlord_id, $notes = '')
    {
        global $wpdb;

        $application = $this->get_application($application_id);
        if (!$application) {
            return new \WP_Error('invalid_application', __('Application not found.', 'leaselink-core'));
        }

        $listing = get_post($application->listing_id);
        if (!$listing || absint($listing->post_author) !== absint($landlord_id)) {
            return new \WP_Error('unauthorized', __('You do not own this listing.', 'leaselink-core'));
        }

        if (!$this->is_valid_transition($application->application_status, 'rejected')) {
            return new \WP_Error(
                'invalid_transition',
                __('This application cannot be rejected in its current state.', 'leaselink-core')
            );
        }

        $wpdb->update(
            $wpdb->prefix . 'rental_applications',
            array(
                'application_status' => 'rejected',
                'landlord_notes' => sanitize_textarea_field($notes),
                'decision_at' => current_time('mysql'),
            ),
            array('application_id' => $application_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        do_action('srp_application_rejected', $application_id, $notes);

        return true;
    }

    /**
     * Withdraw an application (student action).
     *
     * @since 1.0.0
     *
     * @param int $application_id The application ID.
     * @param int $student_id     The student's user ID.
     * @return true|\WP_Error
     */
    public function withdraw($application_id, $student_id)
    {
        global $wpdb;

        $application = $this->get_application($application_id);
        if (!$application) {
            return new \WP_Error('invalid_application', __('Application not found.', 'leaselink-core'));
        }

        if (absint($application->student_id) !== absint($student_id)) {
            return new \WP_Error('unauthorized', __('You can only withdraw your own applications.', 'leaselink-core'));
        }

        if (!$this->is_valid_transition($application->application_status, 'withdrawn')) {
            return new \WP_Error(
                'invalid_transition',
                __('This application cannot be withdrawn in its current state.', 'leaselink-core')
            );
        }

        $wpdb->update(
            $wpdb->prefix . 'rental_applications',
            array(
                'application_status' => 'withdrawn',
                'decision_at' => current_time('mysql'),
            ),
            array('application_id' => $application_id),
            array('%s', '%s'),
            array('%d')
        );

        do_action('srp_application_withdrawn', $application_id, $student_id);

        return true;
    }

    /**
     * Get an application by ID.
     *
     * @since 1.0.0
     *
     * @param int $application_id The application ID.
     * @return object|null The application row or null.
     */
    public function get_application($application_id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rental_applications WHERE application_id = %d",
            $application_id
        ));
    }

    /**
     * Get the count of active applications for a student.
     *
     * @since 1.0.0
     *
     * @param int $student_id The student's user ID.
     * @return int
     */
    public function get_active_application_count($student_id)
    {
        global $wpdb;

        $placeholders = implode(',', array_fill(0, count($this->active_statuses), '%s'));
        $params = array_merge(array($student_id), $this->active_statuses);

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}rental_applications
			 WHERE student_id = %d AND application_status IN ({$placeholders})",
            ...$params
        ));
    }

    /**
     * Get the listing ID associated with a unit.
     *
     * @since 1.0.0
     *
     * @param int $unit_id The unit post ID.
     * @return int|null The listing post ID, or null if not found.
     */
    private function get_listing_for_unit($unit_id)
    {
        $listings = get_posts(array(
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'meta_key' => '_listing_unit_id',
            'meta_value' => $unit_id,
            'posts_per_page' => 1,
            'fields' => 'ids',
        ));

        return !empty($listings) ? $listings[0] : null;
    }
}
