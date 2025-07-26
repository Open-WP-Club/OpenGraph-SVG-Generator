<?php

/**
 * Simple Featured Theme - Clean layout with featured image
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_SimpleFeatured extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Simple Featured',
      'description' => 'Clean layout with featured image, title, and domain',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#ffffff', '#f8fafc', '#1e293b')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#ffffff',
      'gradient_start' => '#f8fafc',
      'gradient_end' => '#ffffff',
      'text_primary' => '#1e293b',
      'text_secondary' => '#64748b',
      'accent' => '#3b82f6',
      'accent_secondary' => '#e2e8f0'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);

    // Clean white background
    $svg .= '<rect width="1200" height="630" fill="' . $colors['background'] . '"/>' . "\n";

    // Get featured image
    $featured_image = $this->getFeaturedImageUrl();

    if ($featured_image) {
      // Layout with featured image on the left
      $svg .= $this->generateFeaturedImageLayout($colors, $featured_image);
    } else {
      // Layout without featured image - centered text
      $svg .= $this->generateTextOnlyLayout($colors);
    }

    // Logo/Domain in bottom right
    $svg .= $this->generateDomainDisplay($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function getFeaturedImageUrl()
  {
    // Try to get featured image from post
    if (!empty($this->data['post_id'])) {
      $post_id = $this->data['post_id'];
      $featured_id = get_post_thumbnail_id($post_id);

      if ($featured_id) {
        $image_url = wp_get_attachment_image_url($featured_id, 'large');
        if ($image_url) {
          return $image_url;
        }
      }
    }

    // Fallback to avatar if no featured image
    return !empty($this->data['avatar_url']) ? $this->data['avatar_url'] : null;
  }

  private function generateFeaturedImageLayout($colors, $featured_image_url)
  {
    $layout = '';

    // Featured image on the left (400px width)
    $layout .= '<rect x="0" y="0" width="400" height="630" fill="#f1f5f9"/>' . "\n";

    try {
      $image_data = $this->getImageAsBase64($featured_image_url);
      if ($image_data) {
        // Image with proper aspect ratio fitting
        $layout .= '<image x="20" y="20" width="360" height="590" href="' . $image_data . '" preserveAspectRatio="xMidYMid slice" style="border-radius: 12px;"/>' . "\n";
      }
    } catch (Exception $e) {
      // Fallback placeholder
      $layout .= '<rect x="20" y="20" width="360" height="590" rx="12" fill="#e2e8f0"/>' . "\n";
      $layout .= '<text x="200" y="320" font-family="system-ui, sans-serif" font-size="16" fill="#64748b" text-anchor="middle">Featured Image</text>' . "\n";
    }

    // Content area on the right
    $layout .= $this->generateContentArea($colors, 450, 580);

    return $layout;
  }

  private function generateTextOnlyLayout($colors)
  {
    $layout = '';

    // Subtle background pattern
    $layout .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

    // Centered content
    $layout .= $this->generateContentArea($colors, 100, 1000);

    return $layout;
  }

  private function generateContentArea($colors, $x_start, $width)
  {
    $content = '';

    // Main title
    $title = $this->data['page_title'] ?: $this->data['site_title'];
    $title = $this->truncateText($title, 45);

    $content .= '<text x="' . ($x_start + 50) . '" y="200" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="48" font-weight="700" fill="' . $colors['text_primary'] . '">' . "\n";
    $content .= $this->escapeXML($title) . "\n";
    $content .= '</text>' . "\n";

    // Subtitle/Site name (if page title is different)
    if (!empty($this->data['page_title']) && $this->data['page_title'] !== $this->data['site_title']) {
      $site_name = $this->truncateText($this->data['site_title'], 35);
      $content .= '<text x="' . ($x_start + 50) . '" y="250" font-family="system-ui, sans-serif" font-size="24" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $content .= $this->escapeXML($site_name) . "\n";
      $content .= '</text>' . "\n";
    }

    // Description/Tagline
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 90);
      $content .= '<text x="' . ($x_start + 50) . '" y="320" font-family="system-ui, sans-serif" font-size="18" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
      $content .= $this->escapeXML($tagline) . "\n";
      $content .= '</text>' . "\n";
    }

    // Accent line
    $content .= '<rect x="' . ($x_start + 50) . '" y="360" width="100" height="4" rx="2" fill="' . $colors['accent'] . '"/>' . "\n";

    return $content;
  }

  private function generateDomainDisplay($colors)
  {
    $domain = '';

    // Extract clean domain from site URL
    $clean_domain = $this->getCleanDomain();

    // Logo/Avatar in bottom right if available
    if (!empty($this->data['avatar_url'])) {
      $domain .= '<circle cx="1120" cy="580" r="25" fill="rgba(255,255,255,0.9)" stroke="' . $colors['accent_secondary'] . '" stroke-width="2"/>' . "\n";

      try {
        $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
        if ($avatar_data) {
          $domain .= '<image x="1100" y="560" width="40" height="40" href="' . $avatar_data . '" clip-path="url(#logoClip)"/>' . "\n";
        }
      } catch (Exception $e) {
        // Fallback to domain text
      }
    }

    // Domain text
    $domain .= '<text x="1050" y="555" font-family="system-ui, sans-serif" font-size="14" font-weight="500" fill="' . $colors['text_secondary'] . '" text-anchor="end">' . "\n";
    $domain .= $this->escapeXML($clean_domain) . "\n";
    $domain .= '</text>' . "\n";

    return $domain;
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
    $defs = parent::generateDefs($colors);

    // Add logo clip path
    $defs = str_replace('</defs>', '', $defs);
    $defs .= '<clipPath id="logoClip">' . "\n";
    $defs .= '<circle cx="20" cy="20" r="20"/>' . "\n";
    $defs .= '</clipPath>' . "\n";
    $defs .= '</defs>' . "\n";

    return $defs;
  }
}
