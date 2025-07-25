<?php

/**
 * Creative Theme - Bold and Colorful
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_Creative extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Creative',
      'description' => 'Bold and colorful design with creative elements',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#7c3aed', '#f59e0b', '#ef4444')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#7c3aed',
      'gradient_start' => '#a855f7',
      'gradient_end' => '#7c3aed',
      'text_primary' => '#ffffff',
      'text_secondary' => '#e5e7eb',
      'accent' => '#f59e0b',
      'accent_secondary' => '#ef4444'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateCreativeDefs($colors);

    // Gradient background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

    // Creative decorations
    $svg .= $this->generateCreativeDecorations();

    // Main content area with creative border
    $svg .= '<rect x="60" y="60" width="1080" height="510" rx="25" fill="rgba(255,255,255,0.1)" stroke="rgba(245, 158, 11, 0.3)" stroke-width="2" stroke-dasharray="10,5"/>' . "\n";

    // Avatar with creative styling
    if (!empty($this->data['avatar_url'])) {
      $svg .= $this->generateCreativeAvatar();
    }

    // Creative text layout
    $svg .= $this->generateCreativeText($colors);

    // Artistic footer
    $svg .= $this->generateCreativeFooter($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function generateCreativeDefs($colors)
  {
    $defs = '<defs>' . "\n";

    // Multi-color gradient
    $defs .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="50%" style="stop-color:' . $colors['background'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['gradient_end'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Colorful accent gradient
    $defs .= '<linearGradient id="accentGradient" x1="0%" y1="0%" x2="100%" y2="0%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['accent'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['accent_secondary'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    // Text shadow filter
    $defs .= '<filter id="textShadow" x="-20%" y="-20%" width="140%" height="140%">' . "\n";
    $defs .= '<feDropShadow dx="3" dy="3" stdDeviation="4" flood-color="rgba(0,0,0,0.4)"/>' . "\n";
    $defs .= '</filter>' . "\n";

    // Avatar clip path
    $defs .= '<clipPath id="avatarClip">' . "\n";
    $defs .= '<circle cx="65" cy="65" r="65"/>' . "\n";
    $defs .= '</clipPath>' . "\n";

    $defs .= '</defs>' . "\n";

    return $defs;
  }

  private function generateCreativeDecorations()
  {
    $decorations = '';

    // Colorful shapes
    $decorations .= '<circle cx="1050" cy="120" r="80" fill="rgba(245, 158, 11, 0.2)"/>' . "\n";
    $decorations .= '<circle cx="1100" cy="520" r="60" fill="rgba(239, 68, 68, 0.15)"/>' . "\n";
    $decorations .= '<circle cx="120" cy="150" r="40" fill="rgba(245, 158, 11, 0.25)"/>' . "\n";

    // Creative triangles
    $decorations .= '<polygon points="950,80 990,80 970,40" fill="rgba(239, 68, 68, 0.2)"/>' . "\n";
    $decorations .= '<polygon points="80,500 120,500 100,460" fill="rgba(245, 158, 11, 0.3)"/>' . "\n";

    // Artistic lines
    $decorations .= '<path d="M 50 300 Q 100 280 150 300 T 250 300" stroke="rgba(245, 158, 11, 0.3)" stroke-width="3" fill="none"/>' . "\n";
    $decorations .= '<path d="M 950 400 Q 1000 380 1050 400 T 1150 400" stroke="rgba(239, 68, 68, 0.3)" stroke-width="3" fill="none"/>' . "\n";

    return $decorations;
  }

  private function generateCreativeAvatar()
  {
    $avatar = '';

    // Creative avatar border with multiple colors
    $avatar .= '<circle cx="200" cy="200" r="78" fill="none" stroke="url(#accentGradient)" stroke-width="4"/>' . "\n";
    $avatar .= '<circle cx="200" cy="200" r="72" fill="rgba(255,255,255,0.95)" stroke="rgba(255,255,255,0.5)" stroke-width="2"/>' . "\n";

    $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
    if ($avatar_data) {
      $avatar .= '<image x="135" y="135" width="130" height="130" href="' . $avatar_data . '" clip-path="url(#avatarClip)" transform="translate(65,65)"/>' . "\n";
    } else {
      // Creative fallback with colors - always show something
      $avatar .= '<circle cx="200" cy="200" r="50" fill="rgba(245, 158, 11, 0.3)"/>' . "\n";
      $avatar .= '<circle cx="200" cy="200" r="35" fill="rgba(239, 68, 68, 0.4)"/>' . "\n";
      $avatar .= '<path d="M200 180 c-8 0 -15 7 -15 15 s 7 15 15 15 s 15 -7 15 -15 s -7 -15 -15 -15 z M200 210 c-12 0 -22 10 -22 22 l 44 0 c 0 -12 -10 -22 -22 -22 z" fill="rgba(255,255,255,0.9)"/>' . "\n";
    }

    return $avatar;
  }

  private function generateCreativeText($colors)
  {
    $text = '';

    // Bold site title with creative styling
    $site_title = $this->truncateText($this->data['site_title'], 25);
    $text .= '<text x="320" y="160" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="44" font-weight="800" fill="' . $colors['text_primary'] . '" filter="url(#textShadow)">' . "\n";
    $text .= $this->escapeXML($site_title) . "\n";
    $text .= '</text>' . "\n";

    // Creative page title with accent
    $page_title = $this->truncateText($this->data['page_title'], 50);
    $text .= '<text x="320" y="210" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="30" font-weight="600" fill="' . $colors['accent'] . '">' . "\n";
    $text .= $this->escapeXML($page_title) . "\n";
    $text .= '</text>' . "\n";

    // Stylized tagline
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 80);
      $text .= '<text x="320" y="250" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="400" fill="' . $colors['text_secondary'] . '" opacity="0.9">' . "\n";
      $text .= $this->escapeXML($tagline) . "\n";
      $text .= '</text>' . "\n";
    }

    return $text;
  }

  private function generateCreativeFooter($colors)
  {
    $footer = '';

    // Artistic accent bars
    $footer .= '<rect x="320" y="280" width="80" height="6" rx="3" fill="url(#accentGradient)"/>' . "\n";
    $footer .= '<rect x="410" y="280" width="40" height="6" rx="3" fill="' . $colors['accent_secondary'] . '" opacity="0.7"/>' . "\n";
    $footer .= '<rect x="460" y="280" width="20" height="6" rx="3" fill="' . $colors['accent'] . '" opacity="0.5"/>' . "\n";

    // Creative URL styling
    $footer .= '<text x="320" y="320" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="16" font-weight="500" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
    $footer .= $this->escapeXML($this->data['site_url']) . "\n";
    $footer .= '</text>' . "\n";

    // Creative corner elements
    $footer .= '<circle cx="1050" cy="560" r="5" fill="' . $colors['accent'] . '" opacity="0.6"/>' . "\n";
    $footer .= '<circle cx="1070" cy="565" r="3" fill="' . $colors['accent_secondary'] . '" opacity="0.5"/>' . "\n";
    $footer .= '<circle cx="1090" cy="560" r="4" fill="' . $colors['accent'] . '" opacity="0.4"/>' . "\n";

    return $footer;
  }
}
