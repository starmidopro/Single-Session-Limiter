# Single-Session-Limiter
Single Session Limiter for WordPress
A WordPress plugin that limits users to a single active session. When a user in an enabled role logs in from a new device or browser, any existing sessions are automatically invalidated, forcing a logout from previous sessions.

# Features
Single Session Enforcement: Ensure users can only have one active session at a time based on selected user roles (e.g., subscribers, contributors).
Admin Dashboard: Responsive admin interface to configure enabled roles and manage active sessions by expiring them manually.
Automatic Logout: Old sessions are invalidated upon new login, redirecting users to the login page with a notification.
Secure Token Management: Uses WordPress-generated secure tokens and cookies for session validation.
Compatible: Built using WordPress core functions and hooks for seamless integration.
# Installation
Download or clone this repository.
Upload the single-session-limiter folder to the /wp-content/plugins/ directory of your WordPress installation.
Activate the plugin via the WordPress Admin > Plugins menu.
# Usage
Configure Roles: In WordPress Admin > Settings > Single Session Limiter, select the user roles for which session limiting should apply.
Monitor Sessions: View and manually expire active session tokens from the plugin's admin page.
User Experience: Users with enabled roles will be forced to log out of previous sessions upon new login. They'll see a redirect to the login page with a session-expired notice.
Hooks and Customization
wp_login: Fires when a user logs in and sets a new session token.
init: Checks session validity on every page load for logged-in users.
You can extend by hooking into these or modifying the role checks.
# Requirements
WordPress 5.0 or higher
PHP 7.0 or higher
No external dependencies
Changelog
v1.0 (Initial Release): Basic single session limiting with admin UI.
Contributing
Contributions are welcome! If you'd like to improve this plugin:

Fork the repository.
Create a new branch for your feature.
Submit a pull request with a description of changes.
License
This plugin is licensed under the GPL v2 or later. See http://www.gnu.org/licenses/gpl-2.0.html for details.


