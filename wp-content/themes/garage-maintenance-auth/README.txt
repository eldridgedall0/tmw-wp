Garage Maintenance Auth Theme
============================

What you get
- Front page (/) = Login + Lost Password link
- Page template: "GM Registration"
- Page template: "GM My Profile"
- Styled to match your Garage Maintenance app (dark, cards, pill buttons), using your modular CSS bundle.

Quick setup (after installing the theme)
1) Activate the theme.
2) Create a page named: Register  (slug: register)
   - Template: GM Registration
3) Create a page named: My Profile (slug: my-profile)
   - Template: GM My Profile
4) Go to Settings → Reading
   - Homepage displays: Your latest posts
   (This makes front-page.php take over / as the login screen.)
5) Optional: Settings → General
   - Enable "Anyone can register" if you want public registration.
   - New User Default Role: Subscriber

Notes
- Lost password uses WordPress core reset flow.
- Registration respects the "Anyone can register" setting.
- Replace the placeholder logo at:
  /assets/img/logo.svg



Admin Settings
- In WP Admin, go to: Garage Maintenance → Settings
  - Set Garage Web App URL
  - Select Subscribe Page
