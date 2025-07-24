<?php

/**
 * Theme Manager Class
 * Handles loading and managing all available themes
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('OG_SVG_Theme_Manager')) {

  class OG_SVG_Theme_Manager
  {
    private $themes = array();
    private $themes_dir;

    public function __construct()
    {
      $this->themes_dir = OG_SVG_PLUGIN_PATH . 'themes/';
      $this->loadThemes();
    }

    /**
     * Load all available themes
     */
    private function loadThemes()
    {
      // Load base theme class first
      if (!class_exists('OG_SVG_Theme_Base')) {
        require_once $this->themes_dir . 'base-theme.php';
      }

      // Get all theme files
      $theme_files = glob($this->themes_dir . '*.php');

      foreach ($theme_files as $theme_file) {
        $filename = basename($theme_file, '.php');

        // Skip base theme
        if ($filename === 'base-theme') {
          continue;
        }

        // Load theme file
        require_once $theme_file;

        // Get theme class name (convert filename to class name)
        $class_name = 'OG_SVG_Theme_' . $this->fileNameToClassName($filename);

        if (class_exists($class_name)) {
          // Create temporary instance to get theme info
          $temp_instance = new $class_name(array(), array());
          $theme_info = $temp_instance->getThemeInfo();

          $this->themes[$filename] = array(
            'class' => $class_name,
            'info' => $theme_info,
            'file' => $theme_file
          );
        }
      }
    }

    /**
     * Convert filename to class name format
     */
    private function fileNameToClassName($filename)
    {
      // Convert kebab-case or snake_case to PascalCase
      return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $filename)));
    }

    /**
     * Get all available themes
     */
    public function getAvailableThemes()
    {
      $available = array();

      foreach ($this->themes as $theme_id => $theme_data) {
        $available[$theme_id] = $theme_data['info'];
      }

      return $available;
    }

    /**
     * Get theme instance
     */
    public function getTheme($theme_id, $settings = array(), $data = array())
    {
      if (!isset($this->themes[$theme_id])) {
        // Fallback to default theme
        $theme_id = 'gabriel';
        if (!isset($this->themes[$theme_id])) {
          throw new Exception('No themes available');
        }
      }

      $class_name = $this->themes[$theme_id]['class'];
      return new $class_name($settings, $data);
    }

    /**
     * Check if theme exists
     */
    public function themeExists($theme_id)
    {
      return isset($this->themes[$theme_id]);
    }

    /**
     * Get theme info
     */
    public function getThemeInfo($theme_id)
    {
      if (!isset($this->themes[$theme_id])) {
        return null;
      }

      return $this->themes[$theme_id]['info'];
    }

    /**
     * Create new theme from template
     */
    public function createThemeTemplate($theme_name, $theme_id = null)
    {
      if (!$theme_id) {
        $theme_id = sanitize_title($theme_name);
      }

      $class_name = 'OG_SVG_Theme_' . $this->fileNameToClassName($theme_id);
      $file_path = $this->themes_dir . $theme_id . '.php';

      if (file_exists($file_path)) {
        throw new Exception('Theme already exists');
      }

      $template = $this->getThemeTemplate($theme_name, $class_name);

      if (file_put_contents($file_path, $template) === false) {
        throw new Exception('Failed to create theme file');
      }

      // Reload themes
      $this->loadThemes();

      return $theme_id;
    }

    /**
     * Get theme template code
     */
    private function getThemeTemplate($theme_name, $class_name)
    {
      return '<?php

/**
 * ' . $theme_name . ' Theme
 * Custom theme for OpenGraph SVG Generator
 */

if (!defined(\'ABSPATH\')) {
  exit;
}

class ' . $class_name . ' extends OG_SVG_Theme_Base
{
  public function getThemeInfo()
  {
    return array(
      \'name\' => \'' . $theme_name . '\',
      \'description\' => \'Custom theme for OpenGraph images\',
      \'author\' => \'Custom\',
      \'preview_colors\' => array(\'#3b82f6\', \'#1e40af\', \'#60a5fa\')
    );
  }

  public function getColorScheme()
  {
    return array(
      \'background\' => \'#1e40af\',
      \'gradient_start\' => \'#3b82f6\',
      \'gradient_end\' => \'#1e40af\',
      \'text_primary\' => \'#ffffff\',
      \'text_secondary\' => \'#e5e7eb\',
      \'accent\' => \'#60a5fa\',
      \'accent_secondary\' => \'#93c5fd\'
    );
  }

  public function generateSVG()
  {
    $colors = $this->getColorScheme();
    
    $svg = $this->generateSVGHeader();
    $svg .= $this->generateDefs($colors);
    
    // Background
    $svg .= \'<rect width="1200" height="630" fill="url(#bgGradient)"/>\' . "\n";
    
    // Content container
    $svg .= \'<rect x="60" y="60" width="1080" height="510" rx="20" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>\' . "\n";
    
    // Avatar section
    if (!empty($this->data[\'avatar_url\'])) {
      $svg .= $this->generateAvatar();
    }
    
    // Text content
    $svg .= $this->generateTextContent($colors);
    
    // Footer
    $svg .= $this->generateFooter($colors);
    
    $svg .= $this->generateSVGFooter();
    
    return $svg;
  }

  private function generateAvatar()
  {
    $avatar = \'\';
    
    $avatar .= \'<circle cx="200" cy="200" r="70" fill="rgba(255,255,255,0.9)" stroke="rgba(255,255,255,0.3)" stroke-width="2"/>\' . "\n";
    
    $avatar_data = $this->getImageAsBase64($this->data[\'avatar_url\']);
    if ($avatar_data) {
      $avatar .= \'<image x="135" y="135" width="130" height="130" href="\' . $avatar_data . \'" clip-path="url(#avatarClip)" transform="translate(65,65)"/>\' . "\n";
    }
    
    return $avatar;
  }

  private function generateTextContent($colors)
  {
    $text = \'\';
    
    // Site title
    $site_title = $this->truncateText($this->data[\'site_title\'], 25);
    $text .= \'<text x="320" y="160" font-family="system-ui, sans-serif" font-size="42" font-weight="700" fill="\' . $colors[\'text_primary\'] . \'">\' . "\n";
    $text .= $this->escapeXML($site_title) . "\n";
    $text .= \'</text>\' . "\n";

    // Page title
    $page_title = $this->truncateText($this->data[\'page_title\'], 50);
    $text .= \'<text x="320" y="210" font-family="system-ui, sans-serif" font-size="28" font-weight="400" fill="\' . $colors[\'text_secondary\'] . \'">\' . "\n";
    $text .= $this->escapeXML($page_title) . "\n";
    $text .= \'</text>\' . "\n";

    return $text;
  }

  private function generateFooter($colors)
  {
    $footer = \'\';
    
    $footer .= \'<rect x="320" y="280" width="100" height="4" rx="2" fill="\' . $colors[\'accent\'] . \'"/>\' . "\n";
    $footer .= \'<text x="320" y="320" font-family="system-ui, sans-serif" font-size="16" fill="\' . $colors[\'text_secondary\'] . \'" opacity="0.7">\' . "\n";
    $footer .= $this->escapeXML($this->data[\'site_url\']) . "\n";
    $footer .= \'</text>\' . "\n";
    
    return $footer;
  }
}';
    }

    /**
     * Get themes directory path
     */
    public function getThemesDirectory()
    {
      return $this->themes_dir;
    }

    /**
     * Scan for new themes (useful for development)
     */
    public function refreshThemes()
    {
      $this->themes = array();
      $this->loadThemes();
      return count($this->themes);
    }
  }
}
