jQuery(document).ready(function($) {
    let frame;
    
    // Upload image button
    $('#upload-default-image').on('click', function(e) {
        e.preventDefault();
        
        // If the media frame already exists, reopen it
        if (frame) {
            frame.open();
            return;
        }
        
        // Create a new media frame
        frame = wp.media({
            title: 'Select Default Business Image',
            button: {
                text: 'Use this image'
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });
        
        // When an image is selected in the media frame
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            
            // Set the image ID in the hidden field
            $('#business_directory_default_image').val(attachment.id);
            
            // Update the preview
            const imageUrl = attachment.sizes && attachment.sizes.medium ? 
                           attachment.sizes.medium.url : attachment.url;
            
            $('.image-preview').html(
                '<img src="' + imageUrl + '" style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; padding: 5px;" />'
            );
            
            // Update buttons
            $('#upload-default-image').text('Change Image');
            if ($('#remove-default-image').length === 0) {
                $('#upload-default-image').after('<button type="button" class="button" id="remove-default-image" style="margin-left: 10px;">Remove Image</button>');
            }
        });
        
        // Open the media frame
        frame.open();
    });
    
    // Remove image button
    $(document).on('click', '#remove-default-image', function(e) {
        e.preventDefault();
        
        // Clear the hidden field
        $('#business_directory_default_image').val('');
        
        // Reset the preview
        $('.image-preview').html(
            '<div style="width: 200px; height: 150px; border: 2px dashed #ccc; display: flex; align-items: center; justify-content: center; color: #666;">' +
            'No default image selected' +
            '</div>'
        );
        
        // Update button
        $('#upload-default-image').text('Upload Image');
        $('#remove-default-image').remove();
    });
});