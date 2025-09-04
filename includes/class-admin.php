<?php
/**
 * Business Directory Admin Class
 * Handles all admin functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class BusinessDirectory_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function admin_menu() {
        add_submenu_page(
            'edit.php?post_type=business-listing',
            'Directory Settings',
            'Directory Settings',
            'manage_options',
            'business-directory-settings',
            array($this, 'admin_page')
        );
    }
    
    public function register_settings() {
        register_setting('business_directory_settings', 'business_directory_default_image');
        
        add_settings_section(
            'business_directory_general',
            'General Settings',
            null,
            'business_directory_settings'
        );
        
        add_settings_field(
            'default_image',
            'Default Business Image',
            array($this, 'default_image_callback'),
            'business_directory_settings',
            'business_directory_general'
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'business-listing_page_business-directory-settings') {
            return;
        }
        
        wp_enqueue_media();
        wp_enqueue_script(
            'business-directory-admin',
            BUSINESS_DIRECTORY_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            BUSINESS_DIRECTORY_VERSION,
            true
        );
    }
    
    public function default_image_callback() {
        $image_id = get_option('business_directory_default_image', '');
        $image_url = '';
        
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'medium');
        }
        ?>
        <div class="default-image-upload">
            <input type="hidden" id="business_directory_default_image" name="business_directory_default_image" value="<?php echo esc_attr($image_id); ?>" />
            
            <div class="image-preview" style="margin-bottom: 10px;">
                <?php if ($image_url): ?>
                    <img src="<?php echo esc_url($image_url); ?>" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;" />
                <?php else: ?>
                    <div style="width: 200px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #666;">
                        No default image selected
                    </div>
                <?php endif; ?>
            </div>
            
            <p>
                <button type="button" class="button" id="upload-default-image">
                    <?php echo $image_id ? 'Change Image' : 'Upload Image'; ?>
                </button>
                <?php if ($image_id): ?>
                    <button type="button" class="button" id="remove-default-image" style="margin-left: 10px;">Remove Image</button>
                <?php endif; ?>
            </p>
            
            <p class="description">
                This image will be used for any business listings that don't have their own logo/image uploaded. Recommended size: 400x300px or similar ratio.
            </p>
        </div>
        <?php
    }
    
    public function admin_page() {
        // Handle form submission
        if (isset($_POST['submit']) && check_admin_referer('business_directory_settings_nonce', 'business_directory_nonce')) {
            update_option('business_directory_default_image', sanitize_text_field($_POST['business_directory_default_image']));
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved!</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Business Directory Settings</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('business_directory_settings_nonce', 'business_directory_nonce'); ?>
                
                <div class="card">
                    <h2>Default Business Image</h2>
                    <?php $this->default_image_callback(); ?>
                    
                    <?php submit_button('Save Settings'); ?>
                </div>
            </form>
            
            <div class="card">
                <h2>Main Directory Shortcode</h2>
                <p>Use this shortcode to display your complete business directory:</p>
                <code>[business_directory]</code>
                
                <h3>Available Parameters:</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>columns</code></td>
                            <td>3</td>
                            <td>Number of columns (1-4)</td>
                        </tr>
                        <tr>
                            <td><code>posts_per_page</code></td>
                            <td>-1</td>
                            <td>Number of businesses to show (-1 for all)</td>
                        </tr>
                        <tr>
                            <td><code>category</code></td>
                            <td></td>
                            <td>Show only specific category (use category slug)</td>
                        </tr>
                        <tr>
                            <td><code>show_filter</code></td>
                            <td>true</td>
                            <td>Show category filter dropdown (true/false)</td>
                        </tr>
                        <tr>
                            <td><code>show_search</code></td>
                            <td>true</td>
                            <td>Show search box (true/false)</td>
                        </tr>
                        <tr>
                            <td><code>show_excerpt</code></td>
                            <td>true</td>
                            <td>Show business description (true/false)</td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td>title</td>
                            <td>Sort by: title, date, menu_order, rand</td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td>ASC</td>
                            <td>Sort order: ASC or DESC</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Category Template Shortcode</h2>
                <p>Use this shortcode in your Divi Business Category template:</p>
                <code>[business_category_template]</code>
                
                <h3>Available Parameters:</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>columns</code></td>
                            <td>3</td>
                            <td>Number of columns (1-4)</td>
                        </tr>
                        <tr>
                            <td><code>posts_per_page</code></td>
                            <td>-1</td>
                            <td>Number of businesses to show (-1 for all)</td>
                        </tr>
                        <tr>
                            <td><code>show_search</code></td>
                            <td>true</td>
                            <td>Show search box (true/false)</td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td>title</td>
                            <td>Sort by: title, date, menu_order, rand</td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td>ASC</td>
                            <td>Sort order: ASC or DESC</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Categories Grid Shortcode</h2>
                <p>Use this shortcode to display a grid of business categories with icons:</p>
                <code>[business_categories]</code>
                
                <h3>Available Parameters:</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>columns</code></td>
                            <td>5</td>
                            <td>Number of columns (1-6)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Homepage Search Shortcode</h2>
                <p>Use this shortcode to display a search bar on your homepage:</p>
                <code>[business_search]</code>
                
                <h3>Available Parameters:</h3>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>placeholder</code></td>
                            <td>Search for businesses, services, or categories...</td>
                            <td>Placeholder text in search box</td>
                        </tr>
                        <tr>
                            <td><code>button_text</code></td>
                            <td>Search</td>
                            <td>Text on search button</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Business Field Shortcodes</h2>
                <p>Use these shortcodes to display individual business fields in Divi modules on business listing pages:</p>
                
                <h3>Business Description</h3>
                <code>[business_description]</code>
                
                <h4>Available Parameters:</h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>word_limit</code></td>
                            <td></td>
                            <td>Limit number of words (e.g., 50)</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>Business Phone Button</h3>
                <code>[business_phone_button]</code>
                
                <h4>Available Parameters:</h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>text</code></td>
                            <td>Call Now</td>
                            <td>Button text</td>
                        </tr>
                    </tbody>
                </table>
                
                <h3>Business Email Button</h3>
                <code>[business_email_button]</code>
                
                <h4>Available Parameters:</h4>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Parameter</th>
                            <th>Default</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>text</code></td>
                            <td>Email Us</td>
                            <td>Button text</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h2>Example Usage</h2>
                
                <h3>Main Directory Examples:</h3>
                <p><strong>Basic directory:</strong><br>
                <code>[business_directory]</code></p>
                
                <p><strong>2 columns, restaurants only:</strong><br>
                <code>[business_directory columns="2" category="restaurants"]</code></p>
                
                <p><strong>No filters, random order:</strong><br>
                <code>[business_directory show_filter="false" show_search="false" orderby="rand"]</code></p>
                
                <h3>Homepage Search Examples:</h3>
                <p><strong>Basic search bar:</strong><br>
                <code>[business_search]</code></p>
                
                <p><strong>Custom text:</strong><br>
                <code>[business_search placeholder="Find Chelsea businesses..." button_text="Find Now"]</code></p>
                
                <h3>Business Field Examples:</h3>
                <p><strong>Basic description:</strong><br>
                <code>[business_description]</code></p>
                
                <p><strong>Limited to 50 words:</strong><br>
                <code>[business_description word_limit="50"]</code></p>
                
                <p><strong>Phone button:</strong><br>
                <code>[business_phone_button]</code></p>
                
                <p><strong>Custom phone button:</strong><br>
                <code>[business_phone_button text="Call Us Now"]</code></p>
                
                <p><strong>Email button:</strong><br>
                <code>[business_email_button]</code></p>
                
                <p><strong>Custom email button:</strong><br>
                <code>[business_email_button text="Contact Us"]</code></p>
                
                <h3>Categories Grid Examples:</h3>
                <p><strong>Basic categories grid:</strong><br>
                <code>[business_categories]</code></p>
                
                <p><strong>4 columns:</strong><br>
                <code>[business_categories columns="4"]</code></p>
            </div>
            
            <div class="card">
                <h2>ACF Field Mapping</h2>
                <p>The plugin automatically looks for these ACF field names (in order of preference):</p>
                <ul>
                    <li><strong>Phone:</strong> 'phone' or 'business_phone'</li>
                    <li><strong>Email:</strong> 'email' or 'business_email'</li>
                    <li><strong>Website:</strong> 'website' or 'business_website'</li>
                    <li><strong>Address:</strong> 'address' or 'business_address'</li>
                    <li><strong>Logo/Image:</strong> 'logo', 'business_logo', or 'image'</li>
                    <li><strong>Description:</strong> 'description' or 'business_description' (falls back to post content)</li>
                    <li><strong>Search Keywords:</strong> 'search_keywords' or 'business_search_keywords' (improves search results)</li>
                </ul>
                <p><em>Field names can be easily updated in the Queries class if needed.</em></p>
                
                <h3>Search Keywords Field</h3>
                <p>The <strong>Search Keywords</strong> field greatly improves search functionality. Business owners can add relevant terms that customers might search for:</p>
                <div style="background: #f0f8ff; padding: 15px; border-left: 4px solid #1976d2; margin: 15px 0;">
                    <h4>Examples:</h4>
                    <p><strong>"2 Girls and a Dog" (Pet Transport):</strong><br>
                    <code>dogs, pets, pet transport, pet taxi, dog walking, pet care, animals</code></p>
                    
                    <p><strong>"Mario's Pizza":</strong><br>
                    <code>food, italian, delivery, takeout, dinner, lunch, pizza, restaurant</code></p>
                    
                    <p><strong>"QuickFix Auto Repair":</strong><br>
                    <code>car, automotive, mechanic, repair, oil change, brake service, tires</code></p>
                </div>
                <p><strong>Field Type:</strong> Text or Textarea<br>
                <strong>Instructions for business owners:</strong> "Add keywords that customers might search for (separated by commas)"</p>
            </div>
            
            <div class="card">
                <h2>Setup Checklist</h2>
                <ol>
                    <li>✅ Business Listings post type (you have this)</li>
                    <li>✅ Business Categories taxonomy (you have this)</li>
                    <li>Create ACF fields for business information</li>
                    <li>Add business listings with contact details</li>
                    <li>Create a main directory page and add <code>[business_directory]</code></li>
                    <li>Set up Divi Business Category template with <code>[business_category_template]</code></li>
                    <li>Add homepage search with <code>[business_search]</code></li>
                    <li>Use field shortcodes in individual business templates</li>
                    <li>Customize styling if needed</li>
                </ol>
            </div>
            
            <div class="card">
                <h2>Styling</h2>
                <p>The plugin includes default CSS styling. You can:</p>
                <ul>
                    <li>Edit <code>/wp-content/plugins/business-directory/assets/style.css</code> directly</li>
                    <li>Override styles in your theme's CSS</li>
                    <li>Use the CSS classes: <code>.business-directory</code>, <code>.business-card</code>, <code>.directory-filters</code></li>
                </ul>
            </div>
        </div>
        <?php
    }
    
    public function admin_notices() {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p><strong>Business Directory:</strong> Advanced Custom Fields plugin is required for full functionality.</p>
            </div>
            <?php
        }
        
        // Check if business listings post type exists
        if (!post_type_exists('business-listing')) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Business Directory:</strong> "Business Listings" post type not found. Please make sure it's properly registered.</p>
            </div>
            <?php
        }
        
        // Check if business categories taxonomy exists
        if (!taxonomy_exists('business-category')) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><strong>Business Directory:</strong> "Business Categories" taxonomy not found. Please make sure it's properly registered.</p>
            </div>
            <?php
        }
    }
}
?>