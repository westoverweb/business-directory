<?php
/**
 * Business Card Template
 * Used by both main directory and category template shortcodes
 */

if (!defined('ABSPATH')) {
    exit;
}

// Variables available:
// $business_fields - array of ACF field values
// $categories - array with 'names' and 'slugs'
// $search_content - prepared search terms
// $atts - shortcode attributes
// $show_categories - whether to display category tags (default true)

if (!isset($show_categories)) {
    $show_categories = true;
}
?>

<div class="business-card" 
     data-categories="<?php echo esc_attr(implode(' ', $categories['slugs'])); ?>"
     data-search-terms="<?php echo esc_attr($search_content); ?>">
    
    <?php if ($business_fields['logo']): ?>
    <div class="business-logo">
        <?php if (is_array($business_fields['logo'])): ?>
            <img src="<?php echo esc_url($business_fields['logo']['url']); ?>" 
                 alt="<?php echo esc_attr($business_fields['logo']['alt'] ?? get_the_title()); ?>">
        <?php else: ?>
            <img src="<?php echo esc_url($business_fields['logo']); ?>" 
                 alt="<?php echo esc_attr(get_the_title()); ?>">
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <div class="business-content">
        <h3 class="business-title">
            <a href="<?php the_permalink(); ?>">
                <?php the_title(); ?>
            </a>
        </h3>
        
        <?php if ($show_categories && !empty($categories['names'])): ?>
        <div class="business-categories">
            <?php foreach ($categories['names'] as $cat_name): ?>
                <span class="category-tag"><?php echo esc_html($cat_name); ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_excerpt'] === 'true'): ?>
        <div class="business-excerpt">
            <?php 
            $excerpt_text = $business_fields['description'] ?: get_the_content();
            
            if ($excerpt_text) {
                $excerpt_text = wp_strip_all_tags($excerpt_text);
                if (strlen($excerpt_text) > $atts['excerpt_length']) {
                    $excerpt_text = substr($excerpt_text, 0, $atts['excerpt_length']) . '...';
                }
                echo esc_html($excerpt_text);
            }
            ?>
        </div>
        <?php endif; ?>
        
        <div class="business-contact">
            
            <?php if ($business_fields['address']): ?>
            <div class="contact-item">
                <span class="contact-icon">📍</span>
                <span><?php echo nl2br(esc_html($business_fields['address'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($business_fields['phone']): ?>
            <div class="contact-item">
                <span class="contact-icon">📞</span>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $business_fields['phone'])); ?>">
                    <?php echo esc_html($business_fields['phone']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($business_fields['email']): ?>
            <div class="contact-item">
                <span class="contact-icon">✉️</span>
                <a href="mailto:<?php echo esc_attr($business_fields['email']); ?>">
                    <?php echo esc_html($business_fields['email']); ?>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ($business_fields['website']): ?>
            <div class="contact-item">
                <span class="contact-icon">🌐</span>
                <a href="<?php echo esc_url($business_fields['website']); ?>" target="_blank" rel="noopener">
                    Visit Website
                </a>
            </div>
            <?php endif; ?>
            
        </div>
        
        <div class="business-actions">
            <a href="<?php the_permalink(); ?>" class="view-details-btn">
                View Details
            </a>
        </div>
    </div>
    
</div>