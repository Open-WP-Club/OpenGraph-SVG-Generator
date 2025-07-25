<?php

/**
 * Plugin Name: OpenGraph SVG Generator
 * Description: Dynamically generates beautiful SVG OpenGraph images using WordPress site title, page titles, and custom avatar. Features modular theme system.
 * Version: 1.2.0
 * Author: Gabriel Kanev
 * Text Domain: og-svg-generator
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('OG_SVG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OG_SVG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('OG_SVG_VERSION', '1.2.0');
define('OG_SVG_MIN_PHP_VERSION', '7.4');
define('OG_SVG_MIN_WP_VERSION', '5.0');

/**
 * Main Plugin Class
 */
class OpenGraphSVGGenerator
{
  private static $instance = null;
  private $components = array();

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    // Check system requirements
    if (!$this->checkRequirements()) {
      return;
    }

    // Hook into WordPress
    add_action('plugins_loaded', array($this, 'init'));
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    register_uninstall_hook(__FILE__, array('OpenGraphSVGGenerator', 'uninstall'));

    // Add settings link
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'addSettingsLink'));
  }

  private function checkRequirements()
  {
    // Check PHP version
    if (version_compare(PHP_VERSION, OG_SVG_MIN_PHP_VERSION, '<')) {
      add_action('admin_notices', function () {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
          __('OpenGraph SVG Generator requires PHP %s or higher. Your current version is %s.', 'og-svg-generator'),
          OG_SVG_MIN_PHP_VERSION,
          PHP_VERSION
        );
        echo '</p></div>';
      });
      return false;
    }

    // Check WordPress version
    global $wp_version;
    if (version_compare($wp_version, OG_SVG_MIN_WP_VERSION, '<')) {
      add_action('admin_notices', function () use ($wp_version) {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
          __('OpenGraph SVG Generator requires WordPress %s or higher. Your current version is %s.', 'og-svg-generator'),
          OG_SVG_MIN_WP_VERSION,
          $wp_version
        );
        echo '</p></div>';
      });
      return false;
    }

    return true;
  }

  public function init()
  {
    // Load text domain
    load_plugin_textdomain('og-svg-generator', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Load required files
    $this->loadIncludes();

    // Initialize components
    try {
      $this->initializeComponents();
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator initialization failed: ' . $e->getMessage());
      add_action('admin_notices', function () use ($e) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($e->getMessage()) . '</p></div>';
      });
      return;
    }

    // Add URL handling
    add_action('init', array($this, 'addRewriteRules'));
    add_filter('query_vars', array($this, 'addQueryVars'));
    add_action('template_redirect', array($this, 'handleSVGRequest'));

    // Add cleanup cron
    if (!wp_next_scheduled('og_svg_cleanup_cron')) {
      wp_schedule_event(time(), 'weekly', 'og_svg_cleanup_cron');
    }
    add_action('og_svg_cleanup_cron', array($this, 'scheduledCleanup'));

    // Create themes directory if it doesn't exist
    $this->ensureThemesDirectory();
  }

  private function loadIncludes()
  {
    // Load theme system first
    $theme_base_path = OG_SVG_PLUGIN_PATH . 'themes/base-theme.php';
    $theme_manager_path = OG_SVG_PLUGIN_PATH . 'themes/theme-manager.php';

    if (file_exists($theme_base_path)) {
      require_once $theme_base_path;
    }

    if (file_exists($theme_manager_path)) {
      require_once $theme_manager_path;
    }

    // Load main includes
    $includes = array(
      'includes/svg-generator.php' => 'OG_SVG_Generator',
      'includes/admin-settings.php' => 'OG_SVG_Admin_Settings',
      'includes/meta-handler.php' => 'OG_SVG_Meta_Handler'
    );

    foreach ($includes as $file => $class) {
      $path = OG_SVG_PLUGIN_PATH . $file;

      if (!file_exists($path)) {
        throw new Exception("Required file not found: {$file}");
      }

      if (!class_exists($class)) {
        require_once $path;
      }
    }

    // Load utilities (no class check needed)
    $utilities_path = OG_SVG_PLUGIN_PATH . 'includes/utilities.php';
    if (file_exists($utilities_path)) {
      require_once $utilities_path;
    }
  }

  private function initializeComponents()
  {
    if (class_exists('OG_SVG_Generator')) {
      $this->components['generator'] = new OG_SVG_Generator();
    } else {
      throw new Exception('SVG Generator class not found');
    }

    if (is_admin() && class_exists('OG_SVG_Admin_Settings')) {
      $this->components['admin'] = new OG_SVG_Admin_Settings();
    }

    if (class_exists('OG_SVG_Meta_Handler')) {
      $this->components['meta'] = new OG_SVG_Meta_Handler();
    }

    // Log successful initialization
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('OpenGraph SVG Generator: All components initialized successfully');
    }
  }

  private function ensureThemesDirectory()
  {
    $themes_dir = OG_SVG_PLUGIN_PATH . 'themes/';
    if (!file_exists($themes_dir)) {
      wp_mkdir_p($themes_dir);
    }
  }

  public function addRewriteRules()
  {
    add_rewrite_rule('^og-svg/home/?$', 'index.php?og_svg_home=1', 'top');
    add_rewrite_rule('^og-svg/([0-9]+)/?$', 'index.php?og_svg_id=$matches[1]', 'top');

    // Force flush if rules don't exist
    $rules = get_option('rewrite_rules');
    if (empty($rules) || !isset($rules['^og-svg/home/?$'])) {
      flush_rewrite_rules(false);
    }
  }

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');

    if ($post_id || $is_home) {
      // Debug logging
      if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('OG SVG: Handling request - post_id: ' . ($post_id ?: 'none') . ', is_home: ' . ($is_home ?: 'no'));
      }

      if (!isset($this->components['generator'])) {
        status_header(500);
        exit('SVG Generator not available');
      }

      try {
        $generator = $this->components['generator'];

        if ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $post = get_post($post_id);
          if (!$post || $post->post_status !== 'publish') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
              error_log('OG SVG: Post not found or not published: ' . $post_id);
            }
            status_header(404);
            exit('Post not found');
          }
          $generator->serveSVG($post_id);
        }

        exit;
      } catch (Exception $e) {
        error_log('OG SVG: Request handling failed - ' . $e->getMessage());
        status_header(500);
        exit('SVG generation failed');
      }
    }
  }

  public function scheduledCleanup()
  {
    if (isset($this->components['generator'])) {
      try {
        $this->components['generator']->cleanupOrphanedFiles();
      } catch (Exception $e) {
        error_log('OG SVG scheduled cleanup failed: ' . $e->getMessage());
      }
    }
  }

  public function activate()
  {
    // Set default options
    $default_options = array(
      'avatar_url' => '',
      'color_scheme' => 'gabriel',
      'show_tagline' => true,
      'enabled_post_types' => array('post', 'page'),
      'fallback_title' => 'Welcome',
      'version' => OG_SVG_VERSION
    );

    if (!get_option('og_svg_settings')) {
      add_option('og_svg_settings', $default_options);
    }

    // Create upload directory
    $upload_dir = wp_upload_dir();
    $svg_dir = $upload_dir['basedir'] . '/og-svg/';
    if (!file_exists($svg_dir)) {
      wp_mkdir_p($svg_dir);
    }

    // Create themes directory
    $themes_dir = OG_SVG_PLUGIN_PATH . 'themes/';
    if (!file_exists($themes_dir)) {
      wp_mkdir_p($themes_dir);
    }

    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public function deactivate()
  {
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove options
    delete_option('og_svg_settings');

    // Remove files
    $upload_dir = wp_upload_dir();
    $svg_dir = $upload_dir['basedir'] . '/og-svg/';

    if (is_dir($svg_dir)) {
      $files = glob($svg_dir . '*');
      foreach ($files as $file) {
        if (is_file($file)) {
          unlink($file);
        }
      }
      rmdir($svg_dir);
    }

    // Remove attachments
    $attachments = get_posts(array(
      'post_type' => 'attachment',
      'meta_query' => array(
        array(
          'key' => '_og_svg_generated',
          'value' => '1',
          'compare' => '='
        )
      ),
      'posts_per_page' => -1,
      'fields' => 'ids'
    ));

    foreach ($attachments as $attachment_id) {
      wp_delete_attachment($attachment_id, true);
    }
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get available themes (for external use)
   */
  public function getAvailableThemes()
  {
    if (isset($this->components['generator'])) {
      return $this->components['generator']->getAvailableThemes();
    }
    return array();
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */
function og_svg_get_url($post_id = null)
{
  $instance = OpenGraphSVGGenerator::getInstance();
  $generator = $instance->getComponent('generator');

  if ($generator) {
    return $generator->getSVGUrl($post_id);
  }

  return '';
}

function og_svg_generate($post_id = null)
{
  $instance = OpenGraphSVGGenerator::getInstance();
  $generator = $instance->getComponent('generator');

  if ($generator) {
    try {
      return $generator->generateSVG($post_id);
    } catch (Exception $e) {
      error_log('OpenGraph SVG generation failed: ' . $e->getMessage());
      return false;
    }
  }

  return false;
}

function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();

  return in_array($post_type, $enabled_types);
}

function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());

  if ($key === null) {
    return $settings;
  }

  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Get available themes
 */
function og_svg_get_themes()
{
  $instance = OpenGraphSVGGenerator::getInstance();
  return $instance->getAvailableThemes();
}
