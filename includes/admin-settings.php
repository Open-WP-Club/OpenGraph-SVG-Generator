<?php

/**
 * Admin Settings Class
 * Handles WordPress admin interface for plugin configuration
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('OG_SVG_Admin_Settings')) {

  class OG_SVG_Admin_Settings
  {

    private $settings;

    public function __construct()
    {
      add_action('admin_menu', array($this, 'addAdminMenu'));
      add_action('admin_init', array($this, 'initSettings'));
      add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
      add_action('wp_ajax_og_svg_generate_preview', array($this, 'ajaxGeneratePreview'));
      add_action('wp_ajax_og_svg_cleanup_images', array($this, 'ajaxCleanupImages'));
      add_action('wp_ajax_og_svg_flush_rewrite', array($this, 'ajaxFlushRewrite'));
      add_action('wp_ajax_og_svg_test_url', array($this, 'ajaxTestUrl'));
      add_action('wp_ajax_og_svg_bulk_generate', array($this, 'ajaxBulkGenerate'));

      $this->settings = get_option('og_svg_settings', array());
    }

    public function addAdminMenu()
    {
      add_options_page(
        'OpenGraph SVG Settings',
        'OpenGraph SVG',
        'manage_options',
        'og-svg-settings',
        array($this, 'settingsPage')
      );
    }

    public function enqueueAdminScripts($hook)
    {
      if ($hook !== 'settings_page_og-svg-settings') {
        return;
      }

      wp_enqueue_media();

      wp_enqueue_style(
        'og-svg-admin-css',
        OG_SVG_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        OG_SVG_VERSION
      );

      wp_enqueue_script(
        'og-svg-admin',
        OG_SVG_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        OG_SVG_VERSION,
        true
      );

      wp_localize_script('og-svg-admin', 'og_svg_admin', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('og_svg_admin_nonce'),
        'messages' => array(
          'generating' => __('Generating preview...', 'og-svg-generator'),
          'cleaning' => __('Removing images...', 'og-svg-generator'),
          'success' => __('Operation completed successfully!', 'og-svg-generator'),
          'error' => __('An error occurred. Please try again.', 'og-svg-generator')
        )
      ));
    }

    public function initSettings()
    {
      register_setting(
        'og_svg_settings_group',
        'og_svg_settings',
        array($this, 'sanitizeSettings')
      );

      add_settings_section(
        'og_svg_general_section',
        'General Settings',
        array($this, 'generalSectionCallback'),
        'og-svg-settings'
      );

      add_settings_field(
        'avatar_url',
        'Avatar Image',
        array($this, 'avatarUrlFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      add_settings_field(
        'color_scheme',
        'Color Scheme',
        array($this, 'colorSchemeFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      add_settings_field(
        'show_tagline',
        'Display Options',
        array($this, 'displayOptionsFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      add_settings_field(
        'fallback_title',
        'Fallback Title',
        array($this, 'fallbackTitleFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      add_settings_field(
        'enabled_post_types',
        'Enabled Post Types',
        array($this, 'enabledPostTypesFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );
    }

    public function generalSectionCallback()
    {
      echo '<div class="og-svg-section-intro">';
      echo '<p>Configure your OpenGraph SVG image generation settings. These images will be automatically generated when your content is shared on social media platforms.</p>';
      echo '</div>';
    }

    public function avatarUrlFieldCallback()
    {
      $value = isset($this->settings['avatar_url']) ? $this->settings['avatar_url'] : '';

      echo '<div class="og-svg-avatar-field">';
      echo '<div class="og-svg-input-group">';
      echo '<input type="url" id="avatar_url" name="og_svg_settings[avatar_url]" value="' . esc_attr($value) . '" class="og-svg-field-input" placeholder="https://example.com/avatar.jpg" />';
      echo '<button type="button" class="button og-svg-button-secondary" id="upload_avatar_button">';
      echo '<span class="dashicons dashicons-upload"></span> Upload Image';
      echo '</button>';
      echo '</div>';
      echo '<p class="og-svg-field-description">Upload or enter the URL of your avatar image. Recommended size: 200×200px or larger.</p>';

      if ($value) {
        echo '<div class="og-svg-avatar-preview">';
        echo '<img src="' . esc_url($value) . '" alt="Avatar Preview" />';
        echo '<button type="button" class="button-link og-svg-remove-avatar" data-target="avatar_url">Remove</button>';
        echo '</div>';
      }
      echo '</div>';
    }

    public function colorSchemeFieldCallback()
    {
      $value = isset($this->settings['color_scheme']) ? $this->settings['color_scheme'] : 'gabriel';
      $schemes = array(
        'gabriel' => array(
          'name' => 'Gabriel Kanev',
          'description' => 'Professional tech theme with dark gradients',
          'colors' => array('#0f172a', '#3b82f6', '#06b6d4')
        ),
        'blue' => array(
          'name' => 'Professional Blue',
          'description' => 'Clean blue gradient for business',
          'colors' => array('#1e40af', '#3b82f6', '#60a5fa')
        ),
        'purple' => array(
          'name' => 'Creative Purple',
          'description' => 'Vibrant purple for creative industries',
          'colors' => array('#7c3aed', '#a855f7', '#c084fc')
        ),
        'dark' => array(
          'name' => 'Modern Dark',
          'description' => 'Sleek dark theme for modern brands',
          'colors' => array('#111827', '#374151', '#6b7280')
        ),
        'green' => array(
          'name' => 'Fresh Green',
          'description' => 'Natural green for eco-friendly brands',
          'colors' => array('#059669', '#10b981', '#34d399')
        )
      );

      echo '<div class="og-svg-color-schemes">';
      foreach ($schemes as $key => $scheme) {
        $checked = checked($value, $key, false);
        echo '<div class="og-svg-color-option">';
        echo '<input type="radio" id="color_scheme_' . $key . '" name="og_svg_settings[color_scheme]" value="' . esc_attr($key) . '" ' . $checked . ' />';
        echo '<label for="color_scheme_' . $key . '" class="og-svg-color-label">';
        echo '<div class="og-svg-color-preview">';
        foreach ($scheme['colors'] as $color) {
          echo '<span class="og-svg-color-swatch" style="background-color: ' . $color . '"></span>';
        }
        echo '</div>';
        echo '<div class="og-svg-color-info">';
        echo '<strong>' . esc_html($scheme['name']) . '</strong>';
        echo '<span>' . esc_html($scheme['description']) . '</span>';
        echo '</div>';
        echo '</label>';
        echo '</div>';
      }
      echo '</div>';
    }

    public function displayOptionsFieldCallback()
    {
      $show_tagline = isset($this->settings['show_tagline']) ? $this->settings['show_tagline'] : true;

      echo '<div class="og-svg-checkbox-group">';
      echo '<label class="og-svg-checkbox-item">';
      echo '<input type="checkbox" name="og_svg_settings[show_tagline]" value="1" ' . checked(1, $show_tagline, false) . ' />';
      echo '<span>Display site tagline in OpenGraph images</span>';
      echo '</label>';
      echo '</div>';
      echo '<p class="og-svg-field-description">When enabled, your site\'s tagline will appear below the main title in generated images.</p>';
    }

    public function fallbackTitleFieldCallback()
    {
      $value = isset($this->settings['fallback_title']) ? $this->settings['fallback_title'] : 'Welcome';
      echo '<div class="og-svg-input-group">';
      echo '<input type="text" id="fallback_title" name="og_svg_settings[fallback_title]" value="' . esc_attr($value) . '" class="og-svg-field-input" placeholder="Welcome" />';
      echo '</div>';
      echo '<p class="og-svg-field-description">This title will be used for pages without a specific title, such as your homepage.</p>';
    }

    public function enabledPostTypesFieldCallback()
    {
      $value = isset($this->settings['enabled_post_types']) ? $this->settings['enabled_post_types'] : array('post', 'page');
      $post_types = get_post_types(array('public' => true), 'objects');

      echo '<div class="og-svg-checkbox-group">';
      foreach ($post_types as $post_type) {
        $checked = in_array($post_type->name, $value) ? 'checked="checked"' : '';
        echo '<label class="og-svg-checkbox-item">';
        echo '<input type="checkbox" name="og_svg_settings[enabled_post_types][]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' />';
        echo '<span>' . esc_html($post_type->label) . '</span>';
        echo '</label>';
      }
      echo '</div>';
      echo '<p class="og-svg-field-description">Select which post types should have OpenGraph SVG images automatically generated.</p>';
    }

    public function sanitizeSettings($input)
    {
      $sanitized = array();

      if (isset($input['avatar_url'])) {
        $sanitized['avatar_url'] = esc_url_raw($input['avatar_url']);
      }

      $valid_schemes = array('gabriel', 'blue', 'purple', 'dark', 'green');
      if (isset($input['color_scheme']) && in_array($input['color_scheme'], $valid_schemes)) {
        $sanitized['color_scheme'] = $input['color_scheme'];
      } else {
        $sanitized['color_scheme'] = 'gabriel';
      }

      $sanitized['show_tagline'] = isset($input['show_tagline']) ? true : false;

      if (isset($input['fallback_title'])) {
        $sanitized['fallback_title'] = sanitize_text_field($input['fallback_title']);
      }

      if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
        $sanitized['enabled_post_types'] = array_map('sanitize_text_field', $input['enabled_post_types']);
      } else {
        $sanitized['enabled_post_types'] = array();
      }

      return $sanitized;
    }

    public function ajaxGeneratePreview()
    {
      if (!wp_verify_nonce($_POST['nonce'], 'og_svg_admin_nonce')) {
        wp_die('Security check failed');
      }

      if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
      }

      try {
        $generator = new OG_SVG_Generator();
        $preview_url = $generator->getSVGUrl();

        wp_send_json_success(array(
          'image_url' => $preview_url,
          'message' => 'Preview generated successfully!'
        ));
      } catch (Exception $e) {
        wp_send_json_error(array(
          'message' => 'Failed to generate preview: ' . $e->getMessage()
        ));
      }
    }

    public function ajaxCleanupImages()
    {
      if (!wp_verify_nonce($_POST['nonce'], 'og_svg_admin_nonce')) {
        wp_die('Security check failed');
      }

      if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
      }

      try {
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';

        $count = 0;
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . '*.svg');
          foreach ($files as $file) {
            if (unlink($file)) {
              $count++;
            }
          }

          if (count(glob($svg_dir . '*')) === 0) {
            rmdir($svg_dir);
          }
        }

        $attachments = get_posts(array(
          'post_type' => 'attachment',
          'meta_query' => array(
            array(
              'key' => '_og_svg_generated',
              'value' => '1',
              'compare' => '='
            )
          ),
          'posts_per_page' => -1
        ));

        foreach ($attachments as $attachment) {
          wp_delete_attachment($attachment->ID, true);
        }

        wp_send_json_success(array(
          'message' => sprintf('Successfully removed %d SVG files and %d media library entries.', $count, count($attachments))
        ));
      } catch (Exception $e) {
        wp_send_json_error(array(
          'message' => 'Failed to cleanup images: ' . $e->getMessage()
        ));
      }
    }

    public function ajaxFlushRewrite()
    {
      if (!wp_verify_nonce($_POST['nonce'], 'og_svg_admin_nonce')) {
        wp_die('Security check failed');
      }

      if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
      }

      try {
        flush_rewrite_rules(true);

        wp_send_json_success(array(
          'message' => 'Rewrite rules flushed successfully! Please test the URLs again.'
        ));
      } catch (Exception $e) {
        wp_send_json_error(array(
          'message' => 'Failed to flush rewrite rules: ' . $e->getMessage()
        ));
      }
    }

    public function ajaxTestUrl()
    {
      if (!wp_verify_nonce($_POST['nonce'], 'og_svg_admin_nonce')) {
        wp_die('Security check failed');
      }

      if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
      }

      try {
        $test_url = get_site_url() . '/og-svg/home/';

        $response = wp_safe_remote_head($test_url, array(
          'timeout' => 10,
          'sslverify' => false
        ));

        if (is_wp_error($response)) {
          throw new Exception('URL test failed: ' . $response->get_error_message());
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if ($response_code === 200) {
          wp_send_json_success(array(
            'message' => 'URL is working correctly!',
            'details' => array(
              'url' => $test_url,
              'response_code' => $response_code,
              'content_type' => $content_type
            )
          ));
        } else {
          wp_send_json_error(array(
            'message' => 'URL returned status code: ' . $response_code,
            'details' => array(
              'url' => $test_url,
              'response_code' => $response_code
            )
          ));
        }
      } catch (Exception $e) {
        wp_send_json_error(array(
          'message' => 'URL test failed: ' . $e->getMessage()
        ));
      }
    }

    public function ajaxBulkGenerate()
    {
      if (!wp_verify_nonce($_POST['nonce'], 'og_svg_admin_nonce')) {
        wp_die('Security check failed');
      }

      if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
      }

      try {
        $settings = get_option('og_svg_settings', array());
        $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
        $force_regenerate = isset($_POST['force']) && $_POST['force'];
        $batch_size = 5;
        $offset = intval($_POST['offset'] ?? 0);

        $query_args = array(
          'post_type' => $enabled_types,
          'post_status' => 'publish',
          'posts_per_page' => $batch_size,
          'offset' => $offset,
          'fields' => 'ids'
        );

        $posts = get_posts($query_args);

        if (empty($posts)) {
          wp_send_json_success(array(
            'completed' => true,
            'message' => 'All images generated successfully!',
            'processed' => $offset
          ));
          return;
        }

        $generator = new OG_SVG_Generator();
        $generated = 0;
        $skipped = 0;
        $errors = array();

        foreach ($posts as $post_id) {
          try {
            $file_path = $generator->getSVGFilePath($post_id);

            if (!$force_regenerate && file_exists($file_path)) {
              $skipped++;
            } else {
              $svg_content = $generator->generateSVG($post_id);
              file_put_contents($file_path, $svg_content);
              $generator->saveSVGToMedia($svg_content, $post_id);
              $generated++;
            }
          } catch (Exception $e) {
            $errors[] = "Post {$post_id}: " . $e->getMessage();
          }
        }

        $total_query = array(
          'post_type' => $enabled_types,
          'post_status' => 'publish',
          'posts_per_page' => -1,
          'fields' => 'ids'
        );
        $total_posts = count(get_posts($total_query));

        wp_send_json_success(array(
          'completed' => false,
          'processed' => $offset + count($posts),
          'total' => $total_posts,
          'generated' => $generated,
          'skipped' => $skipped,
          'errors' => $errors,
          'next_offset' => $offset + $batch_size,
          'message' => sprintf(
            'Processed %d/%d posts. Generated: %d, Skipped: %d',
            $offset + count($posts),
            $total_posts,
            $generated,
            $skipped
          )
        ));
      } catch (Exception $e) {
        wp_send_json_error(array(
          'message' => 'Bulk generation failed: ' . $e->getMessage()
        ));
      }
    }

    public function settingsPage()
    {
      if (!current_user_can('manage_options')) {
        return;
      }

      $total_posts = wp_count_posts()->publish ?? 0;
      $enabled_types = $this->settings['enabled_post_types'] ?? array();
      $estimated_images = 0;
      foreach ($enabled_types as $type) {
        $count = wp_count_posts($type);
        $estimated_images += isset($count->publish) ? $count->publish : 0;
      }

?>
      <div class="wrap og-svg-admin-wrap">
        <div class="og-svg-header">
          <h1><span class="dashicons dashicons-share"></span> OpenGraph SVG Generator</h1>
          <p class="og-svg-subtitle">Create beautiful, branded social media preview images automatically</p>
        </div>

        <?php if (isset($_GET['settings-updated'])): ?>
          <div class="notice notice-success is-dismissible">
            <p><strong>Settings saved successfully!</strong> Your OpenGraph images will now use the updated configuration.</p>
          </div>
        <?php endif; ?>

        <div class="og-svg-admin-container">
          <div class="og-svg-main-content">
            <div class="og-svg-settings-card">
              <form method="post" action="options.php" class="og-svg-settings-form">
                <?php
                settings_fields('og_svg_settings_group');
                do_settings_sections('og-svg-settings');
                ?>

                <div class="og-svg-form-actions">
                  <?php submit_button('Save Settings', 'primary', 'submit', false, array('class' => 'button-primary og-svg-button-primary')); ?>
                  <button type="button" class="button og-svg-button-secondary" id="generate_preview_button">
                    <span class="dashicons dashicons-visibility"></span> Generate Preview
                  </button>
                </div>
              </form>
            </div>

            <div class="og-svg-preview-card" id="preview_section" style="display: none;">
              <h2><span class="dashicons dashicons-format-image"></span> Preview</h2>
              <div id="preview_container"></div>
            </div>
          </div>

          <div class="og-svg-sidebar">
            <div class="og-svg-sidebar-card">
              <h3><span class="dashicons dashicons-info"></span> Quick Stats</h3>
              <div class="og-svg-stats">
                <div class="og-svg-stat">
                  <span class="og-svg-stat-number"><?php echo number_format($estimated_images); ?></span>
                  <span class="og-svg-stat-label">Estimated Images</span>
                </div>
                <div class="og-svg-stat">
                  <span class="og-svg-stat-number"><?php echo count($enabled_types); ?></span>
                  <span class="og-svg-stat-label">Enabled Post Types</span>
                </div>
              </div>
            </div>

            <div class="og-svg-sidebar-card">
              <h3><span class="dashicons dashicons-admin-tools"></span> Tools</h3>
              <div class="og-svg-tools">
                <button type="button" class="button button-primary og-svg-full-width" id="bulk_generate_button">
                  <span class="dashicons dashicons-images-alt2"></span> Generate All Images
                </button>
                <p class="og-svg-tool-description">Generate OpenGraph images for all published posts and pages.</p>

                <div class="og-svg-bulk-options" style="margin: 15px 0;">
                  <label>
                    <input type="checkbox" id="force_regenerate" />
                    <span>Force regenerate existing images</span>
                  </label>
                </div>

                <div id="bulk_progress" class="og-svg-progress-container" style="display: none;">
                  <div class="og-svg-progress-bar">
                    <div class="og-svg-progress-fill" style="width: 0%"></div>
                  </div>
                  <div class="og-svg-progress-text">Preparing...</div>
                </div>

                <button type="button" class="button button-secondary og-svg-full-width" id="cleanup_images_button">
                  <span class="dashicons dashicons-trash"></span> Remove All Generated Images
                </button>
                <p class="og-svg-tool-description">This will remove all generated SVG files from your server and media library.</p>

                <button type="button" class="button button-secondary og-svg-full-width" id="flush_rewrite_button">
                  <span class="dashicons dashicons-update"></span> Fix URL Issues
                </button>
                <p class="og-svg-tool-description">If OpenGraph URLs are not working, this will refresh the URL structure.</p>

                <button type="button" class="button button-secondary og-svg-full-width" id="test_url_button">
                  <span class="dashicons dashicons-admin-links"></span> Test URLs
                </button>
                <p class="og-svg-tool-description">Test if the OpenGraph URLs are working correctly.</p>
              </div>
            </div>

            <div class="og-svg-sidebar-card">
              <h3><span class="dashicons dashicons-info"></span> Troubleshooting</h3>
              <div class="og-svg-troubleshooting">
                <div class="og-svg-status-item">
                  <span class="og-svg-status-label">Upload Directory:</span>
                  <span class="og-svg-status-value <?php echo is_writable(wp_upload_dir()['basedir']) ? 'og-svg-status-ok' : 'og-svg-status-error'; ?>">
                    <?php echo is_writable(wp_upload_dir()['basedir']) ? 'Writable' : 'Not Writable'; ?>
                  </span>
                </div>
                <div class="og-svg-status-item">
                  <span class="og-svg-status-label">Permalinks:</span>
                  <span class="og-svg-status-value <?php echo get_option('permalink_structure') ? 'og-svg-status-ok' : 'og-svg-status-warning'; ?>">
                    <?php echo get_option('permalink_structure') ? 'Enabled' : 'Plain URLs'; ?>
                  </span>
                </div>
                <div class="og-svg-status-item">
                  <span class="og-svg-status-label">Test URL:</span>
                  <span class="og-svg-status-value">
                    <a href="<?php echo get_site_url() . '/og-svg/home/'; ?>" target="_blank" class="og-svg-test-link">
                      <?php echo parse_url(get_site_url(), PHP_URL_HOST) . '/og-svg/home/'; ?>
                    </a>
                  </span>
                </div>
              </div>
            </div>

            <div class="og-svg-sidebar-card">
              <h3><span class="dashicons dashicons-lightbulb"></span> How It Works</h3>
              <div class="og-svg-help-content">
                <div class="og-svg-help-item">
                  <span class="dashicons dashicons-yes-alt"></span>
                  <div>
                    <strong>Automatic Integration</strong>
                    <p>The plugin automatically adds OpenGraph meta tags to your pages.</p>
                  </div>
                </div>
                <div class="og-svg-help-item">
                  <span class="dashicons dashicons-update"></span>
                  <div>
                    <strong>Dynamic Content</strong>
                    <p>Each page gets a unique image with its title and your branding.</p>
                  </div>
                </div>
                <div class="og-svg-help-item">
                  <span class="dashicons dashicons-performance"></span>
                  <div>
                    <strong>Optimized Performance</strong>
                    <p>SVG images are lightweight and cached for fast loading.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
<?php
    }
  }
} // End class_exists check