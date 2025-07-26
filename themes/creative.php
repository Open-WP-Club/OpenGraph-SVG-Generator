<?php

/**
 * Modern Card Theme - Card-style layout with featured image
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_Modern_Card extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Modern Card',
      'description' => 'Card-style layout with featured image header and clean typography',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#f8fafc', '#ffffff', '#0f172a')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#f8fafc',
      'gradient_start' => '#f8fafc',
      'gradient_end' => '#e2e8f0',
      'text_primary' => '#0f172a',
      'text_secondary' => '#475569',
      'accent' => '#0ea5e9',
      'accent_secondary' => '#f0f9ff',
      'card_background' => '#ffffff'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);

    // Background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

    // Card container
    $svg .= '<rect x="60" y="60" width="1080" height="510" rx="24" fill="' . $colors['card_background'] . '" stroke="rgba(0,0,0,0.05)" stroke-width="1" filter="url(#cardShadow)"/>' . "\n";

    // Featured image area
    $featured_image = $this->getFeaturedImageUrl();
    if ($featured_image) {
      $svg .= $this->generateFeaturedImageHeader($colors, $featured_image);
      $svg .= $this->generateCardContent($colors, 280); // With image
    } else {
      $svg .= $this->generateCardContent($colors, 120); // No image
    }

    // Domain/Logo footer
    $svg .= $this->generateCardFooter($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function getFeaturedImageUrl()
  {
    // Get featured image from current post
    global $post;

    if (!empty($this->data['post_id'])) {
      $post_id = $this->data['post_id'];
    } elseif ($post && $post->ID) {
      $post_id = $post->ID;
    } else {
      return null;
    }

    $featured_id = get_post_thumbnail_id($post_id);

    if ($featured_id) {
      $image_url = wp_get_attachment_image_url($featured_id, 'large');
      if ($image_url) {
        return $image_url;
      }
    }

    return null;
  }

  private function generateFeaturedImageHeader($colors, $featured_image_url)
  {
    $header = '';

    // Image container with rounded top corners
    $header .= '<clipPath id="imageHeaderClip">' . "\n";
    $header .= '<rect x="60" y="60" width="1080" height="200" rx="24"/>' . "\n";
    $header .= '</clipPath>' . "\n";

    // Background for image
    $header .= '<rect x="60" y="60" width="1080" height="200" fill="#e2e8f0" clip-path="url(#imageHeaderClip)"/>' . "\n";

    try {
      $image_data = $this->getImageAsBase64($featured_image_url);
      if ($image_data) {
        $header .= '<image x="60" y="60" width="1080" height="200" href="' . $image_data . '" preserveAspectRatio="xMidYMid slice" clip-path="url(#imageHeaderClip)"/>' . "\n";

        // Overlay gradient for better text readability
        $header .= '<rect x="60" y="180" width="1080" height="80" fill="url(#imageOverlay)" clip-path="url(#imageHeaderClip)"/>' . "\n";
      }
    } catch (Exception $e) {
      // Fallback pattern
      $header .= '<rect x="60" y="60" width="1080" height="200" fill="url(#patternGradient)" clip-path="url(#imageHeaderClip)"/>' . "\n";
    }

    return $header;
  }

  private function generateCardContent($colors, $y_start)
  {
    $content = '';

    // Main title
    $title = $this->data['page_title'] ?: $this->data['site_title'];
    $title = $this->truncateText($title, 50);

    $content .= '<text x="120" y="' . ($y_start + 50) . '" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="42" font-weight="700" fill="' . $colors['text_primary'] . '">' . "\n";
    $content .= $this->escapeXML($title) . "\n";
    $content .= '</text>' . "\n";

    // Subtitle (site name if showing page title)
    if (!empty($this->data['page_title']) && $this->data['page_title'] !== $this->data['site_title']) {
      $site_name = $this->truncateText($this->data['site_title'], 40);
      $content .= '<text x="120" y="' . ($y_start + 90) . '" font-family="system-ui, sans-serif" font-size="20" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
      $content .= $this->escapeXML($site_name) . "\n";
      $content .= '</text>' . "\n";
    }

    // Description/Tagline
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 100);
      $y_offset = (!empty($this->data['page_title']) && $this->data['page_title'] !== $this->data['site_title']) ? 140 : 110;

      $content .= '<text x="120" y="' . ($y_start + $y_offset) . '" font-family="system-ui, sans-serif" font-size="16" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
      $content .= $this->escapeXML($tagline) . "\n";
      $content .= '</text>' . "\n";
    }

    return $content;
  }

  private function generateCardFooter($colors)
  {
    $footer = '';

    // Footer separator
    $footer .= '<line x1="120" y1="520" x2="1080" y2="520" stroke="' . $colors['accent_secondary'] . '" stroke-width="1"/>' . "\n";

    // Logo/Avatar on the left
    if (!empty($this->data['avatar_url'])) {
      $footer .= '<circle cx="150" cy="550" r="18" fill="' . $colors['card_background'] . '" stroke="' . $colors['accent'] . '" stroke-width="2"/>' . "\n";

      try {
        $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
        if ($avatar_data) {
          $footer .= '<image x="135" y="535" width="30" height="30" href="' . $avatar_data . '" clip-path="url(#footerLogoClip)"/>' . "\n";
        }
      } catch (Exception $e) {
        // Fallback icon
        $footer .= '<circle cx="150" cy="550" r="12" fill="' . $colors['accent'] . '"/>' . "\n";
      }
    }

    // Domain text
    $clean_domain = $this->getCleanDomain();
    $x_position = !empty($this->data['avatar_url']) ? 185 : 120;

    $footer .= '<text x="' . $x_position . '" y="558" font-family="system-ui, sans-serif" font-size="14" font-weight="500" fill="' . $colors['text_secondary'] . '">' . "\n";
    $footer .= $this->escapeXML($clean_domain) . "\n";
    $footer .= '</text>' . "\n";

    // Publication date or category (if available)
    if (!empty($this->data['post_id'])) {
      $post_date = get_the_date('M j, Y', $this->data['post_id']);
      if ($post_date) {
        $footer .= '<text x="1080" y="558" font-family="system-ui, sans-serif" font-size="12" font-weight="400" fill="' . $colors['text_secondary'] . '" text-anchor="end" opacity="0.7">' . "\n";
        $footer .= $this->escapeXML($post_date) . "\n";
        $footer .= '</text>' . "\n";
      }
    }

    return $footer;
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

    // Background gradient
    $defs .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Image overlay gradient
    $defs .= '<linearGradient id="imageOverlay" x1="0%" y1="0%" x2="0%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:rgba(0,0,0,0);stop-opacity:0" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:rgba(0,0,0,0.4);stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Pattern gradient (fallback)
    $defs .= '<linearGradient id="patternGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['accent_secondary'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['accent'] . ';stop-opacity:0.2" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Card shadow
    $defs .= '<filter id="cardShadow" x="-50%" y="-50%" width="200%" height="200%">' . "\n";
    $defs .= '<feDropShadow dx="0" dy="8" stdDeviation="24" flood-color="rgba(0,0,0,0.12)"/>' . "\n";
    $defs .= '</filter>' . "\n";

    // Footer logo clip path
    $defs .= '<clipPath id="footerLogoClip">' . "\n";
    $defs .= '<circle cx="15" cy="15" r="15"/>' . "\n";
    $defs .= '</clipPath>' . "\n";

    $defs .= '</defs>' . "\n";

    return $defs;
  }
}
