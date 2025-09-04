jQuery(document).ready(function($) {
    const searchInput = $("#business-search");
    const categoryFilter = $("#category-filter");
    const businessCards = $(".business-card");
    
    function updateSearchResultCount() {
        const searchData = $('#search-data');
        if (searchData.length > 0) {
            const searchTerm = searchData.data('search-term');
            setTimeout(function() {
                const visibleCards = $('.business-card:not(.hidden)').length;
                const searchInfo = $('.search-info');
                if (searchInfo.length > 0 && searchTerm) {
                    const resultText = visibleCards === 1 ? 'result' : 'results';
                    // Create elements instead of using innerHTML for CSP compliance
                    const resultSpan = $('<span class="result-count"></span>').text(visibleCards + ' ' + resultText);
                    const searchTermStrong = $('<strong></strong>').text('"' + searchTerm + '"');
                    const clearBtn = $('<button type="button" class="clear-search-btn" data-clear-search="true">Clear Search</button>');
                    
                    searchInfo.empty().append(resultSpan).append(' for: ').append(searchTermStrong).append(' ').append(clearBtn);
                }
            }, 500);
        }
    }
    
    function filterBusinesses() {
        const searchTerm = searchInput.val().toLowerCase();
        const selectedCategory = categoryFilter.val();
        
        businessCards.each(function() {
            const $card = $(this);
            const searchTerms = $card.data("search-terms") || "";
            const categories = $card.data("categories") || "";
            
            const matchesSearch = !searchTerm || searchTerms.includes(searchTerm);
            const matchesCategory = !selectedCategory || categories.includes(selectedCategory);
            
            if (matchesSearch && matchesCategory) {
                $card.removeClass("hidden").fadeIn(300);
            } else {
                $card.addClass("hidden").fadeOut(300);
            }
        });
        
        // Show/hide no results message
        setTimeout(function() {
            const visibleCards = businessCards.filter(":visible").length;
            const $noResults = $(".no-results");
            
            if (visibleCards === 0 && $noResults.length === 0) {
                const noResultsP = $('<p class="no-results">No businesses match your search criteria.</p>');
                $(".business-listings-grid").append(noResultsP);
            } else if (visibleCards > 0) {
                $noResults.remove();
            }
            
            // Update search result count if we're on a search page
            updateSearchResultCount();
        }, 350);
    }
    
    // Check for URL search parameter on page load
    function initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('business_search');
        
        if (searchParam && searchInput.length > 0) {
            searchInput.val(searchParam);
            // Trigger filtering after a short delay to ensure DOM is ready
            setTimeout(function() {
                filterBusinesses();
            }, 100);
        } else {
            // Still update result count on initial load if searching
            updateSearchResultCount();
        }
    }
    
    // Initialize search from URL parameter
    initializeFromURL();
    
    // Bind events
    searchInput.on("input", filterBusinesses);
    categoryFilter.on("change", filterBusinesses);
    
    // Clear search functionality
    searchInput.on("keyup", function(e) {
        if (e.keyCode === 27) { // ESC key
            $(this).val("");
            filterBusinesses();
        }
    });
    
    // Update URL when searching (CSP-safe method)
    searchInput.on("input", function() {
        const searchTerm = $(this).val();
        try {
            if (searchTerm) {
                const url = new URL(window.location.href);
                url.searchParams.set('business_search', searchTerm);
                window.history.replaceState(null, '', url.toString());
            } else {
                const url = new URL(window.location.href);
                url.searchParams.delete('business_search');
                window.history.replaceState(null, '', url.toString());
            }
        } catch (e) {
            // Fallback if URL manipulation fails
            console.log('URL update failed:', e);
        }
    });
    
    // Handle clear search buttons (CSP-safe)
    $(document).on('click', '[data-clear-search]', function(e) {
        e.preventDefault();
        // Remove search parameter and reload
        try {
            const url = new URL(window.location.href);
            url.searchParams.delete('business_search');
            window.location.href = url.toString();
        } catch (e) {
            // Fallback
            window.location.href = window.location.pathname;
        }
    });
    
    // Handle view all buttons (CSP-safe)
    $(document).on('click', '[data-view-all]', function(e) {
        e.preventDefault();
        // Remove search parameter and reload
        try {
            const url = new URL(window.location.href);
            url.searchParams.delete('business_search');
            window.location.href = url.toString();
        } catch (e) {
            // Fallback
            window.location.href = window.location.pathname;
        }
    });
    
  // Add this to the end of your existing business directory script.js file
// (before the closing bracket of the jQuery ready function)

function hideEmptyModules() {
    // Hide empty blurb modules
    $('.et_pb_blurb').each(function() {
        var $blurb = $(this);
        var $container = $blurb.find('.et_pb_blurb_container');
        
        // Check if container is empty or contains only whitespace
        if ($container.length && ($container.is(':empty') || $container.text().trim() === '')) {
            $blurb.hide();
        }
    });
    
    // Hide "Available Jobs" module if no job items are present
    $('.et_pb_text_3_tb_body').each(function() {
        var $textModule = $(this);
        var $jobItems = $textModule.find('.current-job-item');
        
        // If no job items found in this module, hide it
        if ($jobItems.length === 0) {
            $textModule.hide();
        } else {
            // Jobs exist, make sure the module is visible
            $textModule.show();
        }
    });
}

// Run immediately
hideEmptyModules();

// Run again after a short delay (in case shortcodes load later)
setTimeout(hideEmptyModules, 500);

// Run when window loads (final safety net)
$(window).on('load', hideEmptyModules);
});