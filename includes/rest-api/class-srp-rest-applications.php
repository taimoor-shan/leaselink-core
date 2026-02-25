<?php
/**
 * REST API — Applications Endpoints.
 *
 * Authenticated endpoints for submitting, viewing,
 * and managing rental applications.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform\RestApi;

use StudentRentalPlatform\Workflows\SRP_Application_Workflow;

/**
 * Class SRP_REST_Applications
 *
 * Registers REST routes for application management per REQUIREMENTS §7.1.
 */
class SRP_REST_Applications
{

    /**
     * REST namespace.
     *
     * @var string
     */
    const NAMESPACE = 'rental/v1';

    /**
     * Application workflow instance.
     *
     * @var SRP_Application_Workflow
     */
    private $workflow;

    /**
     * Initialize with workflow dependency.
     *
     * @since 1.0.0
     *
     * @param SRP_Application_Workflow $workflow Application workflow.
     */
    public function __construct(SRP_Application_Workflow $workflow)
    {
        $this->workflow = $workflow;
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register the REST routes.
     *
     * @since 1.0.0
     */
    public function register_routes()
    {
        // Student: submit application.
        register_rest_route(self::NAMESPACE , '/applications', array(
            'methods' => 'POST',
            'callback' => array($this, 'submit_application'),
            'permission_callback' => array($this, 'check_student_permission'),
            'args' => array(
                'unit_id' => array('required' => true, 'sanitize_callback' => 'absint'),
                'move_in_date' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
                'lease_duration' => array('required' => true, 'sanitize_callback' => 'absint'),
                'message' => array('required' => false, 'sanitize_callback' => 'sanitize_textarea_field'),
            ),
        ));

        // Student: get own applications.
        register_rest_route(self::NAMESPACE , '/applications/mine', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_my_applications'),
            'permission_callback' => array($this, 'check_student_permission'),
        ));

        // Student: withdraw / Landlord: accept or reject.
        register_rest_route(self::NAMESPACE , '/applications/(?P<id>\d+)', array(
            'methods' => 'PATCH',
            'callback' => array($this, 'update_application'),
            'permission_callback' => 'is_user_logged_in',
            'args' => array(
                'id' => array(
                    'validate_callback' => function ($param) {
                        return is_numeric($param);
                    }
                ),
                'status' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => function ($value) {
                        return in_array($value, array('accepted', 'rejected', 'withdrawn', 'under_review'), true);
                    },
                ),
                'notes' => array('required' => false, 'sanitize_callback' => 'sanitize_textarea_field'),
            ),
        ));

        // Landlord: get applications for their listings.
        register_rest_route(self::NAMESPACE , '/applications', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_landlord_applications'),
            'permission_callback' => array($this, 'check_landlord_permission'),
            'args' => array(
                'status' => array('sanitize_callback' => 'sanitize_text_field'),
            ),
        ));
    }

    /**
     * Submit a new application (student action).
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function submit_application($request)
    {
        $result = $this->workflow->submit(
            get_current_user_id(),
            $request->get_param('unit_id'),
            array(
                'move_in_date' => $request->get_param('move_in_date'),
                'lease_duration' => $request->get_param('lease_duration'),
                'message' => $request->get_param('message') ?? '',
            )
        );

        if (is_wp_error($result)) {
            return $result;
        }

        return new \WP_REST_Response(
            array(
                'application_id' => $result,
                'status' => 'submitted',
                'message' => __('Application submitted successfully.', 'leaselink-core'),
            ),
            201
        );
    }

    /**
     * Get the current student's applications.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_my_applications($request)
    {
        global $wpdb;

        $student_id = get_current_user_id();

        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.post_title AS listing_title
			 FROM {$wpdb->prefix}rental_applications a
			 LEFT JOIN {$wpdb->prefix}posts p ON a.listing_id = p.ID
			 WHERE a.student_id = %d
			 ORDER BY a.submitted_at DESC",
            $student_id
        ));

        return new \WP_REST_Response($applications, 200);
    }

    /**
     * Get applications for a landlord's listings.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_landlord_applications($request)
    {
        global $wpdb;

        $landlord_id = get_current_user_id();
        $status = $request->get_param('status');

        $sql = "SELECT a.*, p.post_title AS listing_title, u.display_name AS student_name
				FROM {$wpdb->prefix}rental_applications a
				LEFT JOIN {$wpdb->prefix}posts p ON a.listing_id = p.ID
				LEFT JOIN {$wpdb->prefix}users u ON a.student_id = u.ID
				WHERE p.post_author = %d";

        $params = array($landlord_id);

        if ($status) {
            $sql .= " AND a.application_status = %s";
            $params[] = $status;
        }

        $sql .= " ORDER BY a.submitted_at DESC";

        $applications = $wpdb->get_results($wpdb->prepare($sql, ...$params));

        return new \WP_REST_Response($applications, 200);
    }

    /**
     * Update an application status (accept/reject/withdraw).
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_application($request)
    {
        $application_id = absint($request->get_param('id'));
        $new_status = $request->get_param('status');
        $notes = $request->get_param('notes') ?? '';
        $current_user = get_current_user_id();

        switch ($new_status) {
            case 'accepted':
                $result = $this->workflow->accept($application_id, $current_user, $notes);
                break;

            case 'rejected':
                $result = $this->workflow->reject($application_id, $current_user, $notes);
                break;

            case 'withdrawn':
                $result = $this->workflow->withdraw($application_id, $current_user);
                break;

            case 'under_review':
                $result = $this->workflow->mark_under_review($application_id, $current_user);
                break;

            default:
                return new \WP_Error('invalid_status', __('Invalid status.', 'leaselink-core'), array('status' => 400));
        }

        if (is_wp_error($result)) {
            return $result;
        }

        return new \WP_REST_Response(
            array(
                'application_id' => $application_id,
                'status' => $new_status,
                'message' => __('Application updated.', 'leaselink-core'),
            ),
            200
        );
    }

    /**
     * Check if the current user is a student with submit_applications capability.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function check_student_permission()
    {
        return is_user_logged_in() && current_user_can('submit_applications');
    }

    /**
     * Check if the current user is a landlord with manage_applications capability.
     *
     * @since 1.0.0
     *
     * @return bool
     */
    public function check_landlord_permission()
    {
        return is_user_logged_in() && current_user_can('manage_applications');
    }
}
