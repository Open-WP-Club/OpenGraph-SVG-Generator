/**
 * Admin JavaScript for OpenGraph SVG Generator
 * Handles media upload and admin interactions
 */

jQuery(document).ready(function($) {
    
  // Handle avatar image upload
  $('#upload_avatar_button').on('click', function(e) {
      e.preventDefault();
      
      // Create media uploader instance
      var mediaUploader = wp.media({
          title: 'Choose Avatar Image',
          button: {
              text: 'Use This Image'
          },
          multiple: false,
          library: {
              type: 'image'
          }
      });
      
      // Handle image selection
      mediaUploader.on('select', function() {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          
          // Update the input field
          $('#avatar_url').val(attachment.url);
          
          // Show preview if there's an existing preview container
          var previewContainer = $('#avatar_url').siblings('div').first();
          if (previewContainer.length === 0) {
              // Create preview container if it doesn't exist
              previewContainer = $('<div style="margin-top: 10px;"></div>');
              $('#avatar_url').parent().append(previewContainer);
          }
          
          // Update preview image
          previewContainer.html('<img src="' + attachment.url + '" style="max-width: 100px; max-height: 100px; border-radius: 50%; border: 2px solid #ddd;" alt="Avatar Preview" />');
          
          // Show success message
          showAdminNotice('Avatar image updated! Don\'t forget to save your settings.', 'success');
      });
      
      // Open media uploader
      mediaUploader.open();
  });
  
  // Handle color scheme preview (optional enhancement)
  $('#color_scheme').on('change', function() {
      var selectedScheme = $(this).val();
      showColorSchemePreview(selectedScheme);
  });
  
  // Show initial color scheme preview
  if ($('#color_scheme').length > 0) {
      showColorSchemePreview($('#color_scheme').val());
  }
  
  // Handle preview generation
  $('#generate_preview_button').on('click', function(e) {
      e.preventDefault();
      
      var $button = $(this);
      var originalText = $button.text();
      
      // Show loading state
      $button.text('Generating...').prop('disabled', true);
      
      // Get current form data
      var formData = $('#og_svg_settings_form').serialize();
      
      // Make AJAX request to generate preview
      $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {
              action: 'og_svg_generate_preview',
              nonce: og_svg_admin.nonce,
              settings: formData
          },
          success: function(response) {
              if (response.success) {
                  // Show preview image
                  showPreviewImage(response.data.image_url);
                  showAdminNotice('Preview generated successfully!', 'success');
              } else {
                  showAdminNotice('Error generating preview: ' + response.data.message, 'error');
              }
          },
          error: function() {
              showAdminNotice('Error generating preview. Please try again.', 'error');
          },
          complete: function() {
              // Restore button state
              $button.text(originalText).prop('disabled', false);
          }
      });
  });
  
  // Utility function to show admin notices
  function showAdminNotice(message, type) {
      type = type || 'info';
      
      var noticeClass = 'notice notice-' + type + ' is-dismissible';
      var notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
      
      // Add dismiss button functionality
      notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
      
      // Insert notice after the main heading
      $('.wrap h1').after(notice);
      
      // Handle dismiss button
      notice.find('.notice-dismiss').on('click', function() {
          notice.fadeOut(300, function() {
              notice.remove();
          });
      });
      
      // Auto-dismiss success messages after 5 seconds
      if (type === 'success') {
          setTimeout(function() {
              notice.fadeOut(300, function() {
                  notice.remove();
              });
          }, 5000);
      }
  }
  
  // Function to show color scheme preview
  function showColorSchemePreview(scheme) {
      var colorSchemes = {
          'blue': {
              primary: '#1e40af',
              secondary: '#3b82f6',
              text: '#ffffff'
          },
          'purple': {
              primary: '#7c3aed',
              secondary: '#a855f7',
              text: '#ffffff'
          },
          'dark': {
              primary: '#111827',
              secondary: '#374151',
              text: '#ffffff'
          },
          'green': {
              primary: '#059669',
              secondary: '#10b981',
              text: '#ffffff'
          }
      };
      
      var colors = colorSchemes[scheme] || colorSchemes['blue'];
      
      // Remove existing preview
      $('#color_scheme_preview').remove();
      
      // Create color preview
      var preview = $('<div id="color_scheme_preview" style="margin-top: 10px; padding: 15px; border-radius: 8px; background: linear-gradient(135deg, ' + colors.secondary + ', ' + colors.primary + '); color: ' + colors.text + '; font-weight: bold; text-align: center;">Preview: ' + scheme.charAt(0).toUpperCase() + scheme.slice(1) + ' Color Scheme</div>');
      
      // Add preview after color scheme select
      $('#color_scheme').parent().append(preview);
  }
  
  // Function to show preview image
  function showPreviewImage(imageUrl) {
      // Remove existing preview
      $('#og_svg_preview_container').remove();
      
      // Create preview container
      var previewContainer = $('<div id="og_svg_preview_container" style="margin-top: 20px; padding: 20px; border: 1px solid #ccc; background: #f9f9f9; border-radius: 8px;"><h3>Generated Preview</h3><img src="' + imageUrl + '" style="max-width: 100%; height: auto; border: 1px solid #ddd; border-radius: 8px;" alt="OpenGraph Preview" /><p style="margin-top: 10px;"><strong>Image URL:</strong> <code>' + imageUrl + '</code></p></div>');
      
      // Add preview after the form
      $('form').after(previewContainer);
      
      // Scroll to preview
      $('html, body').animate({
          scrollTop: previewContainer.offset().top - 100
      }, 500);
  }
  
  // Form validation
  $('form').on('submit', function(e) {
      var avatarUrl = $('#avatar_url').val();
      
      // Basic URL validation for avatar
      if (avatarUrl && !isValidUrl(avatarUrl)) {
          e.preventDefault();
          showAdminNotice('Please enter a valid URL for the avatar image.', 'error');
          $('#avatar_url').focus();
          return false;
      }
      
      // Show saving notice
      showAdminNotice('Saving settings...', 'info');
  });
  
  // URL validation helper
  function isValidUrl(string) {
      try {
          new URL(string);
          return true;
      } catch (_) {
          return false;
      }
  }
  
  // Add tooltips for better UX (if you want to enhance further)
  $('[data-tooltip]').each(function() {
      var $element = $(this);
      var tooltipText = $element.attr('data-tooltip');
      
      $element.on('mouseenter', function() {
          var tooltip = $('<div class="og-tooltip">' + tooltipText + '</div>');
          tooltip.css({
              position: 'absolute',
              background: '#333',
              color: '#fff',
              padding: '5px 10px',
              borderRadius: '4px',
              fontSize: '12px',
              zIndex: 9999,
              whiteSpace: 'nowrap'
          });
          
          $('body').append(tooltip);
          
          var offset = $element.offset();
          tooltip.css({
              top: offset.top - tooltip.outerHeight() - 10,
              left: offset.left + ($element.outerWidth() / 2) - (tooltip.outerWidth() / 2)
          });
      });
      
      $element.on('mouseleave', function() {
          $('.og-tooltip').remove();
      });
  });
  
});