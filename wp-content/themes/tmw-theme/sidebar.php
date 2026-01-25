<?php
/**
 * The sidebar containing the main widget area
 *
 * @package tmw-theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Don't show sidebar on membership pages
if (is_page_template('templates/template-login.php') ||
    is_page_template('templates/template-register.php') ||
    is_page_template('templates/template-forgot-password.php') ||
    is_page_template('templates/template-reset-password.php') ||
    is_page_template('templates/template-profile.php') ||
    is_page_template('page-simple-membership.php')) {
    return;
}

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<aside id="secondary" class="widget-area sidebar">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>
