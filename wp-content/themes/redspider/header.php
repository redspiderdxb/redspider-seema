    <?php
    /**
     * Header Template
     *
     * @package RedSpider
     */
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>

    <head>

    <meta charset="<?php bloginfo('charset'); ?>">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php wp_head(); ?>

    </head>

    <body <?php body_class('index-page'); ?>>

    <?php wp_body_open(); ?>

    <header id="header" class="header d-flex align-items-center fixed-top">

    <div class="container-fluid position-relative d-flex align-items-center rs-header-inner">

        <!-- Logo -->
        <a href="<?php echo esc_url(home_url('/')); ?>" class="logo d-flex align-items-center me-auto">

            <?php if (has_custom_logo()) : ?>

            <?php the_custom_logo(); ?>

            <?php else : ?>

            <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/logo.png"
                alt="<?php echo esc_attr(get_bloginfo('name')); ?>">

            <?php endif; ?>

        </a>

        <!-- Navigation -->
        <nav id="navmenu" class="navmenu">

            <?php
    wp_nav_menu([
        'theme_location' => 'primary_menu',
        'container'      => false,
        'menu_class'     => '',
        'fallback_cb'    => false,
        'walker'         => new RedSpider_Navwalker(),
    ]);
    ?>

        </nav>

        <!-- Right Action Buttons -->
        <div class="rs-header-actions d-flex align-items-center gap-2">

            <?php
    $whatsapp_number   = get_theme_mod('whatsapp_number',  '971505698733');
    $consultation_url  = get_theme_mod('consultation_url', '#');
    $consultation_text = get_theme_mod('consultation_text', 'Schedule Free Consultation');
    ?>

            <!-- WhatsApp (hidden for now, enable by removing d-none) -->
            <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>" target="_blank"
                class="d-none btn btn-animation btn-green-transparent d-inline-flex align-items-center gap-2"
                aria-label="WhatsApp">
                <span class="btn-icon-wrap">
                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/icons/whatsapp.svg"
                        alt="WhatsApp" style="width:22px;">
                </span>
            </a>

            <!-- Consultation Button: visible on all devices, adjusted responsively -->
            <a href="<?php echo esc_url($consultation_url); ?>"
               class="btn btn-animation btn-red d-inline-flex align-items-center gap-2 rs-consult-btn"
               aria-label="<?php echo esc_attr($consultation_text); ?>">
                <span class="btn-title"><?php echo esc_html($consultation_text); ?></span>
                <span class="btn-icon-wrap d-flex align-items-center">
                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/icons/cc-icon.svg"
                        alt="Consultation" style="width:20px;">
                </span>
            </a>

        </div>

        <!-- Mobile / Tablet Hamburger Toggle -->
        <i class="mobile-nav-toggle bi bi-list" id="mobile-nav-toggle" aria-label="Toggle navigation"></i>

    </div>

    </header>