<?php
/**
 * Template Name: GM Registration
 */
if (!defined('ABSPATH')) { exit; }
get_header();

if (is_user_logged_in()) {
  $profile_url = gm_auth_page_url_by_slug('my-profile');
  wp_safe_redirect($profile_url ?: home_url('/'));
  exit;
}

$login_url    = home_url('/');
$redirect_url = isset($_GET['redirect_to']) ? esc_url_raw($_GET['redirect_to']) : ($login_url);

$errors = get_transient('gm_register_errors');
$success = get_transient('gm_register_success');
delete_transient('gm_register_errors');
delete_transient('gm_register_success');

?>
<div class="gm-auth-wrap">
  <div class="gm-auth-card">
    <?php get_template_part('template-parts/auth-header'); ?>

    <?php if (!empty($errors)) : ?>
      <div class="gm-alert error">
        <strong>Registration issue:</strong>
        <ul style="margin:8px 0 0 18px; color: var(--text-main);">
          <?php foreach ($errors as $m) : ?>
            <li><?php echo esc_html($m); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)) : ?>
      <div class="gm-alert success"><?php echo esc_html($success); ?></div>
    <?php endif; ?>

    <form class="gm-row" method="post">
      <?php wp_nonce_field('gm_register', 'gm_register_nonce'); ?>
      <input type="hidden" name="gm_redirect" value="<?php echo esc_url($redirect_url); ?>">

      <div class="gm-field">
        <label for="username">Username</label>
        <input type="text" name="username" id="username" autocomplete="username" required>
      </div>

      <div class="gm-field">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" autocomplete="email" required>
      </div>

      <div class="gm-field">
        <div class="gm-inline">
          <label for="pass1">Password</label>
          <button type="button" class="gm-btn gm-btn-ghost small" data-gm-toggle-password="pass1">Show</button>
        </div>
        <input type="password" name="pass1" id="pass1" autocomplete="new-password" required>
      </div>

      <div class="gm-field">
        <div class="gm-inline">
          <label for="pass2">Confirm password</label>
          <button type="button" class="gm-btn gm-btn-ghost small" data-gm-toggle-password="pass2">Show</button>
        </div>
        <input type="password" name="pass2" id="pass2" autocomplete="new-password" required>
      </div>

      <div class="gm-actions">
        <button class="gm-btn gm-btn-primary" type="submit">Create account</button>
        <a class="gm-btn gm-btn-ghost" href="<?php echo esc_url($login_url); ?>">Back to login</a>
      </div>
    </form>

    <div class="gm-footer-note">
      Already have an account? <a href="<?php echo esc_url($login_url); ?>">Login</a>
    </div>
  </div>
</div>
<?php
get_footer();
