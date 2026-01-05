<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 *
 * @package flavor-starter-flavor
 */

get_header();
?>

<div class="tmw-container">
    <div class="tmw-section">
        
        <?php if (have_posts()) : ?>

            <?php if (is_home() && !is_front_page()) : ?>
                <header class="tmw-section-header">
                    <h1 class="tmw-section-title"><?php single_post_title(); ?></h1>
                </header>
            <?php endif; ?>

            <div class="tmw-grid tmw-grid-auto">
                <?php while (have_posts()) : the_post(); ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class('tmw-card'); ?>>
                        <?php if (has_post_thumbnail()) : ?>
                            <div class="tmw-card-image">
                                <a href="<?php the_permalink(); ?>">
                                    <?php the_post_thumbnail('tmw-card'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tmw-card-body">
                            <h2 class="h4">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h2>
                            
                            <div class="text-muted text-sm mb-4">
                                <?php echo get_the_date(); ?>
                            </div>
                            
                            <div class="text-secondary">
                                <?php the_excerpt(); ?>
                            </div>
                        </div>
                    </article>

                <?php endwhile; ?>
            </div>

            <?php the_posts_pagination(array(
                'prev_text' => '<i class="fas fa-chevron-left"></i> ' . __('Previous', 'flavor-starter-flavor'),
                'next_text' => __('Next', 'flavor-starter-flavor') . ' <i class="fas fa-chevron-right"></i>',
            )); ?>

        <?php else : ?>

            <div class="tmw-empty-state">
                <div class="tmw-empty-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h2 class="tmw-empty-title"><?php _e('Nothing Found', 'flavor-starter-flavor'); ?></h2>
                <p class="tmw-empty-text">
                    <?php _e('It seems we can\'t find what you\'re looking for.', 'flavor-starter-flavor'); ?>
                </p>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php
get_footer();
