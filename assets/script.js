jQuery(document).ready(function($) {
    const searchInput = $("#business-search");
    const categoryFilter = $("#category-filter");
    const businessCards = $(".business-card");
    
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
        }, 350);
    }
    
    // Check for URL search parameter on page load
    function initializeFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        const searchParam = urlParams.get('business_search');
        
        if (searchParam && searchInput.length > 0) {
            searchInput.val(searchParam);
            setTimeout(function() {
                filterBusinesses();
            }, 100);
        }
    }
    
    // Initialize search from URL parameter
    initializeFromURL();
    
    // Bind events
    searchInput.on("input", filterBusinesses);
    categoryFilter.on("change", filterBusinesses);
    
    // ESC key to clear search
    searchInput.on("keyup", function(e) {
        if (e.keyCode === 27) { // ESC key
            $(this).val("");
            filterBusinesses();
        }
    });
    
    // Update URL when searching
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
            console.log('URL update failed:', e);
        }
    });
    
    // Handle view all buttons only
    $(document).on('click', '[data-view-all]', function(e) {
        e.preventDefault();
        try {
            const url = new URL(window.location.href);
            url.searchParams.delete('business_search');
            window.location.href = url.toString();
        } catch (e) {
            window.location.href = window.location.pathname;
        }
    });
    
    function hideEmptyModules() {
        // Hide empty blurb modules
        $('.et_pb_blurb').each(function() {
            var $blurb = $(this);
            var $container = $blurb.find('.et_pb_blurb_container');
            
            if ($container.length && ($container.is(':empty') || $container.text().trim() === '')) {
                $blurb.hide();
            }
        });
        
        // Hide "Available Jobs" module if no job items are present
        $('.et_pb_text_3_tb_body').each(function() {
            var $textModule = $(this);
            var $jobItems = $textModule.find('.current-job-item');
            
            if ($jobItems.length === 0) {
                $textModule.hide();
            } else {
                $textModule.show();
            }
        });
    }

    hideEmptyModules();
    setTimeout(hideEmptyModules, 500);
    $(window).on('load', hideEmptyModules);
});