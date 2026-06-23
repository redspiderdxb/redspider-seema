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

    <div class="container-fluid container-xl position-relative d-flex align-items-center" style="max-width:1800px;">

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

            <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>

        </nav>

        <!-- Right Buttons -->
        <div class="inlinebtns text-center d-flex gap-3 align-items-center justify-content-center ms-3">

            <!-- WhatsApp -->
            <?php
    $whatsapp_number = get_theme_mod(
        'whatsapp_number',
        '971505698733'
    );
    ?>

            <a href="https://wa.me/<?php echo esc_attr($whatsapp_number); ?>" target="_blank"
                class="d-none btn btn-animation btn-green-transparent d-inline-flex align-items-center gap-3"
                style="padding:10px 20px;">

                <span class="btn-icon-wrap">

                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/icons/whatsapp.svg"
                        alt="WhatsApp">

                </span>

            </a>

            <!-- Consultation Button -->
            <?php
    $consultation_url = get_theme_mod(
        'consultation_url',
        '#'
    );
    ?>

            <a href="<?php echo esc_url($consultation_url); ?>"
                class="btn btn-animation btn-red d-inline-flex align-items-center gap-3 d-none d-md-flex"
                style="padding:10px 20px;">

                <span class="btn-title">
                    Schedule Free Consultation
                </span>

                <span class="btn-icon-wrap">

                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/assets/img/icons/cc-icon.svg"
                        alt="Consultation">

                </span>

            </a>

        </div>

    </div>

    </header>