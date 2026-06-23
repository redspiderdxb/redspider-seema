<?php
/**
 * Plugin Name: RedSpider Page Editor
 * Description: Sleek and dynamic content editor to modify layout text and images visually.
 * Version: 1.0
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
  // 1. Update Headings & Paragraphs
  if (isset($post_data['texts']) && is_array($post_data['texts'])) {
    foreach ($post_data['texts'] as $key => $new_text) {
      // Find the tag pattern with placeholder index comment e.g. <!-- text-index: 5 -->
      $pattern = '/(<!--\s*text-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<[h1-6|p|span|a][^>]*>)(.*?)(<\/[h1-6|p|span|a]>)/is';
      // Replace with new text
      $html = preg_replace($pattern, '${1}' . wp_kses_post($new_text) . '${3}', $html);
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
      // Strip domain to save local paths, or keep placeholder {{theme_uri}} if it matches theme folder
      $theme_uri = get_template_directory_uri();
      if (strpos($new_src, $theme_uri) !== false) {
        $new_src = str_replace($theme_uri, '{{theme_uri}}', $new_src);
      }
      
      $pattern = '/(<!--\s*img-index:\s*' . preg_quote($key, '/') . '\s*-->\s*<img[^>]+src=["\'])(.*?)(["\'])/is';
      $html = preg_replace($pattern, '${1}' . esc_url($new_src) . '${3}', $html);
    }
  }

  return $html;
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
      $updated_content = redspider_update_html_tags($original_post->post_content, $_POST);
      wp_update_post([
        'ID'           => $page_to_update,
        'post_content' => $updated_content,
      ]);
      $message = __('Changes saved successfully!', 'redspider-editor');
      $selected_page_id = $page_to_update;
    }
  }

  // Fetch all pages
  $pages = get_pages();
  ?>

  <div class="wrap" style="margin-top: 25px; max-width: 900px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
    <div style="background: #111; color: #fff; padding: 35px; border-radius: 10px; border-left: 6px solid #DE1515; box-shadow: 0 5px 20px rgba(0,0,0,0.15);">
      <h2 style="color: #fff; font-size: 26px; font-weight: 700; margin: 0 0 10px 0;">RedSpider Visual Page Editor</h2>
      <p style="color: #aaa; font-size: 14px; margin: 0 0 25px 0;">Visual, code-free inline text and image management system created by RedSpider.</p>

      <?php if (!empty($message)) : ?>
        <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 12px 18px; border-radius: 6px; margin-bottom: 25px; font-size: 14px;">
          ✓ <?php echo esc_html($message); ?>
        </div>
      <?php endif; ?>

      <div style="background: #1a1a1a; padding: 20px; border-radius: 6px; border: 1px solid #292929; margin-bottom: 25px;">
        <form method="get" action="">
          <input type="hidden" name="page" value="redspider-editor">
          <label style="display: block; font-weight: 600; margin-bottom: 8px; color: #ccc; font-size: 14px;">Select Page to Edit:</label>
          <select name="page_id" onchange="this.form.submit()" style="background: #333; color: #fff; border: 1px solid #444; padding: 8px 12px; font-size: 14px; border-radius: 4px; width: 100%; max-width: 350px;">
            <option value="0">-- Select a Page --</option>
            <?php foreach ($pages as $p) : ?>
              <option value="<?php echo esc_attr($p->ID); ?>" <?php selected($selected_page_id, $p->ID); ?>><?php echo esc_html($p->post_title); ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php
      if ($selected_page_id) :
        $post = get_post($selected_page_id);
        $content = $post->post_content;

        // Parse HTML and inject index markers if they do not exist
        // Note: index markers are injected dynamically on edit view, then saved back so they remain persistent.
        $needs_inject = (strpos($content, '<!-- text-index:') === false);

        if ($needs_inject) {
          $text_counter = 0;
          $link_counter = 0;
          $img_counter = 0;

          // Inject markers for headings, paragraphs, spans, anchors
          $content = preg_replace_callback(
            '/(<[h1-6|p|span][^>]*>)(.*?)(<\/[h1-6|p|span]>)/is',
            function ($matches) use (&$text_counter) {
              $tag_start = $matches[1];
              $inner = $matches[2];
              $tag_end = $matches[3];
              // Skip if empty or purely whitespace
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

          // Save markers to database so they persist
          wp_update_post([
            'ID'           => $selected_page_id,
            'post_content' => $content,
          ]);
        }

        // Gather all indexed elements
        preg_match_all('/<!--\s*text-index:\s*(\d+)\s*-->\s*<([h1-6|p|span|a])[^>]*>(.*?)(<\/[h1-6|p|span|a]>)/is', $content, $text_matches, PREG_SET_ORDER);
        preg_match_all('/<!--\s*link-index:\s*(\d+)\s*-->\s*<a[^>]+href=["\'](.*?)["\']/is', $content, $link_matches, PREG_SET_ORDER);
        preg_match_all('/<!--\s*img-index:\s*(\d+)\s*-->\s*<img[^>]+src=["\'](.*?)["\']/is', $content, $img_matches, PREG_SET_ORDER);
        ?>

        <form method="post" action="">
          <?php wp_nonce_field('redspider_editor_save_action', 'redspider_editor_nonce'); ?>
          <input type="hidden" name="edit_page_id" value="<?php echo esc_attr($selected_page_id); ?>">

          <!-- Text Section -->
          <?php if (!empty($text_matches)) : ?>
            <div style="margin-bottom: 30px;">
              <h3 style="color: #DE1515; border-bottom: 2px solid #333; padding-bottom: 8px; font-size: 18px; font-weight: 600;">Edit Page Texts & Headings</h3>
              <div style="display: grid; gap: 15px; margin-top: 15px;">
                <?php foreach ($text_matches as $match) :
                  $index = $match[1];
                  $tag = $match[2];
                  $text = trim(strip_tags($match[3]));
                  if (empty($text)) continue;
                  ?>
                  <div>
                    <label style="display: block; font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 4px;">Element: &lt;<?php echo esc_html($tag); ?>&gt; (Index: <?php echo esc_html($index); ?>)</label>
                    <?php if ($tag === 'p') : ?>
                      <textarea name="texts[<?php echo esc_attr($index); ?>]" style="background: #222; color: #fff; border: 1px solid #333; padding: 10px; width: 100%; border-radius: 4px; font-size: 14px; min-height: 80px; box-sizing: border-box;"><?php echo esc_textarea($text); ?></textarea>
                    <?php else : ?>
                      <input type="text" name="texts[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr($text); ?>" style="background: #222; color: #fff; border: 1px solid #333; padding: 8px 10px; width: 100%; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Links Section -->
          <?php if (!empty($link_matches)) : ?>
            <div style="margin-bottom: 30px;">
              <h3 style="color: #DE1515; border-bottom: 2px solid #333; padding-bottom: 8px; font-size: 18px; font-weight: 600;">Edit Button URLs</h3>
              <div style="display: grid; gap: 15px; margin-top: 15px;">
                <?php foreach ($link_matches as $match) :
                  $index = $match[1];
                  $url = $match[2];
                  ?>
                  <div>
                    <label style="display: block; font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 4px;">Link URL (Index: <?php echo esc_html($index); ?>)</label>
                    <input type="text" name="links[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr($url); ?>" style="background: #222; color: #fff; border: 1px solid #333; padding: 8px 10px; width: 100%; border-radius: 4px; font-size: 14px; box-sizing: border-box;">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- Images Section -->
          <?php if (!empty($img_matches)) : ?>
            <div style="margin-bottom: 30px;">
              <h3 style="color: #DE1515; border-bottom: 2px solid #333; padding-bottom: 8px; font-size: 18px; font-weight: 600;">Edit Page Images</h3>
              <div style="display: grid; gap: 20px; margin-top: 15px;">
                <?php foreach ($img_matches as $match) :
                  $index = $match[1];
                  $src = $match[2];
                  // Resolve placeholder {{theme_uri}} to actual theme path for thumbnail previews
                  $display_src = str_replace('{{theme_uri}}', get_template_directory_uri(), $src);
                  ?>
                  <div style="display: flex; gap: 20px; align-items: center; background: #1a1a1a; padding: 15px; border-radius: 6px; border: 1px solid #292929;">
                    <div style="width: 100px; height: 100px; background: #222; display: flex; align-items: center; justify-content: center; overflow: hidden; border-radius: 4px; border: 1px solid #333;">
                      <img id="img-preview-<?php echo esc_attr($index); ?>" src="<?php echo esc_url($display_src); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <div style="flex-grow: 1;">
                      <label style="display: block; font-size: 12px; color: #888; text-transform: uppercase; margin-bottom: 6px;">Image path (Index: <?php echo esc_html($index); ?>)</label>
                      <input type="text" id="img-input-<?php echo esc_attr($index); ?>" name="images[<?php echo esc_attr($index); ?>]" value="<?php echo esc_attr($src); ?>" style="background: #222; color: #fff; border: 1px solid #333; padding: 8px 10px; width: 100%; border-radius: 4px; font-size: 14px; box-sizing: border-box; margin-bottom: 8px;">
                      <button type="button" class="button redspider-upload-btn" data-index="<?php echo esc_attr($index); ?>" style="background: #333; color: #fff; border-color: #444; border-radius: 4px; font-weight: 600;">Choose Image</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

          <hr style="border: 0; border-top: 1px solid #333; margin: 30px 0;">

          <button type="submit" name="redspider_save_editor" class="button button-primary button-hero" style="background: #DE1515; border-color: #DE1515; color: #fff; font-weight: 600; padding: 10px 25px; height: auto; line-height: 1.5; border-radius: 4px; font-size: 15px; cursor: pointer; transition: all 0.2s;">
            Save Page Content
          </button>
        </form>

        <script>
        jQuery(document).ready(function($){
          $('.redspider-upload-btn').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var index = button.data('index');
            
            var custom_uploader = wp.media({
              title: 'Select RedSpider Asset Image',
              button: {
                text: 'Use Selected Image'
              },
              multiple: false
            });

            custom_uploader.on('select', function() {
              var attachment = custom_uploader.state().get('selection').first().toJSON();
              $('#img-input-' + index).val(attachment.url);
              $('#img-preview-' + index).attr('src', attachment.url);
            });

            custom_uploader.open();
          });
        });
        </script>
      <?php endif; ?>

    </div>
  </div>
  
  <style>
    .button-primary:hover {
      background: #c00f0f !important;
      border-color: #c00f0f !important;
    }
  </style>
  <?php
}
