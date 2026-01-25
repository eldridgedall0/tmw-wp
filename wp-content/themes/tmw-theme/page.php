<?php
/**
 * Default Page Template
 *
 * @package tmw-theme
 */

get_header();
?>

<div class="tmw-page">
    <div class="tmw-page-content">
        
        <?php while (have_posts()) : the_post(); ?>
        
        <header class="tmw-page-header">
            <h1><?php the_title(); ?></h1>
            <?php if (has_excerpt()) : ?>
            <p class="page-description"><?php echo get_the_excerpt(); ?></p>
            <?php endif; ?>
        </header>

        <div class="tmw-page-body">
            <?php the_content(); ?>
        </div>

        <?php endwhile; ?>

    </div>
</div>

<?php get_footer(); ?>
