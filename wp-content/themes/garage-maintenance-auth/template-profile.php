<?php
/**
 * Template Name: GM My Profile
 */
if (!defined('ABSPATH')) { exit; }
get_header();

if (!is_user_logged_in()) {
  wp_safe_redirect(wp_login_url(home_url('/')));
  exit;
}

$user_id = get_current_user_id();
$user = get_user_by('id', $user_id);

$errors = get_transient('gm_profile_errors_' . $user_id);
$success = get_transient('gm_profile_success_' . $user_id);
delete_transient('gm_profile_errors_' . $user_id);
delete_transient('gm_profile_success_' . $user_id);

$home_url = home_url('/');
$app_url = gm_auth_get_setting('app_url', '');
$subscribe_url = gm_auth_get_subscribe_url();
$subscribed = gm_auth_user_is_subscribed($user_id);

?>
<div class="gm-auth-wrap">
  <div class="gm-auth-card">
    <?php get_template_part('template-parts/auth-header'); ?>

    <?php if (!empty($errors)) : ?>
      <div class="gm-alert error">
        <strong>Update issue:</strong>
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

    <div class="gm-alert" style="margin-top:0;">
      Signed in as <strong><?php echo esc_html($user->user_login); ?></strong>
    </div>

    <form class="gm-row" method="post">
      <?php wp_nonce_field('gm_profile', 'gm_profile_nonce'); ?>

      <div class="gm-field">
        <label for="display_name">Display name</label>
        <input type="text" name="display_name" id="display_name" value="<?php echo esc_attr($user->display_name); ?>" required>
      </div>

      <div class="gm-field">
        <label for="email">Email</label>
        <input type="email" name="email" id="email" value="<?php echo esc_attr($user->user_email); ?>" required>
      </div>

      <div class="gm-field">
        <div class="gm-inline">
          <label for="pass1">New password (optional)</label>
          <button type="button" class="gm-btn gm-btn-ghost small" data-gm-toggle-password="pass1">Show</button>
        </div>
        <input type="password" name="pass1" id="pass1" autocomplete="new-password" placeholder="Leave blank to keep current">
      </div>

      <div class="gm-field">
        <div class="gm-inline">
          <label for="pass2">Confirm new password</label>
          <button type="button" class="gm-btn gm-btn-ghost small" data-gm-toggle-password="pass2">Show</button>
        </div>
        <input type="password" name="pass2" id="pass2" autocomplete="new-password" placeholder="Leave blank to keep current">
      </div>

      <div class="gm-actions">
        <button class="gm-btn gm-btn-primary" type="submit">Save changes</button>
        <?php if ($app_url && $subscribed) : ?>
        <a class="gm-btn gm-btn-primary" href="<?php echo esc_url($app_url); ?>">Go to App</a>
      <?php elseif (!$subscribed) : ?>
        <a class="gm-btn gm-btn-primary" href="<?php echo esc_url($subscribe_url); ?>">Subscribe</a>
      <?php endif; ?>
      <a class="gm-btn gm-btn-ghost" href="<?php echo esc_url($home_url); ?>">Back to login</a>
        <a class="gm-btn gm-btn-ghost" href="<?php echo esc_url(wp_logout_url($home_url)); ?>">Logout</a>
      </div>
    </form>

    <div class="gm-footer-note">
      Want a separate password reset email? <a href="<?php echo esc_url(wp_lostpassword_url($home_url)); ?>">Lost password</a>
    </div>
  </div>
</div>
<?php
get_footer();
