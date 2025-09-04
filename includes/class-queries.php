<?php

if (!defined('ABSPATH')) {
    exit;
}

class BusinessDirectory_Queries {
    
    public static function get_businesses($args = array()) {
        $defaults = array(
            'posts_per_page' => -1,
            'post_type' => 'business-listing',
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
            'category' => '',
            'featured_first' => false,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'post_type' => $args['post_type'],
            'posts_per_page' => $args['posts_per_page'],
            'post_status' => $args['post_status'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
        );
        
        if (!empty($args['category'])) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'business-category',
                    'field' => 'slug',
                    'terms' => $args['category'],
                ),
            );
        }
        
        return new WP_Query($query_args);
    }
    
    public static function get_businesses_by_category($category_id, $args = array()) {
        $defaults = array(
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'post_type' => 'business-listing',
            'posts_per_page' => $args['posts_per_page'],
            'post_status' => 'publish',
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'tax_query' => array(
                array(
                    'taxonomy' => 'business-category',
                    'field' => 'term_id',
                    'terms' => $category_id,
                ),
            ),
        );
        
        return new WP_Query($query_args);
    }
    
    public static function get_business_categories() {
        return get_terms(array(
            'taxonomy' => 'business-category',
            'hide_empty' => true,
        ));
    }
    
    public static function get_business_fields($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $logo = get_field('logo', $post_id) ?: get_field('business_logo', $post_id) ?: get_field('image', $post_id);
        
        if (!$logo) {
            $default_image_id = get_option('business_directory_default_image', '');
            if ($default_image_id) {
                $logo = wp_get_attachment_image_url($default_image_id, 'full');
            }
        }
        
        return array(
            'phone' => get_field('phone', $post_id) ?: get_field('business_phone', $post_id),
            'email' => get_field('email', $post_id) ?: get_field('business_email', $post_id),
            'website' => get_field('website', $post_id) ?: get_field('business_website', $post_id),
            'address' => get_field('address', $post_id) ?: get_field('business_address', $post_id),
            'logo' => $logo,
            'description' => get_field('description', $post_id) ?: get_field('business_description', $post_id),
            'search_keywords' => get_field('search_keywords', $post_id) ?: get_field('business_search_keywords', $post_id),
        );
    }
    
    public static function get_business_categories_for_post($post_id = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        $business_cats = get_the_terms($post_id, 'business-category');
        $cat_data = array(
            'names' => array(),
            'slugs' => array(),
        );
        
        if ($business_cats && !is_wp_error($business_cats)) {
            foreach ($business_cats as $cat) {
                $cat_data['names'][] = $cat->name;
                $cat_data['slugs'][] = $cat->slug;
            }
        }
        
        return $cat_data;
    }
    
    public static function get_business_search_content($post_id = null, $business_fields = null, $categories = null) {
        if (!$post_id) {
            $post_id = get_the_ID();
        }
        
        if (!$business_fields) {
            $business_fields = self::get_business_fields($post_id);
        }
        
        if (!$categories) {
            $categories = self::get_business_categories_for_post($post_id);
        }
        
        $search_content = get_the_title($post_id) . ' ' . get_the_content(null, false, $post_id);
        
        if ($business_fields['description']) {
            $search_content .= ' ' . $business_fields['description'];
        }
        
        if ($business_fields['search_keywords']) {
            $search_content .= ' ' . $business_fields['search_keywords'];
        }
        
        $search_content .= ' ' . implode(' ', $categories['names']);
        
        return strtolower($search_content);
    }
}

?>