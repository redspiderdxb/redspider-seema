<?php
/**
 * Footer Template
 *
 * @package RedSpider
 */

$theme_uri = get_template_directory_uri();
$whatsapp_number = get_theme_mod('whatsapp_number', '971505698733');

// Fetch Customizer Footer options with defaults matching original HTML
$footer_heading   = get_theme_mod('footer_heading', 'Power up your website <br> with <span>our experts</span>');
$email_title      = get_theme_mod('footer_email_title', 'Got Questions?');
$footer_email     = get_theme_mod('footer_email', 'info@redspider.ae');
$phone_title      = get_theme_mod('footer_phone_title', 'Quick Answer?');
$footer_phone     = get_theme_mod('footer_phone', '+971 55 5515475');

$fb_link  = get_theme_mod('social_facebook', '#');
$li_link  = get_theme_mod('social_linkedin', '#');
$ig_link  = get_theme_mod('social_instagram', '#');
$yt_link  = get_theme_mod('social_youtube', '#');

$footer_logo = get_theme_mod('footer_logo_image');
if (empty($footer_logo)) {
  $footer_logo = $theme_uri . '/assets/img/swim.png';
}

$footer_faq_url          = get_theme_mod('footer_faq_url', '#');
$footer_blog_url         = get_theme_mod('footer_blog_url', '#');
$footer_get_in_touch_url = get_theme_mod('footer_get_in_touch_url', home_url('/contactus/'));
$footer_copyright        = get_theme_mod('footer_copyright', '© Copyright 2026, RedSpider. All Rights Reserved.');
// Dynamically match and replace years
$footer_copyright        = str_replace(['2026', '[year]'], date('Y'), $footer_copyright);
?>

<footer class="rs-footer-sec py-5 dark-background">
  <div class="container" style="max-width: 1600px;">

    <div class="row align-items-start gy-5"> 

      <!-- Left Content -->
      <div class="col-lg-5 position-relative"
           data-aos="fade-right"
           data-aos-duration="900"
           data-aos-once="true">
        <h2 class="rs-heading">
          <?php echo wp_kses_post($footer_heading); ?>
        </h2>
        <img src="<?php echo esc_url($footer_logo); ?>" alt="Swim Foot" class="swim-foot">
      </div>

      <!-- Middle Links -->
      <div class="col-lg-4">
        <?php
        wp_nav_menu([
          'theme_location' => 'footer_services',
          'container'      => false,
          'menu_class'     => 'rs-services',
          'fallback_cb'    => false,
        ]);
        ?>
      </div>

      <!-- Right Contact -->
      <div class="col-lg-3">
        <!-- Email Box -->
        <div class="rs-contact-card red"
             data-aos="fade-left"
             data-aos-delay="100"
             data-aos-duration="800"
             data-aos-once="true">
          <small><?php echo esc_html($email_title); ?></small>
          <h5><a href="mailto:<?php echo esc_attr($footer_email); ?>" style="color: inherit; text-decoration: none;"><?php echo esc_html($footer_email); ?></a></h5>
          <span class="rs-icon"><img src="<?php echo esc_url($theme_uri); ?>/assets/img/icons/email.svg" alt="Email"></span>
        </div>

        <!-- Phone Box -->
        <div class="rs-contact-card dark mt-3"
             data-aos="fade-left"
             data-aos-delay="250"
             data-aos-duration="800"
             data-aos-once="true">
          <small><?php echo esc_html($phone_title); ?></small>
          <h5><a href="tel:<?php echo esc_attr(str_replace(' ', '', $footer_phone)); ?>" style="color: inherit; text-decoration: none;"><?php echo esc_html($footer_phone); ?></a></h5>
          <span class="rs-icon"><img src="<?php echo esc_url($theme_uri); ?>/assets/img/icons/ph-foot.svg" alt="Phone"></span>
        </div>
      </div>

    </div>

    <!-- Bottom -->
    <div class="row rs-footer-bottom align-items-center mt-5 pt-4 border-0">
      <div class="col-md-3">
        <?php
        wp_nav_menu([
          'theme_location' => 'footer_menu',
          'container'      => false,
          'menu_class'     => 'rs-menu',
          'fallback_cb'    => false,
          'walker'         => new RedSpider_Footer_Walker(),
        ]);
        ?>
      </div>  

      <div class="col-md-9">
        <div class="row align-items-center gy-3">
          <div class="col-md-4 text-md-start text-center"
               data-aos="fade-up"
               data-aos-delay="100"
               data-aos-duration="700"
               data-aos-once="true">
            <p class="mb-0"><?php echo esc_html($footer_copyright); ?></p>
          </div>

          <div class="col-md-4 text-center"
               data-aos="zoom-in"
               data-aos-delay="200"
               data-aos-duration="700"
               data-aos-once="true">
            <div class="rs-social">
              <?php
              wp_nav_menu([
                'theme_location' => 'footer_social',
                'container'      => false,
                'menu_class'     => 'rs-social-list',
                'fallback_cb'    => false,
                'walker'         => new RedSpider_Social_Walker(),
              ]);
              ?>
            </div>
          </div>

          <div class="col-md-4 text-md-end text-center"
               data-aos="fade-left"
               data-aos-delay="300"
               data-aos-duration="700"
               data-aos-once="true">
            <div class="rs-social">
              <?php
              wp_nav_menu([
                'theme_location' => 'footer_bottom_links',
                'container'      => false,
                'menu_class'     => 'rs-bottom-links-list',
                'fallback_cb'    => false,
              ]);
              ?>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>

  <a href="https://wa.me/<?php echo esc_attr(str_replace(' ', '', $whatsapp_number)); ?>"
     target="_blank"
     class="floating-whatsapp"
     aria-label="WhatsApp">
    <img src="<?php echo esc_url($theme_uri); ?>/assets/img/icons/whatsapp.svg" alt="WhatsApp">
  </a>
</footer>

<!-- Scroll Top -->
<a href="#" id="scroll-top" class="scroll-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

<!-- Preloader -->
<div id="preloader" class="d-none"></div>

<?php wp_footer(); ?>
</body>
</html>
