<?php
/**
 * Template Name: RedSpider Email Marketing
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
