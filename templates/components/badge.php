<?php
/**
 * Component: Badge
 *
 * Plugin default template — any theme can override
 * by placing a copy in yourtheme/leaselink/components/badge.php
 *
 * @param string $text  Badge text.
 * @param string $color Color variant: primary, success, warning, danger, gray, accent.
 * @param string $size  Size: sm, md.
 *
 * @package LeaseLink
 * @since   1.1.0
 */

$text = $text ?? ($args['text'] ?? '');
$color = $color ?? ($args['color'] ?? 'gray');
$size_class = ($size ?? ($args['size'] ?? 'sm')) === 'md' ? 'padding:0.25rem 0.625rem;font-size:0.8125rem;' : '';

if (empty($text)) {
    return;
}
?>
<span class="ll-badge ll-badge-<?php echo esc_attr($color); ?>" style="<?php echo esc_attr($size_class); ?>">
    <?php echo esc_html($text); ?>
</span>