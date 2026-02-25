<?php
/**
 * Badge Component
 *
 * @package LeaseLink
 */

$text = $text ?? 'Status';
$color = $color ?? 'gray';

$color_map = [
    'primary' => 'bg-primary/10 text-primary',
    'success' => 'bg-success/10 text-success',
    'warning' => 'bg-warning/10 text-warning',
    'danger' => 'bg-danger/10 text-danger',
    'gray' => 'bg-zinc-100 text-zinc-500',
    'accent' => 'bg-accent/10 text-accent',
];
$classes = $color_map[$color] ?? $color_map['gray'];
?>

<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo esc_attr($classes); ?>">
    <?php echo esc_html($text); ?>
</span>