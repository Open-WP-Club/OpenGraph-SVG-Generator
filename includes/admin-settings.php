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

      wp_enqueue_media(); // For avatar upload

      // Enqueue admin CSS
      wp_enqueue_style(
        'og-svg-admin-css',
        OG_SVG_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        OG_SVG_VERSION
      );

      // Enqueue admin JS
      wp_enqueue_script(
        'og-svg-admin',
        OG_SVG_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        OG_SVG_VERSION,
        true
      );
    }

    public function initSettings()
    {
      register_setting(
        'og_svg_settings_group',
        'og_svg_settings',
        array($this, 'sanitizeSettings')
      );

      // General Settings Section
      add_settings_section(
        'og_svg_general_section',
        'General Settings',
        array($this, 'generalSectionCallback'),
        'og-svg-settings'
      );

      // Avatar URL field
      add_settings_field(
        'avatar_url',
        'Avatar Image URL',
        array($this, 'avatarUrlFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      // Color scheme field
      add_settings_field(
        'color_scheme',
        'Color Scheme',
        array($this, 'colorSchemeFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      // Show tagline field
      add_settings_field(
        'show_tagline',
        'Show Site Tagline',
        array($this, 'showTaglineFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      // Fallback title field
      add_settings_field(
        'fallback_title',
        'Fallback Title',
        array($this, 'fallbackTitleFieldCallback'),
        'og-svg-settings',
        'og_svg_general_section'
      );

      // Enabled post types field
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
      echo '<p>Configure your OpenGraph SVG image generation settings below.</p>';
    }

    public function avatarUrlFieldCallback()
    {
      $value = isset($this->settings['avatar_url']) ? $this->settings['avatar_url'] : '';
      echo '<input type="url" id="avatar_url" name="og_svg_settings[avatar_url]" value="' . esc_attr($value) . '" class="regular-text" />';
      echo '<button type="button" class="button" id="upload_avatar_button">Upload Image</button>';
      echo '<p class="description">URL to your avatar image. Recommended size: 200x200px</p>';

      if ($value) {
        echo '<div style="margin-top: 10px;">';
        echo '<img src="' . esc_url($value) . '" style="max-width: 100px; max-height: 100px; border-radius: 50%;" />';
        echo '</div>';
      }
    }

    public function colorSchemeFieldCallback()
    {
      $value = isset($this->settings['color_scheme']) ? $this->settings['color_scheme'] : 'gabriel';
      $schemes = array(
        'gabriel' => 'Gabriel Kanev (Professional Tech)',
        'blue' => 'Professional Blue',
        'purple' => 'Creative Purple',
        'dark' => 'Modern Dark',
        'green' => 'Fresh Green'
      );

      echo '<select id="color_scheme" name="og_svg_settings[color_scheme]">';
      foreach ($schemes as $key => $label) {
        $selected = selected($value, $key, false);
        echo '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($label) . '</option>';
      }
      echo '</select>';
      echo '<p class="description">Choose the color scheme for your OpenGraph images. Gabriel Kanev theme is optimized for your brand.</p>';
    }

    public function showTaglineFieldCallback()
    {
      $value = isset($this->settings['show_tagline']) ? $this->settings['show_tagline'] : true;
      echo '<input type="checkbox" id="show_tagline" name="og_svg_settings[show_tagline]" value="1" ' . checked(1, $value, false) . ' />';
      echo '<label for="show_tagline">Display site tagline in OpenGraph images</label>';
    }

    public function fallbackTitleFieldCallback()
    {
      $value = isset($this->settings['fallback_title']) ? $this->settings['fallback_title'] : 'Welcome';
      echo '<input type="text" id="fallback_title" name="og_svg_settings[fallback_title]" value="' . esc_attr($value) . '" class="regular-text" />';
      echo '<p class="description">Title to use when no page title is available (e.g., homepage)</p>';
    }

    public function enabledPostTypesFieldCallback()
    {
      $value = isset($this->settings['enabled_post_types']) ? $this->settings['enabled_post_types'] : array('post', 'page');
      $post_types = get_post_types(array('public' => true), 'objects');

      echo '<fieldset>';
      foreach ($post_types as $post_type) {
        $checked = in_array($post_type->name, $value) ? 'checked="checked"' : '';
        echo '<label>';
        echo '<input type="checkbox" name="og_svg_settings[enabled_post_types][]" value="' . esc_attr($post_type->name) . '" ' . $checked . ' />';
        echo ' ' . esc_html($post_type->label);
        echo '</label><br>';
      }
      echo '</fieldset>';
      echo '<p class="description">Select which post types should have OpenGraph SVG images generated</p>';
    }

    public function sanitizeSettings($input)
    {
      $sanitized = array();

      // Sanitize avatar URL
      if (isset($input['avatar_url'])) {
        $sanitized['avatar_url'] = esc_url_raw($input['avatar_url']);
      }

      // Sanitize color scheme
      $valid_schemes = array('gabriel', 'blue', 'purple', 'dark', 'green');
      if (isset($input['color_scheme']) && in_array($input['color_scheme'], $valid_schemes)) {
        $sanitized['color_scheme'] = $input['color_scheme'];
      } else {
        $sanitized['color_scheme'] = 'gabriel';
      }

      // Sanitize show tagline
      $sanitized['show_tagline'] = isset($input['show_tagline']) ? true : false;

      // Sanitize fallback title
      if (isset($input['fallback_title'])) {
        $sanitized['fallback_title'] = sanitize_text_field($input['fallback_title']);
      }

      // Sanitize enabled post types
      if (isset($input['enabled_post_types']) && is_array($input['enabled_post_types'])) {
        $sanitized['enabled_post_types'] = array_map('sanitize_text_field', $input['enabled_post_types']);
      } else {
        $sanitized['enabled_post_types'] = array();
      }

      return $sanitized;
    }

    public function settingsPage()
    {
      if (!current_user_can('manage_options')) {
        return;
      }

      // Handle preview generation
      $preview_url = '';
      if (isset($_GET['preview']) && $_GET['preview'] === '1') {
        $generator = new OG_SVG_Generator();
        $preview_url = $generator->getSVGUrl();
      }

?>
      <div class="wrap">
        <h1>OpenGraph SVG Generator Settings</h1>

        <?php if (isset($_GET['settings-updated'])): ?>
          <div class="notice notice-success is-dismissible">
            <p>Settings saved successfully!</p>
          </div>
        <?php endif; ?>

        <div style="display: flex; gap: 20px;">
          <div style="flex: 2;">
            <form action="options.php" method="post">
              <?php
              settings_fields('og_svg_settings_group');
              do_settings_sections('og-svg-settings');
              submit_button();
              ?>
            </form>

            <h2>Preview & Testing</h2>
            <p>Test your OpenGraph image settings:</p>
            <a href="<?php echo admin_url('options-general.php?page=og-svg-settings&preview=1'); ?>" class="button button-secondary">Generate Preview</a>

            <?php if ($preview_url): ?>
              <div style="margin-top: 20px; padding: 20px; border: 1px solid #ccc; background: #f9f9f9;">
                <h3>Preview Image</h3>
                <img src="<?php echo esc_url($preview_url); ?>" style="max-width: 100%; height: auto; border: 1px solid #ddd;" alt="OpenGraph Preview" />
                <p><strong>Image URL:</strong> <code><?php echo esc_html($preview_url); ?></code></p>
              </div>
            <?php endif; ?>
          </div>

          <div style="flex: 1;">
            <div class="postbox">
              <div class="postbox-header">
                <h2 class="hndle">How It Works</h2>
              </div>
              <div class="inside">
                <p><strong>Automatic Integration:</strong> The plugin automatically adds OpenGraph meta tags to your pages.</p>
                <p><strong>Dynamic Content:</strong> Each page gets a unique image with its title and your site branding.</p>
                <p><strong>Performance:</strong> SVG images are generated on-demand and cached for optimal performance.</p>
                <p><strong>Social Sharing:</strong> When someone shares your content on social media, they'll see your custom branded image.</p>
              </div>
            </div>

            <div class="postbox">
              <div class="postbox-header">
                <h2 class="hndle">Quick Tips</h2>
              </div>
              <div class="inside">
                <ul>
                  <li>Avatar images work best at 200x200px</li>
                  <li>Use high-contrast color schemes for better readability</li>
                  <li>Test your images on different social platforms</li>
                  <li>Consider your brand colors when choosing a scheme</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

      <script>
        jQuery(document).ready(function($) {
          $('#upload_avatar_button').click(function(e) {
            e.preventDefault();
            var mediaUploader = wp.media({
              title: 'Choose Avatar Image',
              button: {
                text: 'Use This Image'
              },
              multiple: false
            });

            mediaUploader.on('select', function() {
              var attachment = mediaUploader.state().get('selection').first().toJSON();
              $('#avatar_url').val(attachment.url);
            });

            mediaUploader.open();
          });
        });
      </script>
<?php
    }
  }
} // End class_exists check