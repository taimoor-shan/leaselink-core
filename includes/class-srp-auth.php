<?php
/**
 * Custom Auth Handler — AJAX login, signup, and wp-login.php redirects.
 *
 * @package    StudentRentalPlatform
 * @since      1.1.0
 */

namespace StudentRentalPlatform;

class SRP_Auth
{
    /**
     * Register hooks.
     */
    public function __construct()
    {
        // AJAX login (logged-out users only).
        add_action('wp_ajax_nopriv_leaselink_login', [$this, 'ajax_login']);

        // AJAX signup (logged-out users only).
        add_action('wp_ajax_nopriv_leaselink_signup', [$this, 'ajax_signup']);

        // Redirect wp-login.php to custom pages.
        add_action('login_init', [$this, 'redirect_login_page']);
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
        add_filter('register_url', [$this, 'filter_register_url']);
        add_filter('logout_redirect', [$this, 'after_logout_redirect'], 10, 3);

        // After login, redirect by role.
        add_filter('login_redirect', [$this, 'role_based_redirect'], 10, 3);

        // Remove WP's built-in redirect of /login → wp-login.php.
        // wp_redirect_admin_locations() is the core function that causes
        // /login → wp-login.php → our redirect_login_page() → /login → loop.
        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);
    }

    /**
     * AJAX Login Handler.
     */
    public function ajax_login()
    {
        // Verify nonce.
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leaselink_login')) {
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page.']);
        }

        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = !empty($_POST['remember']);
        $redirect_to = esc_url_raw($_POST['redirect_to'] ?? '');

        if (empty($username) || empty($password)) {
            wp_send_json_error(['message' => 'Please enter your email and password.']);
        }

        $user = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ], is_ssl());

        if (is_wp_error($user)) {
            $code = $user->get_error_code();
            $messages = [
                'invalid_username' => 'No account found with this email or username.',
                'invalid_email' => 'No account found with this email.',
                'incorrect_password' => 'Incorrect password. Please try again.',
            ];
            wp_send_json_error(['message' => $messages[$code] ?? 'Invalid credentials. Please try again.']);
        }

        // Determine redirect.
        if ($redirect_to) {
            $redirect = $redirect_to;
        } else {
            $redirect = in_array('landlord', (array) $user->roles)
                ? home_url('/landlord-dashboard/')
                : home_url('/student-dashboard/');
        }

        wp_send_json_success(['redirect' => $redirect]);
    }

    /**
     * AJAX Signup Handler.
     */
    public function ajax_signup()
    {
        // Verify nonce.
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'leaselink_signup')) {
            wp_send_json_error(['message' => 'Security check failed. Please refresh the page.']);
        }

        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = sanitize_text_field($_POST['role'] ?? 'student');

        // Validate.
        if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            wp_send_json_error(['message' => 'All fields are required.']);
        }

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }

        if (strlen($password) < 8) {
            wp_send_json_error(['message' => 'Password must be at least 8 characters.']);
        }

        if (!in_array($role, ['student', 'landlord'])) {
            wp_send_json_error(['message' => 'Invalid role selected.']);
        }

        if (email_exists($email)) {
            wp_send_json_error(['message' => 'An account with this email already exists.']);
        }

        if (username_exists($email)) {
            wp_send_json_error(['message' => 'This username is already taken.']);
        }

        // Create user.
        $user_id = wp_insert_user([
            'user_login' => $email,
            'user_email' => $email,
            'user_pass' => $password,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
            'role' => $role,
        ]);

        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }

        // Trigger new user notification.
        wp_new_user_notification($user_id, null, 'both');

        // Redirect to login with success flag.
        wp_send_json_success([
            'redirect' => home_url('/login/?registered=1'),
        ]);
    }

    /**
     * Redirect wp-login.php to custom login page.
     * Only intercept GET requests (not POST — let wp-login.php handle the actual
     * WordPress cookie auth, password resets, etc.).
     */
    public function redirect_login_page()
    {
        $action = $_REQUEST['action'] ?? '';

        // Allow certain wp-login.php actions to proceed normally.
        $allowed_actions = ['logout', 'lostpassword', 'rp', 'resetpass', 'postpass', 'confirmaction'];
        if (in_array($action, $allowed_actions, true)) {
            return;
        }

        // Don't redirect POST requests (form submissions).
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // Don't redirect admin/CLI calls.
        if (defined('DOING_AJAX') || defined('WP_CLI') || defined('XMLRPC_REQUEST')) {
            return;
        }

        // Prevent redirect loop: if the referer is already our login page, bail.
        $referer = wp_get_referer();
        $login_path = '/login/';
        if ($referer && strpos($referer, $login_path) !== false) {
            return;
        }

        // Also check if this request itself came from /login/ (WordPress internally redirects /login → wp-login.php).
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/login') !== false && strpos($request_uri, 'wp-login.php') === false) {
            return;
        }

        // Redirect to custom login.
        $login_url = home_url('/login/');
        if (!empty($_GET['redirect_to'])) {
            $login_url = add_query_arg('redirect_to', urlencode($_GET['redirect_to']), $login_url);
        }

        wp_safe_redirect($login_url);
        exit;
    }

    /**
     * Filter login_url() to return our custom page.
     */
    public function filter_login_url($login_url, $redirect, $force_reauth)
    {
        $url = home_url('/login/');
        if ($redirect) {
            $url = add_query_arg('redirect_to', urlencode($redirect), $url);
        }
        return $url;
    }

    /**
     * Filter register_url() to return our custom page.
     */
    public function filter_register_url($register_url)
    {
        return home_url('/signup/');
    }

    /**
     * After logout, redirect to login page.
     */
    public function after_logout_redirect($redirect_to, $requested, $user)
    {
        return home_url('/login/');
    }

    /**
     * Role-based redirect after standard login.
     */
    public function role_based_redirect($redirect_to, $requested, $user)
    {
        if (!is_wp_error($user) && $user instanceof \WP_User) {
            if (in_array('administrator', (array) $user->roles)) {
                return admin_url();
            }
            if (in_array('landlord', (array) $user->roles)) {
                return home_url('/landlord-dashboard/');
            }
            return home_url('/student-dashboard/');
        }
        return $redirect_to;
    }
}
