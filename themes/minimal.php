<?php

/**
 * Minimal Theme - Clean and Simple
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_Minimal extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Minimal',
      'description' => 'Clean and simple design with lots of white space',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#ffffff', '#f8fafc', '#64748b')
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

    // Clean background
    $svg .= '<rect width="1200" height="630" fill="' . $colors['background'] . '"/>' . "\n";

    // Subtle border
    $svg .= '<rect x="0" y="0" width="1200" height="630" fill="none" stroke="' . $colors['accent_secondary'] . '" stroke-width="2"/>' . "\n";

    // Simple content area
    $svg .= '<rect x="80" y="80" width="1040" height="470" fill="' . $colors['gradient_start'] . '" rx="12"/>' . "\n";

    // Avatar section
    if (!empty($this->data['avatar_url'])) {
      $svg .= $this->generateMinimalAvatar();
    }

    // Text content
    $svg .= $this->generateCleanText($colors);

    // Simple footer
    $svg .= $this->generateMinimalFooter($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function generateMinimalAvatar()
  {
    $avatar = '';

    // Simple circular avatar
    $avatar .= '<circle cx="200" cy="200" r="50" fill="#ffffff" stroke="#e2e8f0" stroke-width="3"/>' . "\n";

    $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
    if ($avatar_data) {
      $avatar .= '<image x="155" y="155" width="90" height="90" href="' . $avatar_data . '" clip-path="url(#avatarClip)" transform="translate(45,45)"/>' . "\n";
    } else {
      // Simple fallback
      $avatar .= '<circle cx="200" cy="200" r="35" fill="#f1f5f9"/>' . "\n";
      $avatar .= '<path d="M200 180 c-8 0 -15 7 -15 15 s 7 15 15 15 s 15 -7 15 -15 s -7 -15 -15 -15 z M200 220 c-12 0 -22 10 -22 22 l 44 0 c 0 -12 -10 -22 -22 -22 z" fill="#cbd5e1"/>' . "\n";
    }

    return $avatar;
  }

  private function generateCleanText($colors)
  {
    $text = '';

    // Clean site title
    $site_title = $this->truncateText($this->data['site_title'], 30);
    $text .= '<text x="320" y="170" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="36" font-weight="600" fill="' . $colors['text_primary'] . '">' . "\n";
    $text .= $this->escapeXML($site_title) . "\n";
    $text .= '</text>' . "\n";

    // Simple page title
    $page_title = $this->truncateText($this->data['page_title'], 60);
    $text .= '<text x="320" y="210" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="22" font-weight="400" fill="' . $colors['text_secondary'] . '">' . "\n";
    $text .= $this->escapeXML($page_title) . "\n";
    $text .= '</text>' . "\n";

    // Optional tagline
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 90);
      $text .= '<text x="320" y="245" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="16" font-weight="300" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
      $text .= $this->escapeXML($tagline) . "\n";
      $text .= '</text>' . "\n";
    }

    return $text;
  }

  private function generateMinimalFooter($colors)
  {
    $footer = '';

    // Simple accent line
    $footer .= '<rect x="320" y="280" width="60" height="2" fill="' . $colors['accent'] . '"/>' . "\n";

    // Clean URL display
    $footer .= '<text x="320" y="320" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="14" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.7">' . "\n";
    $footer .= $this->escapeXML($this->data['site_url']) . "\n";
    $footer .= '</text>' . "\n";

    return $footer;
  }
}
