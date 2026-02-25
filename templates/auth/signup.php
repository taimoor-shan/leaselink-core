<?php
/**
 * Custom Signup Page
 *
 * @package LeaseLink
 */

// Already logged in? Redirect to dashboard.
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $redirect = in_array('landlord', $user->roles) ? home_url('/landlord-dashboard/') : home_url('/student-dashboard/');
    wp_redirect($redirect);
    exit;
}

get_header();

$nonce = wp_create_nonce('leaselink_signup');
?>

<section class="min-h-screen flex items-center justify-center bg-surface py-12 px-4" x-data="signupForm()">
    <div class="w-full max-w-lg">
        <!-- Logo / Branding -->
        <div class="text-center mb-8">
            <a href="<?php echo home_url('/'); ?>" class="inline-block no-underline">
                <?php if (has_custom_logo()): ?>
                    <?php the_custom_logo(); ?>
                <?php else: ?>
                    <span class="text-3xl font-bold text-primary">
                        <?php echo esc_html(get_bloginfo('name')); ?>
                    </span>
                <?php endif; ?>
            </a>
            <h1 class="text-2xl font-bold text-dark mt-4 mb-1">Create your account</h1>
            <p class="text-gray text-sm">Join LeaseLink to find or list student housing</p>
        </div>

        <!-- Signup Card -->
        <div class="bg-white rounded-2xl border border-zinc-100 shadow-lg p-8">
            <!-- Error -->
            <div x-show="error" x-transition
                class="mb-5 p-3 rounded-lg bg-danger/10 border border-danger/20 text-sm text-danger" x-text="error">
            </div>

            <form @submit.prevent="handleSignup()">
                <div class="space-y-5">
                    <!-- Role Selection -->
                    <div>
                        <label class="block text-sm font-medium text-dark mb-2.5">I want to</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="role" value="student" x-model="role" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 transition-all text-center
                                    peer-checked:border-primary peer-checked:bg-primary/5
                                    border-zinc-200 hover:border-zinc-300">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-zinc-400 peer-checked:text-primary"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                                    </svg>
                                    <span class="text-sm font-semibold"
                                        :class="role === 'student' ? 'text-primary' : 'text-dark'">Find Housing</span>
                                    <p class="text-xs text-gray mt-0.5 mb-0">I'm a student</p>
                                </div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="role" value="landlord" x-model="role" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 transition-all text-center
                                    peer-checked:border-primary peer-checked:bg-primary/5
                                    border-zinc-200 hover:border-zinc-300">
                                    <svg class="w-8 h-8 mx-auto mb-2 text-zinc-400 peer-checked:text-primary"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                    <span class="text-sm font-semibold"
                                        :class="role === 'landlord' ? 'text-primary' : 'text-dark'">List Property</span>
                                    <p class="text-xs text-gray mt-0.5 mb-0">I'm a landlord</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Name Fields -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="ll-first-name" class="block text-sm font-medium text-dark mb-1.5">First
                                Name</label>
                            <input id="ll-first-name" type="text" x-model="firstName" required autocomplete="given-name"
                                class="ll-input" placeholder="John">
                        </div>
                        <div>
                            <label for="ll-last-name" class="block text-sm font-medium text-dark mb-1.5">Last
                                Name</label>
                            <input id="ll-last-name" type="text" x-model="lastName" required autocomplete="family-name"
                                class="ll-input" placeholder="Doe">
                        </div>
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="ll-signup-email" class="block text-sm font-medium text-dark mb-1.5">Email
                            Address</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            <input id="ll-signup-email" type="email" x-model="email" required autocomplete="email"
                                class="ll-input pl-11" placeholder="email@example.com">
                        </div>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="ll-signup-password"
                            class="block text-sm font-medium text-dark mb-1.5">Password</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="ll-signup-password" :type="showPass ? 'text' : 'password'" x-model="password"
                                required autocomplete="new-password" class="ll-input pl-11 pr-11"
                                placeholder="Min. 8 characters">
                            <button type="button" @click="showPass = !showPass"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600">
                                <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        <!-- Strength meter -->
                        <div class="mt-2 flex gap-1">
                            <div class="h-1 flex-1 rounded-full transition-colors"
                                :class="passwordStrength >= 1 ? (passwordStrength >= 3 ? 'bg-success' : passwordStrength >= 2 ? 'bg-warning' : 'bg-danger') : 'bg-zinc-200'">
                            </div>
                            <div class="h-1 flex-1 rounded-full transition-colors"
                                :class="passwordStrength >= 2 ? (passwordStrength >= 3 ? 'bg-success' : 'bg-warning') : 'bg-zinc-200'">
                            </div>
                            <div class="h-1 flex-1 rounded-full transition-colors"
                                :class="passwordStrength >= 3 ? 'bg-success' : 'bg-zinc-200'"></div>
                            <div class="h-1 flex-1 rounded-full transition-colors"
                                :class="passwordStrength >= 4 ? 'bg-success' : 'bg-zinc-200'"></div>
                        </div>
                        <p class="text-xs mt-1 mb-0"
                            :class="passwordStrength >= 3 ? 'text-success' : passwordStrength >= 2 ? 'text-warning' : 'text-gray'"
                            x-text="passwordStrength >= 4 ? 'Very strong' : passwordStrength >= 3 ? 'Strong' : passwordStrength >= 2 ? 'Fair' : passwordStrength >= 1 ? 'Weak' : 'Enter a password'">
                        </p>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="ll-confirm-password" class="block text-sm font-medium text-dark mb-1.5">Confirm
                            Password</label>
                        <input id="ll-confirm-password" :type="showPass ? 'text' : 'password'" x-model="confirmPassword"
                            required autocomplete="new-password" class="ll-input" placeholder="Repeat password">
                        <p x-show="confirmPassword && password !== confirmPassword"
                            class="text-xs text-danger mt-1 mb-0">Passwords do not match</p>
                    </div>

                    <!-- Terms -->
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" x-model="terms"
                            class="w-4 h-4 rounded border-zinc-300 text-primary focus:ring-primary mt-0.5">
                        <span class="text-sm text-zinc-600">I agree to the
                            <a href="<?php echo home_url('/terms/'); ?>"
                                class="text-primary no-underline hover:underline" target="_blank">Terms of Service</a>
                            and
                            <a href="<?php echo home_url('/privacy/'); ?>"
                                class="text-primary no-underline hover:underline" target="_blank">Privacy Policy</a>
                        </span>
                    </label>

                    <!-- Submit -->
                    <button type="submit" class="ll-btn ll-btn-primary w-full justify-center py-3 text-base"
                        :disabled="loading || !terms || password !== confirmPassword || passwordStrength < 2">
                        <span x-show="!loading">Create Account</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Creating account...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sign In Link -->
        <p class="text-center text-sm text-gray mt-6">
            Already have an account?
            <a href="<?php echo home_url('/login/'); ?>"
                class="text-primary font-medium no-underline hover:underline">Sign in</a>
        </p>
    </div>
</section>

<script>
    function signupForm() {
        return {
            role: 'student', firstName: '', lastName: '', email: '',
            password: '', confirmPassword: '', terms: false,
            showPass: false, loading: false, error: '',

            get passwordStrength() {
                const p = this.password;
                if (!p) return 0;
                let score = 0;
                if (p.length >= 8) score++;
                if (/[a-z]/.test(p) && /[A-Z]/.test(p)) score++;
                if (/\d/.test(p)) score++;
                if (/[^a-zA-Z0-9]/.test(p)) score++;
                return score;
            },

            async handleSignup() {
                this.error = '';

                if (!this.firstName || !this.lastName || !this.email || !this.password) {
                    this.error = 'Please fill in all required fields.';
                    return;
                }
                if (this.password !== this.confirmPassword) {
                    this.error = 'Passwords do not match.';
                    return;
                }
                if (this.password.length < 8) {
                    this.error = 'Password must be at least 8 characters.';
                    return;
                }
                if (!this.terms) {
                    this.error = 'You must agree to the Terms of Service.';
                    return;
                }

                this.loading = true;
                try {
                    const form = new FormData();
                    form.append('action', 'leaselink_signup');
                    form.append('first_name', this.firstName);
                    form.append('last_name', this.lastName);
                    form.append('email', this.email);
                    form.append('password', this.password);
                    form.append('role', this.role);
                    form.append('nonce', '<?php echo $nonce; ?>');

                    const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST', body: form,
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.href = data.data.redirect;
                    } else {
                        this.error = data.data?.message || 'Registration failed. Please try again.';
                    }
                } catch (e) {
                    this.error = 'Something went wrong. Please try again.';
                }
                this.loading = false;
            }
        };
    }
</script>

<?php get_footer(); ?>