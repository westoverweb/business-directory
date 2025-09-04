<?php
/**
 * Category Header Template
 * Used by business category template shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available:
// $current_category - the current category object
// $atts - shortcode attributes
?>

<div class="category-header">
    <h1 class="category-title"><?php echo esc_html($current_category->name); ?></h1>
    
    <?php if ($atts['show_category_description'] === 'true' && !empty($current_category->description)): ?>
    <div class="category-description">
        <?php echo wp_kses_post($current_category->description); ?>
    </div>
    <?php endif; ?>
    
    <div class="category-count">
        <?php echo $current_category->count; ?> business<?php echo $current_category->count !== 1 ? 'es' : ''; ?> found
    </div>
</div>