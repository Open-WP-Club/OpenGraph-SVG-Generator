/**
 * Admin JavaScript for OpenGraph SVG Generator
 * Enhanced UX with better interactions and error handling
 */

jQuery(document).ready(function($) {
    
    // Initialize admin functionality
    initAvatarUpload();
    initColorSchemePreview();
    initPreviewGeneration();
    initImageCleanup();
    initFormValidation();
    initTooltips();
  
    /**
     * Avatar upload functionality
     */
    function initAvatarUpload() {
      $('#upload_avatar_button').on('click', function(e) {
        e.preventDefault();
        
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
        
        mediaUploader.on('select', function() {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          
          // Update the input field
          $('#avatar_url').val(attachment.url);
          
          // Update preview
          updateAvatarPreview(attachment.url);
          
          // Show success message
          showNotice('Avatar image updated! Don\'t forget to save your settings.', 'success');
        });
        
        mediaUploader.open();
      });
  
      // Handle avatar removal
      $(document).on('click', '.og-svg-remove-avatar', function(e) {
        e.preventDefault();
        var targetField = $(this).data('target');
        $('#' + targetField).val('');
        $(this).closest('.og-svg-avatar-preview').remove();
        showNotice('Avatar removed. Save settings to apply changes.', 'info');
      });
    }
  
    /**
     * Update avatar preview
     */
    function updateAvatarPreview(imageUrl) {
      var $field = $('.og-svg-avatar-field');
      var $existing = $field.find('.og-svg-avatar-preview');
      
      if ($existing.length > 0) {
        $existing.find('img').attr('src', imageUrl);
      } else {
        var previewHtml = '<div class="og-svg-avatar-preview">' +
          '<img src="' + imageUrl + '" alt="Avatar Preview" />' +
          '<button type="button" class="og-svg-remove-avatar" data-target="avatar_url">×</button>' +
          '</div>';
        $field.find('.og-svg-input-group').after(previewHtml);
      }
    }
  
    /**
     * Color scheme preview functionality
     */
    function initColorSchemePreview() {
      $('input[name="og_svg_settings[color_scheme]"]').on('change', function() {
        var $option = $(this).closest('.og-svg-color-option');
        
        // Remove active state from all options
        $('.og-svg-color-option').removeClass('active');
        
        // Add active state to selected option
        $option.addClass('active');
        
        // Update any global preview if needed
        updateGlobalPreview();
      });
  
      // Set initial active state
      $('input[name="og_svg_settings[color_scheme]"]:checked').closest('.og-svg-color-option').addClass('active');
    }
  
    /**
     * Preview generation
     */
    function initPreviewGeneration() {
      $('#generate_preview_button').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalHtml = $button.html();
        
        // Show loading state
        $button.addClass('og-svg-loading').html('<span class="dashicons dashicons-update"></span> Generating...');
        
        // Prepare form data
        var formData = $('.og-svg-settings-form').serialize();
        
        $.ajax({
          url: og_svg_admin.ajax_url,
          type: 'POST',
          data: {
            action: 'og_svg_generate_preview',
            nonce: og_svg_admin.nonce,
            settings: formData
          },
          success: function(response) {
            if (response.success) {
              showPreview(response.data.image_url);
              showNotice(response.data.message, 'success');
            } else {
              showNotice('Error: ' + response.data.message, 'error');
            }
          },
          error: function(xhr, status, error) {
            console.error('Preview generation failed:', error);
            showNotice('Failed to generate preview. Please check console for details.', 'error');
          },
          complete: function() {
            $button.removeClass('og-svg-loading').html(originalHtml);
          }
        });
      });
    }
  
    /**
     * Show preview image
     */
    function showPreview(imageUrl) {
      var previewHtml = '<div class="og-svg-preview-result">' +
        '<img src="' + imageUrl + '" class="og-svg-preview-image" alt="OpenGraph Preview" />' +
        '<div class="og-svg-preview-url">' + imageUrl + '</div>' +
        '<p><strong>Note:</strong> This preview shows how your OpenGraph image will appear when shared on social media.</p>' +
        '</div>';
      
      $('#preview_container').html(previewHtml);
      $('#preview_section').slideDown(300);
      
      // Scroll to preview
      $('html, body').animate({
        scrollTop: $('#preview_section').offset().top - 100
      }, 500);
    }
  
    /**
     * Image cleanup functionality
     */
    function initImageCleanup() {
      $('#cleanup_images_button').on('click', function(e) {
        e.preventDefault();
        
        // Confirm action
        if (!confirm('Are you sure you want to remove all generated SVG images? This action cannot be undone.')) {
          return;
        }
        
        var $button = $(this);
        var originalHtml = $button.html();
        
        // Show loading state
        $button.addClass('og-svg-loading').html('<span class="dashicons dashicons-update"></span> Removing...');
        
        $.ajax({
          url: og_svg_admin.ajax_url,
          type: 'POST',
          data: {
            action: 'og_svg_cleanup_images',
            nonce: og_svg_admin.nonce
          },
          success: function(response) {
            if (response.success) {
              showNotice(response.data.message, 'success');
              updateStats(); // Refresh stats after cleanup
            } else {
              showNotice('Error: ' + response.data.message, 'error');
            }
          },
          error: function(xhr, status, error) {
            console.error('Cleanup failed:', error);
            showNotice('Failed to cleanup images. Please check console for details.', 'error');
          },
          complete: function() {
            $button.removeClass('og-svg-loading').html(originalHtml);
          }
        });
      });
    }
  
    /**
     * Form validation
     */
    function initFormValidation() {
      $('.og-svg-settings-form').on('submit', function(e) {
        var isValid = true;
        var errors = [];
        
        // Validate avatar URL if provided
        var avatarUrl = $('#avatar_url').val().trim();
        if (avatarUrl && !isValidUrl(avatarUrl)) {
          isValid = false;
          errors.push('Please enter a valid URL for the avatar image.');
          $('#avatar_url').addClass('og-svg-field-error');
        } else {
          $('#avatar_url').removeClass('og-svg-field-error');
        }
        
        // Validate fallback title
        var fallbackTitle = $('#fallback_title').val().trim();
        if (!fallbackTitle) {
          isValid = false;
          errors.push('Fallback title is required.');
          $('#fallback_title').addClass('og-svg-field-error');
        } else {
          $('#fallback_title').removeClass('og-svg-field-error');
        }
        
        // Check if at least one post type is selected
        var selectedPostTypes = $('input[name="og_svg_settings[enabled_post_types][]"]:checked').length;
        if (selectedPostTypes === 0) {
          isValid = false;
          errors.push('Please select at least one post type.');
        }
        
        if (!isValid) {
          e.preventDefault();
          showNotice('Please fix the following errors:\n• ' + errors.join('\n• '), 'error');
          return false;
        }
        
        // Show saving notice
        showNotice('Saving settings...', 'info');
      });
      
      // Real-time validation for URL fields
      $('#avatar_url').on('blur', function() {
        var url = $(this).val().trim();
        if (url && !isValidUrl(url)) {
          $(this).addClass('og-svg-field-error');
          showFieldError($(this), 'Please enter a valid URL.');
        } else {
          $(this).removeClass('og-svg-field-error');
          hideFieldError($(this));
        }
      });
    }
  
    /**
     * Initialize tooltips
     */
    function initTooltips() {
      $('[data-tooltip]').each(function() {
        var $element = $(this);
        var tooltipText = $element.attr('data-tooltip');
        
        $element.on('mouseenter', function(e) {
          var tooltip = $('<div class="og-svg-tooltip">' + tooltipText + '</div>');
          $('body').append(tooltip);
          
          var offset = $element.offset();
          var elementWidth = $element.outerWidth();
          var tooltipWidth = tooltip.outerWidth();
          
          tooltip.css({
            top: offset.top - tooltip.outerHeight() - 10,
            left: offset.left + (elementWidth / 2) - (tooltipWidth / 2)
          });
          
          tooltip.fadeIn(200);
        });
        
        $element.on('mouseleave', function() {
          $('.og-svg-tooltip').fadeOut(200, function() {
            $(this).remove();
          });
        });
      });
    }
  
    /**
     * Utility functions
     */
    function showNotice(message, type) {
      type = type || 'info';
      
      var noticeClass = 'notice notice-' + type + ' is-dismissible og-svg-notice';
      var notice = $('<div class="' + noticeClass + '"><p>' + message + '</p></div>');
      
      // Add dismiss button functionality
      notice.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
      
      // Remove existing notices of the same type
      $('.og-svg-notice.notice-' + type).remove();
      
      // Insert notice
      $('.og-svg-header').after(notice);
      
      // Handle dismiss button
      notice.find('.notice-dismiss').on('click', function() {
        notice.fadeOut(300, function() {
          notice.remove();
        });
      });
      
      // Auto-dismiss success messages
      if (type === 'success') {
        setTimeout(function() {
          notice.fadeOut(300, function() {
            notice.remove();
          });
        }, 5000);
      }
      
      // Scroll to notice
      $('html, body').animate({
        scrollTop: notice.offset().top - 100
      }, 300);
    }
  
    function showFieldError($field, message) {
      hideFieldError($field);
      var error = $('<div class="og-svg-field-error-message">' + message + '</div>');
      $field.after(error);
    }
  
    function hideFieldError($field) {
      $field.siblings('.og-svg-field-error-message').remove();
    }
  
    function isValidUrl(string) {
      try {
        new URL(string);
        return true;
      } catch (_) {
        return false;
      }
    }
  
    function updateGlobalPreview() {
      // Update any global previews based on current settings
      // This could be expanded to show live color scheme previews
    }
  
    function updateStats() {
      // Refresh statistics in sidebar
      // This could make an AJAX call to get updated stats
    }
  
    // Handle settings updates
    $(window).on('beforeunload', function() {
      if ($('.og-svg-settings-form').data('changed')) {
        return 'You have unsaved changes. Are you sure you want to leave?';
      }
    });
  
    // Track form changes
    $('.og-svg-settings-form input, .og-svg-settings-form select, .og-svg-settings-form textarea').on('change', function() {
      $('.og-svg-settings-form').data('changed', true);
    });
  
    // Clear changed flag on form submit
    $('.og-svg-settings-form').on('submit', function() {
      $(this).data('changed', false);
    });
  
    // Add smooth animations
    $('.og-svg-color-option').on('click', function() {
      $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
    });
  
    // Handle keyboard navigation for color options
    $('.og-svg-color-option input[type="radio"]').on('keydown', function(e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        $(this).prop('checked', true).trigger('change');
      }
    });
  
    // Initialize progressive enhancement features
    if (typeof IntersectionObserver !== 'undefined') {
      // Lazy load preview images if they come into view
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            // Handle any lazy loading here
          }
        });
      });
  
      // Observe preview containers
      document.querySelectorAll('.og-svg-preview-result').forEach(el => {
        observer.observe(el);
      });
    }
  
    // Debug mode helper
    if (window.location.hash === '#debug') {
      console.log('OpenGraph SVG Generator - Debug Mode');
      console.log('Settings:', og_svg_admin);
      
      // Add debug info to page
      $('body').append('<div style="position: fixed; bottom: 10px; right: 10px; background: #000; color: #fff; padding: 10px; border-radius: 5px; font-size: 12px; z-index: 9999;">Debug Mode Active</div>');
    }
  
  });