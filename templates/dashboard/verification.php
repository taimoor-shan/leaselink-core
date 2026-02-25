<?php
/**
 * Verification — Landlord verification workflow
 *
 * @package LeaseLink
 */

use StudentRentalPlatform\SRP_Template_Loader;

if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();

$user_id = get_current_user_id();
$nonce = wp_create_nonce('wp_rest');

global $wpdb;
$verification_table = $wpdb->prefix . 'landlord_verification';
$verification = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$verification_table} WHERE landlord_id = %d ORDER BY verification_level DESC LIMIT 1",
    $user_id
));

$current_level = $verification ? absint($verification->verification_level) : 0;
$current_status = $verification ? $verification->verification_status : 'unverified';

$levels = [
    ['level' => 1, 'title' => 'Email Verification', 'description' => 'Verify your email address'],
    ['level' => 2, 'title' => 'Phone Verification', 'description' => 'Add and verify a phone number'],
    ['level' => 3, 'title' => 'Document Upload', 'description' => 'Upload government ID and proof of ownership'],
    ['level' => 4, 'title' => 'Admin Review', 'description' => 'Documents reviewed and approved by admin'],
    ['level' => 5, 'title' => 'Premium Verified', 'description' => 'Enhanced trust badge and priority listing'],
];

// Get verification history
$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$verification_table} WHERE landlord_id = %d ORDER BY submitted_at DESC",
    $user_id
));
?>

<div class="ll-dashboard">
    <?php SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'landlord']); ?>

    <div class="ll-dashboard-content" x-data="verificationPage()">
        <div class="mb-8">
            <h1 class="text-2xl font-bold mb-1">Verification</h1>
            <p class="text-gray">Complete verification steps to publish listings and build trust with students.</p>
        </div>

        <!-- Verification Progress -->
        <div class="bg-white rounded-xl border border-zinc-100 shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-semibold mb-0">Verification Progress</h2>
                <span
                    class="text-sm font-medium px-3 py-1 rounded-full
                    <?php echo $current_level >= 4 ? 'bg-success/10 text-success' : ($current_level >= 2 ? 'bg-warning/10 text-warning' : 'bg-danger/10 text-danger'); ?>">
                    Level
                    <?php echo esc_html($current_level); ?>/5
                </span>
            </div>

            <!-- Progress Steps -->
            <div class="space-y-0">
                <?php foreach ($levels as $idx => $lvl):
                    $is_complete = $current_level >= $lvl['level'];
                    $is_active = $current_level === $lvl['level'] - 1;
                    $is_pending = $current_level === $lvl['level'] && $current_status === 'pending';
                    ?>
                    <div class="flex items-start gap-4 <?php echo $idx < count($levels) - 1 ? 'pb-6' : ''; ?> relative">
                        <!-- Connector Line -->
                        <?php if ($idx < count($levels) - 1): ?>
                            <div class="absolute left-[15px] top-[36px] w-0.5 h-[calc(100%-20px)]
                                <?php echo $is_complete ? 'bg-success' : 'bg-zinc-200'; ?>"></div>
                        <?php endif; ?>

                        <!-- Step Icon -->
                        <div
                            class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 text-sm font-semibold relative z-10
                            <?php echo $is_complete ? 'bg-success text-white' : ($is_active || $is_pending ? 'bg-primary text-white' : 'bg-zinc-200 text-zinc-500'); ?>">
                            <?php if ($is_complete): ?>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            <?php elseif ($is_pending): ?>
                                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            <?php else: ?>
                                <?php echo esc_html($lvl['level']); ?>
                            <?php endif; ?>
                        </div>

                        <!-- Step Content -->
                        <div class="flex-1 pt-0.5">
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="text-sm font-semibold text-dark mb-0">
                                    <?php echo esc_html($lvl['title']); ?>
                                </h3>
                                <?php if ($is_pending): ?>
                                    <?php SRP_Template_Loader::get_template('components/badge.php', ['text' => 'Pending Review', 'color' => 'warning']); ?>
                                <?php elseif ($is_complete): ?>
                                    <?php SRP_Template_Loader::get_template('components/badge.php', ['text' => 'Complete', 'color' => 'success']); ?>
                                <?php endif; ?>
                            </div>
                            <p class="text-xs text-gray mb-0">
                                <?php echo esc_html($lvl['description']); ?>
                            </p>

                            <?php if ($is_active && $lvl['level'] <= 3): ?>
                                <div class="mt-3">
                                    <?php if ($lvl['level'] === 1): ?>
                                        <button @click="sendVerificationEmail()" class="ll-btn ll-btn-primary ll-btn-sm"
                                            :disabled="emailSending">
                                            <span x-show="!emailSending">Send Verification Email</span>
                                            <span x-show="emailSending">Sending...</span>
                                        </button>
                                        <span x-show="emailSent" x-transition class="ml-2 text-sm text-success">✓ Email sent! Check
                                            your inbox.</span>
                                    <?php elseif ($lvl['level'] === 2): ?>
                                        <div class="flex gap-2 items-end">
                                            <div>
                                                <label class="block text-xs font-medium mb-1">Phone Number</label>
                                                <input type="tel" x-model="phoneNumber" class="ll-input" placeholder="+43..."
                                                    style="width: 200px;">
                                            </div>
                                            <button @click="verifyPhone()" class="ll-btn ll-btn-primary ll-btn-sm"
                                                :disabled="phoneSending">Verify Phone</button>
                                        </div>
                                    <?php elseif ($lvl['level'] === 3): ?>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="block text-xs font-medium mb-1">Government ID</label>
                                                <input type="file" @change="files.gov_id = $event.target.files[0]"
                                                    accept="image/*,.pdf"
                                                    class="block w-full text-sm text-gray file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                            </div>
                                            <div>
                                                <label class="block text-xs font-medium mb-1">Proof of Property Ownership</label>
                                                <input type="file" @change="files.ownership = $event.target.files[0]"
                                                    accept="image/*,.pdf"
                                                    class="block w-full text-sm text-gray file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-primary/10 file:text-primary hover:file:bg-primary/20">
                                            </div>
                                            <button @click="uploadDocuments()" class="ll-btn ll-btn-primary ll-btn-sm"
                                                :disabled="uploading || (!files.gov_id && !files.ownership)">
                                                <span x-show="!uploading">Upload Documents</span>
                                                <span x-show="uploading">Uploading...</span>
                                            </button>
                                            <p x-show="uploadError" class="text-sm text-danger mt-1 mb-0" x-text="uploadError"></p>
                                            <p x-show="uploadSuccess" x-transition class="text-sm text-success mt-1 mb-0">✓
                                                Documents uploaded! They will be reviewed by an admin.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Verification History -->
        <?php if (!empty($history)): ?>
            <div class="bg-white rounded-xl border border-zinc-100 shadow-sm">
                <div class="p-5 border-b border-zinc-100">
                    <h2 class="text-lg font-semibold mb-0">Verification History</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50">
                            <tr>
                                <th class="text-left px-5 py-3 font-medium text-gray">Date</th>
                                <th class="text-left px-5 py-3 font-medium text-gray">Level</th>
                                <th class="text-left px-5 py-3 font-medium text-gray">Status</th>
                                <th class="text-left px-5 py-3 font-medium text-gray">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100">
                            <?php foreach ($history as $record):
                                $status_map = [
                                    'approved' => 'success',
                                    'pending' => 'warning',
                                    'rejected' => 'danger',
                                    'unverified' => 'gray',
                                ];
                                ?>
                                <tr>
                                    <td class="px-5 py-3 text-dark">
                                        <?php echo esc_html($record->submitted_at ? date('M j, Y', strtotime($record->submitted_at)) : '—'); ?>
                                    </td>
                                    <td class="px-5 py-3">Level
                                        <?php echo esc_html($record->verification_level); ?>
                                    </td>
                                    <td class="px-5 py-3">
                                        <?php SRP_Template_Loader::get_template('components/badge.php', [
                                            'text' => ucfirst($record->verification_status),
                                            'color' => $status_map[$record->verification_status] ?? 'gray',
                                        ]); ?>
                                    </td>
                                    <td class="px-5 py-3 text-gray">
                                        <?php echo esc_html($record->admin_notes ?: '—'); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Benefits -->
        <div class="mt-6 bg-primary/5 rounded-xl border border-primary/10 p-6">
            <h3 class="text-base font-semibold mb-4">Benefits of Verification</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-dark mb-0.5">Verified Badge</p>
                        <p class="text-xs text-gray mb-0">Stand out with a trust badge on your listings</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-dark mb-0.5">Auto-Approval</p>
                        <p class="text-xs text-gray mb-0">Fully verified landlords get listings auto-approved</p>
                    </div>
                </div>
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-primary shrink-0 mt-0.5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-dark mb-0.5">More Applications</p>
                        <p class="text-xs text-gray mb-0">Students prefer verified landlords</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function verificationPage() {
        return {
            files: { gov_id: null, ownership: null },
            phoneNumber: '',
            emailSending: false, emailSent: false,
            phoneSending: false,
            uploading: false, uploadError: '', uploadSuccess: false,

            async sendVerificationEmail() {
                this.emailSending = true;
                try {
                    const res = await fetch('<?php echo esc_url(rest_url('rental/v1/verification/email')); ?>', {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                    });
                    if (res.ok) { this.emailSent = true; }
                    else { alert('Failed to send email. Please try again.'); }
                } catch (e) { alert('Something went wrong.'); }
                this.emailSending = false;
            },

            async verifyPhone() {
                if (!this.phoneNumber) return;
                this.phoneSending = true;
                try {
                    const res = await fetch('<?php echo esc_url(rest_url('rental/v1/verification/phone')); ?>', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                        body: JSON.stringify({ phone: this.phoneNumber }),
                    });
                    if (res.ok) { location.reload(); }
                    else { alert('Failed to verify phone.'); }
                } catch (e) { alert('Something went wrong.'); }
                this.phoneSending = false;
            },

            async uploadDocuments() {
                this.uploading = true;
                this.uploadError = '';
                this.uploadSuccess = false;
                try {
                    const formData = new FormData();
                    if (this.files.gov_id) formData.append('gov_id', this.files.gov_id);
                    if (this.files.ownership) formData.append('ownership', this.files.ownership);

                    const res = await fetch('<?php echo esc_url(rest_url('rental/v1/verification/documents')); ?>', {
                        method: 'POST',
                        headers: { 'X-WP-Nonce': '<?php echo $nonce; ?>' },
                        body: formData,
                    });
                    if (res.ok) {
                        this.uploadSuccess = true;
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        const data = await res.json();
                        this.uploadError = data.message || 'Upload failed. Please try again.';
                    }
                } catch (e) { this.uploadError = 'Something went wrong.'; }
                this.uploading = false;
            }
        };
    }
</script>

<?php get_footer(); ?>