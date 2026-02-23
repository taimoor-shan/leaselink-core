<?php
/**
 * Component: Empty State
 *
 * Plugin default template — any theme can override
 * by placing a copy in yourtheme/leaselink/components/empty-state.php
 *
 * @param string $title       Heading text.
 * @param string $description Body text.
 * @param string $icon        Icon name (inbox, heart, building).
 * @param string $action_url  Optional CTA URL.
 * @param string $action_text Optional CTA label.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

$title = $title ?? ($args['title'] ?? 'Nothing here');
$description = $description ?? ($args['description'] ?? '');
$icon = $icon ?? ($args['icon'] ?? 'inbox');
$action_url = $action_url ?? ($args['action_url'] ?? '');
$action_text = $action_text ?? ($args['action_text'] ?? '');

$icons = [
    'inbox' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>',
    'heart' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
    'building' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
];
$svg_path = $icons[$icon] ?? $icons['inbox'];
?>
<div class="ll-empty-state">
    <div class="ll-empty-state-icon">
        <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <?php echo $svg_path; ?>
        </svg>
    </div>
    <p class="ll-empty-state-title">
        <?php echo esc_html($title); ?>
    </p>
    <?php if ($description): ?>
        <p class="ll-empty-state-description">
            <?php echo esc_html($description); ?>
        </p>
    <?php endif; ?>
    <?php if ($action_url): ?>
        <a href="<?php echo esc_url($action_url); ?>" class="ll-btn ll-btn-primary" style="margin-top:1rem;">
            <?php echo esc_html($action_text); ?>
        </a>
    <?php endif; ?>
</div>