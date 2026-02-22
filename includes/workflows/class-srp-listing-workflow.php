<?php
/**
 * Listing Workflow — State Machine.
 *
 * Handles the full listing publication lifecycle including
 * custom post statuses and validated state transitions.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\Workflows;

/**
 * Class SRP_Listing_Workflow
 *
 * Manages listing state transitions per REQUIREMENTS §3.1.
 */
class SRP_Listing_Workflow
{

    /**
     * Valid state transitions.
     *
     * @var array<string, string[]>
     */
    private $allowed_transitions = array(
        'draft' => array('pending_review'),
        'pending_review' => array('publish', 'draft'),
        'publish' => array('booked', 'expired', 'reported', 'suspended', 'archived'),
        'booked' => array('archived', 'publish'),
        'expired' => array('archived', 'draft'),
        'reported' => array('publish', 'suspended'),
        'suspended' => array('draft'),
        'archived' => array('draft'),
    );

    /**
     * Initialize the workflow.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('init', array($this, 'register_post_statuses'));
    }

    /**
     * Register custom post statuses for listings.
     *
     * WordPress natively provides: draft, pending, publish, trash, private.
     * We register the additional statuses our state machine needs.
     *
     * @since 1.0.0
     */
    public function register_post_statuses()
    {
        register_post_status('pending_review', array(
            'label' => _x('Pending Review', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            // translators: %s: number of pending review posts.
            'label_count' => _n_noop(
                'Pending Review <span class="count">(%s)</span>',
                'Pending Review <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));

        register_post_status('booked', array(
            'label' => _x('Booked', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Booked <span class="count">(%s)</span>',
                'Booked <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));

        register_post_status('expired', array(
            'label' => _x('Expired', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Expired <span class="count">(%s)</span>',
                'Expired <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));

        register_post_status('reported', array(
            'label' => _x('Reported', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Reported <span class="count">(%s)</span>',
                'Reported <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));

        register_post_status('suspended', array(
            'label' => _x('Suspended', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Suspended <span class="count">(%s)</span>',
                'Suspended <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));

        register_post_status('archived', array(
            'label' => _x('Archived', 'post status', 'leaselink-core'),
            'public' => false,
            'exclude_from_search' => true,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                'Archived <span class="count">(%s)</span>',
                'Archived <span class="count">(%s)</span>',
                'leaselink-core'
            ),
        ));
    }

    /**
     * Check if a transition is valid.
     *
     * @since 1.0.0
     *
     * @param string $current_status Current post status.
     * @param string $new_status     Target post status.
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
     * Get allowed transitions from a given status.
     *
     * @since 1.0.0
     *
     * @param string $current_status Current post status.
     * @return string[] Array of valid target statuses.
     */
    public function get_allowed_transitions($current_status)
    {
        if (!isset($this->allowed_transitions[$current_status])) {
            return array();
        }
        return $this->allowed_transitions[$current_status];
    }

    /**
     * Transition a listing to a new status.
     *
     * Validates the transition, checks capabilities, performs the
     * update, and fires appropriate hooks.
     *
     * @since 1.0.0
     *
     * @param int    $listing_id The listing post ID.
     * @param string $new_status The target status.
     * @param array  $args       Optional. Extra arguments (e.g., reason for suspension).
     * @return true|\WP_Error True on success, WP_Error on failure.
     */
    public function transition($listing_id, $new_status, $args = array())
    {
        $post = get_post($listing_id);

        if (!$post || 'cpt_listing' !== $post->post_type) {
            return new \WP_Error(
                'invalid_listing',
                __('Invalid listing ID.', 'leaselink-core')
            );
        }

        $current_status = $post->post_status;

        if (!$this->is_valid_transition($current_status, $new_status)) {
            return new \WP_Error(
                'invalid_transition',
                sprintf(
                    /* translators: 1: current status 2: target status */
                    __('Cannot transition from "%1$s" to "%2$s".', 'leaselink-core'),
                    $current_status,
                    $new_status
                ),
                array(
                    'current' => $current_status,
                    'target' => $new_status,
                    'allowed' => $this->get_allowed_transitions($current_status),
                )
            );
        }

        // Capability gate: only verified landlords can submit for review.
        if ('pending_review' === $new_status) {
            $verification_level = absint(get_user_meta($post->post_author, '_srp_verification_level', true));
            if ($verification_level < 2) {
                return new \WP_Error(
                    'insufficient_verification',
                    __('Landlord must be at least verification level 2 to submit for review.', 'leaselink-core')
                );
            }
        }

        // Perform the status update.
        $result = wp_update_post(
            array(
                'ID' => $listing_id,
                'post_status' => $new_status,
            ),
            true
        );

        if (is_wp_error($result)) {
            return $result;
        }

        // Store transition metadata.
        update_post_meta($listing_id, '_srp_last_transition', current_time('mysql'));
        update_post_meta($listing_id, '_srp_previous_status', $current_status);

        // Handle suspension reason.
        if ('suspended' === $new_status && !empty($args['reason'])) {
            update_post_meta($listing_id, '_suspension_reason', sanitize_text_field($args['reason']));
            update_post_meta($listing_id, '_suspended_at', current_time('mysql'));
        }

        // Set expiry date on publish.
        if ('publish' === $new_status) {
            $expiry_days = absint(get_option('srp_listing_expiry_days', 90));
            $expiry_date = gmdate('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
            update_post_meta($listing_id, '_listing_expiry_date', $expiry_date);
            update_post_meta($listing_id, '_published_at', current_time('mysql'));
        }

        /**
         * Fires after a listing status transition.
         *
         * @since 1.0.0
         *
         * @param int    $listing_id     The listing post ID.
         * @param string $new_status     The new status.
         * @param string $current_status The previous status.
         * @param array  $args           Extra arguments.
         */
        do_action('srp_listing_transitioned', $listing_id, $new_status, $current_status, $args);

        /**
         * Fires for a specific new listing status.
         *
         * @since 1.0.0
         *
         * @param int    $listing_id     The listing post ID.
         * @param string $current_status The previous status.
         * @param array  $args           Extra arguments.
         */
        do_action("srp_listing_status_{$new_status}", $listing_id, $current_status, $args);

        return true;
    }

    /**
     * Submit a listing for review.
     *
     * Convenience method that wraps transition().
     *
     * @since 1.0.0
     *
     * @param int $listing_id The listing post ID.
     * @return true|\WP_Error
     */
    public function submit_for_review($listing_id)
    {
        return $this->transition($listing_id, 'pending_review');
    }

    /**
     * Approve a listing (admin action).
     *
     * @since 1.0.0
     *
     * @param int $listing_id The listing post ID.
     * @return true|\WP_Error
     */
    public function approve($listing_id)
    {
        return $this->transition($listing_id, 'publish');
    }

    /**
     * Reject a listing back to draft (admin action).
     *
     * @since 1.0.0
     *
     * @param int    $listing_id The listing post ID.
     * @param string $reason     Rejection reason.
     * @return true|\WP_Error
     */
    public function reject($listing_id, $reason = '')
    {
        $result = $this->transition($listing_id, 'draft');
        if (!is_wp_error($result) && !empty($reason)) {
            update_post_meta($listing_id, '_admin_notes', sanitize_textarea_field($reason));
        }
        return $result;
    }

    /**
     * Suspend a listing (admin action).
     *
     * @since 1.0.0
     *
     * @param int    $listing_id The listing post ID.
     * @param string $reason     Suspension reason.
     * @return true|\WP_Error
     */
    public function suspend($listing_id, $reason = '')
    {
        return $this->transition($listing_id, 'suspended', array('reason' => $reason));
    }
}
