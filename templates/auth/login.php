<?php
/**
 * Custom Login Page
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

$redirect_to = isset($_GET['redirect_to']) ? esc_url($_GET['redirect_to']) : '';
$login_error = isset($_GET['login_error']) ? sanitize_text_field($_GET['login_error']) : '';
$registered = isset($_GET['registered']) ? true : false;
$nonce = wp_create_nonce('leaselink_login');
?>

<section class="min-h-screen flex items-center justify-center bg-surface py-12 px-4" x-data="loginForm()">
    <div class="w-full max-w-md">
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
            <h1 class="text-2xl font-bold text-dark mt-4 mb-1">Welcome back</h1>
            <p class="text-gray text-sm">Sign in to your account to continue</p>
        </div>

        <!-- Success: just registered -->
        <?php if ($registered): ?>
            <div
                class="mb-5 p-4 rounded-xl bg-success/10 border border-success/20 text-sm text-success flex items-start gap-3">
                <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Account created successfully! Please sign in.</span>
            </div>
        <?php endif; ?>

        <!-- Login Card -->
        <div class="bg-white rounded-2xl border border-zinc-100 shadow-lg p-8">
            <!-- Server Error -->
            <?php if ($login_error): ?>
                <div class="mb-5 p-3 rounded-lg bg-danger/10 border border-danger/20 text-sm text-danger">
                    <?php echo esc_html($login_error); ?>
                </div>
            <?php endif; ?>

            <!-- Client Error -->
            <div x-show="error" x-transition
                class="mb-5 p-3 rounded-lg bg-danger/10 border border-danger/20 text-sm text-danger" x-text="error">
            </div>

            <form @submit.prevent="handleLogin()">
                <div class="space-y-5">
                    <div>
                        <label for="ll-login-email" class="block text-sm font-medium text-dark mb-1.5">Email or
                            Username</label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input id="ll-login-email" type="text" x-model="username" required autocomplete="username"
                                class="ll-input pl-11" placeholder="email@example.com">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="ll-login-password" class="block text-sm font-medium text-dark">Password</label>
                            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>"
                                class="text-xs text-primary hover:underline no-underline">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="ll-login-password" :type="showPass ? 'text' : 'password'" x-model="password"
                                required autocomplete="current-password" class="ll-input pl-11 pr-11"
                                placeholder="••••••••">
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
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="remember"
                            class="w-4 h-4 rounded border-zinc-300 text-primary focus:ring-primary">
                        <span class="text-sm text-zinc-600">Remember me</span>
                    </label>

                    <button type="submit" class="ll-btn ll-btn-primary w-full justify-center py-3 text-base"
                        :disabled="loading">
                        <span x-show="!loading">Sign In</span>
                        <span x-show="loading" class="flex items-center gap-2">
                            <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Signing in...
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Sign Up Link -->
        <p class="text-center text-sm text-gray mt-6">
            Don't have an account?
            <a href="<?php echo home_url('/signup/'); ?>"
                class="text-primary font-medium no-underline hover:underline">Create account</a>
        </p>
    </div>
</section>

<script>
    function loginForm() {
        return {
            username: '', password: '', remember: false,
            showPass: false, loading: false, error: '',

            async handleLogin() {
                this.error = '';
                if (!this.username || !this.password) {
                    this.error = 'Please enter your email and password.';
                    return;
                }
                this.loading = true;
                try {
                    const form = new FormData();
                    form.append('action', 'leaselink_login');
                    form.append('username', this.username);
                    form.append('password', this.password);
                    form.append('remember', this.remember ? '1' : '0');
                    form.append('nonce', '<?php echo $nonce; ?>');
                    form.append('redirect_to', '<?php echo esc_js($redirect_to); ?>');

                    const res = await fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST', body: form,
                    });
                    const data = await res.json();
                    if (data.success) {
                        window.location.href = data.data.redirect;
                    } else {
                        this.error = data.data?.message || 'Invalid email or password.';
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