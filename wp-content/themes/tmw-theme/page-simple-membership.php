<?php
/**
 * Template Name: Simple Membership Page
 * Simplified template for Simple Membership plugin pages (no sidebar)
 *
 * @package tmw-theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main-content" class="site-main tmw-membership-page">
    <div class="tmw-container">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class('tmw-page-simple'); ?>>
                
                <?php if (get_the_title() && !is_front_page()) : ?>
                    <header class="entry-header">
                        <h1 class="entry-title"><?php the_title(); ?></h1>
                    </header>
                <?php endif; ?>

                <div class="entry-content">
                    <?php
                    // This will output Simple Membership shortcodes and content
                    the_content();
                    ?>
                </div>

            </article>
        <?php
        endwhile;
        ?>
    </div>
</main>

<?php
get_footer();
