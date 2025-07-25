<?php

/**
 * Gabriel Theme - Professional Tech Style
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_Gabriel extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Gabriel Kanev',
      'description' => 'Professional tech theme with dark gradients and modern elements',
      'author' => 'Gabriel Kanev',
      'preview_colors' => array('#0f172a', '#3b82f6', '#06b6d4')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#0f172a',
      'gradient_start' => '#1e293b',
      'gradient_end' => '#0f172a',
      'text_primary' => '#f8fafc',
      'text_secondary' => '#cbd5e1',
      'accent' => '#3b82f6',
      'accent_secondary' => '#06b6d4'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();
    
    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);
    
    // Background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";
    
    // Tech-inspired decorative elements
    $svg .= $this->generateTechDecorations();
    
    // Content container with subtle border
    $svg .= '<rect x="60" y="60" width="1080" height="510" rx="20" fill="rgba(255,255,255,0.08)" stroke="rgba(59, 130, 246, 0.2)" stroke-width="1"/>' . "\n";
    
    // Avatar section
    if (!empty($this->data['avatar_url'])) {
      $svg .= $this->generateAvatar();
    }
    
    // Text content
    $svg .= $this->generateTextContent($colors);
    
    // Professional footer
    $svg .= $this->generateProfessionalFooter($colors);
    
    $svg .= $this->generateSVGFooter();
    
    return $svg;
  }

  private function generateTechDecorations()
  {
    $decorations = '';
    
    // Tech circles with gradients
    $decorations .= '<circle cx="1050" cy="150" r="120" fill="rgba(59, 130, 246, 0.08)" opacity="0.6"/>' . "\n";
    $decorations .= '<circle cx="1100" cy="500" r="80" fill="rgba(6, 182, 212, 0.06)" opacity="0.8"/>' . "\n";
    $decorations .= '<circle cx="100" cy="100" r="60" fill="rgba(59, 130, 246, 0.05)" opacity="0.7"/>' . "\n";

    // Hexagon patterns (tech/innovation symbol)
    $decorations .= '<polygon points="950,50 980,35 1010,50 1010,80 980,95 950,80" fill="rgba(59, 130, 246, 0.08)" opacity="0.4"/>' . "\n";
    $decorations .= '<polygon points="1080,400 1100,390 1120,400 1120,420 1100,430 1080,420" fill="rgba(6, 182, 212, 0.06)" opacity="0.5"/>' . "\n";
    
    // Circuit-like lines
    $decorations .= '<path d="M 50 300 L 100 300 L 120 280 L 150 280" stroke="rgba(59, 130, 246, 0.1)" stroke-width="2" fill="none"/>' . "\n";
    $decorations .= '<path d="M 1050 250 L 1100 250 L 1120 230 L 1150 230" stroke="rgba(6, 182, 212, 0.1)" stroke-width="2" fill="none"/>' . "\n";
    
    return $decorations;
  }

  private function generateAvatar()
  {
    $avatar = '';
    
    // Enhanced avatar with tech border
    $avatar .= '<circle cx="200" cy="200" r="75" fill="rgba(59, 130, 246, 0.1)" stroke="rgba(59, 130, 246, 0.3)" stroke-width="3"/>' . "\n";
    $avatar .= '<circle cx="200" cy="200" r="70" fill="rgba(255,255,255,0.95)"/>' . "\n";
    
    // Try to embed avatar image
    $avatar_data = $this->getImageAsBase64($this->data['avatar_url']);
    if ($avatar_data) {
      $avatar .= '<image x="135" y="135" width="130" height="130" href="' . $avatar_data . '" clip-path="url(#avatarClip)" transform="translate(65,65)"/>' . "\n";
    } else {
      // Professional fallback icon
      $avatar .= '<circle cx="200" cy="200" r="50" fill="rgba(59, 130, 246, 0.2)"/>' . "\n";
      $avatar .= '<path d="M200 170 c-11 0 -20 9 -20 20 s 9 20 20 20 s 20 -9 20 -20 s -9 -20 -20 -20 z M200 220 c-16.5 0 -30 13.5 -30 30 l 60 0 c 0 -16.5 -13.5 -30 -30 -30 z" fill="rgba(59, 130, 246, 0.8)"/>' . "\n";
    }
    
    return $avatar;
  }

  private function generateTextContent($colors)
  {
    $text = '';
    
    // Site title with enhanced styling
    $site_title = $this->truncateText($this->data['site_title'], 25);
    $text .= '<text x="320" y="160" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="42" font-weight="700" fill="' . $colors['text_primary'] . '" filter="url(#textShadow)">' . "\n";
    $text .= $this->escapeXML($site_title) . "\n";
    $text .= '</text>' . "\n";

    // Page title with modern styling
    $page_title = $this->truncateText($this->data['page_title'], 50);
    $text .= '<text x="320" y="210" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="28" font-weight="400" fill="' . $colors['text_secondary'] . '">' . "\n";
    $text .= $this->escapeXML($page_title) . "\n";
    $text .= '</text>' . "\n";

    // Tagline with subtle styling
    if (!empty($this->settings['show_tagline']) && !empty($this->data['tagline'])) {
      $tagline = $this->truncateText($this->data['tagline'], 80);
      $text .= '<text x="320" y="250" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="18" font-weight="300" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
      $text .= $this->escapeXML($tagline) . "\n";
      $text .= '</text>' . "\n";
    }
    
    return $text;
  }

  private function generateProfessionalFooter($colors)
  {
    $footer = '';
    
    // Modern accent line with gradient effect
    $footer .= '<rect x="320" y="280" width="100" height="4" rx="2" fill="' . $colors['accent'] . '"/>' . "\n";
    $footer .= '<rect x="430" y="280" width="50" height="4" rx="2" fill="' . $colors['accent_secondary'] . '" opacity="0.6"/>' . "\n";

    // Website URL with professional styling
    $footer .= '<text x="320" y="320" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="16" font-weight="500" fill="' . $colors['text_secondary'] . '" opacity="0.8">' . "\n";
    $footer .= $this->escapeXML($this->data['site_url']) . "\n";
    $footer .= '</text>' . "\n";

    // Professional credentials indicator
    $footer .= '<rect x="320" y="340" width="8" height="8" rx="4" fill="' . $colors['accent_secondary'] . '" opacity="0.8"/>' . "\n";
    $footer .= '<text x="338" y="349" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="12" font-weight="500" fill="' . $colors['text_secondary'] . '" opacity="0.7">' . "\n";
    $footer .= 'Product Manager • PhD Student • Developer' . "\n";
    $footer .= '</text>' . "\n";
    
    // Tech corner element
    $footer .= '<rect x="1050" y="550" width="100" height="2" fill="' . $colors['accent'] . '" opacity="0.3"/>' . "\n";
    $footer .= '<rect x="1050" y="555" width="60" height="2" fill="' . $colors['accent_secondary'] . '" opacity="0.5"/>' . "\n";
    
    return $footer;
  }
}