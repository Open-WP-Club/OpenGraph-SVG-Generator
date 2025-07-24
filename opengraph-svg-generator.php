<?php

/**
 * Plugin Name: OpenGraph SVG Generator
 * Description: Dynamically generates beautiful SVG OpenGraph images using WordPress site title, page titles, and custom avatar. Enhanced with media library integration and improved UX.
 * Version: 1.1.0
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
define('OG_SVG_VERSION', '1.1.0');
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
      add_action('admin_notices', function() {
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
      add_action('admin_notices', function() use ($wp_version) {
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

    // Check if required functions exist
    if (!function_exists('wp_upload_dir') || !function_exists('wp_safe_remote_get')) {
      add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo __('OpenGraph SVG Generator requires WordPress core functions that are not available.', 'og-svg-generator');
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

    // Initialize components with error handling
    try {
      $this->initializeComponents();
    } catch (Exception $e) {
      $this->handleError('Initialization failed: ' . $e->getMessage());
      return;
    }

    // Add custom rewrite rules
    add_action('init', array($this, 'addRewriteRules'));
    add_filter('query_vars', array($this, 'addQueryVars'));
    add_action('template_redirect', array($this, 'handleSVGRequest'));

    // Add admin notices for configuration issues
    add_action('admin_notices', array($this, 'checkConfiguration'));

    // Add cleanup cron job
    add_action('og_svg_cleanup_cron', array($this, 'scheduledCleanup'));
    if (!wp_next_scheduled('og_svg_cleanup_cron')) {
      wp_schedule_event(time(), 'weekly', 'og_svg_cleanup_cron');
    }
  }

  private function loadIncludes()
  {
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
      
      if (!class_exists($class)) {
        throw new Exception("Class {$class} not found in {$file}");
      }
    }
  }

  private function initializeComponents()
  {
    // Initialize generator first
    if (class_exists('OG_SVG_Generator')) {
      $this->components['generator'] = new OG_SVG_Generator();
    }

    // Initialize admin settings
    if (is_admin() && class_exists('OG_SVG_Admin_Settings')) {
      $this->components['admin'] = new OG_SVG_Admin_Settings();
    }

    // Initialize meta handler
    if (class_exists('OG_SVG_Meta_Handler')) {
      $this->components['meta'] = new OG_SVG_Meta_Handler();
    }

    // Log successful initialization
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('OpenGraph SVG Generator: All components initialized successfully');
    }
  }

  public function addRewriteRules()
  {
    // Add rewrite rules with higher priority
    add_rewrite_rule('^og-svg/home/?

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    $vars[] = 'og_svg_file';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');
    $file_name = get_query_var('og_svg_file');

    if ($post_id || $is_home || $file_name) {
      try {
        if (!isset($this->components['generator'])) {
          throw new Exception('SVG Generator not initialized');
        }

        $generator = $this->components['generator'];

        if ($file_name) {
          // Direct file access
          $this->serveStaticSVG($file_name);
        } elseif ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $generator->serveSVG($post_id);
        }
        
        exit;
      } catch (Exception $e) {
        $this->handleError('SVG serving failed: ' . $e->getMessage());
        $this->serve404();
      }
    }
  }

  private function serveStaticSVG($filename)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/og-svg/' . $filename;
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new Exception('SVG file not found or not readable');
    }
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    readfile($file_path);
  }

  private function serve404()
  {
    status_header(404);
    echo '<!-- OpenGraph SVG not found -->';
    exit;
  }

  public function checkConfiguration()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'og-svg') === false) {
      return;
    }

    $issues = array();

    // Check upload directory
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
      $issues[] = 'The uploads directory is not writable. SVG files cannot be saved.';
    }

    // Check permalinks
    if (get_option('permalink_structure') === '') {
      $issues[] = 'Pretty permalinks are not enabled. OpenGraph URLs may not work properly.';
    }

    // Check settings
    $settings = get_option('og_svg_settings', array());
    if (empty($settings['enabled_post_types'])) {
      $issues[] = 'No post types are enabled for OpenGraph image generation.';
    }

    // Display issues
    foreach ($issues as $issue) {
      echo '<div class="notice notice-warning"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($issue) . '</p></div>';
    }
  }

  public function scheduledCleanup()
  {
    try {
      if (isset($this->components['generator'])) {
        // Clean up old orphaned files (files without corresponding posts)
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';
        
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . 'og-svg-*.svg');
          
          foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract post ID from filename
            if (preg_match('/og-svg-(\d+)\.svg/', $filename, $matches)) {
              $post_id = $matches[1];
              
              // Check if post still exists
              if (!get_post($post_id)) {
                unlink($file);
                
                // Also remove from media library
                $attachments = get_posts(array(
                  'post_type' => 'attachment',
                  'meta_query' => array(
                    array(
                      'key' => '_og_svg_post_id',
                      'value' => $post_id,
                      'compare' => '='
                    )
                  ),
                  'posts_per_page' => 1,
                  'fields' => 'ids'
                ));
                
                foreach ($attachments as $attachment_id) {
                  wp_delete_attachment($attachment_id, true);
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator scheduled cleanup failed: ' . $e->getMessage());
    }
  }

  public function activate()
  {
    try {
      // Set default options
      $default_options = array(
        'avatar_url' => '',
        'color_scheme' => 'gabriel',
        'show_tagline' => true,
        'enabled_post_types' => array('post', 'page'),
        'fallback_title' => 'Welcome',
        'version' => OG_SVG_VERSION
      );

      // Only set defaults if no options exist
      if (!get_option('og_svg_settings')) {
        add_option('og_svg_settings', $default_options);
      }

      // Create upload directory
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }

      // Flush rewrite rules
      flush_rewrite_rules();
      
      // Set activation flag for welcome notice
      set_transient('og_svg_activated', true, 60);
      
    } catch (Exception $e) {
      $this->handleError('Activation failed: ' . $e->getMessage());
    }
  }

  public function deactivate()
  {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove all plugin options
    delete_option('og_svg_settings');
    delete_option('og_svg_override_seo');
    delete_option('og_svg_twitter_handle');
    
    // Remove all generated SVG files
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
    
    // Remove all OG SVG attachments from media library
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
    
    // Clear any remaining transients
    delete_transient('og_svg_activated');
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  private function handleError($message)
  {
    error_log('OpenGraph SVG Generator Error: ' . $message);
    
    // Show admin notice for critical errors
    if (is_admin()) {
      add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($message) . '</p></div>';
      });
    }
  }

  /**
   * Get plugin component
   */
  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  /**
   * Check if plugin is properly configured
   */
  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get plugin version
   */
  public function getVersion()
  {
    return OG_SVG_VERSION;
  }

  /**
   * Check if current request is for an OG SVG
   */
  public function isOGSVGRequest()
  {
    return get_query_var('og_svg_id') || get_query_var('og_svg_home') || get_query_var('og_svg_file');
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */

/**
 * Get OpenGraph SVG URL for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string SVG URL
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

/**
 * Generate OpenGraph SVG for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string|false SVG content or false on failure
 */
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

/**
 * Check if OpenGraph SVG is enabled for a post type
 * 
 * @param string $post_type Post type to check
 * @return bool
 */
function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();
  
  return in_array($post_type, $enabled_types);
}

/**
 * Get OpenGraph SVG settings
 * 
 * @param string|null $key Specific setting key, null for all settings
 * @return mixed
 */
function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());
  
  if ($key === null) {
    return $settings;
  }
  
  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Hooks for developers
 */

/**
 * Filter: Modify SVG data before generation
 * 
 * @param array $data SVG data array
 * @param int|null $post_id Post ID
 * @return array Modified data
 */
// apply_filters('og_svg_data', $data, $post_id);

/**
 * Filter: Modify color scheme
 * 
 * @param array $colors Color scheme array
 * @param string $scheme_name Scheme name
 * @return array Modified colors
 */
// apply_filters('og_svg_color_scheme', $colors, $scheme_name);

/**
 * Action: After SVG generation
 * 
 * @param string $svg_content Generated SVG content
 * @param int|null $post_id Post ID
 * @param array $data SVG data used
 */
// do_action('og_svg_generated', $svg_content, $post_id, $data);

/**
 * Filter: Modify SVG content before serving
 * 
 * @param string $svg_content SVG content
 * @param int|null $post_id Post ID
 * @return string Modified SVG content
 */
// apply_filters('og_svg_content', $svg_content, $post_id);

/**
 * WP-CLI Commands (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
  
  /**
   * OpenGraph SVG Generator WP-CLI commands
   */
  class OG_SVG_CLI_Commands extends WP_CLI_Command
  {
    
    /**
     * Generate OpenGraph SVG for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Generate only for specific post type
     * 
     * [--force]
     * : Regenerate existing images
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg generate
     *     wp og-svg generate --post-type=post --force
     */
    public function generate($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $settings = get_option('og_svg_settings', array());
      $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
      
      $post_type = $assoc_args['post-type'] ?? null;
      $force = isset($assoc_args['force']);
      
      if ($post_type && !in_array($post_type, $enabled_types)) {
        WP_CLI::error("Post type '{$post_type}' is not enabled for OpenGraph SVG generation");
      }
      
      $query_args = array(
        'post_type' => $post_type ? $post_type : $enabled_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      );
      
      $posts = get_posts($query_args);
      $total = count($posts);
      
      WP_CLI::log("Found {$total} posts to process");
      
      $progress = WP_CLI\Utils\make_progress_bar('Generating OpenGraph images', $total);
      
      $generated = 0;
      $skipped = 0;
      $errors = 0;
      
      foreach ($posts as $post_id) {
        try {
          $file_path = $generator->getSVGFilePath($post_id);
          
          if (!$force && file_exists($file_path)) {
            $skipped++;
          } else {
            $svg_content = $generator->generateSVG($post_id);
            file_put_contents($file_path, $svg_content);
            $generated++;
          }
        } catch (Exception $e) {
          WP_CLI::warning("Failed to generate SVG for post {$post_id}: " . $e->getMessage());
          $errors++;
        }
        
        $progress->tick();
      }
      
      $progress->finish();
      
      WP_CLI::success("Generated: {$generated}, Skipped: {$skipped}, Errors: {$errors}");
    }
    
    /**
     * Clean up orphaned OpenGraph SVG files
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg cleanup
     */
    public function cleanup($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $result = $generator->cleanupAllSVGs();
      
      WP_CLI::success(sprintf(
        'Cleaned up %d files and %d media library entries',
        $result['files_removed'],
        $result['attachments_removed']
      ));
    }
    
    /**
     * Show OpenGraph SVG statistics
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg stats
     */
    public function stats($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $meta_handler = $instance->getComponent('meta');
      
      if (!$meta_handler) {
        WP_CLI::error('OpenGraph SVG Meta Handler not available');
      }
      
      $stats = $meta_handler->getOGImageStats();
      
      WP_CLI::log('OpenGraph SVG Statistics:');
      WP_CLI::log('Total Images: ' . $stats['total_images']);
      
      if (!empty($stats['by_post_type'])) {
        WP_CLI::log('By Post Type:');
        foreach ($stats['by_post_type'] as $type => $count) {
          WP_CLI::log("  {$type}: {$count}");
        }
      }
    }
  }
  
  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}, 'index.php?og_svg_home=1', 'top');
    add_rewrite_rule('^og-svg/([0-9]+)/?

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    $vars[] = 'og_svg_file';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');
    $file_name = get_query_var('og_svg_file');

    if ($post_id || $is_home || $file_name) {
      try {
        if (!isset($this->components['generator'])) {
          throw new Exception('SVG Generator not initialized');
        }

        $generator = $this->components['generator'];

        if ($file_name) {
          // Direct file access
          $this->serveStaticSVG($file_name);
        } elseif ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $generator->serveSVG($post_id);
        }
        
        exit;
      } catch (Exception $e) {
        $this->handleError('SVG serving failed: ' . $e->getMessage());
        $this->serve404();
      }
    }
  }

  private function serveStaticSVG($filename)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/og-svg/' . $filename;
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new Exception('SVG file not found or not readable');
    }
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    readfile($file_path);
  }

  private function serve404()
  {
    status_header(404);
    echo '<!-- OpenGraph SVG not found -->';
    exit;
  }

  public function checkConfiguration()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'og-svg') === false) {
      return;
    }

    $issues = array();

    // Check upload directory
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
      $issues[] = 'The uploads directory is not writable. SVG files cannot be saved.';
    }

    // Check permalinks
    if (get_option('permalink_structure') === '') {
      $issues[] = 'Pretty permalinks are not enabled. OpenGraph URLs may not work properly.';
    }

    // Check settings
    $settings = get_option('og_svg_settings', array());
    if (empty($settings['enabled_post_types'])) {
      $issues[] = 'No post types are enabled for OpenGraph image generation.';
    }

    // Display issues
    foreach ($issues as $issue) {
      echo '<div class="notice notice-warning"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($issue) . '</p></div>';
    }
  }

  public function scheduledCleanup()
  {
    try {
      if (isset($this->components['generator'])) {
        // Clean up old orphaned files (files without corresponding posts)
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';
        
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . 'og-svg-*.svg');
          
          foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract post ID from filename
            if (preg_match('/og-svg-(\d+)\.svg/', $filename, $matches)) {
              $post_id = $matches[1];
              
              // Check if post still exists
              if (!get_post($post_id)) {
                unlink($file);
                
                // Also remove from media library
                $attachments = get_posts(array(
                  'post_type' => 'attachment',
                  'meta_query' => array(
                    array(
                      'key' => '_og_svg_post_id',
                      'value' => $post_id,
                      'compare' => '='
                    )
                  ),
                  'posts_per_page' => 1,
                  'fields' => 'ids'
                ));
                
                foreach ($attachments as $attachment_id) {
                  wp_delete_attachment($attachment_id, true);
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator scheduled cleanup failed: ' . $e->getMessage());
    }
  }

  public function activate()
  {
    try {
      // Set default options
      $default_options = array(
        'avatar_url' => '',
        'color_scheme' => 'gabriel',
        'show_tagline' => true,
        'enabled_post_types' => array('post', 'page'),
        'fallback_title' => 'Welcome',
        'version' => OG_SVG_VERSION
      );

      // Only set defaults if no options exist
      if (!get_option('og_svg_settings')) {
        add_option('og_svg_settings', $default_options);
      }

      // Create upload directory
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }

      // Flush rewrite rules
      flush_rewrite_rules();
      
      // Set activation flag for welcome notice
      set_transient('og_svg_activated', true, 60);
      
    } catch (Exception $e) {
      $this->handleError('Activation failed: ' . $e->getMessage());
    }
  }

  public function deactivate()
  {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove all plugin options
    delete_option('og_svg_settings');
    delete_option('og_svg_override_seo');
    delete_option('og_svg_twitter_handle');
    
    // Remove all generated SVG files
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
    
    // Remove all OG SVG attachments from media library
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
    
    // Clear any remaining transients
    delete_transient('og_svg_activated');
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  private function handleError($message)
  {
    error_log('OpenGraph SVG Generator Error: ' . $message);
    
    // Show admin notice for critical errors
    if (is_admin()) {
      add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($message) . '</p></div>';
      });
    }
  }

  /**
   * Get plugin component
   */
  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  /**
   * Check if plugin is properly configured
   */
  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get plugin version
   */
  public function getVersion()
  {
    return OG_SVG_VERSION;
  }

  /**
   * Check if current request is for an OG SVG
   */
  public function isOGSVGRequest()
  {
    return get_query_var('og_svg_id') || get_query_var('og_svg_home') || get_query_var('og_svg_file');
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */

/**
 * Get OpenGraph SVG URL for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string SVG URL
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

/**
 * Generate OpenGraph SVG for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string|false SVG content or false on failure
 */
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

/**
 * Check if OpenGraph SVG is enabled for a post type
 * 
 * @param string $post_type Post type to check
 * @return bool
 */
function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();
  
  return in_array($post_type, $enabled_types);
}

/**
 * Get OpenGraph SVG settings
 * 
 * @param string|null $key Specific setting key, null for all settings
 * @return mixed
 */
function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());
  
  if ($key === null) {
    return $settings;
  }
  
  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Hooks for developers
 */

/**
 * Filter: Modify SVG data before generation
 * 
 * @param array $data SVG data array
 * @param int|null $post_id Post ID
 * @return array Modified data
 */
// apply_filters('og_svg_data', $data, $post_id);

/**
 * Filter: Modify color scheme
 * 
 * @param array $colors Color scheme array
 * @param string $scheme_name Scheme name
 * @return array Modified colors
 */
// apply_filters('og_svg_color_scheme', $colors, $scheme_name);

/**
 * Action: After SVG generation
 * 
 * @param string $svg_content Generated SVG content
 * @param int|null $post_id Post ID
 * @param array $data SVG data used
 */
// do_action('og_svg_generated', $svg_content, $post_id, $data);

/**
 * Filter: Modify SVG content before serving
 * 
 * @param string $svg_content SVG content
 * @param int|null $post_id Post ID
 * @return string Modified SVG content
 */
// apply_filters('og_svg_content', $svg_content, $post_id);

/**
 * WP-CLI Commands (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
  
  /**
   * OpenGraph SVG Generator WP-CLI commands
   */
  class OG_SVG_CLI_Commands extends WP_CLI_Command
  {
    
    /**
     * Generate OpenGraph SVG for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Generate only for specific post type
     * 
     * [--force]
     * : Regenerate existing images
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg generate
     *     wp og-svg generate --post-type=post --force
     */
    public function generate($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $settings = get_option('og_svg_settings', array());
      $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
      
      $post_type = $assoc_args['post-type'] ?? null;
      $force = isset($assoc_args['force']);
      
      if ($post_type && !in_array($post_type, $enabled_types)) {
        WP_CLI::error("Post type '{$post_type}' is not enabled for OpenGraph SVG generation");
      }
      
      $query_args = array(
        'post_type' => $post_type ? $post_type : $enabled_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      );
      
      $posts = get_posts($query_args);
      $total = count($posts);
      
      WP_CLI::log("Found {$total} posts to process");
      
      $progress = WP_CLI\Utils\make_progress_bar('Generating OpenGraph images', $total);
      
      $generated = 0;
      $skipped = 0;
      $errors = 0;
      
      foreach ($posts as $post_id) {
        try {
          $file_path = $generator->getSVGFilePath($post_id);
          
          if (!$force && file_exists($file_path)) {
            $skipped++;
          } else {
            $svg_content = $generator->generateSVG($post_id);
            file_put_contents($file_path, $svg_content);
            $generated++;
          }
        } catch (Exception $e) {
          WP_CLI::warning("Failed to generate SVG for post {$post_id}: " . $e->getMessage());
          $errors++;
        }
        
        $progress->tick();
      }
      
      $progress->finish();
      
      WP_CLI::success("Generated: {$generated}, Skipped: {$skipped}, Errors: {$errors}");
    }
    
    /**
     * Clean up orphaned OpenGraph SVG files
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg cleanup
     */
    public function cleanup($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $result = $generator->cleanupAllSVGs();
      
      WP_CLI::success(sprintf(
        'Cleaned up %d files and %d media library entries',
        $result['files_removed'],
        $result['attachments_removed']
      ));
    }
    
    /**
     * Show OpenGraph SVG statistics
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg stats
     */
    public function stats($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $meta_handler = $instance->getComponent('meta');
      
      if (!$meta_handler) {
        WP_CLI::error('OpenGraph SVG Meta Handler not available');
      }
      
      $stats = $meta_handler->getOGImageStats();
      
      WP_CLI::log('OpenGraph SVG Statistics:');
      WP_CLI::log('Total Images: ' . $stats['total_images']);
      
      if (!empty($stats['by_post_type'])) {
        WP_CLI::log('By Post Type:');
        foreach ($stats['by_post_type'] as $type => $count) {
          WP_CLI::log("  {$type}: {$count}");
        }
      }
    }
  }
  
  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}, 'index.php?og_svg_id=$matches[1]', 'top');
    
    // Add rule for direct file access (backup)
    add_rewrite_rule('^og-svg/files/(.+\.svg)

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    $vars[] = 'og_svg_file';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');
    $file_name = get_query_var('og_svg_file');

    if ($post_id || $is_home || $file_name) {
      try {
        if (!isset($this->components['generator'])) {
          throw new Exception('SVG Generator not initialized');
        }

        $generator = $this->components['generator'];

        if ($file_name) {
          // Direct file access
          $this->serveStaticSVG($file_name);
        } elseif ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $generator->serveSVG($post_id);
        }
        
        exit;
      } catch (Exception $e) {
        $this->handleError('SVG serving failed: ' . $e->getMessage());
        $this->serve404();
      }
    }
  }

  private function serveStaticSVG($filename)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/og-svg/' . $filename;
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new Exception('SVG file not found or not readable');
    }
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    readfile($file_path);
  }

  private function serve404()
  {
    status_header(404);
    echo '<!-- OpenGraph SVG not found -->';
    exit;
  }

  public function checkConfiguration()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'og-svg') === false) {
      return;
    }

    $issues = array();

    // Check upload directory
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
      $issues[] = 'The uploads directory is not writable. SVG files cannot be saved.';
    }

    // Check permalinks
    if (get_option('permalink_structure') === '') {
      $issues[] = 'Pretty permalinks are not enabled. OpenGraph URLs may not work properly.';
    }

    // Check settings
    $settings = get_option('og_svg_settings', array());
    if (empty($settings['enabled_post_types'])) {
      $issues[] = 'No post types are enabled for OpenGraph image generation.';
    }

    // Display issues
    foreach ($issues as $issue) {
      echo '<div class="notice notice-warning"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($issue) . '</p></div>';
    }
  }

  public function scheduledCleanup()
  {
    try {
      if (isset($this->components['generator'])) {
        // Clean up old orphaned files (files without corresponding posts)
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';
        
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . 'og-svg-*.svg');
          
          foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract post ID from filename
            if (preg_match('/og-svg-(\d+)\.svg/', $filename, $matches)) {
              $post_id = $matches[1];
              
              // Check if post still exists
              if (!get_post($post_id)) {
                unlink($file);
                
                // Also remove from media library
                $attachments = get_posts(array(
                  'post_type' => 'attachment',
                  'meta_query' => array(
                    array(
                      'key' => '_og_svg_post_id',
                      'value' => $post_id,
                      'compare' => '='
                    )
                  ),
                  'posts_per_page' => 1,
                  'fields' => 'ids'
                ));
                
                foreach ($attachments as $attachment_id) {
                  wp_delete_attachment($attachment_id, true);
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator scheduled cleanup failed: ' . $e->getMessage());
    }
  }

  public function activate()
  {
    try {
      // Set default options
      $default_options = array(
        'avatar_url' => '',
        'color_scheme' => 'gabriel',
        'show_tagline' => true,
        'enabled_post_types' => array('post', 'page'),
        'fallback_title' => 'Welcome',
        'version' => OG_SVG_VERSION
      );

      // Only set defaults if no options exist
      if (!get_option('og_svg_settings')) {
        add_option('og_svg_settings', $default_options);
      }

      // Create upload directory
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }

      // Flush rewrite rules
      flush_rewrite_rules();
      
      // Set activation flag for welcome notice
      set_transient('og_svg_activated', true, 60);
      
    } catch (Exception $e) {
      $this->handleError('Activation failed: ' . $e->getMessage());
    }
  }

  public function deactivate()
  {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove all plugin options
    delete_option('og_svg_settings');
    delete_option('og_svg_override_seo');
    delete_option('og_svg_twitter_handle');
    
    // Remove all generated SVG files
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
    
    // Remove all OG SVG attachments from media library
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
    
    // Clear any remaining transients
    delete_transient('og_svg_activated');
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  private function handleError($message)
  {
    error_log('OpenGraph SVG Generator Error: ' . $message);
    
    // Show admin notice for critical errors
    if (is_admin()) {
      add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($message) . '</p></div>';
      });
    }
  }

  /**
   * Get plugin component
   */
  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  /**
   * Check if plugin is properly configured
   */
  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get plugin version
   */
  public function getVersion()
  {
    return OG_SVG_VERSION;
  }

  /**
   * Check if current request is for an OG SVG
   */
  public function isOGSVGRequest()
  {
    return get_query_var('og_svg_id') || get_query_var('og_svg_home') || get_query_var('og_svg_file');
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */

/**
 * Get OpenGraph SVG URL for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string SVG URL
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

/**
 * Generate OpenGraph SVG for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string|false SVG content or false on failure
 */
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

/**
 * Check if OpenGraph SVG is enabled for a post type
 * 
 * @param string $post_type Post type to check
 * @return bool
 */
function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();
  
  return in_array($post_type, $enabled_types);
}

/**
 * Get OpenGraph SVG settings
 * 
 * @param string|null $key Specific setting key, null for all settings
 * @return mixed
 */
function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());
  
  if ($key === null) {
    return $settings;
  }
  
  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Hooks for developers
 */

/**
 * Filter: Modify SVG data before generation
 * 
 * @param array $data SVG data array
 * @param int|null $post_id Post ID
 * @return array Modified data
 */
// apply_filters('og_svg_data', $data, $post_id);

/**
 * Filter: Modify color scheme
 * 
 * @param array $colors Color scheme array
 * @param string $scheme_name Scheme name
 * @return array Modified colors
 */
// apply_filters('og_svg_color_scheme', $colors, $scheme_name);

/**
 * Action: After SVG generation
 * 
 * @param string $svg_content Generated SVG content
 * @param int|null $post_id Post ID
 * @param array $data SVG data used
 */
// do_action('og_svg_generated', $svg_content, $post_id, $data);

/**
 * Filter: Modify SVG content before serving
 * 
 * @param string $svg_content SVG content
 * @param int|null $post_id Post ID
 * @return string Modified SVG content
 */
// apply_filters('og_svg_content', $svg_content, $post_id);

/**
 * WP-CLI Commands (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
  
  /**
   * OpenGraph SVG Generator WP-CLI commands
   */
  class OG_SVG_CLI_Commands extends WP_CLI_Command
  {
    
    /**
     * Generate OpenGraph SVG for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Generate only for specific post type
     * 
     * [--force]
     * : Regenerate existing images
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg generate
     *     wp og-svg generate --post-type=post --force
     */
    public function generate($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $settings = get_option('og_svg_settings', array());
      $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
      
      $post_type = $assoc_args['post-type'] ?? null;
      $force = isset($assoc_args['force']);
      
      if ($post_type && !in_array($post_type, $enabled_types)) {
        WP_CLI::error("Post type '{$post_type}' is not enabled for OpenGraph SVG generation");
      }
      
      $query_args = array(
        'post_type' => $post_type ? $post_type : $enabled_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      );
      
      $posts = get_posts($query_args);
      $total = count($posts);
      
      WP_CLI::log("Found {$total} posts to process");
      
      $progress = WP_CLI\Utils\make_progress_bar('Generating OpenGraph images', $total);
      
      $generated = 0;
      $skipped = 0;
      $errors = 0;
      
      foreach ($posts as $post_id) {
        try {
          $file_path = $generator->getSVGFilePath($post_id);
          
          if (!$force && file_exists($file_path)) {
            $skipped++;
          } else {
            $svg_content = $generator->generateSVG($post_id);
            file_put_contents($file_path, $svg_content);
            $generated++;
          }
        } catch (Exception $e) {
          WP_CLI::warning("Failed to generate SVG for post {$post_id}: " . $e->getMessage());
          $errors++;
        }
        
        $progress->tick();
      }
      
      $progress->finish();
      
      WP_CLI::success("Generated: {$generated}, Skipped: {$skipped}, Errors: {$errors}");
    }
    
    /**
     * Clean up orphaned OpenGraph SVG files
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg cleanup
     */
    public function cleanup($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $result = $generator->cleanupAllSVGs();
      
      WP_CLI::success(sprintf(
        'Cleaned up %d files and %d media library entries',
        $result['files_removed'],
        $result['attachments_removed']
      ));
    }
    
    /**
     * Show OpenGraph SVG statistics
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg stats
     */
    public function stats($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $meta_handler = $instance->getComponent('meta');
      
      if (!$meta_handler) {
        WP_CLI::error('OpenGraph SVG Meta Handler not available');
      }
      
      $stats = $meta_handler->getOGImageStats();
      
      WP_CLI::log('OpenGraph SVG Statistics:');
      WP_CLI::log('Total Images: ' . $stats['total_images']);
      
      if (!empty($stats['by_post_type'])) {
        WP_CLI::log('By Post Type:');
        foreach ($stats['by_post_type'] as $type => $count) {
          WP_CLI::log("  {$type}: {$count}");
        }
      }
    }
  }
  
  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}, 'index.php?og_svg_file=$matches[1]', 'top');
    
    // Force flush rewrite rules if they don't exist
    $rules = get_option('rewrite_rules');
    if (empty($rules) || !isset($rules['^og-svg/home/?

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    $vars[] = 'og_svg_file';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');
    $file_name = get_query_var('og_svg_file');

    if ($post_id || $is_home || $file_name) {
      try {
        if (!isset($this->components['generator'])) {
          throw new Exception('SVG Generator not initialized');
        }

        $generator = $this->components['generator'];

        if ($file_name) {
          // Direct file access
          $this->serveStaticSVG($file_name);
        } elseif ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $generator->serveSVG($post_id);
        }
        
        exit;
      } catch (Exception $e) {
        $this->handleError('SVG serving failed: ' . $e->getMessage());
        $this->serve404();
      }
    }
  }

  private function serveStaticSVG($filename)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/og-svg/' . $filename;
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new Exception('SVG file not found or not readable');
    }
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    readfile($file_path);
  }

  private function serve404()
  {
    status_header(404);
    echo '<!-- OpenGraph SVG not found -->';
    exit;
  }

  public function checkConfiguration()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'og-svg') === false) {
      return;
    }

    $issues = array();

    // Check upload directory
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
      $issues[] = 'The uploads directory is not writable. SVG files cannot be saved.';
    }

    // Check permalinks
    if (get_option('permalink_structure') === '') {
      $issues[] = 'Pretty permalinks are not enabled. OpenGraph URLs may not work properly.';
    }

    // Check settings
    $settings = get_option('og_svg_settings', array());
    if (empty($settings['enabled_post_types'])) {
      $issues[] = 'No post types are enabled for OpenGraph image generation.';
    }

    // Display issues
    foreach ($issues as $issue) {
      echo '<div class="notice notice-warning"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($issue) . '</p></div>';
    }
  }

  public function scheduledCleanup()
  {
    try {
      if (isset($this->components['generator'])) {
        // Clean up old orphaned files (files without corresponding posts)
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';
        
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . 'og-svg-*.svg');
          
          foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract post ID from filename
            if (preg_match('/og-svg-(\d+)\.svg/', $filename, $matches)) {
              $post_id = $matches[1];
              
              // Check if post still exists
              if (!get_post($post_id)) {
                unlink($file);
                
                // Also remove from media library
                $attachments = get_posts(array(
                  'post_type' => 'attachment',
                  'meta_query' => array(
                    array(
                      'key' => '_og_svg_post_id',
                      'value' => $post_id,
                      'compare' => '='
                    )
                  ),
                  'posts_per_page' => 1,
                  'fields' => 'ids'
                ));
                
                foreach ($attachments as $attachment_id) {
                  wp_delete_attachment($attachment_id, true);
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator scheduled cleanup failed: ' . $e->getMessage());
    }
  }

  public function activate()
  {
    try {
      // Set default options
      $default_options = array(
        'avatar_url' => '',
        'color_scheme' => 'gabriel',
        'show_tagline' => true,
        'enabled_post_types' => array('post', 'page'),
        'fallback_title' => 'Welcome',
        'version' => OG_SVG_VERSION
      );

      // Only set defaults if no options exist
      if (!get_option('og_svg_settings')) {
        add_option('og_svg_settings', $default_options);
      }

      // Create upload directory
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }

      // Flush rewrite rules
      flush_rewrite_rules();
      
      // Set activation flag for welcome notice
      set_transient('og_svg_activated', true, 60);
      
    } catch (Exception $e) {
      $this->handleError('Activation failed: ' . $e->getMessage());
    }
  }

  public function deactivate()
  {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove all plugin options
    delete_option('og_svg_settings');
    delete_option('og_svg_override_seo');
    delete_option('og_svg_twitter_handle');
    
    // Remove all generated SVG files
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
    
    // Remove all OG SVG attachments from media library
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
    
    // Clear any remaining transients
    delete_transient('og_svg_activated');
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  private function handleError($message)
  {
    error_log('OpenGraph SVG Generator Error: ' . $message);
    
    // Show admin notice for critical errors
    if (is_admin()) {
      add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($message) . '</p></div>';
      });
    }
  }

  /**
   * Get plugin component
   */
  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  /**
   * Check if plugin is properly configured
   */
  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get plugin version
   */
  public function getVersion()
  {
    return OG_SVG_VERSION;
  }

  /**
   * Check if current request is for an OG SVG
   */
  public function isOGSVGRequest()
  {
    return get_query_var('og_svg_id') || get_query_var('og_svg_home') || get_query_var('og_svg_file');
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */

/**
 * Get OpenGraph SVG URL for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string SVG URL
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

/**
 * Generate OpenGraph SVG for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string|false SVG content or false on failure
 */
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

/**
 * Check if OpenGraph SVG is enabled for a post type
 * 
 * @param string $post_type Post type to check
 * @return bool
 */
function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();
  
  return in_array($post_type, $enabled_types);
}

/**
 * Get OpenGraph SVG settings
 * 
 * @param string|null $key Specific setting key, null for all settings
 * @return mixed
 */
function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());
  
  if ($key === null) {
    return $settings;
  }
  
  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Hooks for developers
 */

/**
 * Filter: Modify SVG data before generation
 * 
 * @param array $data SVG data array
 * @param int|null $post_id Post ID
 * @return array Modified data
 */
// apply_filters('og_svg_data', $data, $post_id);

/**
 * Filter: Modify color scheme
 * 
 * @param array $colors Color scheme array
 * @param string $scheme_name Scheme name
 * @return array Modified colors
 */
// apply_filters('og_svg_color_scheme', $colors, $scheme_name);

/**
 * Action: After SVG generation
 * 
 * @param string $svg_content Generated SVG content
 * @param int|null $post_id Post ID
 * @param array $data SVG data used
 */
// do_action('og_svg_generated', $svg_content, $post_id, $data);

/**
 * Filter: Modify SVG content before serving
 * 
 * @param string $svg_content SVG content
 * @param int|null $post_id Post ID
 * @return string Modified SVG content
 */
// apply_filters('og_svg_content', $svg_content, $post_id);

/**
 * WP-CLI Commands (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
  
  /**
   * OpenGraph SVG Generator WP-CLI commands
   */
  class OG_SVG_CLI_Commands extends WP_CLI_Command
  {
    
    /**
     * Generate OpenGraph SVG for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Generate only for specific post type
     * 
     * [--force]
     * : Regenerate existing images
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg generate
     *     wp og-svg generate --post-type=post --force
     */
    public function generate($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $settings = get_option('og_svg_settings', array());
      $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
      
      $post_type = $assoc_args['post-type'] ?? null;
      $force = isset($assoc_args['force']);
      
      if ($post_type && !in_array($post_type, $enabled_types)) {
        WP_CLI::error("Post type '{$post_type}' is not enabled for OpenGraph SVG generation");
      }
      
      $query_args = array(
        'post_type' => $post_type ? $post_type : $enabled_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      );
      
      $posts = get_posts($query_args);
      $total = count($posts);
      
      WP_CLI::log("Found {$total} posts to process");
      
      $progress = WP_CLI\Utils\make_progress_bar('Generating OpenGraph images', $total);
      
      $generated = 0;
      $skipped = 0;
      $errors = 0;
      
      foreach ($posts as $post_id) {
        try {
          $file_path = $generator->getSVGFilePath($post_id);
          
          if (!$force && file_exists($file_path)) {
            $skipped++;
          } else {
            $svg_content = $generator->generateSVG($post_id);
            file_put_contents($file_path, $svg_content);
            $generated++;
          }
        } catch (Exception $e) {
          WP_CLI::warning("Failed to generate SVG for post {$post_id}: " . $e->getMessage());
          $errors++;
        }
        
        $progress->tick();
      }
      
      $progress->finish();
      
      WP_CLI::success("Generated: {$generated}, Skipped: {$skipped}, Errors: {$errors}");
    }
    
    /**
     * Clean up orphaned OpenGraph SVG files
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg cleanup
     */
    public function cleanup($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $result = $generator->cleanupAllSVGs();
      
      WP_CLI::success(sprintf(
        'Cleaned up %d files and %d media library entries',
        $result['files_removed'],
        $result['attachments_removed']
      ));
    }
    
    /**
     * Show OpenGraph SVG statistics
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg stats
     */
    public function stats($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $meta_handler = $instance->getComponent('meta');
      
      if (!$meta_handler) {
        WP_CLI::error('OpenGraph SVG Meta Handler not available');
      }
      
      $stats = $meta_handler->getOGImageStats();
      
      WP_CLI::log('OpenGraph SVG Statistics:');
      WP_CLI::log('Total Images: ' . $stats['total_images']);
      
      if (!empty($stats['by_post_type'])) {
        WP_CLI::log('By Post Type:');
        foreach ($stats['by_post_type'] as $type => $count) {
          WP_CLI::log("  {$type}: {$count}");
        }
      }
    }
  }
  
  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}])) {
      flush_rewrite_rules(false);
    }
  }

  public function addQueryVars($vars)
  {
    $vars[] = 'og_svg_id';
    $vars[] = 'og_svg_home';
    $vars[] = 'og_svg_file';
    return $vars;
  }

  public function handleSVGRequest()
  {
    $post_id = get_query_var('og_svg_id');
    $is_home = get_query_var('og_svg_home');
    $file_name = get_query_var('og_svg_file');

    if ($post_id || $is_home || $file_name) {
      try {
        if (!isset($this->components['generator'])) {
          throw new Exception('SVG Generator not initialized');
        }

        $generator = $this->components['generator'];

        if ($file_name) {
          // Direct file access
          $this->serveStaticSVG($file_name);
        } elseif ($is_home) {
          $generator->serveSVG();
        } elseif ($post_id) {
          $generator->serveSVG($post_id);
        }
        
        exit;
      } catch (Exception $e) {
        $this->handleError('SVG serving failed: ' . $e->getMessage());
        $this->serve404();
      }
    }
  }

  private function serveStaticSVG($filename)
  {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/og-svg/' . $filename;
    
    if (!file_exists($file_path) || !is_readable($file_path)) {
      throw new Exception('SVG file not found or not readable');
    }
    
    // Set headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');
    header('Content-Length: ' . filesize($file_path));
    
    // Output file
    readfile($file_path);
  }

  private function serve404()
  {
    status_header(404);
    echo '<!-- OpenGraph SVG not found -->';
    exit;
  }

  public function checkConfiguration()
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'og-svg') === false) {
      return;
    }

    $issues = array();

    // Check upload directory
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
      $issues[] = 'The uploads directory is not writable. SVG files cannot be saved.';
    }

    // Check permalinks
    if (get_option('permalink_structure') === '') {
      $issues[] = 'Pretty permalinks are not enabled. OpenGraph URLs may not work properly.';
    }

    // Check settings
    $settings = get_option('og_svg_settings', array());
    if (empty($settings['enabled_post_types'])) {
      $issues[] = 'No post types are enabled for OpenGraph image generation.';
    }

    // Display issues
    foreach ($issues as $issue) {
      echo '<div class="notice notice-warning"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($issue) . '</p></div>';
    }
  }

  public function scheduledCleanup()
  {
    try {
      if (isset($this->components['generator'])) {
        // Clean up old orphaned files (files without corresponding posts)
        $upload_dir = wp_upload_dir();
        $svg_dir = $upload_dir['basedir'] . '/og-svg/';
        
        if (is_dir($svg_dir)) {
          $files = glob($svg_dir . 'og-svg-*.svg');
          
          foreach ($files as $file) {
            $filename = basename($file);
            
            // Extract post ID from filename
            if (preg_match('/og-svg-(\d+)\.svg/', $filename, $matches)) {
              $post_id = $matches[1];
              
              // Check if post still exists
              if (!get_post($post_id)) {
                unlink($file);
                
                // Also remove from media library
                $attachments = get_posts(array(
                  'post_type' => 'attachment',
                  'meta_query' => array(
                    array(
                      'key' => '_og_svg_post_id',
                      'value' => $post_id,
                      'compare' => '='
                    )
                  ),
                  'posts_per_page' => 1,
                  'fields' => 'ids'
                ));
                
                foreach ($attachments as $attachment_id) {
                  wp_delete_attachment($attachment_id, true);
                }
              }
            }
          }
        }
      }
    } catch (Exception $e) {
      error_log('OpenGraph SVG Generator scheduled cleanup failed: ' . $e->getMessage());
    }
  }

  public function activate()
  {
    try {
      // Set default options
      $default_options = array(
        'avatar_url' => '',
        'color_scheme' => 'gabriel',
        'show_tagline' => true,
        'enabled_post_types' => array('post', 'page'),
        'fallback_title' => 'Welcome',
        'version' => OG_SVG_VERSION
      );

      // Only set defaults if no options exist
      if (!get_option('og_svg_settings')) {
        add_option('og_svg_settings', $default_options);
      }

      // Create upload directory
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }

      // Flush rewrite rules
      flush_rewrite_rules();
      
      // Set activation flag for welcome notice
      set_transient('og_svg_activated', true, 60);
      
    } catch (Exception $e) {
      $this->handleError('Activation failed: ' . $e->getMessage());
    }
  }

  public function deactivate()
  {
    // Clear scheduled cleanup
    wp_clear_scheduled_hook('og_svg_cleanup_cron');
    
    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public static function uninstall()
  {
    // Remove all plugin options
    delete_option('og_svg_settings');
    delete_option('og_svg_override_seo');
    delete_option('og_svg_twitter_handle');
    
    // Remove all generated SVG files
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
    
    // Remove all OG SVG attachments from media library
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
    
    // Clear any remaining transients
    delete_transient('og_svg_activated');
  }

  public function addSettingsLink($links)
  {
    $settings_link = '<a href="' . admin_url('options-general.php?page=og-svg-settings') . '">' . __('Settings', 'og-svg-generator') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  private function handleError($message)
  {
    error_log('OpenGraph SVG Generator Error: ' . $message);
    
    // Show admin notice for critical errors
    if (is_admin()) {
      add_action('admin_notices', function() use ($message) {
        echo '<div class="notice notice-error"><p><strong>OpenGraph SVG Generator:</strong> ' . esc_html($message) . '</p></div>';
      });
    }
  }

  /**
   * Get plugin component
   */
  public function getComponent($name)
  {
    return isset($this->components[$name]) ? $this->components[$name] : null;
  }

  /**
   * Check if plugin is properly configured
   */
  public function isConfigured()
  {
    $settings = get_option('og_svg_settings', array());
    return !empty($settings['enabled_post_types']);
  }

  /**
   * Get plugin version
   */
  public function getVersion()
  {
    return OG_SVG_VERSION;
  }

  /**
   * Check if current request is for an OG SVG
   */
  public function isOGSVGRequest()
  {
    return get_query_var('og_svg_id') || get_query_var('og_svg_home') || get_query_var('og_svg_file');
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();

/**
 * Helper functions for developers
 */

/**
 * Get OpenGraph SVG URL for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string SVG URL
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

/**
 * Generate OpenGraph SVG for a post
 * 
 * @param int|null $post_id Post ID, null for homepage
 * @return string|false SVG content or false on failure
 */
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

/**
 * Check if OpenGraph SVG is enabled for a post type
 * 
 * @param string $post_type Post type to check
 * @return bool
 */
function og_svg_is_enabled_for_post_type($post_type)
{
  $settings = get_option('og_svg_settings', array());
  $enabled_types = $settings['enabled_post_types'] ?? array();
  
  return in_array($post_type, $enabled_types);
}

/**
 * Get OpenGraph SVG settings
 * 
 * @param string|null $key Specific setting key, null for all settings
 * @return mixed
 */
function og_svg_get_setting($key = null)
{
  $settings = get_option('og_svg_settings', array());
  
  if ($key === null) {
    return $settings;
  }
  
  return isset($settings[$key]) ? $settings[$key] : null;
}

/**
 * Hooks for developers
 */

/**
 * Filter: Modify SVG data before generation
 * 
 * @param array $data SVG data array
 * @param int|null $post_id Post ID
 * @return array Modified data
 */
// apply_filters('og_svg_data', $data, $post_id);

/**
 * Filter: Modify color scheme
 * 
 * @param array $colors Color scheme array
 * @param string $scheme_name Scheme name
 * @return array Modified colors
 */
// apply_filters('og_svg_color_scheme', $colors, $scheme_name);

/**
 * Action: After SVG generation
 * 
 * @param string $svg_content Generated SVG content
 * @param int|null $post_id Post ID
 * @param array $data SVG data used
 */
// do_action('og_svg_generated', $svg_content, $post_id, $data);

/**
 * Filter: Modify SVG content before serving
 * 
 * @param string $svg_content SVG content
 * @param int|null $post_id Post ID
 * @return string Modified SVG content
 */
// apply_filters('og_svg_content', $svg_content, $post_id);

/**
 * WP-CLI Commands (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
  
  /**
   * OpenGraph SVG Generator WP-CLI commands
   */
  class OG_SVG_CLI_Commands extends WP_CLI_Command
  {
    
    /**
     * Generate OpenGraph SVG for all posts
     * 
     * ## OPTIONS
     * 
     * [--post-type=<post-type>]
     * : Generate only for specific post type
     * 
     * [--force]
     * : Regenerate existing images
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg generate
     *     wp og-svg generate --post-type=post --force
     */
    public function generate($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $settings = get_option('og_svg_settings', array());
      $enabled_types = $settings['enabled_post_types'] ?? array('post', 'page');
      
      $post_type = $assoc_args['post-type'] ?? null;
      $force = isset($assoc_args['force']);
      
      if ($post_type && !in_array($post_type, $enabled_types)) {
        WP_CLI::error("Post type '{$post_type}' is not enabled for OpenGraph SVG generation");
      }
      
      $query_args = array(
        'post_type' => $post_type ? $post_type : $enabled_types,
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids'
      );
      
      $posts = get_posts($query_args);
      $total = count($posts);
      
      WP_CLI::log("Found {$total} posts to process");
      
      $progress = WP_CLI\Utils\make_progress_bar('Generating OpenGraph images', $total);
      
      $generated = 0;
      $skipped = 0;
      $errors = 0;
      
      foreach ($posts as $post_id) {
        try {
          $file_path = $generator->getSVGFilePath($post_id);
          
          if (!$force && file_exists($file_path)) {
            $skipped++;
          } else {
            $svg_content = $generator->generateSVG($post_id);
            file_put_contents($file_path, $svg_content);
            $generated++;
          }
        } catch (Exception $e) {
          WP_CLI::warning("Failed to generate SVG for post {$post_id}: " . $e->getMessage());
          $errors++;
        }
        
        $progress->tick();
      }
      
      $progress->finish();
      
      WP_CLI::success("Generated: {$generated}, Skipped: {$skipped}, Errors: {$errors}");
    }
    
    /**
     * Clean up orphaned OpenGraph SVG files
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg cleanup
     */
    public function cleanup($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $generator = $instance->getComponent('generator');
      
      if (!$generator) {
        WP_CLI::error('OpenGraph SVG Generator not available');
      }
      
      $result = $generator->cleanupAllSVGs();
      
      WP_CLI::success(sprintf(
        'Cleaned up %d files and %d media library entries',
        $result['files_removed'],
        $result['attachments_removed']
      ));
    }
    
    /**
     * Show OpenGraph SVG statistics
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg stats
     */
    public function stats($args, $assoc_args)
    {
      $instance = OpenGraphSVGGenerator::getInstance();
      $meta_handler = $instance->getComponent('meta');
      
      if (!$meta_handler) {
        WP_CLI::error('OpenGraph SVG Meta Handler not available');
      }
      
      $stats = $meta_handler->getOGImageStats();
      
      WP_CLI::log('OpenGraph SVG Statistics:');
      WP_CLI::log('Total Images: ' . $stats['total_images']);
      
      if (!empty($stats['by_post_type'])) {
        WP_CLI::log('By Post Type:');
        foreach ($stats['by_post_type'] as $type => $count) {
          WP_CLI::log("  {$type}: {$count}");
        }
      }
    }
  }
  
  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}