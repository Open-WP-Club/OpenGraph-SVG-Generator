# OpenGraph SVG Generator

A WordPress plugin that automatically generates beautiful SVG OpenGraph images for social media sharing.

## Features

- **Automatic Social Sharing** - Works with Facebook, Twitter, LinkedIn, Discord, Slack, WhatsApp
- **Dynamic Content** - Uses your site title and page titles
- **Avatar Integration** - Upload your profile image
- **5 Color Schemes** - Blue, Purple, Dark, Green, Gabriel Kanev theme
- **Bulk Generation** - Generate images for all posts at once
- **Media Library Integration** - Images appear in WordPress media library
- **Easy Management** - View, regenerate, and clean up images from admin panel

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through WordPress admin
3. Go to **Settings → OpenGraph SVG**
4. Upload your avatar image
5. Choose your color scheme
6. Save settings

## Usage

The plugin works automatically once activated. Every page on your site will have a unique OpenGraph image when shared on social media.

**Generate Images for All Posts:**

1. Go to Settings → OpenGraph SVG
2. Click "Generate All Images"
3. Wait for the process to complete

**Image URLs:**

- Homepage: `yoursite.com/og-svg/home/`
- Specific pages: `yoursite.com/og-svg/[post-id]/`

## Settings

- **Avatar Image**: Your profile photo (200x200px recommended)
- **Color Scheme**: Choose from 5 professional themes
- **Show Tagline**: Include/exclude site tagline
- **Post Types**: Enable for posts, pages, custom types
- **Fallback Title**: Text for pages without titles

## Troubleshooting

**If OpenGraph URLs return 404 errors:**

1. Go to Settings → OpenGraph SVG
2. Click "Fix URL Issues"
3. Test URLs with "Test URLs" button

**If images don't appear:**

1. Check that your post type is enabled in settings
2. Use "Generate All Images" to create missing images
3. Verify upload directory is writable

## WP-CLI Commands

```bash
# Generate images for all posts
wp og-svg generate

# Force regenerate all images
wp og-svg generate --force

# Generate for specific post type
wp og-svg generate --post-type=post

# Show statistics
wp og-svg stats

# Test URLs
wp og-svg test

# Clean up all images
wp og-svg cleanup
```

## Requirements

- WordPress 5.0+
- PHP 7.4+
- Pretty permalinks enabled (recommended)
- Writable uploads directory

## License

GPL v2 or later
