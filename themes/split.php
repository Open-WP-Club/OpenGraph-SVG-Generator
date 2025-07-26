<?php

/**
 * Split Screen Theme - Featured image on top, content below
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_SplitScreen extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Split Screen',
      'description' => 'Featured image on top half, content on bottom with clean domain display',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#1e293b', '#ffffff', '#3b82f6')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#ffffff',
      'gradient_start' => '#1e293b',
      'gradient_end' => '#0f172a',
      'text_primary' => '#1e293b',
      'text_secondary' => '#64748b',
      'accent' => '#3b82f6',
      'accent_secondary' => '#dbeafe',
      'image_overlay' => 'rgba(30, 41, 59, 0.3)'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);

    $featured_image = $this->getFeaturedImageUrl();

    if ($featured_image) {
      // Split layout: image top, content bottom
      $svg .= $this->generateImageSection($colors, $featured_image);
      $svg .= $this->generateContentSection($colors, true);
    } else {
      // Full content layout with accent background
      $svg .= '<rect width="1200" height="630" fill="' . $colors['background'] . '"/>' . "\n";
      $svg .= '<rect width="1200" height="100" fill="url(#accentGradient)"/>' . "\n";
      $svg .= $this->generateContentSection($colors, false);
    }

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function getFeaturedImageUrl()
  {
    // Get featured image from current post
    if (!empty($this->data['post_id'])) {
      $post_id = $this->data['post_id'];
      $featured_id = get_post_thumbnail_id($post_id);

      if ($featured_id) {
        return wp_get_attachment_image_url($featured_id, 'large');
      }
    }

    // Check for other image sources
    global $post;
    if ($post && $post->ID) {
      $featured_id = get_post_thumbnail_id($post->ID);
      if ($featured_id) {
        return wp_get_attachment_image_url($featured_id, 'large');
      }
    }

    return null;
  }

  private function generateImageSection($colors, $image_url)
  {
    $section = '';

    // Top half background
    $section .= '<rect x="0" y="0" width="1200" height="315" fill="#f1f5f9"/>' . "\n";

    try {
      $image_data = $this->getImageAsBase64($image_url);
      if ($image_data) {
        // Full-width image covering top half
        $section .= '<image x="0" y="0" width="1200" height="315" href="' . $image_data . '" preserveAspectRatio="xMidYMid slice"/>' . "\n";

        // Subtle overlay for better contrast
        $section .= '<rect x="0" y="0" width="1200" height="315" fill="' . $colors['image_overlay'] . '"/>' . "\n";
      }
    } catch (Exception $e) {
      // Fallback gradient
      $section .= '<rect x="0" y="0" width="1200" height="315" fill="url(#imageFallback)"/>' . "\n";
    }

    // Separator line
    $section .= '<rect x="0" y="315" width="1200" height="4" fill="' . $colors['accent'] . '"/>' . "\n";

    return $section;
  }

  private function generateContentSection($colors, $has_image)
  {
    $section = '';
    $y_start = $has_image ? 350 : 150;

    // Bottom half background
    if ($has_image) {
      $section .= '<rect x="0" y="319" width="1200" height="311" fill="' . $colors['background'] . '"/>' . "\n";
    }

    // Main content container
    $content_x = 80;
    $content_width = 1040;

    // Main title
    $title = $this->data['page_title'] ?: $this->data['site_title'];
    $title = $this->truncateText($title, 45);

    $section .= '<text x="' . $content_x . '" y="' . ($y_start + 40) . '" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="44" font-weight="700" fill="' . $colors['text_primary'] . '">' . "\n";
    $section .= $this->escapeXML($title) . "\n";
    $section .= '</text>' . "\n";

    // Subtitle (site name if showing page title)
    $y_offset = 85;
    if (!empty($this->data['page_title']) && $this->data['page_title'] !== $this->data['site_title']) {
      $site_name = $this->truncateText($this->data['site_title'], 35);
      $section .= '<text x="' . $content_x . '" y="' . ($y_start + $y_offset) . '" font-family="system-ui, sans-serif" font-size="22" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $section .= $this->escapeXML($site_name) . "\n";
      $section .= '</text>' . "\n";
      $y_offset += 35;
    }

    // Description/Tagline
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 85);
      $section .= '<text x="' . $content_x . '" y="' . ($y_start + $y_offset) . '" font-family="system-ui, sans-serif" font-size="16" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
      $section .= $this->escapeXML($tagline) . "\n";
      $section .= '</text>' . "\n";
      $y_offset += 30;
    }

    // Domain and logo section
    $section .= $this->generateDomainSection($colors, $y_start + $y_offset + 20);

    return $section;
  }

  private function generateDomainSection($colors, $y_position)
  {
    $domain_section = '';

    // Clean domain
    $clean_domain = $this->getCleanDomain();

    // Accent line
    $domain_section .= '<rect x="80" y="' . ($y_position) . '" width="120" height="3" rx="2" fill="' . $colors['accent'] . '"/>' . "\n";

    // Logo/Avatar (small)
    if (!empty($this->data['avatar_url'])) {
      $logo_y = $y_position + 20;
      $domain_section .= '<circle cx="95" cy="' . ($logo_y + 15) . '" r="15" fill="' . $colors['background'] . '" stroke="' . $colors['accent'] . '" stroke-width="2"/>' . "\n";

      try {
        $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
        if ($avatar_data) {
          $domain_section .= '<image x="83" y="' . ($logo_y + 3) . '" width="24" height="24" href="' . $avatar_data . '" clip-path="url(#domainLogoClip)"/>' . "\n";
        }
      } catch (Exception $e) {
        // Fallback dot
        $domain_section .= '<circle cx="95" cy="' . ($logo_y + 15) . '" r="8" fill="' . $colors['accent'] . '"/>' . "\n";
      }

      // Domain text next to logo
      $domain_section .= '<text x="125" y="' . ($logo_y + 20) . '" font-family="system-ui, sans-serif" font-size="16" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $domain_section .= $this->escapeXML($clean_domain) . "\n";
      $domain_section .= '</text>' . "\n";
    } else {
      // Domain text only
      $domain_section .= '<text x="80" y="' . ($y_position + 35) . '" font-family="system-ui, sans-serif" font-size="16" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $domain_section .= $this->escapeXML($clean_domain) . "\n";
      $domain_section .= '</text>' . "\n";
    }

    // Optional: Post meta (date, category) on the right
    if (!empty($this->data['post_id'])) {
      $post_date = get_the_date('M j, Y', $this->data['post_id']);
      if ($post_date) {
        $domain_section .= '<text x="1120" y="' . ($y_position + 35) . '" font-family="system-ui, sans-serif" font-size="14" font-weight="400" fill="' . $colors['text_secondary'] . '" text-anchor="end" opacity="0.7">' . "\n";
        $domain_section .= $this->escapeXML($post_date) . "\n";
        $domain_section .= '</text>' . "\n";
      }

      // Category
      $categories = get_the_category($this->data['post_id']);
      if (!empty($categories)) {
        $category = $categories[0]->name;
        $domain_section .= '<text x="1120" y="' . ($y_position + 15) . '" font-family="system-ui, sans-serif" font-size="12" font-weight="500" fill="' . $colors['accent'] . '" text-anchor="end" text-transform="uppercase" letter-spacing="1px">' . "\n";
        $domain_section .= $this->escapeXML($category) . "\n";
        $domain_section .= '</text>' . "\n";
      }
    }

    return $domain_section;
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

    // Accent gradient for header
    $defs .= '<linearGradient id="accentGradient" x1="0%" y1="0%" x2="100%" y2="0%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['accent'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['accent'] . ';stop-opacity:0.6" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Image fallback gradient
    $defs .= '<linearGradient id="imageFallback" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Domain logo clip path
    $defs .= '<clipPath id="domainLogoClip">' . "\n";
    $defs .= '<circle cx="12" cy="12" r="12"/>' . "\n";
    $defs .= '</clipPath>' . "\n";

    $defs .= '</defs>' . "\n";

    return $defs;
  }
}
