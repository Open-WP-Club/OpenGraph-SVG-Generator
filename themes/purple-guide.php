<?php

/**
 * Purple Guide Theme - Based on "The Ultimate Guide" design
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_PurpleGuide extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Purple Guide',
      'description' => 'Bold purple design with large title and side image, inspired by modern guide layouts',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#8b5cf6', '#a855f7', '#ffffff')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#8b5cf6',
      'gradient_start' => '#8b5cf6',
      'gradient_end' => '#a855f7',
      'text_primary' => '#ffffff',
      'text_secondary' => '#f3f4f6',
      'accent' => '#fbbf24',
      'accent_secondary' => '#f9fafb'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);

    // Purple gradient background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

    // Main title area (60-70% of screen)
    $svg .= $this->generateMainTitle($colors);

    // Featured image or avatar on the right (15% area)
    $svg .= $this->generateRightImage($colors);

    // Website URL in bottom left
    $svg .= $this->generateBottomUrl($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function generateMainTitle($colors)
  {
    $title = '';

    // Get the main title - prioritize page title, fallback to site title
    $main_title = !empty($this->data['page_title']) ? $this->data['page_title'] : $this->data['site_title'];

    // For very long titles, split into multiple lines
    $words = explode(' ', $main_title);
    $lines = array();
    $current_line = '';

    foreach ($words as $word) {
      $test_line = $current_line . ($current_line ? ' ' : '') . $word;

      // Rough character limit per line for good readability
      if (strlen($test_line) > 25 && $current_line) {
        $lines[] = $current_line;
        $current_line = $word;
      } else {
        $current_line = $test_line;
      }
    }

    if ($current_line) {
      $lines[] = $current_line;
    }

    // If only one line and it's short, keep it large
    if (count($lines) == 1 && strlen($lines[0]) <= 30) {
      $font_size = 72;
      $line_height = 85;
      $start_y = 280; // Center vertically
    } else if (count($lines) <= 2) {
      $font_size = 60;
      $line_height = 75;
      $start_y = 240; // Adjust for 2 lines
    } else {
      $font_size = 48;
      $line_height = 60;
      $start_y = 200; // Adjust for 3+ lines
    }

    // Render each line
    foreach ($lines as $index => $line) {
      $y_position = $start_y + ($index * $line_height);

      $title .= '<text x="80" y="' . $y_position . '" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="' . $font_size . '" font-weight="800" fill="' . $colors['text_primary'] . '" letter-spacing="-1px">' . "\n";
      $title .= $this->escapeXML(trim($line)) . "\n";
      $title .= '</text>' . "\n";
    }

    return $title;
  }

  private function generateRightImage($colors)
  {
    $image_section = '';

    // Image area: 15% of screen width = ~180px, positioned on the right middle
    $image_width = 180;
    $image_height = 180;
    $image_x = 1200 - $image_width - 60; // 60px from right edge
    $image_y = (630 - $image_height) / 2; // Centered vertically

    // Try to get featured image first
    $image_url = $this->getFeaturedImageUrl();

    if ($image_url) {
      // Background circle for the image
      $circle_radius = $image_width / 2;
      $circle_x = $image_x + $circle_radius;
      $circle_y = $image_y + $circle_radius;

      $image_section .= '<circle cx="' . $circle_x . '" cy="' . $circle_y . '" r="' . ($circle_radius + 5) . '" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.2)" stroke-width="2"/>' . "\n";

      try {
        $image_data = $this->getImageAsBase64($image_url);
        if ($image_data) {
          $image_section .= '<image x="' . $image_x . '" y="' . $image_y . '" width="' . $image_width . '" height="' . $image_height . '" href="' . $image_data . '" clip-path="url(#rightImageClip)" preserveAspectRatio="xMidYMid slice"/>' . "\n";
        }
      } catch (Exception $e) {
        // Fallback to decorative element
        $image_section .= $this->generateDecorativeElement($circle_x, $circle_y, $circle_radius, $colors);
      }
    } else {
      // No image available - create a decorative element
      $circle_x = $image_x + $image_width / 2;
      $circle_y = $image_y + $image_height / 2;
      $image_section .= $this->generateDecorativeElement($circle_x, $circle_y, $image_width / 2, $colors);
    }

    return $image_section;
  }

  private function generateDecorativeElement($center_x, $center_y, $radius, $colors)
  {
    $decoration = '';

    // Create a modern geometric decoration similar to the illustration style
    $decoration .= '<circle cx="' . $center_x . '" cy="' . $center_y . '" r="' . $radius . '" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>' . "\n";

    // Inner elements - representing a simplified device/document
    $inner_radius = $radius * 0.6;
    $decoration .= '<rect x="' . ($center_x - $inner_radius / 2) . '" y="' . ($center_y - $inner_radius / 2) . '" width="' . $inner_radius . '" height="' . ($inner_radius * 1.2) . '" rx="8" fill="rgba(255,255,255,0.9)" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>' . "\n";

    // Document lines
    $line_y = $center_y - $inner_radius / 3;
    for ($i = 0; $i < 3; $i++) {
      $decoration .= '<rect x="' . ($center_x - $inner_radius / 3) . '" y="' . ($line_y + $i * 8) . '" width="' . ($inner_radius / 1.5) . '" height="2" rx="1" fill="' . $colors['background'] . '" opacity="0.6"/>' . "\n";
    }

    return $decoration;
  }

  private function generateBottomUrl($colors)
  {
    $url_section = '';

    // Clean domain in bottom left
    $clean_domain = $this->getCleanDomain();

    // Logo/avatar if available (small)
    if (!empty($this->data['avatar_url'])) {
      $logo_x = 80;
      $logo_y = 580;

      $url_section .= '<circle cx="' . ($logo_x + 15) . '" cy="' . ($logo_y + 15) . '" r="18" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>' . "\n";

      try {
        $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
        if ($avatar_data) {
          $url_section .= '<image x="' . $logo_x . '" y="' . $logo_y . '" width="30" height="30" href="' . $avatar_data . '" clip-path="url(#logoClip)"/>' . "\n";
        }
      } catch (Exception $e) {
        // Simple fallback dot
        $url_section .= '<circle cx="' . ($logo_x + 15) . '" cy="' . ($logo_y + 15) . '" r="8" fill="rgba(255,255,255,0.4)"/>' . "\n";
      }

      // Domain text next to logo
      $url_section .= '<text x="' . ($logo_x + 45) . '" y="' . ($logo_y + 20) . '" font-family="system-ui, sans-serif" font-size="18" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $url_section .= $this->escapeXML($clean_domain) . "\n";
      $url_section .= '</text>' . "\n";
    } else {
      // Domain text only
      $url_section .= '<text x="80" y="600" font-family="system-ui, sans-serif" font-size="20" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $url_section .= $this->escapeXML($clean_domain) . "\n";
      $url_section .= '</text>' . "\n";
    }

    return $url_section;
  }

  private function getFeaturedImageUrl()
  {
    // Try to get featured image from post
    if (!empty($this->data['post_id'])) {
      $post_id = $this->data['post_id'];
      $featured_id = get_post_thumbnail_id($post_id);

      if ($featured_id) {
        $image_url = wp_get_attachment_image_url($featured_id, 'medium');
        if ($image_url) {
          return $image_url;
        }
      }
    }

    // Fallback to avatar
    return !empty($this->data['avatar_url']) ? $this->data['avatar_url'] : null;
  }

  private function getCleanDomain()
  {
    $url = $this->data['site_url'];

    // Remove protocol
    $domain = preg_replace('#^https?://#', '', $url);

    // Remove www
    $domain = preg_replace('#^www\.#', '', $domain);

    // Remove trailing slash and paths
    $domain = strtok($domain, '/');

    return $domain;
  }

  protected function generateDefs($colors)
  {
    $defs = '<defs>' . "\n";

    // Purple gradient background
    $defs .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Clip path for right image (circular)
    $defs .= '<clipPath id="rightImageClip">' . "\n";
    $defs .= '<circle cx="90" cy="90" r="90"/>' . "\n";
    $defs .= '</clipPath>' . "\n";

    // Clip path for logo (circular)
    $defs .= '<clipPath id="logoClip">' . "\n";
    $defs .= '<circle cx="15" cy="15" r="15"/>' . "\n";
    $defs .= '</clipPath>' . "\n";

    $defs .= '</defs>' . "\n";

    return $defs;
  }
}
