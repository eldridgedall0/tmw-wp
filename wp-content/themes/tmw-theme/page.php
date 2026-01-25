<?php
/**
 * Template Name: Default Page
 * Template for all standard pages including Simple Membership pages
 *
 * @package tmw-theme
 */

if (!defined('ABSPATH')) {
    exit;
}

get_header();
?>

<main id="main-content" class="site-main tmw-page-content">
    <?php
    while (have_posts()) :
        the_post();
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('tmw-page'); ?>>
            
            <?php if (get_the_title()) : ?>
                <header class="entry-header">
                    <h1 class="entry-title"><?php the_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="entry-content">
                <?php
                // Output the page content (including Simple Membership shortcodes)
                the_content();

                // Page pagination for long pages
                wp_link_pages(array(
                    'before' => '<div class="page-links">' . esc_html__('Pages:', 'flavor-starter-flavor'),
                    'after'  => '</div>',
                ));
                ?>
            </div>

            <?php
            // If comments are open or there's at least one comment, load the comment template
            if (comments_open() || get_comments_number()) :
                comments_template();
            endif;
            ?>

        </article>
    <?php
    endwhile;
    ?>
</main>

<?php
get_sidebar(); // Optional - remove if you don't want a sidebar
get_footer();
