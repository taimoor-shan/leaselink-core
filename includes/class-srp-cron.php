<?php
/**
 * Cron Scheduler — Listing & Application Expiry.
 *
 * Registers WP-Cron hooks to automatically expire stale
 * listings and applications per REQUIREMENTS §3.1 and §3.2.
 *
 * @package    StudentRentalPlatform
 * @since      1.0.0
 */

namespace StudentRentalPlatform;

use StudentRentalPlatform\Workflows\SRP_Listing_Workflow;
use StudentRentalPlatform\Workflows\SRP_Application_Workflow;

/**
 * Class SRP_Cron
 */
class SRP_Cron
{

    /**
     * Listing workflow instance.
     *
     * @var SRP_Listing_Workflow
     */
    private $listing_workflow;

    /**
     * Application workflow instance.
     *
     * @var SRP_Application_Workflow
     */
    private $application_workflow;

    /**
     * Initialize and register cron action hooks.
     *
     * @since 1.0.0
     *
     * @param SRP_Listing_Workflow     $listing_workflow     Listing workflow.
     * @param SRP_Application_Workflow $application_workflow Application workflow.
     */
    public function __construct(SRP_Listing_Workflow $listing_workflow, SRP_Application_Workflow $application_workflow)
    {
        $this->listing_workflow = $listing_workflow;
        $this->application_workflow = $application_workflow;

        add_action('srp_cron_expire_listings', array($this, 'expire_listings'));
        add_action('srp_cron_expire_applications', array($this, 'expire_applications'));
    }

    /**
     * Schedule cron events.
     *
     * Called during plugin activation.
     *
     * @since 1.0.0
     */
    public static function schedule_events()
    {
        if (!wp_next_scheduled('srp_cron_expire_listings')) {
            wp_schedule_event(time(), 'twicedaily', 'srp_cron_expire_listings');
        }

        if (!wp_next_scheduled('srp_cron_expire_applications')) {
            wp_schedule_event(time(), 'twicedaily', 'srp_cron_expire_applications');
        }
    }

    /**
     * Unschedule cron events.
     *
     * Called during plugin deactivation.
     *
     * @since 1.0.0
     */
    public static function unschedule_events()
    {
        $timestamp = wp_next_scheduled('srp_cron_expire_listings');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'srp_cron_expire_listings');
        }

        $timestamp = wp_next_scheduled('srp_cron_expire_applications');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'srp_cron_expire_applications');
        }
    }

    /**
     * Expire published listings past their expiry date.
     *
     * Listings auto-expire after 90 days (configurable via srp_listing_expiry_days).
     * Per REQUIREMENTS §3.1.
     *
     * @since 1.0.0
     */
    public function expire_listings()
    {
        $now = current_time('mysql');

        $expired_listings = get_posts(array(
            'post_type' => 'cpt_listing',
            'post_status' => 'publish',
            'posts_per_page' => 50, // Batch to avoid timeout.
            'meta_query' => array(
                array(
                    'key' => '_listing_expiry_date',
                    'value' => $now,
                    'type' => 'DATETIME',
                    'compare' => '<=',
                ),
            ),
            'fields' => 'ids',
        ));

        foreach ($expired_listings as $listing_id) {
            $this->listing_workflow->transition($listing_id, 'expired');

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[LeaseLink Cron] Listing expired: ' . $listing_id);
            }
        }
    }

    /**
     * Expire unreviewed applications older than 14 days.
     *
     * Per REQUIREMENTS §3.2.
     *
     * @since 1.0.0
     */
    public function expire_applications()
    {
        global $wpdb;

        $expire_days = SRP_Application_Workflow::AUTO_EXPIRE_DAYS;
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$expire_days} days"));
        $now = current_time('mysql');

        $expired = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}rental_applications
			 SET application_status = 'expired', decision_at = %s
			 WHERE application_status IN ('submitted', 'under_review')
			 AND submitted_at <= %s",
            $now,
            $cutoff
        ));

        if ($expired && defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[LeaseLink Cron] Applications expired: ' . $expired);
        }
    }
}
