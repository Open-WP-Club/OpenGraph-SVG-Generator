<?php

/**
 * Meta Handler Class
 * Handles injection of OpenGraph meta tags into page headers
 */

if (!defined('ABSPATH')) {
  exit;
}

if (!class_exists('OG_SVG_Meta_Handler')) {

  class OG_SVG_Meta_Handler
  {

    private $settings;
    private $generator;

    public function __construct()
    {
      $this->settings = get_option('og_svg_settings', array());
      $this->generator = new OG_SVG_Generator();

      add_action('wp_head', array($this, 'addOpenGraphTags'), 5);
      add_filter('language_attributes', array($this, 'addOpenGraphNamespace'));
    }

    public function addOpenGraphNamespace($output)
    {
      return $output . ' prefix="og: http://ogp.me/ns#"';
    }

    public function addOpenGraphTags()
    {
      // Check if we should add OG tags for current post type
      if (!$this->shouldAddOpenGraphTags()) {
        return;
      }

      global $post;

      // Get basic page information
      $title = $this->getPageTitle();
      $description = $this->getPageDescription();
      $url = $this->getCurrentUrl();
      $site_name = get_bloginfo('name');
      $image_url = $this->getOpenGraphImageUrl();

      // Output OpenGraph meta tags
      echo "\n<!-- OpenGraph SVG Generator Meta Tags -->\n";

      // Basic OpenGraph tags
      echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
      echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
      echo '<meta property="og:url" content="' . esc_url($url) . '" />' . "\n";
      echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";
      echo '<meta property="og:type" content="' . $this->getOpenGraphType() . '" />' . "\n";

      // Image meta tags
      if ($image_url) {
        echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
        echo '<meta property="og:image:width" content="1200" />' . "\n";
        echo '<meta property="og:image:height" content="630" />' . "\n";
        echo '<meta property="og:image:type" content="image/svg+xml" />' . "\n";
        echo '<meta property="og:image:alt" content="' . esc_attr($title . ' - ' . $site_name) . '" />' . "\n";
      }

      // Twitter Card tags (for better Twitter integration)
      echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
      echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
      echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
      if ($image_url) {
        echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
      }

      // Additional meta tags for better SEO
      if (!has_action('wp_head', 'rel_canonical')) {
        echo '<link rel="canonical" href="' . esc_url($url) . '" />' . "\n";
      }

      echo "<!-- End OpenGraph SVG Generator Meta Tags -->\n";
    }

    private function shouldAddOpenGraphTags()
    {
      // Check if current post type is enabled
      $enabled_post_types = isset($this->settings['enabled_post_types']) ? $this->settings['enabled_post_types'] : array('post', 'page');

      if (is_home() || is_front_page()) {
        return in_array('page', $enabled_post_types);
      }

      if (is_singular()) {
        $post_type = get_post_type();
        return in_array($post_type, $enabled_post_types);
      }

      // Enable for archives if 'post' is enabled
      if (is_archive() || is_category() || is_tag() || is_author()) {
        return in_array('post', $enabled_post_types);
      }

      return false;
    }

    private function getPageTitle()
    {
      if (is_home() || is_front_page()) {
        $title = get_bloginfo('name');
        $tagline = get_bloginfo('description');
        if ($tagline) {
          $title .= ' - ' . $tagline;
        }
        return $title;
      }

      if (is_singular()) {
        return get_the_title();
      }

      if (is_category()) {
        return 'Category: ' . single_cat_title('', false);
      }

      if (is_tag()) {
        return 'Tag: ' . single_tag_title('', false);
      }

      if (is_author()) {
        return 'Author: ' . get_the_author();
      }

      if (is_archive()) {
        return wp_title('', false);
      }

      return get_bloginfo('name');
    }

    private function getPageDescription()
    {
      if (is_singular()) {
        global $post;

        // Try to get excerpt first
        if (has_excerpt($post)) {
          return wp_strip_all_tags(get_the_excerpt());
        }

        // Get content preview
        $content = wp_strip_all_tags($post->post_content);
        if (strlen($content) > 160) {
          $content = substr($content, 0, 157) . '...';
        }

        return $content ?: get_bloginfo('description');
      }

      if (is_category()) {
        $description = category_description();
        return $description ? wp_strip_all_tags($description) : 'Browse posts in this category';
      }

      if (is_tag()) {
        $description = tag_description();
        return $description ? wp_strip_all_tags($description) : 'Browse posts with this tag';
      }

      if (is_author()) {
        $description = get_the_author_meta('description');
        return $description ?: 'View posts by this author';
      }

      return get_bloginfo('description') ?: 'Welcome to our website';
    }

    private function getCurrentUrl()
    {
      if (is_home() || is_front_page()) {
        return get_home_url();
      }

      if (is_singular()) {
        return get_permalink();
      }

      // For archives, categories, etc.
      global $wp;
      return home_url($wp->request);
    }

    private function getOpenGraphImageUrl()
    {
      global $post;

      if (is_singular() && $post) {
        return $this->generator->getSVGUrl($post->ID);
      }

      // For home page, archives, etc.
      return $this->generator->getSVGUrl();
    }

    private function getOpenGraphType()
    {
      if (is_home() || is_front_page()) {
        return 'website';
      }

      if (is_singular('post')) {
        return 'article';
      }

      if (is_singular()) {
        return 'website';
      }

      if (is_author()) {
        return 'profile';
      }

      return 'website';
    }

    /**
     * Helper method to get schema.org structured data (bonus feature)
     */
    public function addStructuredData()
    {
      if (!$this->shouldAddOpenGraphTags()) {
        return;
      }

      $title = $this->getPageTitle();
      $description = $this->getPageDescription();
      $url = $this->getCurrentUrl();
      $image_url = $this->getOpenGraphImageUrl();

      $schema = array(
        '@context' => 'https://schema.org',
        '@type' => is_singular('post') ? 'Article' : 'WebPage',
        'name' => $title,
        'description' => $description,
        'url' => $url,
      );

      if ($image_url) {
        $schema['image'] = array(
          '@type' => 'ImageObject',
          'url' => $image_url,
          'width' => 1200,
          'height' => 630
        );
      }

      if (is_singular('post')) {
        global $post;
        $schema['datePublished'] = get_the_date('c', $post);
        $schema['dateModified'] = get_the_modified_date('c', $post);
        $schema['author'] = array(
          '@type' => 'Person',
          'name' => get_the_author_meta('display_name', $post->post_author)
        );
      }

      echo '<script type="application/ld+json">';
      echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      echo '</script>' . "\n";
    }
  }
} // End class_exists check

// Optional: Add structured data if desired
// add_action('wp_head', array('OG_SVG_Meta_Handler', 'addStructuredData'), 10);