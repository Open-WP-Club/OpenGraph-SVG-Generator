<?php

/**
 * Base Theme Class
 * Abstract class that all themes must extend
 */

if (!defined('ABSPATH')) {
  exit;
}

abstract class OG_SVG_Theme_Base
{
  protected $settings;
  protected $data;

  public function __construct($settings, $data)
  {
    $this->settings = $settings;
    $this->data = $data;
  }

  /**
   * Get theme information
   * Must be implemented by each theme
   */
  abstract public function getThemeInfo();

  /**
   * Get color scheme
   * Must be implemented by each theme
   */
  abstract public function getColorScheme();

  /**
   * Generate the SVG content
   * Must be implemented by each theme
   */
  abstract public function generateSVG();

  /**
   * Helper methods available to all themes
   */
  protected function escapeXML($text)
  {
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
  }

  protected function truncateText($text, $max_length)
  {
    $text = trim($text);
    if (mb_strlen($text) <= $max_length) {
      return $text;
    }
    return mb_substr($text, 0, $max_length - 3) . '...';
  }

  protected function getImageAsBase64($image_url)
  {
    if (empty($image_url)) {
      return false;
    }

    $upload_dir = wp_upload_dir();

    // Handle local WordPress uploads
    if (strpos($image_url, $upload_dir['baseurl']) === 0) {
      $local_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);
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
      return false;
    }

    $body = wp_remote_retrieve_body($response);
    $content_type = wp_remote_retrieve_header($response, 'content-type');

    if (empty($body) || strpos($content_type, 'image/') !== 0) {
      return false;
    }

    return 'data:' . $content_type . ';base64,' . base64_encode($body);
  }

  protected function generateSVGHeader()
  {
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
      '<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">' . "\n";
  }

  protected function generateSVGFooter()
  {
    return '</svg>';
  }

  protected function generateDefs($colors)
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
}
