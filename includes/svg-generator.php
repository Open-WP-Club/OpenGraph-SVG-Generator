<?php

/**
 * SVG Generator Class
 * Handles the generation and serving of OpenGraph SVG images
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Generator
{

  private $settings;
  private $color_schemes;

  public function __construct()
  {
    $this->settings = get_option('og_svg_settings', array());
    $this->initColorSchemes();
  }

  private function initColorSchemes()
  {
    $this->color_schemes = array(
      'blue' => array(
        'background' => '#1e40af',
        'gradient_start' => '#3b82f6',
        'gradient_end' => '#1e40af',
        'text_primary' => '#ffffff',
        'text_secondary' => '#e5e7eb',
        'accent' => '#60a5fa'
      ),
      'purple' => array(
        'background' => '#7c3aed',
        'gradient_start' => '#a855f7',
        'gradient_end' => '#7c3aed',
        'text_primary' => '#ffffff',
        'text_secondary' => '#e5e7eb',
        'accent' => '#c084fc'
      ),
      'dark' => array(
        'background' => '#111827',
        'gradient_start' => '#374151',
        'gradient_end' => '#111827',
        'text_primary' => '#ffffff',
        'text_secondary' => '#d1d5db',
        'accent' => '#6b7280'
      ),
      'green' => array(
        'background' => '#059669',
        'gradient_start' => '#10b981',
        'gradient_end' => '#059669',
        'text_primary' => '#ffffff',
        'text_secondary' => '#ecfdf5',
        'accent' => '#34d399'
      )
    );
  }

  public function serveSVG($post_id = null)
  {
    // Set proper headers
    header('Content-Type: image/svg+xml');
    header('Cache-Control: public, max-age=3600');

    // Generate SVG content
    $svg_content = $this->generateSVG($post_id);

    echo $svg_content;
  }

  public function generateSVG($post_id = null)
  {
    // Get data for SVG
    $data = $this->getSVGData($post_id);
    $colors = $this->getColorScheme();

    // Start building SVG
    $svg = '<?xml version="1.0" encoding="UTF-8"?>';
    $svg .= '<svg width="1200" height="630" viewBox="0 0 1200 630" xmlns="http://www.w3.org/2000/svg">';

    // Background gradient
    $svg .= '<defs>';
    $svg .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">';
    $svg .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />';
    $svg .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />';
    $svg .= '</linearGradient>';

    // Text shadow filter
    $svg .= '<filter id="textShadow" x="-20%" y="-20%" width="140%" height="140%">';
    $svg .= '<feDropShadow dx="2" dy="2" stdDeviation="3" flood-color="rgba(0,0,0,0.3)"/>';
    $svg .= '</filter>';
    $svg .= '</defs>';

    // Background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>';

    // Decorative elements
    $svg .= '<circle cx="1100" cy="100" r="150" fill="rgba(255,255,255,0.05)"/>';
    $svg .= '<circle cx="1050" cy="550" r="100" fill="rgba(255,255,255,0.03)"/>';
    $svg .= '<circle cx="150" cy="50" r="80" fill="rgba(255,255,255,0.04)"/>';

    // Content container
    $svg .= '<rect x="60" y="60" width="1080" height="510" rx="20" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>';

    // Avatar section
    if (!empty($data['avatar_url'])) {
      $svg .= '<circle cx="200" cy="200" r="70" fill="rgba(255,255,255,0.9)"/>';
      $svg .= '<image x="135" y="135" width="130" height="130" href="' . esc_url($data['avatar_url']) . '" clip-path="circle(65px at 65px 65px)"/>';
    }

    // Site title (main title)
    $site_title = $this->truncateText($data['site_title'], 25);
    $svg .= '<text x="320" y="160" font-family="system-ui, -apple-system, sans-serif" font-size="42" font-weight="700" fill="' . $colors['text_primary'] . '" filter="url(#textShadow)">';
    $svg .= htmlspecialchars($site_title, ENT_XML1, 'UTF-8');
    $svg .= '</text>';

    // Page title (subtitle)
    $page_title = $this->truncateText($data['page_title'], 50);
    $svg .= '<text x="320" y="210" font-family="system-ui, -apple-system, sans-serif" font-size="28" font-weight="400" fill="' . $colors['text_secondary'] . '">';
    $svg .= htmlspecialchars($page_title, ENT_XML1, 'UTF-8');
    $svg .= '</text>';

    // Tagline (if enabled)
    if ($this->settings['show_tagline'] && !empty($data['tagline'])) {
      $tagline = $this->truncateText($data['tagline'], 80);
      $svg .= '<text x="320" y="250" font-family="system-ui, -apple-system, sans-serif" font-size="18" font-weight="300" fill="' . $colors['text_secondary'] . '" opacity="0.8">';
      $svg .= htmlspecialchars($tagline, ENT_XML1, 'UTF-8');
      $svg .= '</text>';
    }

    // Bottom accent line
    $svg .= '<rect x="320" y="280" width="100" height="4" rx="2" fill="' . $colors['accent'] . '"/>';

    // Website URL
    $svg .= '<text x="320" y="320" font-family="system-ui, -apple-system, sans-serif" font-size="16" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.7">';
    $svg .= htmlspecialchars($data['site_url'], ENT_XML1, 'UTF-8');
    $svg .= '</text>';

    // Subtle branding
    $svg .= '<text x="1120" y="610" font-family="system-ui, -apple-system, sans-serif" font-size="12" font-weight="300" fill="rgba(255,255,255,0.4)" text-anchor="end">';
    $svg .= 'Generated by OpenGraph SVG';
    $svg .= '</text>';

    $svg .= '</svg>';

    return $svg;
  }

  private function getSVGData($post_id = null)
  {
    $data = array();

    // Site title (always from WordPress settings)
    $data['site_title'] = get_bloginfo('name') ?: 'WordPress Site';

    // Page title
    if ($post_id) {
      $data['page_title'] = get_the_title($post_id) ?: $this->settings['fallback_title'] ?: 'Welcome';
    } else {
      // Home page or current page
      if (is_home() || is_front_page()) {
        $data['page_title'] = $this->settings['fallback_title'] ?: 'Welcome';
      } else {
        $data['page_title'] = wp_title('', false) ?: get_the_title() ?: 'Page';
      }
    }

    // Tagline
    $data['tagline'] = get_bloginfo('description');

    // Avatar URL
    $data['avatar_url'] = $this->settings['avatar_url'] ?: '';

    // Site URL
    $data['site_url'] = parse_url(get_site_url(), PHP_URL_HOST);

    return $data;
  }

  private function getColorScheme()
  {
    $scheme = $this->settings['color_scheme'] ?: 'blue';
    return $this->color_schemes[$scheme] ?? $this->color_schemes['blue'];
  }

  private function truncateText($text, $max_length)
  {
    if (strlen($text) <= $max_length) {
      return $text;
    }

    return substr($text, 0, $max_length - 3) . '...';
  }

  public function getSVGUrl($post_id = null)
  {
    if ($post_id) {
      return get_site_url() . '/og-svg/' . $post_id . '/';
    } else {
      return get_site_url() . '/og-svg/home/';
    }
  }
}
