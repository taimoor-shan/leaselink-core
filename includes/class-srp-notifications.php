<?php
/**
 * Email Notifications.
 *
 * Wires into workflow event hooks to send email notifications
 * to landlords and students on key state changes.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform;

/**
 * Class SRP_Notifications
 *
 * Handles all email notifications per REQUIREMENTS §10.
 */
class SRP_Notifications
{

    /**
     * Register event hooks.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Application events.
        add_action('srp_application_submitted', array($this, 'on_application_submitted'), 10, 4);
        add_action('srp_application_accepted', array($this, 'on_application_accepted'), 10, 2);
        add_action('srp_application_rejected', array($this, 'on_application_rejected'), 10, 2);

        // Listing events.
        add_action('srp_listing_status_publish', array($this, 'on_listing_published'), 10, 3);
        add_action('srp_listing_status_suspended', array($this, 'on_listing_suspended'), 10, 3);

        // Verification events.
        add_action('srp_verification_approved', array($this, 'on_verification_approved'), 10, 3);
        add_action('srp_verification_rejected', array($this, 'on_verification_rejected'), 10, 3);
    }

    /**
     * Notify landlord of a new application.
     *
     * @since 1.0.0
     *
     * @param int $application_id The application ID.
     * @param int $student_id     The student's user ID.
     * @param int $unit_id        The unit post ID.
     * @param int $listing_id     The listing post ID.
     */
    public function on_application_submitted($application_id, $student_id, $unit_id, $listing_id)
    {
        $listing = get_post($listing_id);
        if (!$listing) {
            return;
        }

        $landlord = get_userdata($listing->post_author);
        $student = get_userdata($student_id);
        if (!$landlord || !$student) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: listing title */
            __('New Application for %s', 'leaselink-core'),
            $listing->post_title
        );

        $message = sprintf(
            /* translators: 1: landlord name, 2: student name, 3: listing title */
            __(
                "Hello %1\$s,\n\nYou have received a new application from %2\$s for your listing \"%3\$s\".\n\nPlease review it in your dashboard.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $landlord->display_name,
            $student->display_name,
            $listing->post_title
        );

        $this->send($landlord->user_email, $subject, $message);
    }

    /**
     * Notify student that their application was accepted.
     *
     * @since 1.0.0
     *
     * @param int $application_id The application ID.
     * @param int $landlord_id    The landlord's user ID.
     */
    public function on_application_accepted($application_id, $landlord_id)
    {
        global $wpdb;

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rental_applications WHERE application_id = %d",
            $application_id
        ));

        if (!$application) {
            return;
        }

        $student = get_userdata($application->student_id);
        $listing = get_post($application->listing_id);
        if (!$student || !$listing) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: listing title */
            __('Great news! Your application for %s was accepted', 'leaselink-core'),
            $listing->post_title
        );

        $message = sprintf(
            /* translators: 1: student name, 2: listing title */
            __(
                "Hello %1\$s,\n\nCongratulations! Your application for \"%2\$s\" has been accepted by the landlord.\n\nNext steps will follow shortly.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $student->display_name,
            $listing->post_title
        );

        $this->send($student->user_email, $subject, $message);
    }

    /**
     * Notify student that their application was rejected.
     *
     * @since 1.0.0
     *
     * @param int    $application_id The application ID.
     * @param string $reason         Rejection reason.
     */
    public function on_application_rejected($application_id, $reason = '')
    {
        global $wpdb;

        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}rental_applications WHERE application_id = %d",
            $application_id
        ));

        if (!$application) {
            return;
        }

        $student = get_userdata($application->student_id);
        $listing = get_post($application->listing_id);
        if (!$student || !$listing) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: listing title */
            __('Application update for %s', 'leaselink-core'),
            $listing->post_title
        );

        $message = sprintf(
            /* translators: 1: student name, 2: listing title */
            __(
                "Hello %1\$s,\n\nUnfortunately, your application for \"%2\$s\" was not accepted.\n\nDon't be discouraged — continue browsing other available listings.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $student->display_name,
            $listing->post_title
        );

        $this->send($student->user_email, $subject, $message);
    }

    /**
     * Notify landlord that their listing was approved/published.
     *
     * @since 1.0.0
     *
     * @param int    $listing_id      The listing post ID.
     * @param string $previous_status The previous status.
     * @param array  $args            Extra arguments.
     */
    public function on_listing_published($listing_id, $previous_status, $args = array())
    {
        $listing = get_post($listing_id);
        if (!$listing) {
            return;
        }

        $landlord = get_userdata($listing->post_author);
        if (!$landlord) {
            return;
        }

        $subject = sprintf(
            /* translators: %s: listing title */
            __('Your listing "%s" has been approved!', 'leaselink-core'),
            $listing->post_title
        );

        $message = sprintf(
            /* translators: 1: landlord name, 2: listing title */
            __(
                "Hello %1\$s,\n\nYour listing \"%2\$s\" has been approved and is now live.\n\nStudents can now find and apply to your listing.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $landlord->display_name,
            $listing->post_title
        );

        $this->send($landlord->user_email, $subject, $message);
    }

    /**
     * Notify landlord that their listing was suspended.
     *
     * @since 1.0.0
     *
     * @param int    $listing_id      The listing post ID.
     * @param string $previous_status The previous status.
     * @param array  $args            Extra arguments (reason).
     */
    public function on_listing_suspended($listing_id, $previous_status, $args = array())
    {
        $listing = get_post($listing_id);
        if (!$listing) {
            return;
        }

        $landlord = get_userdata($listing->post_author);
        if (!$landlord) {
            return;
        }

        $reason = !empty($args['reason']) ? $args['reason'] : __('No reason provided.', 'leaselink-core');

        $subject = sprintf(
            /* translators: %s: listing title */
            __('Your listing "%s" has been suspended', 'leaselink-core'),
            $listing->post_title
        );

        $message = sprintf(
            /* translators: 1: landlord name, 2: listing title, 3: reason */
            __(
                "Hello %1\$s,\n\nYour listing \"%2\$s\" has been suspended.\n\nReason: %3\$s\n\nIf you believe this is an error, please contact support.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $landlord->display_name,
            $listing->post_title,
            $reason
        );

        $this->send($landlord->user_email, $subject, $message);
    }

    /**
     * Notify landlord that their verification was approved.
     *
     * @since 1.0.0
     *
     * @param int $verification_id The verification record ID.
     * @param int $landlord_id     The landlord's user ID.
     * @param int $new_level       The new verification level.
     */
    public function on_verification_approved($verification_id, $landlord_id, $new_level)
    {
        $landlord = get_userdata($landlord_id);
        if (!$landlord) {
            return;
        }

        $subject = sprintf(
            /* translators: %d: verification level */
            __('Verification approved — You are now Level %d', 'leaselink-core'),
            $new_level
        );

        $message = sprintf(
            /* translators: 1: landlord name, 2: level number */
            __(
                "Hello %1\$s,\n\nYour verification has been approved! You are now at Level %2\$d.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $landlord->display_name,
            $new_level
        );

        $this->send($landlord->user_email, $subject, $message);
    }

    /**
     * Notify landlord that their verification was rejected.
     *
     * @since 1.0.0
     *
     * @param int    $verification_id The verification record ID.
     * @param int    $landlord_id     The landlord's user ID.
     * @param string $reason          Rejection reason.
     */
    public function on_verification_rejected($verification_id, $landlord_id, $reason = '')
    {
        $landlord = get_userdata($landlord_id);
        if (!$landlord) {
            return;
        }

        $subject = __('Verification update', 'leaselink-core');

        $reason_text = !empty($reason) ? $reason : __('No reason provided.', 'leaselink-core');

        $message = sprintf(
            /* translators: 1: landlord name, 2: reason */
            __(
                "Hello %1\$s,\n\nUnfortunately, your verification was not approved.\n\nReason: %2\$s\n\nYou can resubmit your documents at any time.\n\nBest regards,\nLeaseLink",
                'leaselink-core'
            ),
            $landlord->display_name,
            $reason_text
        );

        $this->send($landlord->user_email, $subject, $message);
    }

    /**
     * Send an email using wp_mail with HTML content type.
     *
     * @since 1.0.0
     *
     * @param string $to      Recipient email address.
     * @param string $subject Email subject.
     * @param string $message Email body (plain text, auto-wrapped in HTML).
     */
    private function send($to, $subject, $message)
    {
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Wrap plain text in basic HTML.
        $html = '<div style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
        $html .= nl2br(esc_html($message));
        $html .= '</div>';

        wp_mail($to, $subject, $html, $headers);
    }
}
