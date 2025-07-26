<?php

/**
 * Dark Author Theme - Clean dark background with author attribution
 */

if (!defined('ABSPATH')) {
  exit;
}

class OG_SVG_Theme_DarkAuthor extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      'name' => 'Dark Author',
      'description' => 'Clean dark background with large title, categories, and author attribution',
      'author' => 'OpenGraph SVG Generator',
      'preview_colors' => array('#0f172a', '#8b5cf6', '#ffffff')
    );
  }

  public function getColorScheme()
  {
    return array(
      'background' => '#1e293b',
      'gradient_start' => '#334155',
      'gradient_end' => '#1e293b',
      'text_primary' => '#a855f7',  // Brighter purple for title
      'text_secondary' => '#ffffff', // Pure white for subtitle
      'accent' => '#22d3ee',        // Brighter cyan for categories
      'accent_secondary' => '#fbbf24',
      'author_text' => '#e2e8f0'    // Light gray for author, much more visible
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();

    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);

    // Dark gradient background
    $svg .= '<rect width="1200" height="630" fill="url(#bgGradient)"/>' . "\n";

    // Main title (2-3 lines max, big)
    $title_end_y = $this->generateMainTitle($colors);

    // Categories (if available)
    $category_end_y = $this->generateCategories($colors, $title_end_y + 40);

    // Subtitle/description
    $this->generateSubtitle($colors, $category_end_y + 30);

    // Author attribution bottom left
    $svg .= $this->generateAuthorAttribution($colors);

    $svg .= $this->generateSVGFooter();

    return $svg;
  }

  private function generateMainTitle($colors)
  {
    // Get the main title
    $main_title = !empty($this->data['page_title']) ? $this->data['page_title'] : $this->data['site_title'];

    // Split title into words and create 2-3 lines max
    $words = explode(' ', $main_title);
    $lines = array();
    $current_line = '';
    $max_chars_per_line = 28; // Shorter for big text

    foreach ($words as $word) {
      $test_line = $current_line . ($current_line ? ' ' : '') . $word;

      if (strlen($test_line) > $max_chars_per_line && $current_line && count($lines) < 2) {
        $lines[] = $current_line;
        $current_line = $word;
      } else {
        $current_line = $test_line;
      }
    }

    if ($current_line) {
      $lines[] = $current_line;
    }

    // If we have more than 3 lines, combine last lines
    if (count($lines) > 3) {
      $last_lines = array_slice($lines, 2);
      $lines = array_slice($lines, 0, 2);
      $lines[] = implode(' ', $last_lines);
    }

    // Determine font size based on line count and content
    if (count($lines) == 1) {
      $font_size = 76;
      $line_height = 85;
    } else if (count($lines) == 2) {
      $font_size = 68;
      $line_height = 78;
    } else {
      $font_size = 60;
      $line_height = 70;
    }

    $start_y = 120;
    $title = '';

    foreach ($lines as $index => $line) {
      $y_position = $start_y + ($index * $line_height);

      $title .= '<text x="100" y="' . $y_position . '" font-family="system-ui, -apple-system, BlinkMacSystemFont, sans-serif" font-size="' . $font_size . '" font-weight="800" fill="' . $colors['text_primary'] . '" letter-spacing="-1.5px" stroke="rgba(255,255,255,0.1)" stroke-width="1">' . "\n";
      $title .= $this->escapeXML(trim($line)) . "\n";
      $title .= '</text>' . "\n";
    }

    return $start_y + (count($lines) * $line_height);
  }

  private function generateCategories($colors, $start_y)
  {
    $category_section = '';
    $current_y = $start_y;

    // Get categories if this is a post
    if (!empty($this->data['post_id'])) {
      $categories = get_the_category($this->data['post_id']);

      if (!empty($categories)) {
        $category_names = array();
        foreach (array_slice($categories, 0, 3) as $category) { // Max 3 categories
          $category_names[] = $category->name;
        }

        if (!empty($category_names)) {
          $category_text = implode(' • ', $category_names);

          $category_section .= '<text x="100" y="' . $current_y . '" font-family="system-ui, sans-serif" font-size="20" font-weight="700" fill="' . $colors['accent'] . '" letter-spacing="0.5px" text-transform="uppercase">' . "\n";
          $category_section .= $this->escapeXML($category_text) . "\n";
          $category_section .= '</text>' . "\n";

          $current_y += 35;
        }
      }
    }

    return $current_y;
  }

  private function generateSubtitle($colors, $start_y)
  {
    $subtitle = '';

    // Show tagline or excerpt as subtitle
    $subtitle_text = '';

    if (!empty($this->data['tagline']) && !empty($this->settings['show_tagline'])) {
      $subtitle_text = $this->data['tagline'];
    } else if (!empty($this->data['post_id'])) {
      // Try to get post excerpt
      $post = get_post($this->data['post_id']);
      if ($post && !empty($post->post_excerpt)) {
        $subtitle_text = $post->post_excerpt;
      }
    }

    if ($subtitle_text) {
      // Limit subtitle length
      $subtitle_text = $this->truncateText($subtitle_text, 85);

      $subtitle .= '<text x="100" y="' . $start_y . '" font-family="system-ui, sans-serif" font-size="24" font-weight="500" fill="' . $colors['text_secondary'] . '" letter-spacing="-0.5px">' . "\n";
      $subtitle .= $this->escapeXML($subtitle_text) . "\n";
      $subtitle .= '</text>' . "\n";
    }

    return $subtitle;
  }

  private function generateAuthorAttribution($colors)
  {
    $author_section = '';

    // Get author information
    $author_name = '';

    if (!empty($this->data['post_id'])) {
      $post = get_post($this->data['post_id']);
      if ($post) {
        $author_name = get_the_author_meta('display_name', $post->post_author);
      }
    }

    // Fallback to site name if no author
    if (!$author_name) {
      $author_name = get_bloginfo('name');
    }

    if ($author_name) {
      $author_section .= '<text x="100" y="580" font-family="system-ui, sans-serif" font-size="24" font-weight="600" fill="' . $colors['author_text'] . '">' . "\n";
      $author_section .= 'By: ' . $this->escapeXML($author_name) . "\n";
      $author_section .= '</text>' . "\n";
    }

    return $author_section;
  }

  protected function generateDefs($colors)
  {
    $defs = '<defs>' . "\n";

    // Dark gradient background
    $defs .= '<linearGradient id="bgGradient" x1="0%" y1="0%" x2="100%" y2="100%">' . "\n";
    $defs .= '<stop offset="0%" style="stop-color:' . $colors['gradient_start'] . ';stop-opacity:1" />' . "\n";
    $defs .= '<stop offset="100%" style="stop-color:' . $colors['background'] . ';stop-opacity:1" />' . "\n";
    $defs .= '</linearGradient>' . "\n";

    $defs .= '</defs>' . "\n";

    return $defs;
  }
}
