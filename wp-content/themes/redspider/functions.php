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
    filemtime(get_template_directory() . '/assets/css/custom.css')
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
    filemtime(get_template_directory() . '/assets/js/custome.js'),
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

  // Google Places API Section
  $wp_customize->add_section(
    'redspider_google',
    [
      'title'    => __('Google Places Settings', 'redspider'),
      'priority' => 32,
    ]
  );

  // Google Places API Key
  $wp_customize->add_setting('google_places_api_key', [
    'default' => '',
  ]);
  $wp_customize->add_control('google_places_api_key', [
    'label'   => __('Google Places API Key', 'redspider'),
    'section' => 'redspider_google',
    'type'    => 'text',
  ]);

  // Google Place ID
  $wp_customize->add_setting('google_place_id', [
    'default' => '',
  ]);
  $wp_customize->add_control('google_place_id', [
    'label'   => __('Google Place ID', 'redspider'),
    'section' => 'redspider_google',
    'type'    => 'text',
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

/**
 * Fetch Google Reviews with Caching and Mock Fallback
 */
function redspider_get_google_reviews()
{
  $cached_reviews = get_transient('redspider_google_reviews_data');
  if (false !== $cached_reviews) {
    return $cached_reviews;
  }

  $api_key  = get_theme_mod('google_places_api_key');
  $place_id = get_theme_mod('google_place_id');
  $reviews_data = null;

  if (!empty($api_key) && !empty($place_id)) {
    $url = "https://maps.googleapis.com/maps/api/place/details/json?place_id=" . urlencode($place_id) . "&fields=reviews,user_ratings_total,rating&key=" . urlencode($api_key);
    $response = wp_remote_get($url);

    if (!is_wp_error($response)) {
      $body = wp_remote_retrieve_body($response);
      $json = json_decode($body, true);

      if (isset($json['result'])) {
        $result = $json['result'];
        $rating = isset($result['rating']) ? floatval($result['rating']) : 4.9;
        $total  = isset($result['user_ratings_total']) ? intval($result['user_ratings_total']) : 106;
        $raw_reviews = isset($result['reviews']) ? $result['reviews'] : [];

        $reviews = [];
        foreach ($raw_reviews as $rev) {
          $reviews[] = [
            'author_name'               => isset($rev['author_name']) ? $rev['author_name'] : '',
            'profile_photo_url'         => isset($rev['profile_photo_url']) ? $rev['profile_photo_url'] : '',
            'rating'                    => isset($rev['rating']) ? intval($rev['rating']) : 5,
            'relative_time_description' => isset($rev['relative_time_description']) ? $rev['relative_time_description'] : '',
            'text'                      => isset($rev['text']) ? $rev['text'] : '',
          ];
        }

        $reviews_data = [
          'rating'             => $rating,
          'user_ratings_total' => $total,
          'reviews'            => $reviews,
        ];
      }
    }
  }

  // Fallback to Mock Reviews if Places API is not set or failed
  if (empty($reviews_data) || empty($reviews_data['reviews'])) {
    $reviews_data = [
      'rating'             => 4.9,
      'user_ratings_total' => 106,
      'reviews'            => [
        [
          'author_name'               => 'Robert Stephan',
          'profile_photo_url'         => '',
          'rating'                    => 5,
          'relative_time_description' => '1 month ago',
          'text'                      => 'We had a great experience working with RedSpider Web & Art Design. They designed our website exactly to our requirements and were very prompt in implementing modifications. Excellent job!',
        ],
        [
          'author_name'               => 'karima alkaisi',
          'profile_photo_url'         => '',
          'rating'                    => 5,
          'relative_time_description' => '1 month ago',
          'text'                      => 'Amazing project and support from Ahmad,I definitely recommend!',
        ],
        [
          'author_name'               => 'Muhammad Asad',
          'profile_photo_url'         => '',
          'rating'                    => 5,
          'relative_time_description' => '2 months ago',
          'text'                      => 'Ahmad is a great person there and he can solve anyproblem in few mins. appriciated.',
        ],
        [
          'author_name'               => 'Sara Al Mheiri',
          'profile_photo_url'         => '',
          'rating'                    => 5,
          'relative_time_description' => '3 weeks ago',
          'text'                      => 'Highly professional team of developers and designers. They delivered our real estate portal ahead of schedule. Very recommended!',
        ],
        [
          'author_name'               => 'John Doe',
          'profile_photo_url'         => '',
          'rating'                    => 5,
          'relative_time_description' => '3 months ago',
          'text'                      => 'RedSpider provided excellent customer support and did an amazing job with our branding and brochure design. Thank you guys!',
        ]
      ]
    ];
  }

  set_transient('redspider_google_reviews_data', $reviews_data, DAY_IN_SECONDS);
  return $reviews_data;
}

/**
 * Generate Google Reviews dynamic HTML
 */
function redspider_get_google_reviews_html()
{
  $data = redspider_get_google_reviews();
  $rating = isset($data['rating']) ? $data['rating'] : 4.9;
  $total_reviews = isset($data['user_ratings_total']) ? $data['user_ratings_total'] : 106;
  $reviews = isset($data['reviews']) ? $data['reviews'] : [];

  $html = '<div class="rs-reviews-container">';
  $html .= '<div class="row align-items-center gy-4">';
  
  // Left Summary column
  $html .= '<div class="col-lg-3 text-center mb-2 mb-lg-0">';
  $html .= '  <div class="rs-google-summary" data-aos="fade-right" data-aos-duration="800">';
  $html .= '    <h4 class="rs-summary-status">EXCELLENT</h4>';
  $html .= '    <div class="rs-summary-stars">';
  // Output stars
  $full_stars = floor($rating);
  for ($i = 0; $i < 5; $i++) {
    if ($i < $full_stars) {
      $html .= '<span class="star-filled"><i class="bi bi-star-fill text-warning"></i></span>';
    } else {
      $html .= '<span class="star-empty"><i class="bi bi-star text-muted"></i></span>';
    }
  }
  $html .= '    </div>';
  $html .= '    <p class="rs-summary-based">Based on <strong>' . esc_html($total_reviews) . ' reviews</strong></p>';
  $html .= '    <div class="rs-google-logo">';
  $html .= '      <span class="g-blue">G</span><span class="o-red">o</span><span class="o-yellow">o</span><span class="g-blue">g</span><span class="l-green">l</span><span class="e-red">e</span>';
  $html .= '    </div>';
  $html .= '  </div>';
  $html .= '</div>';
  
  // Right Slider column
  $html .= '<div class="col-lg-9 position-relative" data-aos="fade-left" data-aos-duration="800">';
  $html .= '  <div class="swiper rs-reviews-swiper">';
  $html .= '    <div class="swiper-wrapper">';
  
  foreach ($reviews as $idx => $rev) {
    $author = esc_html($rev['author_name']);
    $initial = strtoupper(substr($author, 0, 1));
    $photo = !empty($rev['profile_photo_url']) ? esc_url($rev['profile_photo_url']) : '';
    $stars_count = isset($rev['rating']) ? intval($rev['rating']) : 5;
    $time = esc_html($rev['relative_time_description']);
    $text = esc_html($rev['text']);
    
    // Choose colored avatar class based on name hash
    $avatar_colors = ['avatar-red', 'avatar-blue', 'avatar-green', 'avatar-yellow', 'avatar-purple', 'avatar-teal'];
    $color_class = $avatar_colors[ord($initial) % count($avatar_colors)];
    
    $html .= '      <div class="swiper-slide">';
    $html .= '        <div class="rs-review-card">';
    
    // Card Header (Avatar, Name, Time, G icon)
    $html .= '          <div class="rs-card-header d-flex align-items-center justify-content-between mb-3">';
    $html .= '            <div class="d-flex align-items-center gap-3">';
    if ($photo) {
      $html .= '              <img class="rs-avatar img-fluid" src="' . $photo . '" alt="' . $author . '">';
    } else {
      $html .= '              <div class="rs-avatar-initial ' . $color_class . '">' . $initial . '</div>';
    }
    $html .= '              <div>';
    $html .= '                <h6 class="rs-reviewer-name m-0">' . $author . '</h6>';
    $html .= '                <small class="rs-review-time text-muted">' . $time . '</small>';
    $html .= '              </div>';
    $html .= '            </div>';
    $html .= '            <div class="rs-card-g-logo">';
    $html .= '              <svg viewBox="0 0 24 24" width="20" height="20" xmlns="http://www.w3.org/2000/svg"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/></svg>';
    $html .= '            </div>';
    $html .= '          </div>';
    
    // Rating Stars + blue check badge
    $html .= '          <div class="rs-card-rating d-flex align-items-center gap-2 mb-3">';
    $html .= '            <div class="rs-card-stars">';
    for ($i = 0; $i < 5; $i++) {
      if ($i < $stars_count) {
        $html .= '<i class="bi bi-star-fill text-warning"></i>';
      } else {
        $html .= '<i class="bi bi-star text-muted"></i>';
      }
    }
    $html .= '            </div>';
    $html .= '            <span class="rs-verified-badge" title="Verified Review">';
    $html .= '              <svg viewBox="0 0 24 24" width="16" height="16" fill="#1877f2" xmlns="http://www.w3.org/2000/svg"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>';
    $html .= '            </span>';
    $html .= '          </div>';
    
    // Description text
    $html .= '          <p class="rs-review-text mb-2">' . $text . '</p>';
    $html .= '          <a href="https://search.google.com/local/reviews?placeid=' . esc_attr(get_theme_mod('google_place_id')) . '" target="_blank" class="rs-read-more">Read more</a>';
    
    $html .= '        </div>';
    $html .= '      </div>';
  }
  
  $html .= '    </div>';
  $html .= '  </div>';
  
  // Navigation Arrows
  $html .= '  <div class="swiper-button-prev rs-swiper-prev"></div>';
  $html .= '  <div class="swiper-button-next rs-swiper-next"></div>';
  
  $html .= '</div>';
  $html .= '</div>'; // row
  $html .= '</div>'; // rs-reviews-container
  
  return $html;
}

// Dynamic content filter to replace static reviews layout
function redspider_replace_static_reviews($content)
{
  if (strpos($content, 'review-wrap') !== false) {
    $dynamic_html = redspider_get_google_reviews_html();
    $content = preg_replace(
      '/<div class="review-wrap">.*?<\/div>/is',
      '<div class="review-wrap">' . $dynamic_html . '</div>',
      $content
    );
  }
  return $content;
}
add_filter('the_content', 'redspider_replace_static_reviews', 25);