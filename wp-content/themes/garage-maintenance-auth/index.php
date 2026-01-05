<?php
if (!defined('ABSPATH')) { exit; }
get_header();
?>
<div class="gm-auth-wrap">
  <div class="gm-auth-card">
    <?php get_template_part('template-parts/auth-header'); ?>
    <div class="gm-alert">
      This theme is designed for auth pages. Set your Homepage to "Your latest posts" and use the provided page templates for Register and My Profile.
    </div>
  </div>
</div>
<?php
get_footer();
