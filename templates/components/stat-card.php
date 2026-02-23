<?php
/**
 * Component: Stat Card
 *
 * Plugin default template — any theme can override.
 *
 * @param string $label  Stat label.
 * @param mixed  $value  Stat value.
 * @param string $icon   Icon name.
 * @param string $color  primary|success|warning|danger|accent.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

$label = $label ?? ($args['label'] ?? '');
$value = $value ?? ($args['value'] ?? 0);
$color = $color ?? ($args['color'] ?? 'primary');

$colors = [
    'primary' => ['bg' => 'rgba(79,70,229,0.1)', 'fg' => 'var(--ll-primary)'],
    'success' => ['bg' => 'rgba(16,185,129,0.1)', 'fg' => 'var(--ll-success)'],
    'warning' => ['bg' => 'rgba(245,158,11,0.1)', 'fg' => 'var(--ll-warning)'],
    'danger' => ['bg' => 'rgba(239,68,68,0.1)', 'fg' => 'var(--ll-danger)'],
    'accent' => ['bg' => 'rgba(13,148,136,0.1)', 'fg' => 'var(--ll-accent)'],
];
$c = $colors[$color] ?? $colors['primary'];
?>
<div class="ll-stat-card">
    <div style="display:flex;align-items:center;gap:1rem;">
        <div class="ll-stat-card-icon" style="background:<?php echo $c['bg']; ?>;color:<?php echo $c['fg']; ?>;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
        </div>
        <div>
            <p class="ll-stat-card-label">
                <?php echo esc_html($label); ?>
            </p>
            <p class="ll-stat-card-value">
                <?php echo esc_html($value); ?>
            </p>
        </div>
    </div>
</div>