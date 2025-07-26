/**
 * Updated Admin JavaScript for OpenGraph SVG Generator
 * Handles custom notices and simplified interactions
 */

jQuery(document).ready(function($) {
    
  // Initialize functionality
  initCustomNotices();
  initAvatarUpload();
  initThemeSelection();
  initPreviewGeneration();
  initImageCleanup();
  initBulkGeneration();
  initTroubleshooting();
  initFormValidation();

  // Show custom success message if settings were updated
  if (og_svg_admin.settings_updated) {
    // Small delay to ensure DOM is ready
    setTimeout(function() {
      showCustomNotice('Settings saved successfully! Your OpenGraph images will now use the updated configuration.', 'success');
    }, 100);
  }

  /**
   * Custom notice system
   */
  function initCustomNotices() {
    // Handle dismissal of custom notices
    $(document).on('click', '.og-svg-notice-dismiss', function(e) {
      e.preventDefault();
      $(this).closest('.og-svg-notice').fadeOut(300, function() {
        $(this).remove();
      });
    });

    // Auto-dismiss success notices after 5 seconds
    setTimeout(function() {
      $('.og-svg-notice-success').fadeOut(300, function() {
        $(this).remove();
      });
    }, 5000);
  }

  function showCustomNotice(message, type) {
    type = type || 'success';
    
    // Remove existing notices of the same type
    $('.og-svg-notice-' + type).remove();
    
    var notice = $('<div class="og-svg-notice og-svg-notice-' + type + '">' +
      '<p>' + message + '</p>' +
      '<button type="button" class="og-svg-notice-dismiss">×</button>' +
      '</div>');
    
    $('.og-svg-header').after(notice);
    
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
      scrollTop: notice.offset().top - 20
    }, 300);
  }

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
        
        $('#avatar_url').val(attachment.url);
        updateAvatarPreview(attachment.url);
        showCustomNotice('Avatar updated! Remember to save your settings.', 'info');
      });
      
      mediaUploader.open();
    });

    // Handle avatar removal
    $(document).on('click', '.og-svg-remove-avatar', function(e) {
      e.preventDefault();
      var targetField = $(this).data('target');
      $('#' + targetField).val('');
      $(this).closest('.og-svg-avatar-preview').remove();
    });
  }

  function updateAvatarPreview(imageUrl) {
    var $field = $('.og-svg-field').has('#avatar_url');
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
   * Theme selection
   */
  function initThemeSelection() {
    $('.og-svg-theme-option').on('click', function(e) {
      if (e.target.type !== 'radio') {
        $(this).find('input[type="radio"]').prop('checked', true).trigger('change');
      }
    });

    $('input[name="og_svg_settings[color_scheme]"]').on('change', function() {
      // Visual feedback is handled by CSS
    });
  }

  /**
   * Preview generation
   */
  function initPreviewGeneration() {
    $('#generate_preview_button').on('click', function(e) {
      e.preventDefault();
      
      var $button = $(this);
      var originalHtml = $button.html();
      
      $button.addClass('og-svg-loading').html('<span class="dashicons dashicons-update"></span> Generating...');
      
      var formData = $('.og-svg-form').serialize();
      
      $.ajax({
        url: og_svg_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'og_svg_generate_preview',
          nonce: og_svg_admin.nonce,
          settings_data: formData
        },
        success: function(response) {
          if (response.success) {
            showPreview(response.data.image_url);
            showCustomNotice('Preview generated successfully!', 'success');
          } else {
            showCustomNotice('Preview generation failed: ' + response.data.message, 'error');
          }
        },
        error: function(xhr, status, error) {
          showCustomNotice('Failed to generate preview. Please try again.', 'error');
        },
        complete: function() {
          $button.removeClass('og-svg-loading').html(originalHtml);
        }
      });
    });
  }

  function showPreview(imageUrl) {
    var previewHtml = '<div class="og-svg-preview-result">' +
      '<img src="' + imageUrl + '" class="og-svg-preview-image" alt="OpenGraph Preview" />' +
      '<p style="margin-top: 12px; color: #666; font-size: 13px;">This is how your OpenGraph image will appear when shared on social media.</p>' +
      '</div>';
    
    $('#preview_container').html(previewHtml);
    $('#preview_section').slideDown(300);
    
    $('html, body').animate({
      scrollTop: $('#preview_section').offset().top - 20
    }, 500);
  }

  /**
   * Image cleanup
   */
  function initImageCleanup() {
    $('#cleanup_images_button').on('click', function(e) {
      e.preventDefault();
      
      if (!confirm('Remove all generated SVG images? This cannot be undone.')) {
        return;
      }
      
      var $button = $(this);
      var originalHtml = $button.html();
      
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
            showCustomNotice(response.data.message, 'success');
          } else {
            showCustomNotice('Cleanup failed: ' + response.data.message, 'error');
          }
        },
        error: function() {
          showCustomNotice('Failed to cleanup images. Please try again.', 'error');
        },
        complete: function() {
          $button.removeClass('og-svg-loading').html(originalHtml);
        }
      });
    });
  }

  /**
   * Bulk generation
   */
  function initBulkGeneration() {
    $('#bulk_generate_button').on('click', function(e) {
      e.preventDefault();
      
      var forceRegenerate = $('#force_regenerate').is(':checked');
      var confirmMessage = forceRegenerate 
        ? 'Regenerate ALL OpenGraph images? This may take several minutes.'
        : 'Generate OpenGraph images for all posts without them?';
      
      if (!confirm(confirmMessage)) {
        return;
      }
      
      startBulkGeneration(forceRegenerate);
    });
  }

  function startBulkGeneration(forceRegenerate) {
    var $button = $('#bulk_generate_button');
    var $progress = $('#bulk_progress');
    var $progressFill = $('.og-svg-progress-fill');
    var $progressText = $('.og-svg-progress-text');
    
    $button.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Generating...');
    $progress.show();
    
    processBatch(0, forceRegenerate, function(success, data) {
      $button.prop('disabled', false).html('<span class="dashicons dashicons-images-alt2"></span> Generate All Images');
      
      if (success) {
        $progressFill.css('width', '100%');
        $progressText.text('✓ All images generated successfully!');
        showCustomNotice('Bulk generation completed successfully!', 'success');
        
        setTimeout(function() {
          $progress.fadeOut();
        }, 3000);
      } else {
        $progressText.text('✗ Generation failed');
        showCustomNotice('Bulk generation failed: ' + data.message, 'error');
        
        setTimeout(function() {
          $progress.fadeOut();
        }, 5000);
      }
    });
  }

  function processBatch(offset, forceRegenerate, callback) {
    $.ajax({
      url: og_svg_admin.ajax_url,
      type: 'POST',
      data: {
        action: 'og_svg_bulk_generate',
        nonce: og_svg_admin.nonce,
        offset: offset,
        force: forceRegenerate ? '1' : '0'
      },
      success: function(response) {
        if (response.success) {
          var data = response.data;
          
          if (data.completed) {
            callback(true, data);
          } else {
            var percentage = Math.round((data.processed / data.total) * 100);
            $('.og-svg-progress-fill').css('width', percentage + '%');
            $('.og-svg-progress-text').text(data.message);
            
            if (data.errors && data.errors.length > 0) {
              console.warn('Generation errors:', data.errors);
            }
            
            setTimeout(function() {
              processBatch(data.next_offset, forceRegenerate, callback);
            }, 500);
          }
        } else {
          callback(false, response.data);
        }
      },
      error: function(xhr, status, error) {
        callback(false, { message: 'Request failed: ' + error });
      }
    });
  }

  /**
   * Troubleshooting tools
   */
  function initTroubleshooting() {
    $('#flush_rewrite_button').on('click', function(e) {
      e.preventDefault();
      
      var $button = $(this);
      var originalHtml = $button.html();
      
      $button.addClass('og-svg-loading').html('<span class="dashicons dashicons-update"></span> Fixing...');
      
      $.ajax({
        url: og_svg_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'og_svg_flush_rewrite',
          nonce: og_svg_admin.nonce
        },
        success: function(response) {
          if (response.success) {
            showCustomNotice(response.data.message, 'success');
          } else {
            showCustomNotice('Failed to flush rewrite rules: ' + response.data.message, 'error');
          }
        },
        error: function() {
          showCustomNotice('Failed to flush rewrite rules. Please try again.', 'error');
        },
        complete: function() {
          $button.removeClass('og-svg-loading').html(originalHtml);
        }
      });
    });

    $('#test_url_button').on('click', function(e) {
      e.preventDefault();
      
      var $button = $(this);
      var originalHtml = $button.html();
      
      $button.addClass('og-svg-loading').html('<span class="dashicons dashicons-update"></span> Testing...');
      
      $.ajax({
        url: og_svg_admin.ajax_url,
        type: 'POST',
        data: {
          action: 'og_svg_test_url',
          nonce: og_svg_admin.nonce
        },
        success: function(response) {
          if (response.success) {
            showCustomNotice('URL test passed: ' + response.data.message, 'success');
          } else {
            showCustomNotice('URL test failed: ' + response.data.message, 'error');
          }
        },
        error: function() {
          showCustomNotice('Failed to test URLs. Please try again.', 'error');
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
    $('.og-svg-form').on('submit', function(e) {
      var errors = [];
      
      // Validate avatar URL
      var avatarUrl = $('#avatar_url').val().trim();
      if (avatarUrl && !isValidUrl(avatarUrl)) {
        errors.push('Please enter a valid avatar URL.');
      }
      
      // Validate fallback title
      var fallbackTitle = $('#fallback_title').val().trim();
      if (!fallbackTitle) {
        errors.push('Fallback title is required.');
      }
      
      // Check post types
      var selectedPostTypes = $('input[name="og_svg_settings[enabled_post_types][]"]:checked').length;
      if (selectedPostTypes === 0) {
        errors.push('Please select at least one post type.');
      }
      
      if (errors.length > 0) {
        e.preventDefault();
        showCustomNotice('Please fix these errors: ' + errors.join(', '), 'error');
        return false;
      }
      
      // Show saving message
      showCustomNotice('Saving settings...', 'info');
    });
    
    // Real-time URL validation
    $('#avatar_url').on('blur', function() {
      var url = $(this).val().trim();
      if (url && !isValidUrl(url)) {
        $(this).css('border-color', '#dc3545');
      } else {
        $(this).css('border-color', '#ddd');
      }
    });
  }

  /**
   * Utility functions
   */
  function isValidUrl(string) {
    try {
      new URL(string);
      return true;
    } catch (_) {
      return false;
    }
  }

  // Track form changes for unsaved warning
  var formChanged = false;
  $('.og-svg-form input, .og-svg-form select, .og-svg-form textarea').on('change input', function() {
    formChanged = true;
  });

  // Warn about unsaved changes
  $(window).on('beforeunload', function() {
    if (formChanged) {
      return 'You have unsaved changes. Are you sure you want to leave?';
    }
  });

  // Clear changed flag on form submit
  $('.og-svg-form').on('submit', function() {
    formChanged = false;
  });

  // Smooth animations for interactions
  $('.og-svg-theme-option').on('mouseenter', function() {
    $(this).css('transform', 'translateY(-1px)');
  }).on('mouseleave', function() {
    $(this).css('transform', 'translateY(0)');
  });

  $('.og-svg-button').on('mousedown', function() {
    $(this).css('transform', 'translateY(1px)');
  }).on('mouseup mouseleave', function() {
    $(this).css('transform', 'translateY(0)');
  });

  // Keyboard navigation support
  $('.og-svg-theme-option input[type="radio"]').on('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      $(this).prop('checked', true).trigger('change');
    }
  });

  // Enhanced accessibility
  $('.og-svg-button').on('focus', function() {
    $(this).css('box-shadow', '0 0 0 2px rgba(102, 126, 234, 0.3)');
  }).on('blur', function() {
    $(this).css('box-shadow', 'none');
  });

});