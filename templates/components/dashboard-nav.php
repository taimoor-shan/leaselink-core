<?php
/**
 * Dashboard Sidebar Navigation
 *
 * Usage: SRP_Template_Loader::get_template('components/dashboard-nav.php', ['role' => 'student']);
 *
 * @package LeaseLink
 */

$role = $role ?? 'student';
$current_slug = get_post_field('post_name', get_queried_object_id());
$user = wp_get_current_user();

$student_nav = [
    ['slug' => 'student-dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['slug' => 'my-applications', 'label' => 'Applications', 'icon' => 'inbox'],
    ['slug' => 'saved-listings', 'label' => 'Saved Listings', 'icon' => 'heart'],
    ['slug' => 'messages', 'label' => 'Messages', 'icon' => 'message'],
    ['slug' => 'profile-settings', 'label' => 'Settings', 'icon' => 'settings'],
];

$landlord_nav = [
    ['slug' => 'landlord-dashboard', 'label' => 'Dashboard', 'icon' => 'grid'],
    ['slug' => 'my-properties', 'label' => 'Properties', 'icon' => 'building'],
    ['slug' => 'add-property', 'label' => 'Add Property', 'icon' => 'plus'],
    ['slug' => 'landlord-applications', 'label' => 'Applications', 'icon' => 'inbox'],
    ['slug' => 'verification', 'label' => 'Verification', 'icon' => 'shield'],
    ['slug' => 'profile-settings', 'label' => 'Settings', 'icon' => 'settings'],
];

$nav_items = ($role === 'landlord') ? $landlord_nav : $student_nav;

$svg_icons = [
    'grid' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />',
    'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
    'heart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />',
    'message' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />',
    'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />',
    'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />',
    'plus' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
    'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />',
];
?>

<aside class="ll-dashboard-sidebar">
    <!-- User Info -->
    <div class="mb-6 pb-6 border-b border-zinc-200">
        <div class="flex items-center gap-3">
            <div
                class="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-semibold text-sm">
                <?php echo esc_html(strtoupper(substr($user->display_name, 0, 2))); ?>
            </div>
            <div>
                <p class="font-semibold text-sm text-dark mb-0">
                    <?php echo esc_html($user->display_name); ?>
                </p>
                <p class="text-xs text-gray mb-0 capitalize">
                    <?php echo esc_html($role); ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="space-y-1">
        <?php foreach ($nav_items as $item):
            $is_active = ($current_slug === $item['slug']);
            $active_classes = $is_active
                ? 'bg-primary/10 text-primary font-medium'
                : 'text-zinc-600 hover:bg-zinc-100 hover:text-dark';
            $icon_svg = $svg_icons[$item['icon']] ?? '';
            ?>
            <a href="<?php echo esc_url(home_url('/' . $item['slug'] . '/')); ?>"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors no-underline <?php echo esc_attr($active_classes); ?>">
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php echo $icon_svg; ?>
                </svg>
                <?php echo esc_html($item['label']); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Back to Site -->
    <div class="mt-8 pt-6 border-t border-zinc-200">
        <a href="<?php echo esc_url(home_url('/')); ?>"
            class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-zinc-500 hover:text-dark hover:bg-zinc-100 transition-colors no-underline">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Site
        </a>
    </div>
</aside>