// forms.js - Add this to your Business Directory plugin assets folder
jQuery(document).ready(function($) {
    
    // Form validation and enhancement for business submission
    $('.business-submission-form').each(function() {
        const $form = $(this);
        
        // Add required indicators
        $form.find('label').each(function() {
            const $label = $(this);
            const $input = $form.find('#' + $label.attr('for'));
            if ($input.prop('required')) {
                $label.append('<span class="required">*</span>');
            }
        });
        
        // Real-time validation
        $form.find('input[required], textarea[required]').on('blur', function() {
            validateField($(this));
        });
        
        // Form submission handling
        $form.on('submit', function(e) {
            if (!validateForm($form)) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $form.addClass('form-loading');
            $form.find('.submit-btn').prop('disabled', true);
        });
    });
    
    // File upload preview
    $('#business_logo').on('change', function() {
        const file = this.files[0];
        handleFilePreview($(this), file);
    });
    
    // Auto-save form data
    const $formInputs = $('.business-submission-form input, .business-submission-form textarea');
    $formInputs.on('input change', debounce(saveFormData, 1000));
    
    // Load saved form data
    loadFormData();
    
    // Clear saved data on successful submission
    if (window.location.href.indexOf('form_success') > -1) {
        clearFormData();
    }
    
    // Functions
    function validateField($field) {
        const value = $field.val().trim();
        const fieldType = $field.attr('type') || 'text';
        let isValid = true;
        let message = '';
        
        // Remove existing error styling
        $field.closest('.form-group').removeClass('has-error');
        $field.siblings('.field-error').remove();
        
        // Required check
        if ($field.prop('required') && !value) {
            isValid = false;
            message = 'This field is required';
        }
        
        // Type-specific validation
        if (value && isValid) {
            switch (fieldType) {
                case 'email':
                    if (!isValidEmail(value)) {
                        isValid = false;
                        message = 'Please enter a valid email address';
                    }
                    break;
                case 'url':
                    if (!isValidUrl(value)) {
                        isValid = false;
                        message = 'Please enter a valid website URL (including http:// or https://)';
                    }
                    break;
                case 'tel':
                    if (!isValidPhone(value)) {
                        isValid = false;
                        message = 'Please enter a valid phone number';
                    }
                    break;
            }
        }
        
        if (!isValid) {
            $field.closest('.form-group').addClass('has-error');
            $field.after('<span class="field-error">' + message + '</span>');
        }
        
        return isValid;
    }
    
    function validateForm($form) {
        let isValid = true;
        
        // Validate all required fields
        $form.find('input[required], textarea[required]').each(function() {
            if (!validateField($(this))) {
                isValid = false;
            }
        });
        
        // Check if at least one category is selected
        const $categoriesGrid = $form.find('.categories-grid');
        const $checkedCategories = $categoriesGrid.find('input[type="checkbox"]:checked');
        
        // Remove existing error styling
        $categoriesGrid.removeClass('has-error');
        $categoriesGrid.siblings('.checkbox-error').remove();
        
        if ($checkedCategories.length === 0) {
            isValid = false;
            $categoriesGrid.addClass('has-error');
            $categoriesGrid.after('<span class="checkbox-error">Please select at least one business category</span>');
        }
        
        if (!isValid) {
            // Scroll to first error
            const $firstError = $form.find('.has-error, .checkbox-error').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 300);
            }
        }
        
        return isValid;
    }
    
    function handleFilePreview($input, file) {
        // Remove existing preview
        $input.siblings('.file-preview').remove();
        $input.siblings('.field-error').remove();
        
        if (file) {
            // Validate file
            const maxSize = 5 * 1024 * 1024; // 5MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            
            if (file.size > maxSize) {
                $input.after('<span class="field-error">File size must be less than 5MB</span>');
                $input.val('');
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                $input.after('<span class="field-error">Please upload a JPG, PNG, or GIF image</span>');
                $input.val('');
                return;
            }
            
            // Show preview for images
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $input.after(`
                        <div class="file-preview" style="margin-top: 10px;">
                            <img src="${e.target.result}" style="max-width: 150px; max-height: 150px; border-radius: 4px; border: 1px solid #ddd;">
                            <p style="font-size: 12px; color: #666; margin: 5px 0 0 0;">${file.name} (${(file.size / 1024).toFixed(1)} KB)</p>
                        </div>
                    `);
                };
                reader.readAsDataURL(file);
            }
        }
    }
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function isValidUrl(url) {
        try {
            const urlObj = new URL(url);
            return urlObj.protocol === 'http:' || urlObj.protocol === 'https:';
        } catch {
            return false;
        }
    }
    
    function isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10;
    }
    
    function saveFormData() {
        if (typeof(Storage) !== "undefined") {
            const formData = {};
            
            $('.business-submission-form input, .business-submission-form textarea').each(function() {
                const $field = $(this);
                const name = $field.attr('name');
                if (name && $field.attr('type') !== 'file') {
                    if ($field.attr('type') === 'checkbox') {
                        if (!formData[name]) formData[name] = [];
                        if ($field.is(':checked')) {
                            formData[name].push($field.val());
                        }
                    } else {
                        formData[name] = $field.val();
                    }
                }
            });
            
            localStorage.setItem('chelsea_business_form_data', JSON.stringify(formData));
        }
    }
    
    function loadFormData() {
        if (typeof(Storage) !== "undefined") {
            const savedData = localStorage.getItem('chelsea_business_form_data');
            if (savedData) {
                try {
                    const formData = JSON.parse(savedData);
                    
                    Object.keys(formData).forEach(function(key) {
                        if (Array.isArray(formData[key])) {
                            // Handle checkboxes
                            formData[key].forEach(function(value) {
                                $(`input[name="${key}"][value="${value}"]`).prop('checked', true);
                            });
                        } else {
                            // Handle regular inputs
                            $(`input[name="${key}"], textarea[name="${key}"]`).val(formData[key]);
                        }
                    });
                } catch (e) {
                    console.log('Error loading saved form data:', e);
                }
            }
        }
    }
    
    function clearFormData() {
        if (typeof(Storage) !== "undefined") {
            localStorage.removeItem('chelsea_business_form_data');
        }
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Enhanced UX improvements
    $('input, textarea').on('focus', function() {
        $(this).closest('.form-group').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.form-group').removeClass('focused');
    });
    
    // Smooth checkbox animations
    $('.category-option').on('click', function() {
        const $checkbox = $(this).find('input[type="checkbox"]');
        if (!$checkbox.is(':disabled')) {
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        }
    });
    
    $('.category-option input[type="checkbox"]').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Form progress tracking
    function updateFormProgress() {
        const $form = $('.business-submission-form');
        const $requiredFields = $form.find('input[required], textarea[required]');
        const $categoryGrid = $form.find('.categories-grid');
        
        let completed = 0;
        let total = $requiredFields.length + 1; // +1 for categories
        
        // Check required fields
        $requiredFields.each(function() {
            if ($(this).val().trim()) {
                completed++;
            }
        });
        
        // Check categories
        if ($categoryGrid.find('input:checked').length > 0) {
            completed++;
        }
        
        const percentage = Math.round((completed / total) * 100);
        
        // Update or create progress indicator
        let $progress = $('.form-progress');
        if ($progress.length === 0) {
            $form.prepend('<div class="form-progress"></div>');
            $progress = $('.form-progress');
        }
        
        if (percentage === 100) {
            $progress.html('✓ Form complete - ready to submit!').css({
                'background': '#e8f5e8',
                'color': '#2e7d32',
                'border': '1px solid #4caf50'
            });
        } else {
            $progress.html(`Form Progress: ${completed}/${total} sections completed (${percentage}%)`).css({
                'background': '#f8f9fa',
                'color': '#666',
                'border': '1px solid #ddd'
            });
        }
        
        $progress.css({
            'padding': '12px 15px',
            'margin-bottom': '20px',
            'border-radius': '4px',
            'text-align': 'center',
            'font-size': '14px',
            'font-weight': '500'
        });
    }
    
    // Track progress on input changes
    $('.business-submission-form input, .business-submission-form textarea').on('input change', debounce(updateFormProgress, 300));
    
    // Initial progress check
    setTimeout(updateFormProgress, 500);
    
    // Website URL helper
    $('#business_website').on('blur', function() {
        let url = $(this).val().trim();
        if (url && !url.match(/^https?:\/\//)) {
            url = 'https://' + url;
            $(this).val(url);
        }
    });
    
    // Phone number formatting helper
    $('#business_phone').on('input', function() {
        let phone = $(this).val().replace(/\D/g, '');
        if (phone.length >= 6) {
            if (phone.length === 10) {
                phone = phone.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            } else if (phone.length === 11 && phone[0] === '1') {
                phone = phone.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '$1 ($2) $3-$4');
            }
            $(this).val(phone);
        }
    });
    
    // Keywords helper
    $('#search_keywords').on('blur', function() {
        let keywords = $(this).val();
        // Clean up keywords - remove extra spaces, ensure comma separation
        keywords = keywords.split(/[,\n]+/)
            .map(k => k.trim())
            .filter(k => k.length > 0)
            .join(', ');
        $(this).val(keywords);
    });
    
    // Add helpful placeholder text that updates
    const placeholders = {
        business_description: [
            "Tell us about your business, services, and what makes you special...",
            "What products or services do you offer? What's your specialty?",
            "Describe your business in a way that would attract customers...",
            "What makes your business unique in Chelsea?"
        ],
        search_keywords: [
            "restaurant, pizza, delivery, takeout",
            "hair salon, haircut, styling, beauty",
            "auto repair, oil change, mechanic, car service",
            "retail, shopping, clothing, gifts"
        ]
    };
    
    let placeholderIndex = 0;
    setInterval(function() {
        Object.keys(placeholders).forEach(function(fieldName) {
            const $field = $('#' + fieldName);
            if ($field.length && !$field.is(':focus') && !$field.val()) {
                const options = placeholders[fieldName];
                $field.attr('placeholder', options[placeholderIndex % options.length]);
            }
        });
        placeholderIndex++;
    }, 4000);
});