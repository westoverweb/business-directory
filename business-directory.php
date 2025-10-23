<?php
/**
 * Plugin Name: Business Directory
 * Plugin URI: https://chelseabusiness.com
 * Description: A complete business directory solution with search and filtering capabilities for ACF-powered business listings.
 * Version: 2.0.0
 * Author: Ben DeLoach
 * License: GPL v2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('BUSINESS_DIRECTORY_VERSION', '2.0.1'); // Bump this version
define('BUSINESS_DIRECTORY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('BUSINESS_DIRECTORY_PLUGIN_URL', plugin_dir_url(__FILE__));

class BusinessDirectoryPlugin {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        // Load files first
        $this->load_includes();
        
        // Then initialize classes
        if (class_exists('BusinessDirectory_Shortcodes')) {
            new BusinessDirectory_Shortcodes();
        }
        if (class_exists('BusinessDirectory_Admin')) {
            new BusinessDirectory_Admin();
        }
        
        // Debug shortcode registration
        if (shortcode_exists('business_directory')) {
            error_log('Business Directory: business_directory shortcode registered successfully');
        } else {
            error_log('Business Directory: business_directory shortcode NOT registered');
        }
        
        if (shortcode_exists('business_category_template')) {
            error_log('Business Directory: business_category_template shortcode registered successfully');
        } else {
            error_log('Business Directory: business_category_template shortcode NOT registered');
        }
        
        add_action('admin_post_business_listing_submit', array($this, 'handle_business_submission'));
        add_action('admin_post_nopriv_business_listing_submit', array($this, 'handle_business_submission'));
    }
    
    public function load_includes() {
        // Load files in correct order
        require_once BUSINESS_DIRECTORY_PLUGIN_DIR . 'includes/class-queries.php';
        require_once BUSINESS_DIRECTORY_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once BUSINESS_DIRECTORY_PLUGIN_DIR . 'includes/class-admin.php';
        
        // Debug: Check if files exist
        if (!class_exists('BusinessDirectory_Queries')) {
            error_log('Business Directory: class-queries.php not loaded properly');
        }
        if (!class_exists('BusinessDirectory_Shortcodes')) {
            error_log('Business Directory: class-shortcodes.php not loaded properly');
        }
        if (!class_exists('BusinessDirectory_Admin')) {
            error_log('Business Directory: class-admin.php not loaded properly');
        }
    }
    
    public function enqueue_scripts() {
        wp_enqueue_style(
            'business-directory-style',
            BUSINESS_DIRECTORY_PLUGIN_URL . 'assets/style.css',
            array(),
            BUSINESS_DIRECTORY_VERSION
        );
        
        wp_enqueue_script(
            'business-directory-script',
            BUSINESS_DIRECTORY_PLUGIN_URL . 'assets/script.js',
            array('jquery'),
            BUSINESS_DIRECTORY_VERSION,
            true
        );
    }
    
    public function activate() {
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    public function handle_business_submission() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['business_nonce'], 'submit_business_listing')) {
        wp_die('Security check failed');
    }
    
    // Sanitize input
    $business_name = sanitize_text_field($_POST['business_name']);
    $business_email = sanitize_email($_POST['business_email']);
    $business_phone = sanitize_text_field($_POST['business_phone']);
    $business_website = esc_url_raw($_POST['business_website']);
    $business_address = sanitize_textarea_field($_POST['business_address']);
    $business_description = sanitize_textarea_field($_POST['business_description']);
    $search_keywords = sanitize_textarea_field($_POST['search_keywords']);
    $business_categories = isset($_POST['business_categories']) ? array_map('sanitize_text_field', $_POST['business_categories']) : array();
    $google_business_url = esc_url_raw($_POST['google_business_url']);
    
    // Handle logo upload
$logo_id = 0;
if (!empty($_FILES['business_logo']['name'])) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $uploadedfile = $_FILES['business_logo'];
    $upload_overrides = array('test_form' => false);
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);
    
    if ($movefile && !isset($movefile['error'])) {
        $attachment = array(
            'post_mime_type' => $movefile['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($movefile['file'])),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attachment_id = wp_insert_attachment($attachment, $movefile['file']);
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $movefile['file']);
        wp_update_attachment_metadata($attachment_id, $attachment_data);
        $logo_id = $attachment_id;
    }
}
    
    // Basic validation
    $errors = array();
    if (empty($business_name)) $errors[] = 'Business name is required';
    if (empty($business_email) || !is_email($business_email)) $errors[] = 'Valid email is required';
    if (empty($business_description)) $errors[] = 'Business description is required';
    if (empty($business_categories)) $errors[] = 'Please select at least one category';
    
    if (!empty($errors)) {
        $redirect_url = wp_get_referer();
        $redirect_url = add_query_arg('form_error', urlencode(implode(', ', $errors)), $redirect_url);
        wp_redirect($redirect_url);
        exit;
    }
    
    // Create the post
    $post_data = array(
        'post_title' => $business_name,
        'post_content' => $business_description,
        'post_type' => 'business-listing',
        'post_status' => 'pending',
        'meta_input' => array(
            '_submitter_email' => $business_email,
            '_submission_date' => current_time('mysql'),
        )
    );
    
    $post_id = wp_insert_post($post_data);
    
    if (!is_wp_error($post_id)) {
        // Save ACF fields
        if (function_exists('update_field')) {
            update_field('business_phone', $business_phone, $post_id);
            update_field('business_email', $business_email, $post_id);
            update_field('business_website', $business_website, $post_id);
            update_field('business_address', $business_address, $post_id);
            update_field('business_description', $business_description, $post_id);
            update_field('search_keywords', $search_keywords, $post_id);
            update_field('google_business_url', $google_business_url, $post_id);
            
            // Save the logo if uploaded
if ($logo_id) {
    update_field('business_logo', $logo_id, $post_id);
}
        }
        
        // Set categories
        if (!empty($business_categories)) {
            $category_ids = array();
            foreach ($business_categories as $cat_name) {
                $term = get_term_by('name', $cat_name, 'business-category');
                if ($term) {
                    $category_ids[] = $term->term_id;
                }
            }
            if (!empty($category_ids)) {
                wp_set_object_terms($post_id, $category_ids, 'business-category');
            }
        }
        
        // Send admin notification
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $admin_subject = "[{$site_name}] New Business Directory Submission - LEAD OPPORTUNITY";
        $admin_message = "New business submission:\n\n";
        $admin_message .= "Business: {$business_name}\n";
        $admin_message .= "Email: {$business_email}\n";
        $admin_message .= "Review: " . admin_url("post.php?post={$post_id}&action=edit") . "\n\n";
        $admin_message .= "This is a potential web development lead!";
        
        wp_mail($admin_email, $admin_subject, $admin_message);
    }
    
    // Redirect back with success message
    $redirect_url = wp_get_referer();
    $redirect_url = add_query_arg('form_success', 'submitted', $redirect_url);
    wp_redirect($redirect_url);
    exit;
}
}

// Initialize the plugin
new BusinessDirectoryPlugin();
?>