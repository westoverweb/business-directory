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
define('BUSINESS_DIRECTORY_VERSION', '1.0.0');
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
}

// Initialize the plugin
new BusinessDirectoryPlugin();
?>