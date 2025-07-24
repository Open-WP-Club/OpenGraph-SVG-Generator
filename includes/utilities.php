<?php

/**
 * Utilities and WP-CLI Commands
 * Additional functionality for OpenGraph SVG Generator
 */

if (!defined('ABSPATH')) {
  exit;
}

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

    /**
     * Test OpenGraph SVG URLs
     * 
     * ## EXAMPLES
     * 
     *     wp og-svg test
     */
    public function test($args, $assoc_args)
    {
      $home_url = get_site_url() . '/og-svg/home/';

      WP_CLI::log("Testing OpenGraph SVG URLs...");
      WP_CLI::log("Home URL: {$home_url}");

      // Test home URL
      $response = wp_safe_remote_head($home_url, array('timeout' => 10));

      if (is_wp_error($response)) {
        WP_CLI::error("Home URL test failed: " . $response->get_error_message());
      }

      $code = wp_remote_retrieve_response_code($response);
      $content_type = wp_remote_retrieve_header($response, 'content-type');

      if ($code === 200) {
        WP_CLI::success("Home URL working! Content-Type: {$content_type}");
      } else {
        WP_CLI::error("Home URL returned status code: {$code}");
      }

      // Test a post URL if posts exist
      $posts = get_posts(array('numberposts' => 1, 'fields' => 'ids'));
      if (!empty($posts)) {
        $post_url = get_site_url() . '/og-svg/' . $posts[0] . '/';
        WP_CLI::log("Testing post URL: {$post_url}");

        $response = wp_safe_remote_head($post_url, array('timeout' => 10));

        if (!is_wp_error($response)) {
          $code = wp_remote_retrieve_response_code($response);
          if ($code === 200) {
            WP_CLI::success("Post URL working!");
          } else {
            WP_CLI::warning("Post URL returned status code: {$code}");
          }
        } else {
          WP_CLI::warning("Post URL test failed: " . $response->get_error_message());
        }
      }
    }
  }

  WP_CLI::add_command('og-svg', 'OG_SVG_CLI_Commands');
}

/**
 * Debug and troubleshooting utilities
 */
class OG_SVG_Debug
{
  public static function logRequest($message)
  {
    if (defined('WP_DEBUG') && WP_DEBUG) {
      error_log('OG SVG: ' . $message);
    }
  }

  public static function getSystemInfo()
  {
    $upload_dir = wp_upload_dir();

    return array(
      'php_version' => PHP_VERSION,
      'wp_version' => get_bloginfo('version'),
      'plugin_version' => OG_SVG_VERSION,
      'upload_dir_writable' => is_writable($upload_dir['basedir']),
      'permalink_structure' => get_option('permalink_structure'),
      'memory_limit' => ini_get('memory_limit'),
      'max_execution_time' => ini_get('max_execution_time'),
    );
  }

  public static function testRewriteRules()
  {
    $rules = get_option('rewrite_rules');
    $og_rules = array();

    foreach ($rules as $pattern => $rewrite) {
      if (strpos($pattern, 'og-svg') !== false) {
        $og_rules[$pattern] = $rewrite;
      }
    }

    return $og_rules;
  }
}
