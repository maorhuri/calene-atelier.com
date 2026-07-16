/**
 * Admin Fabric Variations JavaScript
 * Media uploader for swatch images
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize media uploader for swatch images
        initSwatchUploader();
    });

    function initSwatchUploader() {
        // Use event delegation for dynamically added variations
        $(document).on('click', '.upload-swatch-image', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $input = $button.siblings('input[id^="_swatch_image"]');
            const $preview = $button.siblings('.swatch-image-preview');
            
            // Create media frame
            const frame = wp.media({
                title: 'בחר תמונת דוגמית בד',
                button: {
                    text: 'השתמש בתמונה זו'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When image is selected
            frame.on('select', function() {
                const attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                
                // Show preview
                if ($preview.length) {
                    $preview.html('<img src="' + attachment.sizes.thumbnail.url + '" style="max-width: 100px; margin-top: 10px;">');
                }
            });
            
            frame.open();
        });
        
        // Remove swatch image
        $(document).on('click', '.remove-swatch-image', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $input = $button.siblings('input[id^="_swatch_image"]');
            const $preview = $button.siblings('.swatch-image-preview');
            
            $input.val('');
            $preview.html('');
        });
    }

})(jQuery);
