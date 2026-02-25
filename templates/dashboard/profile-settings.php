<?php
/**
 * Profile Settings — shared for both student and landlord
 *
 * @package LeaseLink
 */

use StudentRentalPlatform\SRP_Template_Loader;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user = wp_get_current_user();
$user_id = $user->ID;
$role = in_array('landlord', $user->roles) ? 'landlord' : 'student';

$phone = get_user_meta($user_id, 'phone', true);
$bio = get_user_meta($user_id, 'description', true);
$notify_applications = get_user_meta($user_id, '_notify_applications', true) !== 'off';
$notify_messages = get_user_meta($user_id, '_notify_messages', true) !== 'off';
$notify_listings = get_user_meta($user_id, '_notify_listings', true) !== 'off';

$nonce = wp_create_nonce('wp_rest');
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => $role]); ?>

    <div class="ll-dashboard-content" x-data="profileSettings()">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-1">Profile Settings</h1>
            <p class="text-gray">Manage your account details, password, and notification preferences.</p>
        </div>

        <!-- Profile Info -->
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-5">Personal Information</h2>
            <div class="space-y-4 max-w-2xl">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xl font-bold">
                        <?php echo esc_html(strtoupper(substr($user->display_name, 0, 2))); ?>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold mb-0">
                            <?php echo esc_html($user->display_name); ?>
                        </h3>
                        <p class="text-sm text-gray mb-0 capitalize">
                            <?php echo esc_html($role); ?> Account
                        </p>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Display Name</label>
                        <input type="text" x-model="profile.display_name" class="ll-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Email Address</label>
                        <input type="email" x-model="profile.email" class="ll-input">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1.5">Phone Number</label>
                        <input type="tel" x-model="profile.phone" class="ll-input" placeholder="+43 ...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1.5">First Name</label>
                        <input type="text" x-model="profile.first_name" class="ll-input">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Bio</label>
                    <textarea x-model="profile.bio" class="ll-textarea" rows="3"
                        placeholder="Tell us about yourself..."></textarea>
                </div>
                <div class="pt-2">
                    <button @click="saveProfile()" class="ll-btn ll-btn-primary" :disabled="savingProfile">
                        <span x-show="!savingProfile">Save Changes</span>
                        <span x-show="savingProfile">Saving...</span>
                    </button>
                    <span x-show="profileSaved" x-transition class="ml-3 text-sm text-success font-medium">✓ Saved
                        successfully</span>
                </div>
            </div>
        </div>

        <!-- Password Change -->
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-5">Change Password</h2>
            <div class="space-y-4 max-w-md">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Current Password</label>
                    <input type="password" x-model="passwords.current" class="ll-input" autocomplete="current-password">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">New Password</label>
                    <input type="password" x-model="passwords.new_pass" class="ll-input" autocomplete="new-password">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1.5">Confirm New Password</label>
                    <input type="password" x-model="passwords.confirm" class="ll-input" autocomplete="new-password">
                </div>
                <p x-show="passwordError" class="text-sm text-danger mb-0" x-text="passwordError"></p>
                <div class="pt-2">
                    <button @click="changePassword()" class="ll-btn ll-btn-primary"
                        :disabled="savingPassword || !passwords.current || !passwords.new_pass">
                        <span x-show="!savingPassword">Update Password</span>
                        <span x-show="savingPassword">Updating...</span>
                    </button>
                    <span x-show="passwordSaved" x-transition class="ml-3 text-sm text-success font-medium">✓ Password
                        updated</span>
                </div>
            </div>
        </div>

        <!-- Notification Preferences -->
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 mb-6">
            <h2 class="text-lg font-semibold mb-5">Notification Preferences</h2>
            <div class="space-y-4 max-w-md">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" x-model="notifications.applications"
                        class="w-4 h-4 rounded border-zinc-300 text-primary focus:ring-primary">
                    <div>
                        <p class="text-sm font-medium text-dark mb-0">Application Updates</p>
                        <p class="text-xs text-gray mb-0">Get notified when applications change status</p>
                    </div>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" x-model="notifications.messages"
                        class="w-4 h-4 rounded border-zinc-300 text-primary focus:ring-primary">
                    <div>
                        <p class="text-sm font-medium text-dark mb-0">New Messages</p>
                        <p class="text-xs text-gray mb-0">Email notification for new messages</p>
                    </div>
                </label>
                <?php if ($role === 'landlord'): ?>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="notifications.listings"
                            class="w-4 h-4 rounded border-zinc-300 text-primary focus:ring-primary">
                        <div>
                            <p class="text-sm font-medium text-dark mb-0">Listing Updates</p>
                            <p class="text-xs text-gray mb-0">Notifications about listing status changes</p>
                        </div>
                    </label>
                <?php endif; ?>
                <div class="pt-2">
                    <button @click="saveNotifications()" class="ll-btn ll-btn-secondary" :disabled="savingNotifs">Save
                        Preferences</button>
                    <span x-show="notifsSaved" x-transition class="ml-3 text-sm text-success font-medium">✓ Saved</span>
                </div>
            </div>
        </div>

        <!-- Account Actions -->
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6">
            <h2 class="text-lg font-semibold mb-5">Account</h2>
            <div class="flex flex-wrap gap-3">
                <a href="<?php echo wp_logout_url(home_url('/')); ?>" class="ll-btn ll-btn-secondary no-underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Log Out
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    function profileSettings() {
        return {
            profile: {
                display_name: '<?php echo esc_js($user->display_name); ?>',
                email: '<?php echo esc_js($user->user_email); ?>',
                first_name: '<?php echo esc_js($user->first_name); ?>',
                phone: '<?php echo esc_js($phone); ?>',
                bio: <?php echo json_encode($bio); ?>,
        },
            passwords: { current: '', new_pass: '', confirm: '' },
        notifications: {
            applications: <?php echo $notify_applications ? 'true' : 'false'; ?>,
                messages: <?php echo $notify_messages ? 'true' : 'false'; ?>,
                    listings: <?php echo $notify_listings ? 'true' : 'false'; ?>,
            },
        savingProfile: false, profileSaved: false,
            savingPassword: false, passwordSaved: false, passwordError: '',
                savingNotifs: false, notifsSaved: false,

                    async saveProfile() {
            this.savingProfile = true;
            this.profileSaved = false;
            try {
                const res = await fetch('<?php echo esc_url(rest_url('wp/v2/users/me')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                    body: JSON.stringify({
                        name: this.profile.display_name,
                        first_name: this.profile.first_name,
                        email: this.profile.email,
                        description: this.profile.bio,
                        meta: { phone: this.profile.phone },
                    }),
                });
                if (res.ok) {
                    this.profileSaved = true;
                    setTimeout(() => this.profileSaved = false, 3000);
                }
            } catch (e) { alert('Failed to save. Please try again.'); }
            this.savingProfile = false;
        },

            async changePassword() {
            this.passwordError = '';
            this.passwordSaved = false;
            if (this.passwords.new_pass !== this.passwords.confirm) {
                this.passwordError = 'New passwords do not match.';
                return;
            }
            if (this.passwords.new_pass.length < 8) {
                this.passwordError = 'Password must be at least 8 characters.';
                return;
            }
            this.savingPassword = true;
            try {
                const res = await fetch('<?php echo esc_url(rest_url('wp/v2/users/me')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                    body: JSON.stringify({ password: this.passwords.new_pass }),
                });
                if (res.ok) {
                    this.passwordSaved = true;
                    this.passwords = { current: '', new_pass: '', confirm: '' };
                    setTimeout(() => this.passwordSaved = false, 3000);
                }
            } catch (e) { this.passwordError = 'Failed to update password.'; }
            this.savingPassword = false;
        },

            async saveNotifications() {
            this.savingNotifs = true;
            this.notifsSaved = false;
            try {
                const res = await fetch('<?php echo esc_url(rest_url('wp/v2/users/me')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                    body: JSON.stringify({
                        meta: {
                            _notify_applications: this.notifications.applications ? 'on' : 'off',
                            _notify_messages: this.notifications.messages ? 'on' : 'off',
                            _notify_listings: this.notifications.listings ? 'on' : 'off',
                        }
                    }),
                });
                if (res.ok) {
                    this.notifsSaved = true;
                    setTimeout(() => this.notifsSaved = false, 3000);
                }
            } catch (e) { alert('Failed to save preferences.'); }
            this.savingNotifs = false;
        }
    };
    }
</script>

<?php get_footer(); ?>