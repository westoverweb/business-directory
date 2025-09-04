<?php
/**
 * Business Directory Shortcodes Class
 * Handles all shortcode functionality
 */

if (!defined('ABSPATH')) {
    exit;
}

class BusinessDirectory_Shortcodes {
    
    public function __construct() {
        $this->register_shortcodes();
    }
    
    public function register_shortcodes() {
        add_shortcode('business_directory', array($this, 'directory_shortcode'));
        add_shortcode('business_category_template', array($this, 'category_template_shortcode'));
        add_shortcode('business_categories', array($this, 'categories_grid_shortcode'));
        add_shortcode('business_search', array($this, 'homepage_search_shortcode'));
        add_shortcode('business_description', array($this, 'business_description_shortcode'));
        add_shortcode('business_phone_button', array($this, 'business_phone_button_shortcode'));
        add_shortcode('business_email_button', array($this, 'business_email_button_shortcode'));
        add_shortcode('business_address_link', array($this, 'business_address_link_shortcode'));
        add_shortcode('business_website_link', array($this, 'business_website_link_shortcode'));
        add_shortcode('business_phone_link', array($this, 'business_phone_link_shortcode'));
        add_shortcode('business_map', array($this, 'business_map_shortcode'));
		add_shortcode('business_map_link', array($this, 'business_map_link_shortcode'));
		add_shortcode('business_current_jobs', array($this, 'business_current_jobs_shortcode'));
		add_shortcode('business_listing_submission_form', ['BusinessDirectory_Shortcodes', 'business_listing_submission_form']);
    }
    
    /**
     * Main business directory shortcode
     */
    public function directory_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => -1,
            'category' => '',
            'show_filter' => 'true',
            'show_search' => 'true',
            'columns' => '3',
            'show_excerpt' => 'true',
            'excerpt_length' => 150,
            'show_featured_first' => 'false',
            'orderby' => 'title',
            'order' => 'ASC',
            'show_dynamic_title' => 'true',
            'show_search_status' => 'true',
        ), $atts);

        // Get search parameter from URL
        $search_term = isset($_GET['business_search']) ? sanitize_text_field($_GET['business_search']) : '';
        $is_searching = !empty($search_term);

        ob_start();
        
        // Get all business categories for filter
        $categories = BusinessDirectory_Queries::get_business_categories();
        
        ?>
        <div class="business-directory" id="business-directory">
            
            <?php if ($atts['show_dynamic_title'] === 'true'): ?>
            <div class="directory-header">
                <?php if ($is_searching): ?>
                    <h1 class="directory-title">Search Results</h1>
                    <?php if ($atts['show_search_status'] === 'true'): ?>
                    <div class="search-status">
                        <p class="search-info">
                            Showing results for: <strong>"<?php echo esc_html($search_term); ?>"</strong>
                            <button type="button" class="clear-search-btn" data-clear-search="true">
                                Clear Search
                            </button>
                        </p>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <h1 class="directory-title">All Listings</h1>
                    <?php if ($atts['show_search_status'] === 'true'): ?>
                    <div class="search-status">
                        <p class="browse-info">Browse all businesses in our directory</p>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($atts['show_filter'] === 'true' || $atts['show_search'] === 'true'): ?>
            <div class="directory-filters">
                
                <?php if ($atts['show_search'] === 'true'): ?>
                <div class="search-box">
                    <input type="text" id="business-search" placeholder="Search businesses..." 
                           value="<?php echo esc_attr($search_term); ?>" />
                </div>
                <?php endif; ?>
                
                <?php if ($atts['show_filter'] === 'true' && !empty($categories)): ?>
                <div class="category-filter">
                    <select id="category-filter">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->slug); ?>">
                                <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
            </div>
            <?php endif; ?>
            
            <div class="business-listings-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <?php
                // Get businesses using our query class
                $business_query = BusinessDirectory_Queries::get_businesses(array(
                    'posts_per_page' => $atts['posts_per_page'],
                    'category' => $atts['category'],
                    'orderby' => $atts['orderby'],
                    'order' => $atts['order'],
                ));
                
                if ($business_query->have_posts()):
                    $total_businesses = $business_query->found_posts;
                    while ($business_query->have_posts()): $business_query->the_post();
                        $this->render_business_card($atts);
                    endwhile;
                    wp_reset_postdata();
                    
                    // Add result count after listings load
                    if ($is_searching && $atts['show_search_status'] === 'true'): 
                        // Store search term in data attribute for JavaScript to use
                        echo '<div id="search-data" data-search-term="' . esc_attr($search_term) . '" style="display:none;"></div>';
                    endif;
                else:
                    if ($is_searching): ?>
                        <div class="no-results-search">
                            <h3>No businesses found for "<?php echo esc_html($search_term); ?>"</h3>
                            <p>Try:</p>
                            <ul>
                                <li>Checking your spelling</li>
                                <li>Using more general terms</li>
                                <li>Browsing by category instead</li>
                            </ul>
                            <button type="button" class="view-all-btn" data-view-all="true">
                                View All Businesses
                            </button>
                        </div>
                    <?php else: ?>
                        <p class="no-results">No businesses found.</p>
                    <?php endif;
                endif;
                ?>
                
            </div>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Business category template shortcode
     */
    public function category_template_shortcode($atts) {
        $atts = shortcode_atts(array(
            'posts_per_page' => -1,
            'show_filter' => 'false',
            'show_search' => 'true',
            'columns' => '3',
            'show_excerpt' => 'true',
            'excerpt_length' => 150,
            'orderby' => 'title',
            'order' => 'ASC',
            'show_category_title' => 'true',
            'show_category_description' => 'true',
        ), $atts);

        // Get the current category
        $current_category = get_queried_object();
        
        // Make sure we're on a business category page
        if (!$current_category || $current_category->taxonomy !== 'business-category') {
            return '<p>This shortcode should only be used on Business Category pages.</p>';
        }

        ob_start();
        ?>
        <div class="business-directory business-category-template" id="business-directory">
            
            <?php if ($atts['show_category_title'] === 'true'): ?>
            <?php $this->render_category_header($current_category, $atts); ?>
            <?php endif; ?>
            
            <?php if ($atts['show_search'] === 'true'): ?>
            <div class="directory-filters">
                <div class="search-box">
                    <input type="text" id="business-search" placeholder="Search <?php echo esc_attr($current_category->name); ?>..." />
                </div>
            </div>
            <?php endif; ?>
            
            <div class="business-listings-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
                <?php
                // Get businesses for this category
                $business_query = BusinessDirectory_Queries::get_businesses_by_category($current_category->term_id, array(
                    'posts_per_page' => $atts['posts_per_page'],
                    'orderby' => $atts['orderby'],
                    'order' => $atts['order'],
                ));
                
                if ($business_query->have_posts()):
                    while ($business_query->have_posts()): $business_query->the_post();
                        $this->render_business_card($atts, false); // false = don't show categories since we're on category page
                    endwhile;
                    wp_reset_postdata();
                else:
                    echo '<p class="no-results">No businesses found in this category.</p>';
                endif;
                ?>
                
            </div>
            
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Business categories grid shortcode
     */
    public function categories_grid_shortcode($atts) {
        $atts = shortcode_atts(array(
            'columns' => '5',
        ), $atts);

        $terms = get_terms(array(
            'taxonomy' => 'business-category',
            'hide_empty' => true,
        ));
        
        if (empty($terms) || is_wp_error($terms)) {
            return '<p class="no-categories">No categories found.</p>';
        }

        ob_start();
        ?>
        <div class="business-categories-grid" data-columns="<?php echo esc_attr($atts['columns']); ?>">
            <?php foreach ($terms as $term): ?>
                <?php
                $image_url = get_field('business_category_image', 'business-category_' . $term->term_id);
                $term_link = get_term_link($term);
                ?>
                <div class="category-item">
                    <a href="<?php echo esc_url($term_link); ?>" class="category-link">
                        <?php if (!empty($image_url)): ?>
                            <?php if (is_array($image_url)): ?>
                                <img src="<?php echo esc_url($image_url['url']); ?>" 
                                     alt="<?php echo esc_attr($image_url['alt'] ?: $term->name); ?>" 
                                     class="category-image" 
                                     style="width: 150px; height: 150px;" />
                            <?php else: ?>
                                <img src="<?php echo esc_url($image_url); ?>" 
                                     alt="<?php echo esc_attr($term->name); ?>" 
                                     class="category-image" 
                                     style="width: 150px; height: 150px;" />
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="category-placeholder" style="width: 150px; height: 150px;">
                                <span class="category-icon">📁</span>
                            </div>
                        <?php endif; ?>
                        
                        <span class="category-name"><?php echo esc_html($term->name); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Homepage search bar shortcode
     */
    public function homepage_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => 'Search for businesses, services, or categories...',
            'button_text' => 'Search',
        ), $atts);

        // Get the main directory page URL for search results
        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            's' => '[business_directory',
        ));
        
        $search_url = !empty($pages) ? get_permalink($pages[0]->ID) : home_url('/business-directory/');

        ob_start();
        ?>
        <div class="homepage-search-container">
            <form class="homepage-search-form" method="get" action="<?php echo esc_url($search_url); ?>">
                <div class="search-input-wrapper">
                    <span class="search-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.35-4.35"/>
                        </svg>
                    </span>
                    
                    <input type="text" 
                           name="business_search" 
                           class="homepage-search-input" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           value="<?php echo esc_attr(isset($_GET['business_search']) ? $_GET['business_search'] : ''); ?>">
                    
                    <button type="submit" class="homepage-search-button">
                        <?php echo esc_html($atts['button_text']); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        
        return ob_get_clean();
    }
    
    /**
     * Business description field shortcode
     */
    public function business_description_shortcode($atts) {
        $atts = shortcode_atts(array(
            'word_limit' => '',
        ), $atts);

        $post_id = get_the_ID();
        if (!$post_id) {
            return '';
        }

        // Get description
        $description = get_field('business_description', $post_id) ?: get_field('description', $post_id);
        
        if (empty($description)) {
            $description = get_post_field('post_content', $post_id);
        }

        if (empty($description)) {
            return '';
        }

        // Apply word limit if specified
        if (!empty($atts['word_limit']) && is_numeric($atts['word_limit'])) {
            $word_limit = intval($atts['word_limit']);
            $words = explode(' ', $description);
            if (count($words) > $word_limit) {
                $description = implode(' ', array_slice($words, 0, $word_limit)) . '...';
            }
        }

        return '<div class="business-description">' . wp_kses_post($description) . '</div>';
    }
    
    /**
     * Business phone link shortcode (clickable phone number)
     */
    public function business_phone_link_shortcode($atts) {
        $atts = shortcode_atts(array(), $atts);

        // Get current business listing
        $post_id = null;
        
        if (is_singular('business-listing')) {
            $post_id = get_queried_object_id();
        }
        
        if (!$post_id) {
            global $post;
            if ($post && $post->post_type === 'business-listing') {
                $post_id = $post->ID;
            }
        }
        
        if (!$post_id) {
            return '';
        }

        // Get phone number
        $phone = get_field('business_phone', $post_id) ?: get_field('phone', $post_id);
        
        if (!$phone || trim($phone) === '') {
            return '';
        }

        // Clean phone number for tel: link
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
        if (empty($clean_phone)) {
            return '';
        }
        
        $tel_link = 'tel:' . $clean_phone;
        
        return '<a href="' . esc_url($tel_link) . '">' . esc_html($phone) . '</a>';
    }
    
    /**
     * Complete business phone button shortcode
     */
    public function business_phone_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Call Now',
        ), $atts);

        // Get current business listing
        $post_id = null;
        
        if (is_singular('business-listing')) {
            $post_id = get_queried_object_id();
        }
        
        if (!$post_id) {
            global $post;
            if ($post && $post->post_type === 'business-listing') {
                $post_id = $post->ID;
            }
        }
        
        // Return nothing if no post found
        if (!$post_id) {
            return '';
        }

        // Get phone number
        $phone = get_field('business_phone', $post_id) ?: get_field('phone', $post_id);
        
        // Return nothing if no phone number found or phone is empty/whitespace
        if (!$phone || trim($phone) === '') {
            return '';
        }

        // Clean phone number and validate it has actual numbers
        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // Return nothing if no valid phone digits found
        if (empty($clean_phone)) {
            return '';
        }
        
        $tel_link = 'tel:' . $clean_phone;
        
        return '<a href="' . esc_url($tel_link) . '" class="business-phone-btn">' . esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Complete business email button shortcode
     */
    public function business_email_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Email Us',
        ), $atts);

        // Get current business listing
        $post_id = null;
        
        if (is_singular('business-listing')) {
            $post_id = get_queried_object_id();
        }
        
        if (!$post_id) {
            global $post;
            if ($post && $post->post_type === 'business-listing') {
                $post_id = $post->ID;
            }
        }
        
        // Return nothing if no post found
        if (!$post_id) {
            return '';
        }

        // Get email address
        $email = get_field('business_email', $post_id) ?: get_field('email', $post_id);
        
        // Return nothing if no email found, empty, or invalid email format
        if (!$email || trim($email) === '' || !is_email($email)) {
            return '';
        }

        // Build mailto link
        $mailto_link = 'mailto:' . $email;
        
        return '<a href="' . esc_url($mailto_link) . '" class="business-email-btn">' . esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Business address link shortcode (opens in maps)
     */
    public function business_address_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'map_service' => 'auto', // auto, google, apple
        ), $atts);

        // Get current business listing
        $post_id = null;
        
        if (is_singular('business-listing')) {
            $post_id = get_queried_object_id();
        }
        
        if (!$post_id) {
            global $post;
            if ($post && $post->post_type === 'business-listing') {
                $post_id = $post->ID;
            }
        }
        
        if (!$post_id) {
            return '';
        }

        // Get address
        $address = get_field('business_address', $post_id) ?: get_field('address', $post_id);
        
        if (!$address || trim($address) === '') {
            return '';
        }

        // Create map link based on service preference
        $encoded_address = urlencode($address);
        
        switch ($atts['map_service']) {
            case 'google':
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . $encoded_address;
                break;
            case 'apple':
                $map_url = 'http://maps.apple.com/?q=' . $encoded_address;
                break;
            default: // auto - uses device default
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . $encoded_address;
                break;
        }
        
        return '<a href="' . esc_url($map_url) . '" target="_blank" rel="noopener">' . esc_html($address) . '</a>';
    }
    
    /**
     * Business website link shortcode
     */
    public function business_website_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => '', // Optional: custom text instead of URL
        ), $atts);

        // Get current business listing
        $post_id = null;
        
        if (is_singular('business-listing')) {
            $post_id = get_queried_object_id();
        }
        
        if (!$post_id) {
            global $post;
            if ($post && $post->post_type === 'business-listing') {
                $post_id = $post->ID;
            }
        }
        
        if (!$post_id) {
            return '';
        }

        // Get website
        $website = get_field('business_website', $post_id) ?: get_field('website', $post_id);
        
        if (!$website || trim($website) === '') {
            return '';
        }

        // Ensure URL has protocol
        if (!preg_match('/^https?:\/\//', $website)) {
            $website = 'https://' . $website;
        }

        // Use custom text or clean URL for display
        if (!empty($atts['text'])) {
            $display_text = $atts['text'];
        } else {
            // Clean URL for display (remove protocol and www)
            $display_text = preg_replace('/^https?:\/\/(www\.)?/', '', $website);
            $display_text = rtrim($display_text, '/');
        }
        
        return '<a href="' . esc_url($website) . '" target="_blank" rel="noopener">' . esc_html($display_text) . '</a>';
    }

/**
 * Business map shortcode - displays Google Maps embed
 */
public function business_map_shortcode($atts) {
    $atts = shortcode_atts(array(
        'width' => '100%',
        'height' => '300px',
        'zoom' => '15',
        'maptype' => 'roadmap', // roadmap, satellite, hybrid, terrain
        'show_directions' => 'true',
        'style' => 'default', // default, minimal, card
    ), $atts);

    // Get current business listing
    $post_id = null;
    
    if (is_singular('business-listing')) {
        $post_id = get_queried_object_id();
    }
    
    if (!$post_id) {
        global $post;
        if ($post && $post->post_type === 'business-listing') {
            $post_id = $post->ID;
        }
    }
    
    if (!$post_id) {
        return '<p>Map can only be displayed on business listing pages.</p>';
    }

    // Get address
    $address = get_field('business_address', $post_id) ?: get_field('address', $post_id);
    
    if (!$address || trim($address) === '') {
        return '<p>No address available for this business.</p>';
    }

    // Clean and encode the address
    $encoded_address = urlencode(trim($address));
    
    // Build the Google Maps embed URL
    $embed_url = 'https://www.google.com/maps/embed/v1/place?key=YOUR_API_KEY&q=' . $encoded_address;
    
    // Alternative: Use the iframe embed without API (more reliable)
    $search_url = 'https://www.google.com/maps?q=' . $encoded_address . '&t=' . $atts['maptype'] . '&z=' . $atts['zoom'] . '&output=embed';
    
    // Get business name for aria-label
    $business_name = get_the_title($post_id);
    
    ob_start();
    
    if ($atts['style'] === 'card'): ?>
        <div class="business-map-card">
            <div class="map-header">
                <h4>Location</h4>
                <div class="map-address"><?php echo esc_html($address); ?></div>
            </div>
            <div class="business-map-container">
                <iframe 
                    src="<?php echo esc_url($search_url); ?>"
                    width="<?php echo esc_attr($atts['width']); ?>"
                    height="<?php echo esc_attr($atts['height']); ?>"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    aria-label="Map showing location of <?php echo esc_attr($business_name); ?>">
                </iframe>
            </div>
            <?php if ($atts['show_directions'] === 'true'): ?>
            <div class="map-actions">
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($encoded_address); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="directions-btn">
                    Get Directions
                </a>
                <a href="https://www.google.com/maps/search/?api=1&query=<?php echo esc_attr($encoded_address); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="view-larger-btn">
                    View Larger Map
                </a>
            </div>
            <?php endif; ?>
        </div>
    <?php elseif ($atts['style'] === 'minimal'): ?>
        <div class="business-map-minimal">
            <iframe 
                src="<?php echo esc_url($search_url); ?>"
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>"
                style="border:0; border-radius: 8px;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                aria-label="Map showing location of <?php echo esc_attr($business_name); ?>">
            </iframe>
        </div>
    <?php else: ?>
        <div class="business-map-container">
            <iframe 
                src="<?php echo esc_url($search_url); ?>"
                width="<?php echo esc_attr($atts['width']); ?>"
                height="<?php echo esc_attr($atts['height']); ?>"
                style="border:0;"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                aria-label="Map showing location of <?php echo esc_attr($business_name); ?>">
            </iframe>
            
            <?php if ($atts['show_directions'] === 'true'): ?>
            <div class="map-directions" style="margin-top: 15px; text-align: center;">
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr($encoded_address); ?>" 
                   target="_blank" 
                   rel="noopener"
                   class="directions-link">
                    📍 Get Directions to <?php echo esc_html($business_name); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    <?php endif;
    
    return ob_get_clean();
}

/**
 * Alternative: Business map link shortcode (just a link, no embed)
 */
public function business_map_link_shortcode($atts) {
    $atts = shortcode_atts(array(
        'text' => 'View on Google Maps',
        'directions' => 'false', // if true, shows "Get Directions" instead
    ), $atts);

    // Get current business listing
    $post_id = null;
    
    if (is_singular('business-listing')) {
        $post_id = get_queried_object_id();
    }
    
    if (!$post_id) {
        global $post;
        if ($post && $post->post_type === 'business-listing') {
            $post_id = $post->ID;
        }
    }
    
    if (!$post_id) {
        return '';
    }

    // Get address
    $address = get_field('business_address', $post_id) ?: get_field('address', $post_id);
    
    if (!$address || trim($address) === '') {
        return '';
    }

    $encoded_address = urlencode(trim($address));
    
    if ($atts['directions'] === 'true') {
        $map_url = 'https://www.google.com/maps/dir/?api=1&destination=' . $encoded_address;
        $default_text = 'Get Directions';
    } else {
        $map_url = 'https://www.google.com/maps/search/?api=1&query=' . $encoded_address;
        $default_text = 'View on Google Maps';
    }
    
    $link_text = ($atts['text'] === 'View on Google Maps' && $atts['directions'] === 'true') ? $default_text : $atts['text'];
    
    return '<a href="' . esc_url($map_url) . '" target="_blank" rel="noopener" class="business-map-link">' . esc_html($link_text) . '</a>';
}
    
    /**
     * Render a single business card
     */
    private function render_business_card($atts, $show_categories = true) {
        // Get business data
        $business_fields = BusinessDirectory_Queries::get_business_fields();
        $categories = BusinessDirectory_Queries::get_business_categories_for_post();
        $search_content = BusinessDirectory_Queries::get_business_search_content(get_the_ID(), $business_fields, $categories);
        
        // Render business card inline instead of using template
        ?>
        <div class="business-card" 
             data-categories="<?php echo esc_attr(implode(' ', $categories['slugs'])); ?>"
             data-search-terms="<?php echo esc_attr($search_content); ?>">
            
            <?php if ($business_fields['logo']): ?>
            <div class="business-logo">
                <a href="<?php the_permalink(); ?>">
                    <?php if (is_array($business_fields['logo'])): ?>
                        <img src="<?php echo esc_url($business_fields['logo']['url']); ?>" 
                             alt="<?php echo esc_attr($business_fields['logo']['alt'] ?? get_the_title()); ?>">
                    <?php else: ?>
                        <img src="<?php echo esc_url($business_fields['logo']); ?>" 
                             alt="<?php echo esc_attr(get_the_title()); ?>">
                    <?php endif; ?>
                </a>
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
                    <?php 
                    $business_cats = get_the_terms(get_the_ID(), 'business-category');
                    if ($business_cats && !is_wp_error($business_cats)):
                        foreach ($business_cats as $cat): 
                            $cat_link = get_term_link($cat);
                            ?>
                            <a href="<?php echo esc_url($cat_link); ?>" class="category-tag">
                                <?php echo esc_html($cat->name); ?>
                            </a>
                        <?php endforeach;
                    endif; ?>
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
        <?php
    }
    
    /**
     * Render category header
     */
    private function render_category_header($current_category, $atts) {
        include BUSINESS_DIRECTORY_PLUGIN_DIR . 'templates/category-header.php';
    }

/**
 * Simplified business current jobs shortcode - no parameters, no title, just the listings
 */
public function business_current_jobs_shortcode($atts) {
    // Get current business ID
    $business_id = get_the_ID();
    if (!$business_id || get_post_type($business_id) !== 'business-listing') {
        return '';
    }

    // Query jobs where the business_name field contains this business ID
    $jobs_query = new WP_Query(array(
        'post_type' => 'job-listing',
        'post_status' => 'publish',
        'posts_per_page' => -1, // Show all jobs
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => 'business_name',
                'value' => '"' . $business_id . '"',
                'compare' => 'LIKE'
            )
        )
    ));

    // If no results, try alternative query methods
    if (!$jobs_query->have_posts()) {
        $jobs_query = new WP_Query(array(
            'post_type' => 'job-listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => array(
                array(
                    'key' => 'business_name',
                    'value' => $business_id,
                    'compare' => '='
                )
            )
        ));
    }

    if (!$jobs_query->have_posts()) {
        wp_reset_postdata();
        return '';
    }

    ob_start();
    ?>
    <div class="business-current-jobs">
        <div class="current-jobs-list">
            <?php while ($jobs_query->have_posts()): $jobs_query->the_post(); ?>
                <?php
                $job_types = get_the_terms(get_the_ID(), 'job-type');
                $job_salary = get_field('job_salary_hourly_rate');
                ?>
                <div class="current-job-item">
                    <div class="job-info">
                        <h3 class="job-title">
                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                        </h3>
                        
                        <div class="job-meta">
                            <?php if ($job_types && !is_wp_error($job_types)): ?>
                            <span class="job-type"><?php echo esc_html($job_types[0]->name); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($job_salary)): ?>
                            <span class="job-salary"><?php echo esc_html($job_salary); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="job-actions">
                        <a href="<?php the_permalink(); ?>" class="view-job-link">View Details</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php
    
    wp_reset_postdata();
    return ob_get_clean();
}
/**
     * Business Listing Submission Form Shortcode
     * Usage: [business_listing_submission_form]
     */
    public static function business_listing_submission_form() {
        $output = '';
        $offer_message = '<div class="alert alert-info">Don\'t have a Google Business Page? Westover Web will create one for you at a discounted rate!</div>';
        $admin_email = get_option('admin_email');

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bd_submit_listing'])) {

            if (!isset($_POST['bd_nonce']) || !wp_verify_nonce($_POST['bd_nonce'], 'bd_submit_listing')) {
                $output .= '<div class="alert alert-danger">Security check failed. Please try again.</div>';
            } else {
                $business_name = sanitize_text_field($_POST['business_name'] ?? '');
                $business_phone = sanitize_text_field($_POST['business_phone'] ?? '');
                $business_email = sanitize_email($_POST['business_email'] ?? '');
                $business_website = esc_url_raw($_POST['business_website'] ?? '');
                $business_address = sanitize_text_field($_POST['business_address'] ?? '');
                $business_description = wp_kses_post($_POST['business_description'] ?? '');
                $search_keywords = sanitize_text_field($_POST['search_keywords'] ?? '');
                $google_business_url = esc_url_raw($_POST['google_business_url'] ?? '');

                $business_logo_id = '';
                if (!empty($_FILES['business_logo']['name'])) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                    $uploaded = media_handle_upload('business_logo', 0);
                    if (!is_wp_error($uploaded)) {
                        $business_logo_id = $uploaded;
                    }
                }

                $post_id = wp_insert_post([
                    'post_title'    => $business_name,
                    'post_type'     => 'business-listing',
                    'post_status'   => 'pending',
                ]);

                if ($post_id && !is_wp_error($post_id)) {
                    // Set ACF fields
                    update_field('business_phone', $business_phone, $post_id);
                    update_field('business_email', $business_email, $post_id);
                    update_field('business_website', $business_website, $post_id);
                    update_field('business_address', $business_address, $post_id);
                    update_field('business_logo', $business_logo_id, $post_id);
                    update_field('business_description', $business_description, $post_id);
                    update_field('search_keywords', $search_keywords, $post_id);
                    update_field('google_business_url', $google_business_url, $post_id);

                    // Send admin notification
                    $message = "New business listing submitted:\n\n";
                    $message .= "Name: $business_name\n";
                    $message .= "Phone: $business_phone\n";
                    $message .= "Email: $business_email\n";
                    $message .= "Website: $business_website\n";
                    $message .= "Address: $business_address\n";
                    $message .= "Google Business URL: $google_business_url\n";
                    $message .= "Description: $business_description\n";
                    $message .= "Search Keywords: $search_keywords\n";
                    $message .= "\nApprove in WP Admin.";

                    wp_mail($admin_email, "Business Listing Submission: $business_name", $message);

                    $output .= '<div class="alert alert-success">Thank you for your submission! Your listing will be reviewed and approved soon.</div>';
                } else {
                    $output .= '<div class="alert alert-danger">There was an error submitting your listing. Please try again.</div>';
                }
            }
        }

        ob_start();
        ?>
        <form method="POST" enctype="multipart/form-data" class="bd-business-listing-form">
            <?php wp_nonce_field('bd_submit_listing', 'bd_nonce'); ?>

            <label for="business_name">Business Name *</label>
            <input type="text" name="business_name" id="business_name" required>

            <label for="business_phone">Phone</label>
            <input type="text" name="business_phone" id="business_phone">

            <label for="business_email">Email</label>
            <input type="email" name="business_email" id="business_email">

            <label for="business_website">Website</label>
            <input type="url" name="business_website" id="business_website">

            <label for="business_address">Address</label>
            <input type="text" name="business_address" id="business_address">

            <label for="business_logo">Logo</label>
            <input type="file" name="business_logo" id="business_logo" accept="image/*">

            <label for="business_description">Description</label>
            <textarea name="business_description" id="business_description" rows="4"></textarea>

            <label for="search_keywords">Search Keywords</label>
            <input type="text" name="search_keywords" id="search_keywords">

            <label for="google_business_url">Google Business Page URL</label>
            <input type="url" name="google_business_url" id="google_business_url">
            <?php if (empty($_POST['google_business_url'] ?? '')): ?>
                <?php echo $offer_message; ?>
            <?php endif; ?>

            <button type="submit" name="bd_submit_listing">Submit Listing</button>
        </form>
        <?php
        $output .= ob_get_clean();
        return $output;
    }
}
}
?>
