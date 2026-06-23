<?php
/**
 * Template Name: RedSpider Real Estate Portal
 *
 * @package RedSpider
 */

get_header(); ?>

<main class="main">
    <?php
    if (have_posts()) :
        while (have_posts()) :
            the_post();
            the_content();
        endwhile;
    endif;
    ?>
</main>

<?php get_footer(); ?>
