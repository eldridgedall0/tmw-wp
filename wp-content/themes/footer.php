    </main><!-- #main -->

    <footer id="colophon" class="tmw-footer">
        
        <?php if (!tmw_is_auth_page()) : ?>
        <!-- Full Footer -->
        <div class="tmw-footer-main">
            <div class="tmw-container">
                <div class="tmw-footer-grid">
                    
                    <!-- Brand Column -->
                    <div class="tmw-footer-brand">
                        <?php tmw_logo(); ?>
                        <p><?php _e('Keep track of all your vehicle maintenance in one place. Never miss an oil change, tire rotation, or scheduled service again.', 'flavor-starter-flavor'); ?></p>
                        
                        <div class="tmw-social-links">
                            <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>

                    <!-- Product Column -->
                    <div class="tmw-footer-column">
                        <h4><?php _e('Product', 'flavor-starter-flavor'); ?></h4>
                        <ul>
                            <li><a href="#features"><?php _e('Features', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="<?php echo esc_url(tmw_get_page_url('pricing')); ?>"><?php _e('Pricing', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="<?php echo esc_url(tmw_get_app_url()); ?>"><?php _e('App', 'flavor-starter-flavor'); ?></a></li>
                        </ul>
                    </div>

                    <!-- Company Column -->
                    <div class="tmw-footer-column">
                        <h4><?php _e('Company', 'flavor-starter-flavor'); ?></h4>
                        <ul>
                            <li><a href="#"><?php _e('About Us', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="#"><?php _e('Blog', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="#"><?php _e('Contact', 'flavor-starter-flavor'); ?></a></li>
                        </ul>
                    </div>

                    <!-- Support Column -->
                    <div class="tmw-footer-column">
                        <h4><?php _e('Support', 'flavor-starter-flavor'); ?></h4>
                        <ul>
                            <li><a href="#"><?php _e('Help Center', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="<?php echo esc_url(tmw_get_page_url('terms')); ?>"><?php _e('Terms of Service', 'flavor-starter-flavor'); ?></a></li>
                            <li><a href="<?php echo esc_url(tmw_get_page_url('privacy')); ?>"><?php _e('Privacy Policy', 'flavor-starter-flavor'); ?></a></li>
                        </ul>
                    </div>

                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer Bottom -->
        <div class="tmw-footer-bottom <?php echo tmw_is_auth_page() ? 'tmw-footer-simple' : ''; ?>">
            <div class="tmw-container">
                <div class="tmw-footer-bottom-inner <?php echo tmw_is_auth_page() ? 'tmw-footer-simple-inner' : ''; ?>">
                    
                    <div class="tmw-footer-copyright">
                        &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. 
                        <?php _e('All rights reserved.', 'flavor-starter-flavor'); ?>
                    </div>

                    <div class="tmw-footer-links">
                        <a href="<?php echo esc_url(tmw_get_page_url('terms')); ?>">
                            <?php _e('Terms', 'flavor-starter-flavor'); ?>
                        </a>
                        <a href="<?php echo esc_url(tmw_get_page_url('privacy')); ?>">
                            <?php _e('Privacy', 'flavor-starter-flavor'); ?>
                        </a>
                    </div>

                </div>
            </div>
        </div>

    </footer><!-- #colophon -->

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
