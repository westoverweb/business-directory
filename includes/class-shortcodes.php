<?php
/**
 * Business Directory Shortcodes Class
 * Handles all shortcode functionality - NO CLEAR SEARCH FEATURE
 */

if (!defined('ABSPATH')) {
    exit;
}

class BusinessDirectory_Shortcodes {
    
    public function __construct() {
        $this->register_shortcodes();
        add_action('wp_enqueue_scripts', array($this, 'enqueue_form_scripts'));
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
        add_shortcode('business_submission_form', array($this, 'business_submission_form_shortcode'));
    }
    
    public function enqueue_form_scripts() {
        global $post;
        if ($post && has_shortcode($post->post_content, 'business_submission_form')) {
            wp_enqueue_script(
                'business-directory-forms',
                BUSINESS_DIRECTORY_PLUGIN_URL . 'assets/forms.js',
                array('jquery'),
                BUSINESS_DIRECTORY_VERSION,
                true
            );
            
            wp_enqueue_style(
                'business-directory-forms',
                BUSINESS_DIRECTORY_PLUGIN_URL . 'assets/forms.css',
                array(),
                BUSINESS_DIRECTORY_VERSION
            );
        }
    }
    
    /**
     * Main business directory shortcode - NO CLEAR SEARCH BUTTONS
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
                        <?php
                        // Count total businesses that will be displayed
                        $total_businesses = $business_query->found_posts;
                        $result_text = $total_businesses === 1 ? 'result' : 'results';
                        ?>
                        <p class="search-info">
                            <span class="result-count"><?php echo $total_businesses; ?> <?php echo $result_text; ?></span> for: <strong>"<?php echo esc_html($search_term); ?>"</strong>
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
                    
                    // Add result count data for JavaScript
                    if ($is_searching && $atts['show_search_status'] === 'true'): 
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
     * Homepage search bar shortcode - NO CLEAR SEARCH
     */
    public function homepage_search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => 'Search for businesses, services, or categories...',
            'button_text' => 'Search',
            'style' => 'default',
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
        <div class="homepage-search-container <?php echo esc_attr('style-' . $atts['style']); ?>">
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
                        $this->render_business_card($atts, false);
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
                                <span class="category-icon">📋</span>
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

        $phone = get_field('business_phone', $post_id) ?: get_field('phone', $post_id);
        
        if (!$phone || trim($phone) === '') {
            return '';
        }

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

        $phone = get_field('business_phone', $post_id) ?: get_field('phone', $post_id);
        
        if (!$phone || trim($phone) === '') {
            return '';
        }

        $clean_phone = preg_replace('/[^0-9+]/', '', $phone);
        
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

        $email = get_field('business_email', $post_id) ?: get_field('email', $post_id);
        
        if (!$email || trim($email) === '' || !is_email($email)) {
            return '';
        }

        $mailto_link = 'mailto:' . $email;
        
        return '<a href="' . esc_url($mailto_link) . '" class="business-email-btn">' . esc_html($atts['text']) . '</a>';
    }
    
    /**
     * Business address link shortcode (opens in maps)
     */
    public function business_address_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'map_service' => 'auto',
        ), $atts);

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

        $address = get_field('business_address', $post_id) ?: get_field('address', $post_id);
        
        if (!$address || trim($address) === '') {
            return '';
        }

        $encoded_address = urlencode($address);
        
        switch ($atts['map_service']) {
            case 'google':
                $map_url = 'https://www.google.com/maps/search/?api=1&query=' . $encoded_address;
                break;
            case 'apple':
                $map_url = 'http://maps.apple.com/?q=' . $encoded_address;
                break;
            default:
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
            'text' => '',
        ), $atts);

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

        $website = get_field('business_website', $post_id) ?: get_field('website', $post_id);
        
        if (!$website || trim($website) === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//', $website)) {
            $website = 'https://' . $website;
        }

        if (!empty($atts['text'])) {
            $display_text = $atts['text'];
        } else {
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
            'maptype' => 'roadmap',
            'show_directions' => 'true',
            'style' => 'default',
        ), $atts);

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

        $address = get_field('business_address', $post_id) ?: get_field('address', $post_id);
        
        if (!$address || trim($address) === '') {
            return '<p>No address available for this business.</p>';
        }

        $encoded_address = urlencode(trim($address));
        $search_url = 'https://www.google.com/maps?q=' . $encoded_address . '&t=' . $atts['maptype'] . '&z=' . $atts['zoom'] . '&output=embed';
        $business_name = get_the_title($post_id);
        
        ob_start();
        ?>
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
                    Get Directions to <?php echo esc_html($business_name); ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Business map link shortcode (just a link, no embed)
     */
    public function business_map_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'text' => 'View on Google Maps',
            'directions' => 'false',
        ), $atts);

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
     * Simplified business current jobs shortcode
     */
    public function business_current_jobs_shortcode($atts) {
        $business_id = get_the_ID();
        if (!$business_id || get_post_type($business_id) !== 'business-listing') {
            return '';
        }

        $jobs_query = new WP_Query(array(
            'post_type' => 'job-listing',
            'post_status' => 'publish',
            'posts_per_page' => -1,
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
     * Business submission form shortcode
     */
    public function business_submission_form_shortcode($atts) {
        $categories = BusinessDirectory_Queries::get_business_categories();
        
        ob_start();
        
        if (isset($_GET['form_success'])) {
            echo '<div class="form-message success">Thank you! Your business listing has been submitted and is under review. We\'ll notify you once it\'s approved.</div>';
        }
        if (isset($_GET['form_error'])) {
            echo '<div class="form-message error">Error: ' . esc_html(urldecode($_GET['form_error'])) . '</div>';
        }
        ?>
        
       <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data" class="business-submission-form">
            <input type="hidden" name="action" value="business_listing_submit">
            <?php wp_nonce_field('submit_business_listing', 'business_nonce'); ?>
            
            <div class="form-section">
                <h3>Business Information</h3>
                
                <div class="form-group">
                    <label for="business_name">Business Name *</label>
                    <input type="text" name="business_name" id="business_name" required 
                           value="<?php echo esc_attr($_POST['business_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="business_email">Business Email *</label>
                    <input type="email" name="business_email" id="business_email" required
                           value="<?php echo esc_attr($_POST['business_email'] ?? ''); ?>">
                    <small>This email will be used to link future job postings to your business</small>
                </div>
                
                <div class="form-group">
                    <label for="business_phone">Phone Number</label>
                    <input type="tel" name="business_phone" id="business_phone"
                           value="<?php echo esc_attr($_POST['business_phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="business_website">Website</label>
                    <input type="url" name="business_website" id="business_website" 
                           placeholder="https://yourbusiness.com"
                           value="<?php echo esc_attr($_POST['business_website'] ?? ''); ?>">
                </div>
                
				<div class="form-group">
   					<label for="google_business_url">Google Business Page URL</label>
    				<input type="url" name="google_business_url" id="google_business_url" 
							placeholder="https://www.google.com/maps/place/your-business"
           					value="<?php echo esc_attr($_POST['google_business_url'] ?? ''); ?>">
    				<small>Visit your Google Business Profile and click on the "Share" link or icon. Copy that link and insert it here.</small>
				</div>
                
                <div class="form-group">
                    <label for="business_address">Business Address *</label>
                    <textarea name="business_address" id="business_address" required><?php echo esc_textarea($_POST['business_address'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <h3>Business Details</h3>
                
                <div class="form-group">
                    <label for="business_description">Business Description *</label>
                    <textarea name="business_description" id="business_description" required 
                              placeholder="Tell us about your business, services, and what makes you special"><?php echo esc_textarea($_POST['business_description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="search_keywords">Search Keywords</label>
                    <textarea name="search_keywords" id="search_keywords" 
                              placeholder="restaurant, pizza, delivery, takeout"><?php echo esc_textarea($_POST['search_keywords'] ?? ''); ?></textarea>
                    <small>Add keywords customers might search for (separated by commas)</small>
                </div>
                
                <div class="form-group">
                    <label for="business_logo">Business Logo/Photo</label>
                    <input type="file" name="business_logo" id="business_logo" accept="image/*">
                    <small>Upload your business logo or a photo (JPG, PNG, GIF - Max 5MB)</small>
                </div>
            </div>
            
            <?php if (!empty($categories)): ?>
            <div class="form-section">
                <h3>Business Categories *</h3>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                    <label class="category-option">
                        <input type="checkbox" name="business_categories[]" value="<?php echo esc_attr($category->name); ?>"
                               <?php checked(in_array($category->name, $_POST['business_categories'] ?? [])); ?>>
                        <span><?php echo esc_html($category->name); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="form-submit">
                <button type="submit" name="submit_business_listing" class="submit-btn">
                    Submit Business Listing
                </button>
            </div>
        </form>
        
        <?php
        return ob_get_clean();
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
    
    // Add all your other shortcode methods here...
    // (business_description_shortcode, business_phone_button_shortcode, etc.)
}