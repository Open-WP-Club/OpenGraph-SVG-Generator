<?php

/**
 * SVG Generator Class
 * Handles the generation and serving of OpenGraph SVG images
 * Enhanced with better error handling and media library integration
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('OG_SVG_Generator')) {

  class OG_SVG_Generator
  {

    private $settings;
    private $color_schemes;
    private $upload_dir;

    public function __construct()
    {
      $this->settings = get_option('og_svg_settings', array());
      $this->initColorSchemes();
      $this->upload_dir = wp_upload_dir();

      // Ensure upload directory exists
      $this->ensureUploadDirectory();
    }

    private function initColorSchemes()
    {
      $this->color_schemes = array(
        'gabriel' => array(
          'background' => '#0f172a',
          'gradient_start' => '#1e293b',
          'gradient_end' => '#0f172a',
          'text_primary' => '#f8fafc',
          'text_secondary' => '#cbd5e1',
          'accent' => '#3b82f6',
          'accent_secondary' => '#06b6d4'
        ),
        'blue' => array(
          'background' => '#1e40af',
          'gradient_start' => '#3b82f6',
          'gradient_end' => '#1e40af',
          'text_primary' => '#ffffff',
          'text_secondary' => '#e5e7eb',
          'accent' => '#60a5fa',
          'accent_secondary' => '#93c5fd'
        ),
        'purple' => array(
          'background' => '#7c3aed',
          'gradient_start' => '#a855f7',
          'gradient_end' => '#7c3aed',
          'text_primary' => '#ffffff',
          'text_secondary' => '#e5e7eb',
          'accent' => '#c084fc',
          'accent_secondary' => '#ddd6fe'
        ),
        'dark' => array(
          'background' => '#111827',
          'gradient_start' => '#374151',
          'gradient_end' => '#111827',
          'text_primary' => '#ffffff',
          'text_secondary' => '#d1d5db',
          'accent' => '#6b7280',
          'accent_secondary' => '#9ca3af'
        ),
        'green' => array(
          'background' => '#059669',
          'gradient_start' => '#10b981',
          'gradient_end' => '#059669',
          'text_primary' => '#ffffff',
          'text_secondary' => '#ecfdf5',
          'accent' => '#34d399',
          'accent_secondary' => '#6ee7b7'
        )
      );
    }

    private function ensureUploadDirectory()
    {
      $svg_dir = $this->upload_dir['basedir'] . '/og-svg/';
      if (!file_exists($svg_dir)) {
        wp_mkdir_p($svg_dir);
      }
    }

    public function serveSVG($post_id = null)
    {
      try {
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
          error_log('OG SVG: Serving SVG for post_id: ' . ($post_id ?? 'home'));
        }

        // Set proper headers
        header('Content-Type: image/svg+xml');
        header('Cache-Control: public, max-age=3600');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

        // Generate and output SVG
        $svg_content = $this->generateSVG($post_id);

        // Save to file system and media library if configured
        $this->saveSVGToMedia($svg_content, $post_id);

        // Output the SVG
        echo $svg_content;

        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
          error_log('OG SVG: Successfully served SVG (' . strlen($svg_content) . ' bytes)');
        }
      } catch (Exception $e) {
        // Log error and serve fallback
        error_log('OG SVG Generator Error: ' . $e->getMessage());
        error_log('OG SVG Stack trace: ' . $e->getTraceAsString());
        $this->serveFallbackSVG($e->getMessage());
      }
    }

    public function generateSVG($post_id = null)
    {
      // Get data for SVG
      $data = $this->getSVGData($post_id);
      $colors = $this->getColorScheme();

      // Validate required data
      if (empty($data['site_title']) && empty($data['page_title'])) {
        throw new Exception('No title data available for SVG generation');
      }

      // Start building SVG
      $svg = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      $svg .= '<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";

      // Add definitions
      $svg .= $this->generateSVGDefs($colors);

      // Background
      $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

      // Decorative elements
      $svg .= $this->generateDecorativeElements($colors);

      // Content container
      $svg .= '<rect x="60" y="60" width="1080" height="510" rx="20" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>' . "\n";

      // Avatar section
      if (!empty($data['avatar_url'])) {
        $svg .= $this->generateAvatarSection($data['avatar_url']);
      }

      // Text content
      $svg .= $this->generateTextContent($data, $colors);

      // Footer elements
      $svg .= $this->generateFooterElements($data, $colors);

      $svg .= '</svg>';

      return $svg;
    }

    private function generateSVGDefs($colors)
    {
      $defs = '<defs>' . "\n";

      // Background gradient
      $defs .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
      $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
      $defs .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />' . "\n";
      $defs .= '</linearGradient>' . "\n";

      // Text shadow filter
      $defs .= '<filter id="textShadow" x="-20%" y="-20%" width="140%" height="140%">' . "\n";
      $defs .= '<feDropShadow dx="2" dy="2" stdDeviation="3" flood-color="rgba(0,0,0,0.3)"/>' . "\n";
      $defs .= '</filter>' . "\n";

      // Avatar clip path
      $defs .= '<clipPath id="avatarClip">' . "\n";
      $defs .= '<circle cx="65" cy="65" r="65"/>' . "\n";
      $defs .= '</clipPath>' . "\n";

      $defs .= '</defs>' . "\n";

      return $defs;
    }

    private function generateDecorativeElements($colors)
    {
      $elements = '';

      if ($colors === $this->color_schemes['gabriel']) {
        // Gabriel's tech-inspired theme
        $elements .= '<circle cx="1050" cy="150" r="120" fill="rgba(59, 130, 246, 0.08)" opacity="0.6"/>' . "\n";
        $elements .= '<circle cx="1100" cy="500" r="80" fill="rgba(6, 182, 212, 0.06)" opacity="0.8"/>' . "\n";
        $elements .= '<circle cx="100" cy="100" r="60" fill="rgba(59, 130, 246, 0.05)" opacity="0.7"/>' . "\n";

        // Tech hexagons
        $elements .= '<polygon points="950,50 980,35 1010,50 1010,80 980,95 950,80" fill="rgba(59, 130, 246, 0.08)" opacity="0.4"/>' . "\n";
        $elements .= '<polygon points="1080,400 1100,390 1120,400 1120,420 1100,430 1080,420" fill="rgba(6, 182, 212, 0.06)" opacity="0.5"/>' . "\n";
      } else {
        // Generic decorative elements
        $elements .= '<circle cx="1100" cy="100" r="150" fill="rgba(255,255,255,0.05)"/>' . "\n";
        $elements .= '<circle cx="1050" cy="550" r="100" fill="rgba(255,255,255,0.03)"/>' . "\n";
        $elements .= '<circle cx="150" cy="50" r="80" fill="rgba(255,255,255,0.04)"/>' . "\n";
      }

      return $elements;
    }

    private function generateAvatarSection($avatar_url)
    {
      $avatar = '';

      // Avatar background circle
      $avatar .= '<circle cx="200" cy="200" r="70" fill="rgba(255,255,255,0.9)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>' . "\n";

      // Try to embed avatar image
      try {
        $avatar_data = $this->getImageAsBase64($avatar_url);
        if ($avatar_data) {
          $avatar .= '<image x="135" y="135" width="130" height="130" href="' . $avatar_data . '" clip-path="url(#avatarClip)" transform="translate(65,65)"/>' . "\n";
        }
      } catch (Exception $e) {
        // Fallback to a simple icon if avatar loading fails
        error_log('Avatar loading failed: ' . $e->getMessage());
        $avatar .= '<circle cx="200" cy="200" r="50" fill="rgba(255,255,255,0.3)"/>' . "\n";
        $avatar .= '<path d="M200 170 c-11 0 -20 9 -20 20 s 9 20 20 20 s 20 -9 20 -20 s -9 -20 -20 -20 z M200 220 c-16.5 0 -30 13.5 -30 30 l 60 0 c 0 -16.5 -13.5 -30 -30 -30 z" fill="rgba(255,255,255,0.6)"/>' . "\n";
      }

      return $avatar;
    }

    private function generateTextContent($data, $colors)
    {
      $text = '';

      // Main site title
      $site_title = $this->truncateText($data['site_title'], 25);
      $text .= '<text x="320" y="160" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="42" font-weight="700" fill="' . $colors['text_primary'] . '" filter="url(#textShadow)">' . "\n";
      $text .= $this->escapeXML($site_title) . "\n";
      $text .= '</text>' . "\n";

      // Page title (subtitle)
      $page_title = $this->truncateText($data['page_title'], 50);
      $text .= '<text x="320" y="210" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="28" font-weight="400" fill="' . $colors['text_secondary'] . '">' . "\n";
      $text .= $this->escapeXML($page_title) . "\n";
      $text .= '</text>' . "\n";

      // Tagline (if enabled)
      if (!empty($this->settings['show_tagline']) && !empty($data['tagline'])) {
        $tagline = $this->truncateText($data['tagline'], 80);
        $text .= '<text x="320" y="250" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="300" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
        $text .= $this->escapeXML($tagline) . "\n";
        $text .= '</text>' . "\n";
      }

      return $text;
    }

    private function generateFooterElements($data, $colors)
    {
      $footer = '';

      // Accent line
      $footer .= '<rect x="320" y="280" width="100" height="4" rx="2" fill="' . $colors['accent'] . '"/>' . "\n";

      // Website URL
      $footer .= '<text x="320" y="320" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="16" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.7">' . "\n";
      $footer .= $this->escapeXML($data['site_url']) . "\n";
      $footer .= '</text>' . "\n";

      // Professional indicator for Gabriel theme
      if (isset($colors['accent_secondary']) && $colors === $this->color_schemes['gabriel']) {
        $footer .= '<rect x="320" y="340" width="8" height="8" rx="4" fill="' . $colors['accent_secondary'] . '" opacity="0.6"/>' . "\n";
        $footer .= '<text x="338" y="349" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="12" font-weight="500" fill="' . $colors['text_secondary'] . '" opacity="0.6">' . "\n";
        $footer .= 'Product Manager • PhD Student • Developer' . "\n";
        $footer .= '</text>' . "\n";
      }

      return $footer;
    }

    private function getImageAsBase64($image_url)
    {
      if (empty($image_url)) {
        return false;
      }

      // Handle local WordPress uploads
      if (strpos($image_url, $this->upload_dir['baseurl']) === 0) {
        $local_path = str_replace($this->upload_dir['baseurl'], $this->upload_dir['basedir'], $image_url);
        if (file_exists($local_path)) {
          $image_data = file_get_contents($local_path);
          $mime_type = wp_check_filetype($local_path)['type'] ?: 'image/jpeg';
          return 'data:' . $mime_type . ';base64,' . base64_encode($image_data);
        }
      }

      // Handle external URLs
      $response = wp_safe_remote_get($image_url, array(
        'timeout' => 10,
        'headers' => array(
          'User-Agent' => 'WordPress OpenGraph SVG Generator'
        )
      ));

      if (is_wp_error($response)) {
        throw new Exception('Failed to fetch avatar: ' . $response->get_error_message());
      }

      $body = wp_remote_retrieve_body($response);
      $content_type = wp_remote_retrieve_header($response, 'content-type');

      if (empty($body) || strpos($content_type, 'image/') !== 0) {
        throw new Exception('Invalid image response');
      }

      return 'data:' . $content_type . ';base64,' . base64_encode($body);
    }

    private function saveSVGToMedia($svg_content, $post_id = null)
    {
      try {
        // Generate filename
        $filename = $post_id ? "og-svg-{$post_id}.svg" : "og-svg-home.svg";
        $file_path = $this->upload_dir['basedir'] . '/og-svg/' . $filename;

        // Save to file system
        file_put_contents($file_path, $svg_content);

        // Add to media library if it doesn't exist
        $existing = get_posts(array(
          'post_type' => 'attachment',
          'meta_query' => array(
            array(
              'key' => '_og_svg_file',
              'value' => $filename,
              'compare' => '='
            )
          ),
          'posts_per_page' => 1
        ));

        if (empty($existing)) {
          $attachment_data = array(
            'post_title' => $post_id ? get_the_title($post_id) . ' - OpenGraph Image' : get_bloginfo('name') . ' - OpenGraph Image',
            'post_content' => 'Auto-generated OpenGraph SVG image',
            'post_status' => 'inherit',
            'post_mime_type' => 'image/svg+xml'
          );

          $attachment_id = wp_insert_attachment($attachment_data, $file_path);

          if (!is_wp_error($attachment_id)) {
            // Add custom meta to identify OG SVG files
            update_post_meta($attachment_id, '_og_svg_generated', '1');
            update_post_meta($attachment_id, '_og_svg_file', $filename);
            update_post_meta($attachment_id, '_og_svg_post_id', $post_id ?: 'home');

            // Generate attachment metadata
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
            wp_update_attachment_metadata($attachment_id, $attach_data);
          }
        }
      } catch (Exception $e) {
        error_log('Failed to save SVG to media: ' . $e->getMessage());
      }
    }

    private function serveFallbackSVG($error_message = null)
    {
      // Set headers
      header('Content-Type: image/svg+xml');
      header('Cache-Control: public, max-age=300'); // Shorter cache for fallback

      $site_name = get_bloginfo('name') ?: 'WordPress Site';
      $debug_info = '';

      if (defined('WP_DEBUG') && WP_DEBUG && $error_message) {
        $debug_info = '<!-- Error: ' . esc_html($error_message) . ' -->' . "\n";
      }

      $fallback = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
      $fallback .= $debug_info;
      $fallback .= '<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">' . "\n";
      $fallback .= '<rect width="1200" height="630" fill="#1e293b"/>' . "\n";
      $fallback .= '<text x="600" y="280" font-family="system-ui, sans-serif" font-size="36" font-weight="600" fill="#f8fafc" text-anchor="middle">' . "\n";
      $fallback .= htmlspecialchars($site_name, ENT_XML1, 'UTF-8') . "\n";
      $fallback .= '</text>' . "\n";
      $fallback .= '<text x="600" y="320" font-family="system-ui, sans-serif" font-size="16" fill="#cbd5e1" text-anchor="middle">' . "\n";
      $fallback .= 'OpenGraph Image' . "\n";
      $fallback .= '</text>' . "\n";

      if (defined('WP_DEBUG') && WP_DEBUG && $error_message) {
        $fallback .= '<text x="600" y="360" font-family="monospace" font-size="12" fill="#ef4444" text-anchor="middle">' . "\n";
        $fallback .= 'Debug: ' . htmlspecialchars(substr($error_message, 0, 80), ENT_XML1, 'UTF-8') . "\n";
        $fallback .= '</text>' . "\n";
      }

      $fallback .= '</svg>';

      echo $fallback;
    }

    private function getSVGData($post_id = null)
    {
      $data = array();

      // Site title
      $data['site_title'] = get_bloginfo('name') ?: 'WordPress Site';

      // Page title
      if ($post_id) {
        $post = get_post($post_id);
        if ($post) {
          $data['page_title'] = $post->post_title ?: ($this->settings['fallback_title'] ?: 'Welcome');
        } else {
          $data['page_title'] = $this->settings['fallback_title'] ?: 'Page Not Found';
        }
      } else {
        // Home page
        if (is_home() || is_front_page()) {
          $data['page_title'] = $this->settings['fallback_title'] ?: 'Welcome';
        } else {
          $data['page_title'] = wp_title('', false) ?: get_the_title() ?: 'Page';
        }
      }

      // Tagline
      $data['tagline'] = get_bloginfo('description') ?: '';

      // Avatar URL
      $data['avatar_url'] = $this->settings['avatar_url'] ?: '';

      // Site URL
      $data['site_url'] = parse_url(get_site_url(), PHP_URL_HOST) ?: get_site_url();

      return $data;
    }

    private function getColorScheme()
    {
      $scheme = $this->settings['color_scheme'] ?? 'gabriel';
      return $this->color_schemes[$scheme] ?? $this->color_schemes['gabriel'];
    }

    private function truncateText($text, $max_length)
    {
      $text = trim($text);
      if (mb_strlen($text) <= $max_length) {
        return $text;
      }

      return mb_substr($text, 0, $max_length - 3) . '...';
    }

    private function escapeXML($text)
    {
      return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public function getSVGUrl($post_id = null)
    {
      if ($post_id) {
        return get_site_url() . '/og-svg/' . $post_id . '/';
      } else {
        return get_site_url() . '/og-svg/home/';
      }
    }

    public function getSVGFilePath($post_id = null)
    {
      $filename = $post_id ? "og-svg-{$post_id}.svg" : "og-svg-home.svg";
      return $this->upload_dir['basedir'] . '/og-svg/' . $filename;
    }

    public function getSVGFileUrl($post_id = null)
    {
      $filename = $post_id ? "og-svg-{$post_id}.svg" : "og-svg-home.svg";
      return $this->upload_dir['baseurl'] . '/og-svg/' . $filename;
    }

    public function cleanupAllSVGs()
    {
      $svg_dir = $this->upload_dir['basedir'] . '/og-svg/';
      $count = 0;

      if (is_dir($svg_dir)) {
        $files = glob($svg_dir . '*.svg');
        foreach ($files as $file) {
          if (unlink($file)) {
            $count++;
          }
        }

        // Remove directory if empty
        if (count(glob($svg_dir . '*')) === 0) {
          rmdir($svg_dir);
        }
      }

      // Remove from media library
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

      return array(
        'files_removed' => $count,
        'attachments_removed' => count($attachments)
      );
    }
    public function cleanupOrphanedFiles()
    {
      $upload_dir = wp_upload_dir();
      $svg_dir = $upload_dir['basedir'] . '/og-svg/';
      $cleaned = 0;

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
              $cleaned++;

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

      return $cleaned;
    }
  }
} // End class_exists check