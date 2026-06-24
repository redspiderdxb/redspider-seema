<?php
/**
 * Plugin Name: RedSpider Page Editor
 * Description: Sleek and dynamic content editor to modify layout text and images visually.
 * Version: 1.3
 * Author: seema kashyap
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

// Render Editor Page
function redspider_editor_page_render()
{
  $message = '';
  $selected_page_id = isset($_GET['page_id']) ? intval($_GET['page_id']) : 0;

  // Handle Save Action
  if (isset($_POST['redspider_save_editor']) && check_admin_referer('redspider_editor_save_action', 'redspider_editor_nonce')) {
    $page_to_update = intval($_POST['edit_page_id']);
    $original_post = get_post($page_to_update);
    if ($original_post) {
      // 1. Update Page Title and Status
      $page_title  = sanitize_text_field($_POST['page_title']);
      $page_status = sanitize_text_field($_POST['page_status']);

      // 2. Process Section HTMLs
      $updated_sections = [];
      if (isset($_POST['section_htmls']) && is_array($_POST['section_htmls'])) {
        foreach ($_POST['section_htmls'] as $sec_html) {
          $sec_html = wp_unslash($sec_html);
          $updated_sec = redspider_update_html_tags($sec_html, $_POST);
          $updated_sections[] = $updated_sec;
        }
      }
      
      // Concatenate the sections back together
      $updated_content = implode("\n\n", $updated_sections);
      
      wp_update_post([
        'ID'           => $page_to_update,
        'post_title'   => $page_title,
        'post_status'  => $page_status,
        'post_content' => $updated_content,
      ]);
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
        <div class="rs-logo-badge">RS</div>
        <div>
          <h2>RedSpider Visual Page Editor</h2>
          <p>Drag, reorder, edit, and view page layouts dynamically in real-time.</p>
        </div>
      </div>

      <?php if (!empty($message)) : ?>
        <div class="rs-alert rs-alert-success">
          <span class="rs-alert-icon">✓</span> <?php echo esc_html($message); ?>
        </div>
      <?php endif; ?>

      <div class="rs-selector-section">
        <form method="get" action="">
          <input type="hidden" name="page" value="redspider-editor">
          <label>Select Page to Edit:</label>
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
              Open Page in New Tab ↗
            </a>
          </div>
        <?php endif; ?>
      </div>

      <?php
      if ($selected_page_id) :
        $post = get_post($selected_page_id);
        $content = $post->post_content;

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
        ?>

        <script>
        var rsTemplates = <?php echo json_encode($templates_json); ?>;
        </script>

        <div class="rs-builder-container">
          <!-- LEFT EDITOR PANEL -->
          <div class="rs-editor-panel">
            <form method="post" action="" class="rs-editor-form">
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
                    <?php foreach ($templates as $slug => $label) : ?>
                      <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="button" id="rs-add-template-btn" class="rs-btn rs-btn-primary">
                    + Add Layout
                  </button>
                </div>
              </div>

              <!-- Submit Buttons -->
              <div class="rs-editor-footer">
                <button type="submit" name="redspider_save_editor" class="rs-btn rs-btn-primary rs-btn-large">
                  Save All Changes
                </button>
              </div>
            </form>
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
        });
        </script>
      <?php endif; ?>

    </div>
  </div>

  <style>
    .rs-editor-wrap {
      margin: 15px auto 40px auto;
      max-width: 100%;
      padding: 0 20px;
      box-sizing: border-box;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .rs-editor-card {
      background: #111;
      color: #e5e5e5;
      padding: 30px;
      border-radius: 12px;
      border-left: 6px solid #DE1515;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
    }
    .rs-editor-header {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 25px;
      border-bottom: 1px solid #222;
      padding-bottom: 20px;
    }
    .rs-logo-badge {
      background: #DE1515;
      color: #fff;
      font-size: 24px;
      font-weight: 800;
      width: 50px;
      height: 50px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 4px 15px rgba(222, 21, 21, 0.4);
      flex-shrink: 0;
    }
    .rs-editor-header h2 {
      color: #fff;
      font-size: 24px;
      font-weight: 700;
      margin: 0;
      line-height: 1.2;
    }
    .rs-editor-header p {
      color: #999;
      margin: 3px 0 0 0;
      font-size: 13px;
    }
    .rs-selector-section {
      background: #1a1a1a;
      padding: 15px 20px;
      border-radius: 8px;
      border: 1px solid #282828;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 20px;
    }
    .rs-selector-section form {
      flex-grow: 1;
      max-width: 450px;
    }
    .rs-selector-section label {
      display: block;
      font-weight: 600;
      margin-bottom: 6px;
      color: #ccc;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-select-wrapper select {
      background: #252525;
      color: #fff;
      border: 1px solid #333;
      padding: 8px 12px;
      font-size: 13px;
      border-radius: 6px;
      width: 100%;
      height: 38px;
      line-height: 1.5;
      box-sizing: border-box;
      outline: none;
      transition: all 0.2s;
    }
    .rs-select-wrapper select:focus {
      border-color: #DE1515;
    }
    .rs-alert {
      padding: 15px 20px;
      border-radius: 8px;
      margin-bottom: 25px;
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .rs-alert-success {
      background: rgba(46, 204, 113, 0.1);
      border: 1px solid #2ecc71;
      color: #2ecc71;
    }
    .rs-alert-icon {
      font-weight: bold;
      font-size: 18px;
    }
    .rs-top-actions .rs-btn {
      height: 38px;
    }

    /* SPLIT CONTAINER LAYOUT */
    .rs-builder-container {
      display: flex;
      gap: 25px;
      align-items: flex-start;
    }
    @media (max-width: 1200px) {
      .rs-builder-container {
        flex-direction: column;
      }
      .rs-editor-panel,
      .rs-preview-panel {
        width: 100% !important;
      }
    }
    .rs-editor-panel {
      width: 520px;
      flex-shrink: 0;
      max-height: calc(100vh - 180px);
      overflow-y: auto;
      padding-right: 5px;
    }
    /* Custom scrollbar for editor panel */
    .rs-editor-panel::-webkit-scrollbar {
      width: 6px;
    }
    .rs-editor-panel::-webkit-scrollbar-track {
      background: #111;
    }
    .rs-editor-panel::-webkit-scrollbar-thumb {
      background: #2c2c2c;
      border-radius: 3px;
    }
    .rs-editor-panel::-webkit-scrollbar-thumb:hover {
      background: #DE1515;
    }

    .rs-preview-panel {
      flex-grow: 1;
      background: #1a1a1a;
      border: 1px solid #282828;
      border-radius: 12px;
      padding: 0;
      overflow: hidden;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      position: sticky;
      top: 35px;
    }

    /* Page Settings Box */
    .rs-page-settings-box {
      background: #181818;
      border: 1px solid #292929;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 20px;
    }
    .rs-page-settings-box h4 {
      color: #DE1515;
      font-size: 14px;
      font-weight: 700;
      margin: 0 0 15px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-styled-select {
      background: #222;
      color: #fff;
      border: 1px solid #333;
      border-radius: 6px;
      padding: 9px 12px;
      font-size: 14px;
      width: 100%;
      height: 42px;
      outline: none;
      transition: all 0.2s;
    }
    .rs-styled-select:focus {
      border-color: #DE1515;
    }

    /* Accordion Custom Styling */
    .rs-accordion-item {
      background: #181818;
      border: 1px solid #292929;
      border-radius: 8px;
      margin-bottom: 12px;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s, opacity 0.2s;
    }
    .rs-accordion-item.rs-dragging {
      opacity: 0.4;
      border: 2px dashed #DE1515;
      background: #251212;
    }
    .rs-accordion-item.active {
      border-color: #DE1515;
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    .rs-accordion-header {
      width: 100%;
      background: #1c1c1c;
      border: none;
      padding: 15px 20px;
      text-align: left;
      color: #fff;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      justify-content: space-between;
      align-items: center;
      transition: background 0.2s;
      outline: none;
    }
    .rs-accordion-header:hover {
      background: #252525;
    }
    .rs-drag-handle {
      color: #555;
      margin-right: 12px;
      font-size: 16px;
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
      margin-right: 12px;
    }
    .rs-accordion-controls {
      display: flex;
      gap: 6px;
      margin-right: 12px;
    }
    .rs-control-btn {
      background: #2b2b2b;
      color: #aaa;
      width: 26px;
      height: 26px;
      border-radius: 4px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      transition: all 0.2s;
      cursor: pointer;
    }
    .rs-control-btn:hover {
      background: #DE1515;
      color: #fff;
    }
    .rs-control-btn.rs-delete:hover {
      background: #e74c3c;
    }
    .rs-accordion-icon {
      font-size: 11px;
      color: #888;
    }
    .rs-accordion-body {
      display: none;
      border-top: 1px solid #262626;
      background: #141414;
    }
    .rs-accordion-content {
      padding: 20px;
    }
    .rs-group-container {
      margin-bottom: 25px;
      border-bottom: 1px solid #222;
      padding-bottom: 20px;
    }
    .rs-group-container:last-child {
      margin-bottom: 0;
      border-bottom: none;
      padding-bottom: 0;
    }
    .rs-group-title {
      color: #DE1515;
      font-size: 14px;
      font-weight: 700;
      margin: 0 0 15px 0;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-fields-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
    }
    .rs-field-item label {
      display: block;
      font-size: 11px;
      color: #888;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }
    .rs-field-item input[type="text"],
    .rs-field-item textarea {
      width: 100%;
      background: #222;
      color: #fff;
      border: 1px solid #333;
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
      background: #272727;
    }
    .rs-field-item textarea {
      min-height: 70px;
      resize: vertical;
    }
    .rs-images-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 15px;
    }
    .rs-image-card {
      background: #1a1a1a;
      border: 1px solid #292929;
      border-radius: 8px;
      padding: 12px;
      display: flex;
      gap: 12px;
      align-items: center;
    }
    .rs-image-preview {
      width: 70px;
      height: 70px;
      background: #111;
      border: 1px solid #2d2d2d;
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
      color: #888;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    .rs-image-details input[type="text"] {
      width: 100%;
      background: #222;
      color: #fff;
      border: 1px solid #333;
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
      background: #252525;
      color: #fff;
      border: 1px solid #3a3a3a;
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
      background: #333;
      border-color: #444;
      color: #fff;
    }
    .rs-btn-small {
      padding: 4px 10px;
      font-size: 10px;
      border-radius: 4px;
    }
    .rs-btn-secondary {
      background: transparent;
      border-color: #444;
      color: #aaa;
      height: 38px;
      box-sizing: border-box;
    }
    .rs-btn-secondary:hover {
      border-color: #DE1515;
      color: #fff;
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
      padding: 12px 30px;
      font-size: 15px;
      box-shadow: 0 4px 15px rgba(222, 21, 21, 0.2);
    }
    .rs-add-section-area {
      background: #1a1a1a;
      border: 1px solid #282828;
      border-radius: 8px;
      padding: 20px;
      margin-top: 25px;
    }
    .rs-add-section-area h4 {
      margin: 0 0 3px 0;
      color: #fff;
      font-size: 16px;
      font-weight: 600;
    }
    .rs-add-section-area p {
      margin: 0 0 12px 0;
      color: #999;
      font-size: 12px;
    }
    .rs-add-section-controls {
      display: flex;
      gap: 12px;
    }
    .rs-add-section-controls select {
      background: #252525;
      color: #fff;
      border: 1px solid #333;
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
      color: #2ecc71;
      padding: 12px;
      border-radius: 6px;
      margin-bottom: 0;
    }
    .rs-new-notice strong {
      display: block;
      font-size: 13px;
      margin-bottom: 3px;
    }
    .rs-new-notice p {
      margin: 0;
      font-size: 11px;
      color: #a2e8bc;
    }
    .rs-highlight-temp {
      border-color: #DE1515 !important;
      box-shadow: 0 0 12px rgba(222, 21, 21, 0.6) !important;
    }
    .rs-editor-footer {
      margin-top: 25px;
      text-align: left;
      border-top: 1px solid #222;
      padding-top: 20px;
    }
    .rs-muted {
      color: #555;
      font-weight: normal;
    }

    /* PREVIEW CONTAINER STYLING */
    .rs-preview-header {
      background: #202020;
      padding: 12px 20px;
      border-bottom: 1px solid #2d2d2d;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .rs-preview-title {
      font-weight: 700;
      color: #fff;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .rs-device-controls {
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .rs-device-btn {
      background: #2c2c2c;
      color: #bbb;
      border: 1px solid #3d3d3d;
      border-radius: 4px;
      padding: 5px 12px;
      font-size: 11px;
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
      background: #2c2c2c;
      color: #bbb;
      border: 1px solid #3d3d3d;
      border-radius: 4px;
      padding: 5px 10px;
      font-size: 11px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .rs-refresh-btn:hover {
      background: #fff;
      color: #000;
      border-color: #fff;
    }
    .rs-preview-iframe-wrapper {
      background: #141414;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      padding: 20px 0;
      min-height: calc(100vh - 200px);
      box-sizing: border-box;
      overflow-y: auto;
    }
    #rs-preview-iframe {
      border: none;
      background: #fff;
      box-shadow: 0 10px 30px rgba(0,0,0,0.5);
      border-radius: 4px;
      height: 700px;
      transition: width 0.3s ease, height 0.3s ease;
    }
    .rs-preview-desktop {
      width: 100%;
      max-width: 1200px;
    }
    .rs-preview-tablet {
      width: 768px !important;
    }
    .rs-preview-mobile {
      width: 375px !important;
      height: 650px !important;
    }
  </style>
  <?php
}
