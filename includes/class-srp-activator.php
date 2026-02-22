<?php
/**
 * Fired during plugin activation.
 *
 * @package           StudentRentalPlatform
 * @author            LeaseLink Team
 * @license           GPL-2.0+
 * @link              https://leaselink.com
 * @since             1.0.0
 */

namespace StudentRentalPlatform;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class SRP_Activator
{

	/**
	 * Activate the plugin.
	 *
	 * Only creates tables if they don't exist.
	 *
	 * @since    1.0.0
	 */
	public static function activate()
	{
		require_once plugin_dir_path(__FILE__) . 'class-srp-roles.php';
		SRP_Roles::install_roles();
		self::create_tables();
		update_option('srp_db_version', '1.0.0');
		flush_rewrite_rules();
	}

	/**
	 * Create custom database tables.
	 *
	 * @since    1.0.0
	 */
	private static function create_tables()
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		// Table 1: wp_rental_applications
		$table_applications = $wpdb->prefix . 'rental_applications';
		$sql_applications = "CREATE TABLE $table_applications (
			application_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			unit_id bigint(20) unsigned NOT NULL,
			listing_id bigint(20) unsigned NOT NULL,
			application_status varchar(20) DEFAULT 'submitted',
			message text,
			move_in_date date,
			lease_duration int(11),
			student_info longtext,
			landlord_notes text,
			submitted_at datetime NOT NULL,
			reviewed_at datetime,
			decision_at datetime,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (application_id),
			KEY idx_student (student_id),
			KEY idx_unit (unit_id),
			KEY idx_status (application_status),
			KEY idx_submitted (submitted_at)
		) $charset_collate;";

		// Table 2: wp_rental_availability
		$table_availability = $wpdb->prefix . 'rental_availability';
		$sql_availability = "CREATE TABLE $table_availability (
			calendar_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			unit_id bigint(20) unsigned NOT NULL,
			date date NOT NULL,
			availability_flag tinyint(1) DEFAULT 1,
			booking_type varchar(20),
			notes text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (calendar_id),
			UNIQUE KEY unique_unit_date (unit_id, date),
			KEY idx_date_range (unit_id, date)
		) $charset_collate;";

		// Table 3: wp_landlord_verification
		$table_verification = $wpdb->prefix . 'landlord_verification';
		$sql_verification = "CREATE TABLE $table_verification (
			verification_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			landlord_id bigint(20) unsigned NOT NULL,
			verification_type varchar(50) NOT NULL,
			document_path varchar(255),
			document_type varchar(50),
			verification_status varchar(20) DEFAULT 'pending',
			verification_level int(11) DEFAULT 0,
			reviewed_by bigint(20) unsigned,
			admin_notes text,
			submitted_at datetime NOT NULL,
			reviewed_at datetime,
			expires_at datetime,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (verification_id),
			KEY idx_landlord (landlord_id),
			KEY idx_status (verification_status)
		) $charset_collate;";

		// Table 4: wp_rental_messages
		$table_messages = $wpdb->prefix . 'rental_messages';
		$sql_messages = "CREATE TABLE $table_messages (
			message_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			thread_id varchar(64) NOT NULL,
			sender_id bigint(20) unsigned NOT NULL,
			recipient_id bigint(20) unsigned NOT NULL,
			listing_id bigint(20) unsigned,
			message_content text NOT NULL,
			is_read tinyint(1) DEFAULT 0,
			read_at datetime,
			parent_message_id bigint(20) unsigned,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (message_id),
			KEY idx_thread (thread_id),
			KEY idx_recipient (recipient_id, is_read),
			KEY idx_created (created_at)
		) $charset_collate;";

		// Table 5: wp_rental_saved_listings
		$table_saved_listings = $wpdb->prefix . 'rental_saved_listings';
		$sql_saved_listings = "CREATE TABLE $table_saved_listings (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			student_id bigint(20) unsigned NOT NULL,
			listing_id bigint(20) unsigned NOT NULL,
			saved_at datetime DEFAULT CURRENT_TIMESTAMP,
			notes text,
			PRIMARY KEY  (id),
			UNIQUE KEY unique_student_listing (student_id, listing_id),
			KEY idx_student (student_id)
		) $charset_collate;";

		// Table 6: wp_rental_reports
		$table_reports = $wpdb->prefix . 'rental_reports';
		$sql_reports = "CREATE TABLE $table_reports (
			report_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			reporter_id bigint(20) unsigned NOT NULL,
			reported_entity_type varchar(20) NOT NULL,
			reported_entity_id bigint(20) unsigned NOT NULL,
			report_category varchar(50) NOT NULL,
			report_description text,
			report_status varchar(20) DEFAULT 'pending',
			moderator_id bigint(20) unsigned,
			moderator_notes text,
			action_taken varchar(100),
			submitted_at datetime DEFAULT CURRENT_TIMESTAMP,
			resolved_at datetime,
			PRIMARY KEY  (report_id),
			KEY idx_entity (reported_entity_type, reported_entity_id),
			KEY idx_status (report_status)
		) $charset_collate;";

		dbDelta($sql_applications);
		dbDelta($sql_availability);
		dbDelta($sql_verification);
		dbDelta($sql_messages);
		dbDelta($sql_saved_listings);
		dbDelta($sql_reports);
	}
}
