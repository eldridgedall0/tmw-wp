<?php
$logo = get_template_directory_uri() . '/assets/img/logo.svg';
?>
<div class="gm-auth-header">
  <div class="gm-auth-logo" aria-hidden="true">
    <img src="<?php echo esc_url($logo); ?>" alt="" />
  </div>
  <div class="gm-auth-title">
    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
    <p><?php echo esc_html(get_bloginfo('description')); ?></p>
  </div>
</div>
