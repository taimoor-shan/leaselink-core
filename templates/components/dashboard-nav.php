<?php
/**
 * Component: Dashboard Navigation
 *
 * Plugin default template — any theme can override.
 *
 * @param string $role User role (student|landlord).
 *
 * @package LeaseLink
 * @since   1.1.0
 */

use StudentRentalPlatform\SRP_Frontend;

$role = $role ?? ($args['role'] ?? 'student');
$user = wp_get_current_user();
$current_slug = get_post_field('post_name', get_queried_object_id());

$student_links = [
    ['slug' => 'student-dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['slug' => 'my-applications', 'label' => 'My Applications', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>'],
    ['slug' => 'saved-listings', 'label' => 'Saved Listings', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>'],
];

$landlord_links = [
    ['slug' => 'landlord-dashboard', 'label' => 'Dashboard', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['slug' => 'my-properties', 'label' => 'My Properties', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>'],
    ['slug' => 'add-property', 'label' => 'Add Property', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>'],
    ['slug' => 'landlord-applications', 'label' => 'Applications', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>'],
];

$links = $role === 'landlord' ? $landlord_links : $student_links;
?>
<aside class="ll-dashboard-sidebar">
    <!-- User Info -->
    <div
        style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.5rem;padding-bottom:1.5rem;border-bottom:1px solid var(--ll-border);">
        <div
            style="width:40px;height:40px;border-radius:50%;background:var(--ll-primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:0.875rem;flex-shrink:0;">
            <?php echo esc_html(strtoupper(substr($user->display_name, 0, 2))); ?>
        </div>
        <div>
            <p style="font-weight:600;font-size:0.875rem;color:var(--ll-dark);margin:0;">
                <?php echo esc_html($user->display_name); ?>
            </p>
            <p style="font-size:0.75rem;color:var(--ll-gray);margin:0;text-transform:capitalize;">
                <?php echo esc_html($role); ?>
            </p>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="ll-dashboard-nav" style="display:flex;flex-direction:column;gap:0.25rem;">
        <?php foreach ($links as $link): ?>
            <a href="<?php echo esc_url(home_url('/' . $link['slug'] . '/')); ?>"
                class="<?php echo $current_slug === $link['slug'] ? 'active' : ''; ?>">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <?php echo $link['icon']; ?>
                </svg>
                <?php echo esc_html($link['label']); ?>
            </a>
        <?php endforeach; ?>

        <!-- Divider + Logout -->
        <div style="margin:1rem 0;border-top:1px solid var(--ll-border);"></div>
        <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            Log Out
        </a>
    </nav>
</aside>