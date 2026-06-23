<?php
/**
 * RedSpider Demo Importer
 *
 * @package RedSpider
 */

if (!defined('ABSPATH')) {
  exit;
}

// Helper to parse HTML source file and extract the content of <main class="main">
function redspider_parse_html_page_content($filepath)
{
  if (!file_exists($filepath)) {
    return '';
  }

  $html = file_get_contents($filepath);

  // Find content between <main class="main"> and </main>
  $start_pos = strpos($html, '<main class="main">');
  if ($start_pos === false) {
    $start_pos = strpos($html, '<main class="main ');
  }
  if ($start_pos === false) {
    $start_pos = strpos($html, '<main ');
  }

  if ($start_pos === false) {
    return '';
  }

  // Move start position to the end of the opening main tag
  $end_of_opening_tag = strpos($html, '>', $start_pos);
  if ($end_of_opening_tag === false) {
    return '';
  }
  $start_pos = $end_of_opening_tag + 1;

  $end_pos = strrpos($html, '</main>');
  if ($end_pos === false) {
    return '';
  }

  $main_content = substr($html, $start_pos, $end_pos - $start_pos);

  // Replacements
  // 1. Replace assets paths with placeholder {{theme_uri}}
  $main_content = preg_replace_callback(
    '/(src|href|poster)=["\']assets\/([^"\']+)["\']/i',
    function ($matches) {
      $attr = $matches[1];
      $val = $matches[2];
      // Skip HTML links
      if (preg_match('/\.html$|\.html#/', $val)) {
        return $matches[0];
      }
      return $attr . '="{{theme_uri}}/assets/' . $val . '"';
    },
    $main_content
  );

  // 2. Replace url('assets/...') or url('../img/...') in inline styles
  $main_content = preg_replace(
    '/url\(["\']?(?:\.\.\/)?(assets\/img\/|img\/)([^"\'\)]+)["\']?\)/i',
    'url(\'{{theme_uri}}/assets/img/$2\')',
    $main_content
  );

  // 3. Replace index.html and anchor index.html#portfolio
  $main_content = preg_replace('/href=["\']index\.html(["\'])/i', 'href="' . esc_url(home_url('/')) . '$1', $main_content);
  $main_content = preg_replace('/href=["\']index\.html#([^"\']+)["\']/i', 'href="' . esc_url(home_url('/#$1')) . '"', $main_content);

  // 4. Replace custom pages contactus.html and aboutus.html
  $main_content = preg_replace('/href=["\']contactus\.html(["\'])/i', 'href="' . esc_url(home_url('/contactus/')) . '$1', $main_content);
  $main_content = preg_replace('/href=["\']aboutus\.html(["\'])/i', 'href="' . esc_url(home_url('/aboutus/')) . '$1', $main_content);

  // 5. Replace general html files slug.html to WordPress url
  $main_content = preg_replace('/href=["\']([a-zA-Z0-9\-]+)\.html(["\'])/i', 'href="' . esc_url(home_url('/$1/')) . '$2', $main_content);
  $main_content = preg_replace('/href=["\']([a-zA-Z0-9\-]+)\.html#([^"\']+)["\']/i', 'href="' . esc_url(home_url('/$1/#$2')) . '"', $main_content);

  // 6. Format Contact Form in contactus.html
  if (basename($filepath) === 'contactus.html') {
    $main_content = preg_replace('/<form[^>]*>.*?<\/form>/is', '
  <form id="redspider-contact-form" method="post" action="">
    <div class="row g-5">
      <div class="col-md-6">
        <select class="form-select" name="country" required>
          <option selected disabled>Select Country</option>
          <option value="UAE">UAE</option>
          <option value="USA">USA</option>
          <option value="UK">UK</option>
        </select>
      </div>
      <div class="col-md-6">
        <select class="form-select" name="service" required>
          <option selected disabled>Select Service</option>
          <option value="Consultation">Consultation</option>
          <option value="Support">Support</option>
        </select>
      </div>
      <div class="col-md-6">
        <input type="text" class="form-control" name="full_name" placeholder="Your Full Name*" required>
      </div>
      <div class="col-md-6">
        <input type="tel" class="form-control" name="phone" placeholder="Phone No">
      </div>
      <div class="col-md-12">
        <input type="email" class="form-control" name="email" placeholder="Email" required>
      </div>
      <div class="col-md-12">
         <textarea class="form-control" name="message" placeholder="Leave a comment here" id="floatingTextarea"></textarea>          
      </div>
      <div class="col-12 text-center my-5">
        <button type="submit" class="btn btn-light px-5">MAKE APPOINTMENT</button>
        <div class="form-status mt-3"></div>
      </div>
    </div>
  </form>
    ', $main_content);
  }

  return trim($main_content);
}

// Add submenu under Appearance
function redspider_demo_importer_menu()
{
  add_theme_page(
    __('RedSpider Demo Importer', 'redspider'),
    __('Demo Importer', 'redspider'),
    'manage_options',
    'redspider-demo-importer',
    'redspider_demo_importer_page'
  );
}
add_action('admin_menu', 'redspider_demo_importer_menu');

// Standalone core import handler that runs automatically or via admin dashboard
function redspider_run_demo_import()
{
  // 1. Define Pages and Templates
  $pages_to_create = [
    'home' => [
      'title'    => 'Home',
      'template' => 'default',
    ],
    'aboutus' => [
      'title'    => 'About Us',
      'template' => 'page-templates/template-aboutus.php',
    ],
    'brochure-design' => [
      'title'    => 'Brochure Designing',
      'template' => 'page-templates/template-brochure-design.php',
    ],
    'classified-directory' => [
      'title'    => 'Dubizzle Clone',
      'template' => 'page-templates/template-classified-directory.php',
    ],
    'contactus' => [
      'title'    => 'Contact Us',
      'template' => 'page-templates/template-contactus.php',
    ],
    'daily-deal-website' => [
      'title'    => 'Daily Deal Website',
      'template' => 'page-templates/template-daily-deal-website.php',
    ],
    'design-and-developemnt' => [
      'title'    => 'Web Development',
      'template' => 'page-templates/template-design-and-developemnt.php',
    ],
    'ecommerce-developemnt' => [
      'title'    => 'E-commerce Development',
      'template' => 'page-templates/template-ecommerce-developemnt.php',
    ],
    'email-marketing' => [
      'title'    => 'Email Marketing',
      'template' => 'page-templates/template-email-marketing.php',
    ],
    'graphic-design' => [
      'title'    => 'Graphic Designing',
      'template' => 'page-templates/template-graphic-design.php',
    ],
    'logo-designing' => [
      'title'    => 'Logo Designing',
      'template' => 'page-templates/template-logo-designing.php',
    ],
    'mobile-app-developemnt' => [
      'title'    => 'Mobile App Development',
      'template' => 'page-templates/template-mobile-app-developemnt.php',
    ],
    'real-estate-portal' => [
      'title'    => 'Real Estate Portal',
      'template' => 'page-templates/template-real-estate-portal.php',
    ],
    'sms-marketing-uae' => [
      'title'    => 'SMS Marketing UAE',
      'template' => 'page-templates/template-sms-marketing-uae.php',
    ],
    'web-hositng' => [
      'title'    => 'Web Hosting',
      'template' => 'page-templates/template-web-hositng.php',
    ],
  ];

  $created_page_ids = [];

  foreach ($pages_to_create as $slug => $page_data) {
    $existing_page = get_page_by_path($slug, OBJECT, 'page');
    
    $html_file = ($slug === 'home') ? 'index.html' : $slug . '.html';
    $html_path = ABSPATH . 'redspider-HTML/' . $html_file;
    $parsed_content = redspider_parse_html_page_content($html_path);

    if (!$existing_page) {
      $page_id = wp_insert_post([
        'post_title'   => $page_data['title'],
        'post_name'    => $slug,
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_content' => $parsed_content,
      ]);
    } else {
      $page_id = $existing_page->ID;
      wp_update_post([
        'ID'           => $page_id,
        'post_title'   => $page_data['title'],
        'post_status'  => 'publish',
        'post_content' => $parsed_content,
      ]);
    }

    if ($page_id && !is_wp_error($page_id)) {
      $created_page_ids[$slug] = $page_id;
      update_post_meta($page_id, '_wp_page_template', $page_data['template']);
    }
  }

  // 2. Set static homepage
  if (isset($created_page_ids['home'])) {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $created_page_ids['home']);
  }

  // 3. Create Navigation Menu
  $menu_name = 'Primary Menu';
  $menu_exists = wp_get_nav_menu_object($menu_name);

  if (!$menu_exists) {
    $menu_id = wp_create_nav_menu($menu_name);
  } else {
    $menu_id = $menu_exists->term_id;
    $menu_items = wp_get_nav_menu_items($menu_id);
    if ($menu_items) {
      foreach ($menu_items as $item) {
        wp_delete_post($item->ID, true);
      }
    }
  }

  if ($menu_id && !is_wp_error($menu_id)) {
    // Home
    wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'     => 'Home',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['home'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);

    // About
    wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'     => 'About',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['aboutus'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);

    // Portfolio Custom Link
    wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'  => 'Portfolio',
      'menu-item-url'    => home_url('/#portfolio'),
      'menu-item-type'   => 'custom',
      'menu-item-status' => 'publish',
    ]);

    // Services
    $services_parent_id = wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'  => 'Services',
      'menu-item-url'    => '#',
      'menu-item-type'   => 'custom',
      'menu-item-status' => 'publish',
    ]);

    $services_children = [
      'design-and-developemnt' => 'Web Development',
      'brochure-design'        => 'Brochure Designing',
      'graphic-design'         => 'Graphic Designing',
      'ecommerce-developemnt'  => 'E-commerce Development',
      'email-marketing'        => 'Email Marketing',
      'web-hositng'            => 'Web Hosting',
      'logo-designing'         => 'Logo Designing',
      'mobile-app-developemnt' => 'Mobile App Development',
    ];

    foreach ($services_children as $slug => $title) {
      wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'     => $title,
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $created_page_ids[$slug] ?? 0,
        'menu-item-type'      => 'post_type',
        'menu-item-parent-id' => $services_parent_id,
        'menu-item-status'    => 'publish',
      ]);
    }

    // Products
    $products_parent_id = wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'  => 'Products',
      'menu-item-url'    => '#',
      'menu-item-type'   => 'custom',
      'menu-item-status' => 'publish',
    ]);

    $products_children = [
      'real-estate-portal'   => 'Real Estate Web Design Company',
      'sms-marketing-uae'    => 'SMS-Marketing UAE',
      'daily-deal-website'   => 'Daily Deal Website',
      'classified-directory' => 'Dubizzle Clone',
    ];

    foreach ($products_children as $slug => $title) {
      wp_update_nav_menu_item($menu_id, 0, [
        'menu-item-title'     => $title,
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $created_page_ids[$slug] ?? 0,
        'menu-item-type'      => 'post_type',
        'menu-item-parent-id' => $products_parent_id,
        'menu-item-status'    => 'publish',
      ]);
    }

    // Contact
    wp_update_nav_menu_item($menu_id, 0, [
      'menu-item-title'     => 'Contact',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['contactus'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);

    $locations = get_theme_mod('nav_menu_locations');
    $locations['primary_menu'] = $menu_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // 4. Create Footer Menu
  $footer_menu_name = 'Footer Menu';
  $footer_menu_exists = wp_get_nav_menu_object($footer_menu_name);

  if (!$footer_menu_exists) {
    $footer_menu_id = wp_create_nav_menu($footer_menu_name);
  } else {
    $footer_menu_id = $footer_menu_exists->term_id;
    $footer_items = wp_get_nav_menu_items($footer_menu_id);
    if ($footer_items) {
      foreach ($footer_items as $item) {
        wp_delete_post($item->ID, true);
      }
    }
  }

  if ($footer_menu_id && !is_wp_error($footer_menu_id)) {
    wp_update_nav_menu_item($footer_menu_id, 0, [
      'menu-item-title'     => 'Home',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['home'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);
    wp_update_nav_menu_item($footer_menu_id, 0, [
      'menu-item-title'     => 'About Us',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['aboutus'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);
    wp_update_nav_menu_item($footer_menu_id, 0, [
      'menu-item-title'  => 'Our Work',
      'menu-item-url'    => home_url('/#portfolio'),
      'menu-item-type'   => 'custom',
      'menu-item-status' => 'publish',
    ]);
    wp_update_nav_menu_item($footer_menu_id, 0, [
      'menu-item-title'     => 'Contact',
      'menu-item-object'    => 'page',
      'menu-item-object-id' => $created_page_ids['contactus'] ?? 0,
      'menu-item-type'      => 'post_type',
      'menu-item-status'    => 'publish',
    ]);

    $locations = get_theme_mod('nav_menu_locations');
    $locations['footer_menu'] = $footer_menu_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // 5. Create Footer Services Menu
  $footer_services_name = 'Footer Services';
  $footer_services_exists = wp_get_nav_menu_object($footer_services_name);

  if (!$footer_services_exists) {
    $footer_services_id = wp_create_nav_menu($footer_services_name);
  } else {
    $footer_services_id = $footer_services_exists->term_id;
    $footer_services_items = wp_get_nav_menu_items($footer_services_id);
    if ($footer_services_items) {
      foreach ($footer_services_items as $item) {
        wp_delete_post($item->ID, true);
      }
    }
  }

  if ($footer_services_id && !is_wp_error($footer_services_id)) {
    $services_list = [
      'design-and-developemnt' => 'Web Development',
      'brochure-design'        => 'Brochure Designing',
      'graphic-design'         => 'Graphic Designing',
      'ecommerce-developemnt'  => 'E-commerce Development',
      'email-marketing'        => 'Email Marketing',
      'web-hositng'            => 'Web Hosting',
      'logo-designing'         => 'Logo Designing',
      'mobile-app-developemnt' => 'Mobile App Development',
    ];

    foreach ($services_list as $slug => $title) {
      wp_update_nav_menu_item($footer_services_id, 0, [
        'menu-item-title'     => $title,
        'menu-item-object'    => 'page',
        'menu-item-object-id' => $created_page_ids[$slug] ?? 0,
        'menu-item-type'      => 'post_type',
        'menu-item-status'    => 'publish',
      ]);
    }

    $locations = get_theme_mod('nav_menu_locations');
    $locations['footer_services'] = $footer_services_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // 6. Create Footer Social Menu
  $footer_social_name = 'Footer Social';
  $footer_social_exists = wp_get_nav_menu_object($footer_social_name);

  if (!$footer_social_exists) {
    $footer_social_id = wp_create_nav_menu($footer_social_name);
  } else {
    $footer_social_id = $footer_social_exists->term_id;
    $footer_social_items = wp_get_nav_menu_items($footer_social_id);
    if ($footer_social_items) {
      foreach ($footer_social_items as $item) {
        wp_delete_post($item->ID, true);
      }
    }
  }

  if ($footer_social_id && !is_wp_error($footer_social_id)) {
    $social_links = [
      'https://facebook.com/redspider'  => 'Facebook',
      'https://linkedin.com/company/redspider' => 'LinkedIn',
      'https://instagram.com/redspider' => 'Instagram',
      'https://youtube.com/redspider'   => 'YouTube',
    ];

    foreach ($social_links as $url => $title) {
      wp_update_nav_menu_item($footer_social_id, 0, [
        'menu-item-title'  => $title,
        'menu-item-url'    => $url,
        'menu-item-type'   => 'custom',
        'menu-item-status' => 'publish',
      ]);
    }

    $locations = get_theme_mod('nav_menu_locations');
    $locations['footer_social'] = $footer_social_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // 7. Create Footer Bottom Links Menu
  $footer_bottom_name = 'Footer Bottom Links';
  $footer_bottom_exists = wp_get_nav_menu_object($footer_bottom_name);

  if (!$footer_bottom_exists) {
    $footer_bottom_id = wp_create_nav_menu($footer_bottom_name);
  } else {
    $footer_bottom_id = $footer_bottom_exists->term_id;
    $footer_bottom_items = wp_get_nav_menu_items($footer_bottom_id);
    if ($footer_bottom_items) {
      foreach ($footer_bottom_items as $item) {
        wp_delete_post($item->ID, true);
      }
    }
  }

  if ($footer_bottom_id && !is_wp_error($footer_bottom_id)) {
    $bottom_links = [
      [
        'url'   => '#',
        'title' => 'FAQ\'s',
      ],
      [
        'url'   => '#',
        'title' => 'Blog',
      ],
      [
        'url'   => home_url('/contactus/'),
        'title' => 'Get In Touch',
      ],
    ];

    foreach ($bottom_links as $link) {
      wp_update_nav_menu_item($footer_bottom_id, 0, [
        'menu-item-title'  => $link['title'],
        'menu-item-url'    => $link['url'],
        'menu-item-type'   => 'custom',
        'menu-item-status' => 'publish',
      ]);
    }

    $locations = get_theme_mod('nav_menu_locations');
    $locations['footer_bottom_links'] = $footer_bottom_id;
    set_theme_mod('nav_menu_locations', $locations);
  }

  // Mark theme as demo-imported to prevent duplicate activations
  update_option('redspider_demo_imported', 'yes');
  
  // Set default customize settings
  set_theme_mod('whatsapp_number', '971505698733');
  set_theme_mod('consultation_url', '#');
  set_theme_mod('footer_heading', 'Power up your website <br> with <span>our experts</span>');
  set_theme_mod('footer_email_title', 'Got Questions?');
  set_theme_mod('footer_email', 'info@redspider.ae');
  set_theme_mod('footer_phone_title', 'Quick Answer?');
  set_theme_mod('footer_phone', '+971 55 5515475');
  set_theme_mod('footer_copyright', '© Copyright 2026, RedSpider. All Rights Reserved.');
  set_theme_mod('footer_faq_url', '#');
  set_theme_mod('footer_blog_url', '#');
  set_theme_mod('footer_get_in_touch_url', home_url('/contactus/'));
}

// Demo Importer Page HTML & Logic
function redspider_demo_importer_page()
{
  $message = '';

  if (isset($_POST['redspider_import_demo']) && check_admin_referer('redspider_import_demo_action', 'redspider_import_demo_nonce')) {
    redspider_run_demo_import();
    $message = __('Success! Demo pages, static front page settings, and navigation menus have been imported successfully.', 'redspider');
  }
  ?>

  <div class="wrap" style="margin-top: 30px; max-width: 800px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;">
    <div style="background: #111; color: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border-left: 6px solid #DE1515;">
      
      <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 25px;">
        <div style="background: #DE1515; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; color: #fff; box-shadow: 0 4px 15px rgba(222, 21, 21, 0.4);">
          RS
        </div>
        <div>
          <h1 style="color: #fff; font-size: 28px; font-weight: 700; margin: 0; line-height: 1.2;">RedSpider Demo Importer</h1>
          <p style="color: #aaa; margin: 5px 0 0 0; font-size: 14px;">Set up your WordPress theme to match the HTML design in seconds</p>
        </div>
      </div>

      <hr style="border: 0; border-top: 1px solid #333; margin: 25px 0;">

      <?php if (!empty($message)) : ?>
        <div style="background: rgba(46, 204, 113, 0.1); border: 1px solid #2ecc71; color: #2ecc71; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; font-size: 15px;">
          <strong>✓</strong> <?php echo esc_html($message); ?>
        </div>
      <?php endif; ?>

      <div style="background: #1e1e1e; padding: 25px; border-radius: 8px; margin-bottom: 30px; border: 1px solid #292929;">
        <h3 style="color: #fff; margin-top: 0; font-size: 18px; font-weight: 600;">What will be imported/configured?</h3>
        <ul style="margin: 15px 0 0 0; padding-left: 20px; color: #ccc; line-height: 1.8; font-size: 14px;">
          <li>Create all 15 template pages (Home, About Us, Contact Us, and 12 service/product pages).</li>
          <li>Assign template files dynamically to all page styles.</li>
          <li>Configure default WordPress front page settings to load the static Homepage.</li>
          <li>Create and populate multi-level "Primary Menu" and "Footer Menu" automatically.</li>
          <li>Set up dynamic Customizer social link controls and defaults.</li>
        </ul>
      </div>

      <form method="post" action="">
        <?php wp_nonce_field('redspider_import_demo_action', 'redspider_import_demo_nonce'); ?>
        <button type="submit" name="redspider_import_demo" class="button button-primary button-hero" style="background: #DE1515; border-color: #DE1515; color: #fff; font-weight: 600; padding: 12px 30px; height: auto; line-height: 1.5; border-radius: 6px; box-shadow: 0 4px 15px rgba(222, 21, 21, 0.3); transition: all 0.2s ease-in-out; cursor: pointer; font-size: 16px;">
          <?php _e('Import RedSpider Demo Content', 'redspider'); ?>
        </button>
      </form>
      
      <p style="color: #666; font-size: 12px; margin-top: 20px; margin-bottom: 0;">
        * This operation will reset/override menu configurations for the "Primary Menu" and "Footer Menu" if they already exist, to ensure proper layout hierarchy.
      </p>

    </div>
  </div>

  <style>
    .button-hero:hover {
      background: #c00f0f !important;
      border-color: #c00f0f !important;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(222, 21, 21, 0.4) !important;
    }
    .button-hero:active {
      transform: translateY(1px);
    }
  </style>

  <?php
}
