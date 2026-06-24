<?php

if (!defined('ABSPATH')) {
  exit;
}

require_once get_template_directory() . '/inc/class-redspider-navwalker.php';

function redspider_setup()
{
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo');

  register_nav_menus([
    'primary_menu'        => __('Primary Menu', 'redspider'),
    'footer_menu'         => __('Footer Menu', 'redspider'),
    'footer_services'     => __('Footer Services', 'redspider'),
    'footer_social'       => __('Footer Social', 'redspider'),
    'footer_bottom_links' => __('Footer Bottom Links', 'redspider'),
  ]);
}

add_action('after_setup_theme', 'redspider_setup');

function redspider_assets()
{
  $theme_uri = get_template_directory_uri();

  wp_enqueue_style(
    'bootstrap',
    $theme_uri . '/assets/vendor/bootstrap/css/bootstrap.min.css',
    [],
    null
  );

  wp_enqueue_style(
    'bootstrap-icons',
    $theme_uri . '/assets/vendor/bootstrap-icons/bootstrap-icons.css',
    [],
    null
  );

  wp_enqueue_style(
    'aos',
    $theme_uri . '/assets/vendor/aos/aos.css',
    [],
    null
  );

  wp_enqueue_style(
    'glightbox',
    $theme_uri . '/assets/vendor/glightbox/css/glightbox.min.css',
    [],
    null
  );

  wp_enqueue_style(
    'swiper',
    $theme_uri . '/assets/vendor/swiper/swiper-bundle.min.css',
    [],
    null
  );

  wp_enqueue_style(
    'font-style',
    $theme_uri . '/assets/fonts/stylesheet.css',
    [],
    null
  );

  wp_enqueue_style(
    'main-css',
    $theme_uri . '/assets/css/main.css',
    [],
    null
  );

  wp_enqueue_style(
    'custom-css',
    $theme_uri . '/assets/css/custom.css',
    ['main-css'],
    null
  );

  wp_enqueue_script(
    'bootstrap-js',
    $theme_uri . '/assets/vendor/bootstrap/js/bootstrap.bundle.min.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'aos-js',
    $theme_uri . '/assets/vendor/aos/aos.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'glightbox-js',
    $theme_uri . '/assets/vendor/glightbox/js/glightbox.min.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'swiper-js',
    $theme_uri . '/assets/vendor/swiper/swiper-bundle.min.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'main-js',
    $theme_uri . '/assets/js/main.js',
    [],
    null,
    true
  );

  wp_enqueue_script(
    'custom-js',
    $theme_uri . '/assets/js/custome.js',
    ['main-js'],
    null,
    true
  );

  wp_enqueue_script(
    'redspider-contact-js',
    $theme_uri . '/assets/js/contact-form.js',
    ['jquery'],
    null,
    true
  );

  wp_localize_script('redspider-contact-js', 'redspider_ajax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce'    => wp_create_nonce('redspider_contact_nonce'),
  ]);
}

add_action('wp_enqueue_scripts', 'redspider_assets');

function redspider_customize_register($wp_customize)
{
  $wp_customize->add_section(
    'redspider_contact',
    [
      'title'    => __('Contact Settings', 'redspider'),
      'priority' => 30,
    ]
  );

  $wp_customize->add_setting('whatsapp_number');

  $wp_customize->add_control(
    'whatsapp_number',
    [
      'label'   => __('WhatsApp Number', 'redspider'),
      'section' => 'redspider_contact',
      'type'    => 'text',
    ]
  );

  $wp_customize->add_setting('consultation_url');

  $wp_customize->add_control(
    'consultation_url',
    [
      'label'   => __('Consultation URL', 'redspider'),
      'section' => 'redspider_contact',
      'type'    => 'url',
    ]
  );

  $wp_customize->add_setting('consultation_text', [
    'default' => 'Schedule Free Consultation',
  ]);

  $wp_customize->add_control(
    'consultation_text',
    [
      'label'   => __('Consultation Button Text', 'redspider'),
      'section' => 'redspider_contact',
      'type'    => 'text',
    ]
  );

  // Add Footer Settings Section
  $wp_customize->add_section(
    'redspider_footer',
    [
      'title'    => __('Footer Settings', 'redspider'),
      'priority' => 31,
    ]
  );

  // Footer Heading
  $wp_customize->add_setting('footer_heading', [
    'default' => 'Power up your website <br> with <span>our experts</span>',
  ]);
  $wp_customize->add_control('footer_heading', [
    'label'   => __('Footer Heading', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'textarea',
  ]);

  // Footer Email Title
  $wp_customize->add_setting('footer_email_title', [
    'default' => 'Got Questions?',
  ]);
  $wp_customize->add_control('footer_email_title', [
    'label'   => __('Email Title', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'text',
  ]);

  // Footer Email
  $wp_customize->add_setting('footer_email', [
    'default' => 'info@redspider.ae',
  ]);
  $wp_customize->add_control('footer_email', [
    'label'   => __('Email Address', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'text',
  ]);

  // Footer Phone Title
  $wp_customize->add_setting('footer_phone_title', [
    'default' => 'Quick Answer?',
  ]);
  $wp_customize->add_control('footer_phone_title', [
    'label'   => __('Phone Title', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'text',
  ]);

  // Footer Phone
  $wp_customize->add_setting('footer_phone', [
    'default' => '+971 55 5515475',
  ]);
  $wp_customize->add_control('footer_phone', [
    'label'   => __('Phone Number', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'text',
  ]);

  // Social Links
  $social_platforms = ['facebook', 'linkedin', 'instagram', 'youtube'];
  foreach ($social_platforms as $platform) {
    $wp_customize->add_setting('social_' . $platform, [
      'default' => '#',
    ]);
    $wp_customize->add_control('social_' . $platform, [
      'label'   => __(ucfirst($platform) . ' Link', 'redspider'),
      'section' => 'redspider_footer',
      'type'    => 'url',
    ]);
  }

  // Footer Logo Image Control
  $wp_customize->add_setting('footer_logo_image', [
    'default' => '',
  ]);
  $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, 'footer_logo_image', [
    'label'    => __('Footer Logo/Image', 'redspider'),
    'section'  => 'redspider_footer',
    'settings' => 'footer_logo_image',
  ]));

  // Footer Copyright Text
  $wp_customize->add_setting('footer_copyright', [
    'default' => '© Copyright 2026, RedSpider. All Rights Reserved.',
  ]);
  $wp_customize->add_control('footer_copyright', [
    'label'   => __('Copyright Text', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'text',
  ]);

  // Footer FAQ URL
  $wp_customize->add_setting('footer_faq_url', [
    'default' => '#',
  ]);
  $wp_customize->add_control('footer_faq_url', [
    'label'   => __('FAQ URL', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'url',
  ]);

  // Footer Blog URL
  $wp_customize->add_setting('footer_blog_url', [
    'default' => '#',
  ]);
  $wp_customize->add_control('footer_blog_url', [
    'label'   => __('Blog URL', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'url',
  ]);

  // Footer Get In Touch URL
  $wp_customize->add_setting('footer_get_in_touch_url', [
    'default' => home_url('/contactus/'),
  ]);
  $wp_customize->add_control('footer_get_in_touch_url', [
    'label'   => __('Get In Touch URL', 'redspider'),
    'section' => 'redspider_footer',
    'type'    => 'url',
  ]);
}

add_action('customize_register', 'redspider_customize_register');

// Require Demo Importer
require_once get_template_directory() . '/inc/demo-importer.php';

// Dynamic content filter to replace {{theme_uri}} placeholder
function redspider_filter_content_theme_uri($content)
{
  return str_replace('{{theme_uri}}', get_template_directory_uri(), $content);
}
add_filter('the_content', 'redspider_filter_content_theme_uri');

// Disable wpautop for pages to prevent broken layouts
function redspider_remove_autop_for_pages()
{
  if (is_page()) {
    remove_filter('the_content', 'wpautop');
  }
}
add_action('loop_start', 'redspider_remove_autop_for_pages');

// AJAX Contact Form Submission Handler
function redspider_contact_submit_handler()
{
  check_ajax_referer('redspider_contact_nonce', 'security');

  $full_name = sanitize_text_field($_POST['full_name']);
  $email     = sanitize_email($_POST['email']);
  $phone     = sanitize_text_field($_POST['phone']);
  $country   = sanitize_text_field($_POST['country']);
  $service   = sanitize_text_field($_POST['service']);
  $message   = sanitize_textarea_field($_POST['message']);

  if (empty($full_name) || empty($email)) {
    wp_send_json_error(['message' => __('Please fill out all required fields.', 'redspider')]);
  }

  if (!is_email($email)) {
    wp_send_json_error(['message' => __('Please enter a valid email address.', 'redspider')]);
  }

  $admin_email = get_option('admin_email');
  $subject = __('New Contact Form Submission from ' . $full_name, 'redspider');
  
  $body = "<h2>New Contact Form Submission</h2>";
  $body .= "<p><strong>Name:</strong> " . esc_html($full_name) . "</p>";
  $body .= "<p><strong>Email:</strong> " . esc_html($email) . "</p>";
  $body .= "<p><strong>Phone:</strong> " . esc_html($phone) . "</p>";
  $body .= "<p><strong>Country:</strong> " . esc_html($country) . "</p>";
  $body .= "<p><strong>Service Requested:</strong> " . esc_html($service) . "</p>";
  $body .= "<p><strong>Message:</strong><br>" . nl2br(esc_html($message)) . "</p>";

  $headers = ['Content-Type: text/html; charset=UTF-8'];

  $sent = wp_mail($admin_email, $subject, $body, $headers);

  if ($sent) {
    wp_send_json_success(['message' => __('Your message has been sent successfully!', 'redspider')]);
  } else {
    wp_send_json_error(['message' => __('Failed to send email. Please try again later.', 'redspider')]);
  }
}
add_action('wp_ajax_redspider_contact_submit', 'redspider_contact_submit_handler');
add_action('wp_ajax_nopriv_redspider_contact_submit', 'redspider_contact_submit_handler');

// Theme activation handler: run demo importer automatically on activation
function redspider_theme_activation_redirect()
{
  // Only run if not already imported to prevent reset/loop
  if (get_option('redspider_demo_imported') !== 'yes') {
    redspider_run_demo_import();
  }
  wp_safe_redirect(admin_url('themes.php?page=redspider-demo-importer&activated=1'));
  exit;
}
add_action('after_switch_theme', 'redspider_theme_activation_redirect');

// Custom walker for footer menu to append 01, 02, etc.
class RedSpider_Footer_Walker extends Walker_Nav_Menu
{
  public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
  {
    $number = sprintf('%02d', $item->menu_order);
    $output .= '<li data-aos="fade-up" data-aos-delay="' . ($item->menu_order * 100) . '" data-aos-duration="600" data-aos-once="true">';
    $output .= '<a href="' . esc_url($item->url) . '">';
    $output .= '<span>' . $number . '</span> ' . esc_html($item->title);
    $output .= '</a>';
  }
}

// Custom walker for footer social menu to dynamically output matching SVG icons
class RedSpider_Social_Walker extends Walker_Nav_Menu
{
  public function start_el(&$output, $item, $depth = 0, $args = null, $id = 0)
  {
    $url = esc_url($item->url);
    $theme_uri = get_template_directory_uri();
    $icon = '';

    if (strpos($url, 'facebook.com') !== false) {
      $icon = 'social/fb.svg';
    } elseif (strpos($url, 'linkedin.com') !== false) {
      $icon = 'social/linke.svg';
    } elseif (strpos($url, 'instagram.com') !== false) {
      $icon = 'social/insta.svg';
    } elseif (strpos($url, 'youtube.com') !== false) {
      $icon = 'social/youtube.svg';
    } else {
      // Default icon fallback
      $icon = 'social/fb.svg';
    }

    $output .= '<li><a href="' . $url . '" target="_blank">';
    $output .= '<img src="' . esc_url($theme_uri) . '/assets/img/' . $icon . '" alt="' . esc_attr($item->title) . '">';
    $output .= '</a>';
  }
}