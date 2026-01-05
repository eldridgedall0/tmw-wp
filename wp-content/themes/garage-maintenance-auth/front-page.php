<?php
if (!defined('ABSPATH')) { exit; }
get_header();

$register_url = gm_auth_page_url_by_slug('register');
$profile_url  = gm_auth_page_url_by_slug('my-profile');

$lost_password_url = wp_lostpassword_url(home_url('/'));

?>
<div class="gm-auth-wrap">
  <div class="gm-auth-card">
    <?php get_template_part('template-parts/auth-header'); ?>

    <?php if (is_user_logged_in()) : ?>
      <div class="gm-alert success">You're signed in.</div>

      <div class="gm-row">
        <div class="gm-inline">
          <div class="hint">Go to your profile or continue to the app.</div>
        </div>

        <div class="gm-actions">
          <?php if ($profile_url) : ?>
            <a class="gm-btn gm-btn-primary" href="<?php echo esc_url($profile_url); ?>">My Profile</a>
          <?php endif; ?>
          <?php
  $app_url = gm_auth_get_setting('app_url', '');
  $subscribed = gm_auth_user_is_subscribed(get_current_user_id());
  if ($app_url && $subscribed) :
?>
  <a class="gm-btn gm-btn-primary" href="<?php echo esc_url($app_url); ?>">Go to App</a>
<?php endif; ?>

          <a class="gm-btn gm-btn-ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">Logout</a>
        </div>
      </div>

      <?php if ($register_url) : ?>
        <div class="gm-footer-note">
          Need another account? <a href="<?php echo esc_url($register_url); ?>">Register</a>
        </div>
      <?php endif; ?>

    <?php else : ?>

      <?php
      // Show core WP login errors if present
      $login_err = isset($_GET['login']) && $_GET['login'] === 'failed';
      if ($login_err) {
        echo '<div class="gm-alert error">Login failed. Please check your username/email and password.</div>';
      }
      ?>

      <form class="gm-row" method="post" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>">
        <div class="gm-field">
          <label for="user_login">Username or Email</label>
          <input type="text" name="log" id="user_login" autocomplete="username" required>
        </div>

        <div class="gm-field">
          <div class="gm-inline">
            <label for="user_pass">Password</label>
            <button type="button" class="gm-btn gm-btn-ghost small" data-gm-toggle-password="user_pass">Show</button>
          </div>
          <input type="password" name="pwd" id="user_pass" autocomplete="current-password" required>
        </div>

        <div class="gm-inline" style="margin-top:-4px;">
          <label style="display:flex;align-items:center;gap:8px;color:var(--text-muted);font-size:0.85rem;">
            <input type="checkbox" name="rememberme" value="forever" style="width:16px;height:16px;margin:0;">
            Remember me
          </label>
          <a href="<?php echo esc_url($lost_password_url); ?>">Lost password?</a>
        </div>

        <input type="hidden" name="redirect_to" value="<?php echo esc_url(gm_auth_get_login_redirect_url(0)); ?>">

        <div class="gm-actions">
          <button class="gm-btn gm-btn-primary" type="submit">Login</button>
          <?php if ($register_url) : ?>
            <a class="gm-btn gm-btn-ghost" href="<?php echo esc_url($register_url); ?>">Create account</a>
          <?php endif; ?>
        </div>
      </form>

      <div class="gm-footer-note">
        Tip: If you want public sign-ups, enable <strong>Anyone can register</strong> in Settings → General.
      </div>

    <?php endif; ?>
  </div>
</div>
<?php
get_footer();
