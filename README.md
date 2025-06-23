=== Discord Logger - Real-time Activity Monitoring ===
Contributors: shivamkumar
Tags: discord, logger, notifications, woocommerce, monitoring, activity, webhook
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Transform your WordPress site monitoring with real-time Discord notifications for all site activities including WooCommerce integration.

== Description ==

Discord Logger provides real-time activity monitoring for your WordPress site by sending beautifully formatted notifications to your Discord server. Track user activities, content changes, WooCommerce orders, and system events - all in one place.

= ✨ Key Features =

* Real-time WordPress activity logging (users, posts, comments)
* Complete WooCommerce integration (orders, products, customers)
* Beautiful Discord embeds with rich formatting
* Professional admin dashboard with activity logs
* Secure webhook communication with retry handling
* GDPR-compliant data handling
* Zero configuration required - works out of the box

= 🔥 Perfect for =

* E-commerce monitoring
* Team collaboration
* Security tracking
* User activity insights
* System change alerts

= 🎯 Monitored Events =

**WordPress Events:**
* User registrations, logins, and logouts
* Post publications, updates, and deletions
* Comment activities and moderation
* Plugin and theme changes
* Core WordPress updates

**WooCommerce Events:**
* New orders and status changes
* Payment completions and refunds
* Product management
* Customer registrations
* Cart activities
* Coupon usage
* Stock level changes

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/discord-logger` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings -> Discord Logger to configure your Discord webhook URL.
4. Create a webhook in your Discord server:
   * Go to Server Settings -> Integrations -> Create Webhook
   * Choose the channel for notifications
   * Copy the webhook URL
5. Paste the webhook URL in the plugin settings
6. Customize the bot name and avatar (optional)
7. Save settings and test the connection

== Frequently Asked Questions ==

= Is this plugin GDPR compliant? =

Yes, the plugin is designed with privacy in mind. It only logs necessary information and provides options to control what data is sent to Discord.

= Does it work with WooCommerce? =

Yes! The plugin includes comprehensive WooCommerce integration, tracking orders, products, customers, and more.

= Can I customize what events are logged? =

Yes, future updates will include granular control over which events are logged and sent to Discord.

= Is it secure? =

Yes, the plugin uses WordPress security best practices, including:
* Nonce verification
* Capability checking
* Data sanitization
* Secure webhook communication

= Will this slow down my site? =

No, the plugin is designed to be lightweight and uses asynchronous communication with Discord to prevent any impact on site performance.

== Screenshots ==

1. Discord notifications example
2. Plugin settings page
3. Activity logs dashboard
4. WooCommerce integration

== Changelog ==

= 1.0.0 =
* Initial release
* WordPress core event logging
* WooCommerce integration
* Admin dashboard with activity logs
* Settings management interface

== Upgrade Notice ==

= 1.0.0 =
Initial release of Discord Logger

== Privacy Policy ==

Discord Logger uses Discord webhooks to send notifications about site activities. The following data may be sent to Discord:

* User actions (registrations, logins, etc.)
* Post and comment activities
* WooCommerce order information
* System events

No personal data is stored by the plugin except for basic activity logs in the WordPress database.
