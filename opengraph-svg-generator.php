<?php

/**
 * Plugin Name: OpenGraph SVG Generator
 * Description: Dynamically generates beautiful SVG OpenGraph images using WordPress site title, page titles, and custom avatar.
 * Version: 1.0.0
 * Author: Gabriel Kanev
 * Text Domain: og-svg-generator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
  exit;
}

// Define plugin constants
define('OG_SVG_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OG_SVG_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('OG_SVG_VERSION', '1.0.0');

/**
 * Main Plugin Class
 */
class OpenGraphSVGGenerator
{

  private static $instance = null;

  public static function getInstance()
  {
    if (self::$instance === null) {
      self::$instance = new self();
    }
    return self::$instance;
  }

  private function __construct()
  {
    add_action('plugins_loaded', array($this, 'init'));
    register_activation_hook(__FILE__, array($this, 'activate'));
    register_deactivation_hook(__FILE__, array($this, 'deactivate'));
  }

  public function init()
  {
    // Load required files
    $this->loadIncludes();

    // Initialize components
    new OG_SVG_Admin_Settings();
    new OG_SVG_Meta_Handler();
    new OG_SVG_Generator();

    // Add custom rewrite rules
    add_action('init', array($this, 'addRewriteRules'));
    add_filter('query_vars', array($this, 'addQueryVars'));
    add_action('template_redirect', array($this, 'handleSVGRequest'));
  }

  private function loadIncludes()
  {
    require_once OG_SVG_PLUGIN_PATH . 'includes/svg-generator.php';
    require_once OG_SVG_PLUGIN_PATH . 'includes/admin-settings.php';
    require_once OG_SVG_PLUGIN_PATH . 'includes/meta-handler.php';
  }

  public function addRewriteRules()
  {
    add_rewrite_rule('^og-svg/([0-9]+)/?$', 'index.php?og_svg_id=$matches[1]', 'top');
    add_rewrite_rule('^og-svg/home/?$', 'index.php?og_svg_home=1', 'top');
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
      $generator = new OG_SVG_Generator();

      if ($is_home) {
        $generator->serveSVG();
      } else {
        $generator->serveSVG($post_id);
      }
      exit;
    }
  }

  public function activate()
  {
    // Set default options
    $default_options = array(
      'avatar_url' => get_avatar_url(get_current_user_id(), array('size' => 200)),
      'color_scheme' => 'blue',
      'show_tagline' => true,
      'enabled_post_types' => array('post', 'page'),
      'fallback_title' => 'Welcome'
    );

    add_option('og_svg_settings', $default_options);

    // Flush rewrite rules
    flush_rewrite_rules();
  }

  public function deactivate()
  {
    // Flush rewrite rules
    flush_rewrite_rules();
  }
}

// Initialize the plugin
OpenGraphSVGGenerator::getInstance();
