<?php
/**
 * Plugin Name: Discord Logger - Real-time Activity Monitoring
 * Plugin URI: https://github.com/Shivamx-Dev/Discord-Logger/
 * Description: Transform your WordPress site monitoring with real-time Discord notifications for all site activities including WooCommerce integration.
 * Version: 1.0.0
 * Author: Shivam Kumar
 * Author URI: https://www.linkedin.com/in/shivvx/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: discord-logger-real-time-activity-monitoring
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Network: true
 * 
 * @package WP_Discord_Logger
 * @author Shivam Kumar
 * @copyright 2024 Shivam Kumar
 * @license GPL-2.0-or-later
 * 
 * WP Discord Logger is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 * 
 * WP Discord Logger is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with WP Discord Logger. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_DISCORD_LOGGER_VERSION', '1.0.0');
define('WP_DISCORD_LOGGER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_DISCORD_LOGGER_PLUGIN_URL', plugin_dir_url(__FILE__));

class WP_Discord_Logger {
    
    private $webhook_url;
    private $bot_name;
    private $avatar_url;
    private $log_table;
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // AJAX handlers
        add_action('wp_ajax_test_discord_connection', array($this, 'handle_test_discord_connection'));
        add_action('wp_ajax_wpdl_save_setting', array($this, 'handle_save_setting'));
        add_action('wp_ajax_wpdl_get_logs', array($this, 'handle_get_logs'));
        add_action('wp_ajax_wpdl_clear_logs', array($this, 'handle_clear_logs'));
        
        // Load settings
        $this->webhook_url = get_option('wpdl_webhook_url', '');
        $this->bot_name = get_option('wpdl_bot_name', 'WordPress Logger');
        $this->avatar_url = get_option('wpdl_avatar_url', '');
        
        // Initialize log table
        global $wpdb;
        $this->log_table = $wpdb->prefix . 'discord_logs';
        
        // Hook into WordPress events
        $this->setup_wordpress_hooks();
        
        // Hook into WooCommerce events if active
        if (class_exists('WooCommerce')) {
            $this->setup_woocommerce_hooks();
        }
    }
    
    public function init() {
        // Plugin initialization
    }
    
    public function setup_wordpress_hooks() {
        // User events
        add_action('user_register', array($this, 'log_user_register'));
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('wp_login_failed', array($this, 'log_login_failed'));
        add_action('profile_update', array($this, 'log_profile_update'));
        add_action('delete_user', array($this, 'log_user_delete'));
        
        // Post events
        add_action('publish_post', array($this, 'log_post_published'));
        add_action('draft_to_published', array($this, 'log_post_published'));
        add_action('pending_to_published', array($this, 'log_post_published'));
        add_action('future_to_published', array($this, 'log_post_published'));
        add_action('wp_trash_post', array($this, 'log_post_trashed'));
        add_action('untrash_post', array($this, 'log_post_untrashed'));
        add_action('before_delete_post', array($this, 'log_post_deleted'));
        
        // Comment events
        add_action('comment_post', array($this, 'log_comment_posted'));
        add_action('wp_set_comment_status', array($this, 'log_comment_status_change'), 10, 2);
        add_action('delete_comment', array($this, 'log_comment_deleted'));
        
        // Plugin/Theme events
        add_action('activated_plugin', array($this, 'log_plugin_activated'));
        add_action('deactivated_plugin', array($this, 'log_plugin_deactivated'));
        add_action('switch_theme', array($this, 'log_theme_switched'));
        
        // Core updates
        add_action('_core_updated_successfully', array($this, 'log_core_updated'));
        
        // Error logging
        add_action('wp_die_handler', array($this, 'log_wp_die'));
    }
    
    public function setup_woocommerce_hooks() {
        // Order events
        add_action('woocommerce_new_order', array($this, 'log_new_order'));
        add_action('woocommerce_order_status_changed', array($this, 'log_order_status_change'), 10, 4);
        add_action('woocommerce_payment_complete', array($this, 'log_payment_complete'));
        add_action('woocommerce_order_refunded', array($this, 'log_order_refunded'));
        
        // Product events
        add_action('woocommerce_new_product', array($this, 'log_new_product'));
        add_action('woocommerce_update_product', array($this, 'log_product_updated'));
        add_action('wp_trash_post', array($this, 'log_product_trashed'));
        
        // Customer events
        add_action('woocommerce_created_customer', array($this, 'log_customer_created'));
        add_action('woocommerce_customer_save_address', array($this, 'log_customer_address_updated'));
        
        // Cart events
        add_action('woocommerce_add_to_cart', array($this, 'log_add_to_cart'), 10, 6);
        add_action('woocommerce_cart_item_removed', array($this, 'log_cart_item_removed'));
        
        // Coupon events
        add_action('woocommerce_coupon_loaded', array($this, 'log_coupon_used'));
        
        // Stock events
        add_action('woocommerce_product_set_stock', array($this, 'log_stock_change'));
        add_action('woocommerce_variation_set_stock', array($this, 'log_stock_change'));
    }
    
    // WordPress Event Handlers
    public function log_user_register($user_id) {
        $user = get_userdata($user_id);
        $this->send_to_discord([
            'title' => '👤 New User Registration',
            'description' => "User **{$user->user_login}** ({$user->user_email}) has registered",
            'color' => 3066993, // Green
            'fields' => [
                ['name' => 'Username', 'value' => $user->user_login, 'inline' => true],
                ['name' => 'Email', 'value' => $user->user_email, 'inline' => true],
                ['name' => 'Registration Date', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_user_login($user_login, $user) {
        $this->send_to_discord([
            'title' => '🔐 User Login',
            'description' => "User **{$user_login}** has logged in",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Username', 'value' => $user_login, 'inline' => true],
                ['name' => 'IP Address', 'value' => $this->get_user_ip(), 'inline' => true],
                ['name' => 'Time', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_user_logout() {
        $current_user = wp_get_current_user();
        $this->send_to_discord([
            'title' => '🚪 User Logout',
            'description' => "User **{$current_user->user_login}** has logged out",
            'color' => 10181046, // Purple
            'fields' => [
                ['name' => 'Username', 'value' => $current_user->user_login, 'inline' => true],
                ['name' => 'Time', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_login_failed($username) {
        $this->send_to_discord([
            'title' => '❌ Login Failed',
            'description' => "Failed login attempt for username: **{$username}**",
            'color' => 15158332, // Red
            'fields' => [
                ['name' => 'Username', 'value' => $username, 'inline' => true],
                ['name' => 'IP Address', 'value' => $this->get_user_ip(), 'inline' => true],
                ['name' => 'Time', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_post_published($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'post') {
            $this->send_to_discord([
                'title' => '📝 New Post Published',
                'description' => "Post **{$post->post_title}** has been published",
                'color' => 3066993, // Green
                'fields' => [
                    ['name' => 'Title', 'value' => $post->post_title, 'inline' => false],
                    ['name' => 'Author', 'value' => get_the_author_meta('display_name', $post->post_author), 'inline' => true],
                    ['name' => 'URL', 'value' => get_permalink($post_id), 'inline' => false]
                ]
            ]);
        }
    }
    
    // WooCommerce Event Handlers
    public function log_new_order($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->send_to_discord([
                'title' => '🛒 New Order Received',
                'description' => "Order #{$order_id} has been placed",
                'color' => 3066993, // Green
                'fields' => [
                    ['name' => 'Order ID', 'value' => $order_id, 'inline' => true],
                    ['name' => 'Customer', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 'inline' => true],
                    ['name' => 'Total', 'value' => $order->get_formatted_order_total(), 'inline' => true],
                    ['name' => 'Status', 'value' => $order->get_status(), 'inline' => true],
                    ['name' => 'Payment Method', 'value' => $order->get_payment_method_title(), 'inline' => true],
                    ['name' => 'Items', 'value' => $order->get_item_count(), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_order_status_change($order_id, $old_status, $new_status, $order) {
        $this->send_to_discord([
            'title' => '📦 Order Status Changed',
            'description' => "Order #{$order_id} status changed from **{$old_status}** to **{$new_status}**",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Order ID', 'value' => $order_id, 'inline' => true],
                ['name' => 'Old Status', 'value' => $old_status, 'inline' => true],
                ['name' => 'New Status', 'value' => $new_status, 'inline' => true],
                ['name' => 'Customer', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 'inline' => true],
                ['name' => 'Total', 'value' => $order->get_formatted_order_total(), 'inline' => true]
            ]
        ]);
    }
    
    public function log_payment_complete($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->send_to_discord([
                'title' => '💰 Payment Completed',
                'description' => "Payment for Order #{$order_id} has been completed",
                'color' => 3066993, // Green
                'fields' => [
                    ['name' => 'Order ID', 'value' => $order_id, 'inline' => true],
                    ['name' => 'Amount', 'value' => $order->get_formatted_order_total(), 'inline' => true],
                    ['name' => 'Payment Method', 'value' => $order->get_payment_method_title(), 'inline' => true],
                    ['name' => 'Customer', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_new_product($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $this->send_to_discord([
                'title' => '🆕 New Product Added',
                'description' => "Product **{$product->get_name()}** has been added",
                'color' => 3066993, // Green
                'fields' => [
                    ['name' => 'Product Name', 'value' => $product->get_name(), 'inline' => false],
                    ['name' => 'Price', 'value' => $product->get_price_html(), 'inline' => true],
                    ['name' => 'SKU', 'value' => $product->get_sku() ?: 'N/A', 'inline' => true],
                    ['name' => 'Stock Status', 'value' => $product->get_stock_status(), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
        $product = wc_get_product($product_id);
        if ($product) {
            $this->send_to_discord([
                'title' => '🛍️ Item Added to Cart',
                'description' => "**{$product->get_name()}** was added to cart",
                'color' => 3447003, // Blue
                'fields' => [
                    ['name' => 'Product', 'value' => $product->get_name(), 'inline' => true],
                    ['name' => 'Quantity', 'value' => $quantity, 'inline' => true],
                    ['name' => 'Price', 'value' => $product->get_price_html(), 'inline' => true]
                ]
            ]);
        }
    }
    
    // Missing WordPress Event Handlers
    public function log_profile_update($user_id) {
        $user = get_userdata($user_id);
        $this->send_to_discord([
            'title' => '👤 User Profile Updated',
            'description' => "User **{$user->user_login}** updated their profile",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Username', 'value' => $user->user_login, 'inline' => true],
                ['name' => 'Email', 'value' => $user->user_email, 'inline' => true],
                ['name' => 'Updated', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_user_delete($user_id) {
        $user = get_userdata($user_id);
        $this->send_to_discord([
            'title' => '🗑️ User Deleted',
            'description' => "User **{$user->user_login}** has been deleted",
            'color' => 15158332, // Red
            'fields' => [
                ['name' => 'Username', 'value' => $user->user_login, 'inline' => true],
                ['name' => 'Email', 'value' => $user->user_email, 'inline' => true],
                ['name' => 'Deleted', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_post_trashed($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'post') {
            $this->send_to_discord([
                'title' => '🗑️ Post Trashed',
                'description' => "Post **{$post->post_title}** has been moved to trash",
                'color' => 15105570, // Orange
                'fields' => [
                    ['name' => 'Title', 'value' => $post->post_title, 'inline' => false],
                    ['name' => 'Author', 'value' => get_the_author_meta('display_name', $post->post_author), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_post_untrashed($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'post') {
            $this->send_to_discord([
                'title' => '♻️ Post Restored',
                'description' => "Post **{$post->post_title}** has been restored from trash",
                'color' => 3066993, // Green
                'fields' => [
                    ['name' => 'Title', 'value' => $post->post_title, 'inline' => false],
                    ['name' => 'Author', 'value' => get_the_author_meta('display_name', $post->post_author), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_post_deleted($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'post') {
            $this->send_to_discord([
                'title' => '🗑️ Post Permanently Deleted',
                'description' => "Post **{$post->post_title}** has been permanently deleted",
                'color' => 15158332, // Red
                'fields' => [
                    ['name' => 'Title', 'value' => $post->post_title, 'inline' => false],
                    ['name' => 'Author', 'value' => get_the_author_meta('display_name', $post->post_author), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_comment_posted($comment_id) {
        $comment = get_comment($comment_id);
        $post = get_post($comment->comment_post_ID);
        $this->send_to_discord([
            'title' => '💬 New Comment Posted',
            'description' => "New comment on **{$post->post_title}**",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Author', 'value' => $comment->comment_author, 'inline' => true],
                ['name' => 'Post', 'value' => $post->post_title, 'inline' => true],
                ['name' => 'Content', 'value' => substr($comment->comment_content, 0, 100) . '...', 'inline' => false]
            ]
        ]);
    }
    
    public function log_comment_status_change($comment_id, $status) {
        $comment = get_comment($comment_id);
        $post = get_post($comment->comment_post_ID);
        $this->send_to_discord([
            'title' => '💬 Comment Status Changed',
            'description' => "Comment status changed to **{$status}**",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Post', 'value' => $post->post_title, 'inline' => true],
                ['name' => 'Author', 'value' => $comment->comment_author, 'inline' => true],
                ['name' => 'Status', 'value' => $status, 'inline' => true]
            ]
        ]);
    }
    
    public function log_comment_deleted($comment_id) {
        $comment = get_comment($comment_id);
        $post = get_post($comment->comment_post_ID);
        $this->send_to_discord([
            'title' => '🗑️ Comment Deleted',
            'description' => "Comment deleted from **{$post->post_title}**",
            'color' => 15158332, // Red
            'fields' => [
                ['name' => 'Post', 'value' => $post->post_title, 'inline' => true],
                ['name' => 'Author', 'value' => $comment->comment_author, 'inline' => true]
            ]
        ]);
    }
    
    public function log_plugin_activated($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $this->send_to_discord([
            'title' => '🔌 Plugin Activated',
            'description' => "Plugin **{$plugin_data['Name']}** has been activated",
            'color' => 3066993, // Green
            'fields' => [
                ['name' => 'Plugin', 'value' => $plugin_data['Name'], 'inline' => true],
                ['name' => 'Version', 'value' => $plugin_data['Version'], 'inline' => true]
            ]
        ]);
    }
    
    public function log_plugin_deactivated($plugin) {
        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
        $this->send_to_discord([
            'title' => '🔌 Plugin Deactivated',
            'description' => "Plugin **{$plugin_data['Name']}** has been deactivated",
            'color' => 15105570, // Orange
            'fields' => [
                ['name' => 'Plugin', 'value' => $plugin_data['Name'], 'inline' => true],
                ['name' => 'Version', 'value' => $plugin_data['Version'], 'inline' => true]
            ]
        ]);
    }
    
    public function log_theme_switched($theme_name) {
        $this->send_to_discord([
            'title' => '🎨 Theme Changed',
            'description' => "Theme switched to **{$theme_name}**",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'New Theme', 'value' => $theme_name, 'inline' => true],
                ['name' => 'Changed', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_core_updated($wp_version) {
        $this->send_to_discord([
            'title' => '🔄 WordPress Updated',
            'description' => "WordPress has been updated to version **{$wp_version}**",
            'color' => 3066993, // Green
            'fields' => [
                ['name' => 'Version', 'value' => $wp_version, 'inline' => true],
                ['name' => 'Updated', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    // Missing WooCommerce Event Handlers
    public function log_order_refunded($order_id) {
        $order = wc_get_order($order_id);
        if ($order) {
            $this->send_to_discord([
                'title' => '💸 Order Refunded',
                'description' => "Order #{$order_id} has been refunded",
                'color' => 15158332, // Red
                'fields' => [
                    ['name' => 'Order ID', 'value' => $order_id, 'inline' => true],
                    ['name' => 'Customer', 'value' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(), 'inline' => true],
                    ['name' => 'Total', 'value' => $order->get_formatted_order_total(), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_product_updated($product_id) {
        $product = wc_get_product($product_id);
        if ($product) {
            $this->send_to_discord([
                'title' => '📦 Product Updated',
                'description' => "Product **{$product->get_name()}** has been updated",
                'color' => 3447003, // Blue
                'fields' => [
                    ['name' => 'Product', 'value' => $product->get_name(), 'inline' => true],
                    ['name' => 'Price', 'value' => $product->get_price_html(), 'inline' => true],
                    ['name' => 'Stock', 'value' => $product->get_stock_status(), 'inline' => true]
                ]
            ]);
        }
    }
    
    public function log_product_trashed($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_type === 'product') {
            $product = wc_get_product($post_id);
            if ($product) {
                $this->send_to_discord([
                    'title' => '🗑️ Product Trashed',
                    'description' => "Product **{$product->get_name()}** has been moved to trash",
                    'color' => 15105570, // Orange
                    'fields' => [
                        ['name' => 'Product', 'value' => $product->get_name(), 'inline' => true],
                        ['name' => 'SKU', 'value' => $product->get_sku() ?: 'N/A', 'inline' => true]
                    ]
                ]);
            }
        }
    }
    
    public function log_customer_created($customer_id) {
        $customer = new WC_Customer($customer_id);
        $this->send_to_discord([
            'title' => '👥 New Customer',
            'description' => "New customer **{$customer->get_first_name()} {$customer->get_last_name()}** has been created",
            'color' => 3066993, // Green
            'fields' => [
                ['name' => 'Name', 'value' => $customer->get_first_name() . ' ' . $customer->get_last_name(), 'inline' => true],
                ['name' => 'Email', 'value' => $customer->get_email(), 'inline' => true],
                ['name' => 'Created', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_customer_address_updated($customer_id) {
        $customer = new WC_Customer($customer_id);
        $this->send_to_discord([
            'title' => '📍 Customer Address Updated',
            'description' => "Customer **{$customer->get_first_name()} {$customer->get_last_name()}** updated their address",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Customer', 'value' => $customer->get_first_name() . ' ' . $customer->get_last_name(), 'inline' => true],
                ['name' => 'Email', 'value' => $customer->get_email(), 'inline' => true]
            ]
        ]);
    }
    
    public function log_cart_item_removed($cart_item_key) {
        $this->send_to_discord([
            'title' => '🛍️ Item Removed from Cart',
            'description' => "An item was removed from cart",
            'color' => 15105570, // Orange
            'fields' => [
                ['name' => 'Action', 'value' => 'Item removed', 'inline' => true],
                ['name' => 'Time', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_coupon_used($coupon) {
        $this->send_to_discord([
            'title' => '🎟️ Coupon Used',
            'description' => "Coupon **{$coupon->get_code()}** has been applied",
            'color' => 3447003, // Blue
            'fields' => [
                ['name' => 'Code', 'value' => $coupon->get_code(), 'inline' => true],
                ['name' => 'Discount', 'value' => $coupon->get_amount() . ($coupon->get_discount_type() === 'percent' ? '%' : ''), 'inline' => true],
                ['name' => 'Used', 'value' => current_time('mysql'), 'inline' => true]
            ]
        ]);
    }
    
    public function log_stock_change($product) {
        if (is_numeric($product)) {
            $product = wc_get_product($product);
        }
        
        if ($product) {
            $this->send_to_discord([
                'title' => '📊 Stock Level Changed',
                'description' => "Stock updated for **{$product->get_name()}**",
                'color' => 15105570, // Orange
                'fields' => [
                    ['name' => 'Product', 'value' => $product->get_name(), 'inline' => true],
                    ['name' => 'Stock Quantity', 'value' => $product->get_stock_quantity() ?: 'N/A', 'inline' => true],
                    ['name' => 'Status', 'value' => $product->get_stock_status(), 'inline' => true]
                ]
            ]);
        }
    }
    
    // Utility functions
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    private function send_to_discord($embed_data) {
        if (empty($this->webhook_url)) {
            $this->log_activity('error', 'Webhook URL is not configured', '');
            return false;
        }
        
        // Validate webhook URL format
        if (!$this->is_valid_discord_webhook($this->webhook_url)) {
            $this->log_activity('error', 'Invalid Discord webhook URL format', $this->webhook_url);
            return false;
        }
        
        try {
            $embed = [
                'embeds' => [
                    array_merge($embed_data, [
                        'timestamp' => date('c'),
                        'footer' => [
                            'text' => get_bloginfo('name') . ' | ' . home_url()
                        ]
                    ])
                ],
                'username' => $this->bot_name,
                'avatar_url' => $this->avatar_url
            ];
            
            $args = [
                'body' => wp_json_encode($embed),
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'timeout' => 30,
                'blocking' => true,
                'sslverify' => true
            ];
            
            $response = wp_remote_post($this->webhook_url, $args);
            
            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                $this->log_activity('error', 'Failed to send webhook: ' . $error_message, $this->webhook_url);
                
                // Retry mechanism for network errors
                if (strpos($error_message, 'ENOTFOUND') !== false || strpos($error_message, 'timeout') !== false) {
                    sleep(2);
                    $retry_response = wp_remote_post($this->webhook_url, $args);
                    if (!is_wp_error($retry_response)) {
                        $this->log_activity('success', 'Webhook sent successfully on retry', $embed_data['title']);
                        return true;
                    }
                }
                return false;
            }
            
            $response_code = wp_remote_retrieve_response_code($response);
            if ($response_code !== 204 && $response_code !== 200) {
                $this->log_activity('error', 'Webhook failed with response code ' . $response_code, $this->webhook_url);
                return false;
            }
            
            $this->log_activity('success', 'Webhook sent successfully', $embed_data['title']);
            return true;
        } catch (Exception $e) {
            $this->log_activity('error', 'Exception while sending webhook: ' . $e->getMessage(), $this->webhook_url);
            return false;
        }
    }
    
    private function is_valid_discord_webhook($url) {
        $pattern = '/^https:\/\/(canary\.|ptb\.)?discord(app)?\.com\/api\/webhooks\/\d+\/[\w-]+$/';
        return preg_match($pattern, $url);
    }
    
    private function log_activity($type, $message, $details) {
        global $wpdb;
        
        $wpdb->insert(
            $this->log_table,
            [
                'type' => $type,
                'message' => $message,
                'details' => $details,
                'timestamp' => current_time('mysql'),
                'user_id' => get_current_user_id()
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_wp-discord-logger') {
            return;
        }
        
        wp_enqueue_style(
            'wpdl-admin-style',
            WP_DISCORD_LOGGER_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            WP_DISCORD_LOGGER_VERSION
        );
        
        wp_enqueue_script(
            'wpdl-admin-script',
            WP_DISCORD_LOGGER_PLUGIN_URL . 'assets/js/admin-script.js',
            ['jquery'],
            WP_DISCORD_LOGGER_VERSION,
            true
        );
        
        wp_localize_script('wpdl-admin-script', 'wpdl_ajax', [
            'nonce' => wp_create_nonce('wpdl_ajax_nonce'),
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
    }
    
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'wpdl-dashboard-widget',
            'Discord Logger Activity',
            array($this, 'dashboard_widget_content')
        );
    }
    
    public function dashboard_widget_content() {
        global $wpdb;
        
        $recent_logs = $wpdb->get_results(
            "SELECT * FROM {$this->log_table} ORDER BY timestamp DESC LIMIT 10",
            ARRAY_A
        );
        
        if (empty($recent_logs)) {
            echo '<p>No recent activity.</p>';
            return;
        }
        
        echo '<ul class="wpdl-dashboard-list">';
        foreach ($recent_logs as $log) {
            $icon = $log['type'] === 'success' ? '✅' : '❌';
            $time = human_time_diff(strtotime($log['timestamp'])) . ' ago';
            echo '<li>';
            echo '<span class="wpdl-event-icon">' . $icon . '</span>';
            echo esc_html($log['message']);
            echo '<span class="wpdl-event-time">' . $time . '</span>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    public function handle_test_discord_connection() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!check_ajax_referer('wpdl_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $webhook_url = get_option('wpdl_webhook_url');
        if (empty($webhook_url)) {
            wp_send_json_error('No webhook URL configured');
        }
        
        if (!$this->is_valid_discord_webhook($webhook_url)) {
            wp_send_json_error('Invalid Discord webhook URL format');
        }
        
        $embed = [
            'embeds' => [[
                'title' => '✅ Test Message',
                'description' => 'Discord Logger connection test successful!',
                'color' => 3066993,
                'timestamp' => date('c'),
                'footer' => [
                    'text' => get_bloginfo('name') . ' | Test Connection'
                ]
            ]],
            'username' => get_option('wpdl_bot_name', 'WordPress Logger'),
            'avatar_url' => get_option('wpdl_avatar_url', '')
        ];
        
        $response = wp_remote_post($webhook_url, [
            'body' => json_encode($embed),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 204 && $response_code !== 200) {
            wp_send_json_error('HTTP Error: ' . $response_code);
        }
        
        wp_send_json_success('Test message sent successfully!');
    }
    
    public function handle_save_setting() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!check_ajax_referer('wpdl_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        $setting = sanitize_text_field($_POST['setting']);
        $value = sanitize_text_field($_POST['value']);
        
        if (in_array($setting, ['wpdl_webhook_url', 'wpdl_bot_name', 'wpdl_avatar_url'])) {
            update_option($setting, $value);
            wp_send_json_success('Setting saved');
        } else {
            wp_send_json_error('Invalid setting');
        }
    }
    
    public function handle_get_logs() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!check_ajax_referer('wpdl_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $logs = $wpdb->get_results(
            "SELECT * FROM {$this->log_table} ORDER BY timestamp DESC LIMIT 50",
            ARRAY_A
        );
        
        if (empty($logs)) {
            wp_send_json_success('<p>No logs found.</p>');
        }
        
        $output = '<table class="wpdl-log-table">';
        $output .= '<thead><tr><th>Time</th><th>Type</th><th>Message</th><th>Details</th></tr></thead>';
        $output .= '<tbody>';
        
        foreach ($logs as $log) {
            $output .= '<tr>';
            $output .= '<td>' . esc_html($log['timestamp']) . '</td>';
            $output .= '<td>' . esc_html($log['type']) . '</td>';
            $output .= '<td>' . esc_html($log['message']) . '</td>';
            $output .= '<td>' . esc_html(substr($log['details'], 0, 50)) . '...</td>';
            $output .= '</tr>';
        }
        
        $output .= '</tbody></table>';
        
        wp_send_json_success($output);
    }
    
    public function handle_clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        if (!check_ajax_referer('wpdl_ajax_nonce', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        
        $wpdb->query("TRUNCATE TABLE {$this->log_table}");
        
        wp_send_json_success('Logs cleared successfully');
    }
    
    // Admin interface
    public function add_admin_menu() {
        add_options_page(
            'Discord Logger Settings',
            'Discord Logger',
            'manage_options',
            'wp-discord-logger',
            array($this, 'options_page')
        );
    }
    
    public function settings_init() {
        register_setting('wpdl_settings', 'wpdl_webhook_url', array(
            'sanitize_callback' => array($this, 'sanitize_webhook_url')
        ));
        register_setting('wpdl_settings', 'wpdl_bot_name', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('wpdl_settings', 'wpdl_avatar_url', array(
            'sanitize_callback' => array($this, 'sanitize_avatar_url')
        ));
        
        add_settings_section(
            'wpdl_settings_section',
            'Discord Webhook Settings',
            array($this, 'settings_section_callback'),
            'wpdl_settings'
        );
        
        add_settings_field(
            'wpdl_webhook_url',
            'Discord Webhook URL',
            array($this, 'webhook_url_render'),
            'wpdl_settings',
            'wpdl_settings_section'
        );
        
        add_settings_field(
            'wpdl_bot_name',
            'Bot Name',
            array($this, 'bot_name_render'),
            'wpdl_settings',
            'wpdl_settings_section'
        );
        
        add_settings_field(
            'wpdl_avatar_url',
            'Bot Avatar URL',
            array($this, 'avatar_url_render'),
            'wpdl_settings',
            'wpdl_settings_section'
        );
    }
    
    public function webhook_url_render() {
        $webhook_url = get_option('wpdl_webhook_url');
        ?>
        <input type="url" 
               name="wpdl_webhook_url" 
               value="<?php echo esc_attr($webhook_url); ?>" 
               size="70"
               class="regular-text"
               pattern="https://.*"
               required />
        <p class="description"><?php esc_html_e('Enter your Discord webhook URL (must start with https://)', 'wp-discord-logger'); ?></p>
        <?php
    }
    
    public function bot_name_render() {
        $bot_name = get_option('wpdl_bot_name', 'WordPress Logger');
        ?>
        <input type="text" 
               name="wpdl_bot_name" 
               value="<?php echo esc_attr($bot_name); ?>"
               class="regular-text"
               required />
        <?php
    }
    
    public function avatar_url_render() {
        $avatar_url = get_option('wpdl_avatar_url');
        ?>
        <input type="url" 
               name="wpdl_avatar_url" 
               value="<?php echo esc_attr($avatar_url); ?>" 
               size="70"
               class="regular-text"
               pattern="https://.*" />
        <p class="description"><?php esc_html_e('Optional: Bot avatar image URL (must start with https://)', 'wp-discord-logger'); ?></p>
        <?php
    }
    
    public function settings_section_callback() {
        echo 'Configure your Discord webhook settings below:';
    }

    public function sanitize_webhook_url($url) {
        $url = esc_url_raw($url);
        if (!$this->is_valid_discord_webhook($url)) {
            add_settings_error(
                'wpdl_webhook_url',
                'invalid_webhook',
                'Invalid Discord webhook URL format. Must be a valid Discord webhook URL.',
                'error'
            );
            return '';
        }
        return $url;
    }

    public function sanitize_avatar_url($url) {
        if (empty($url)) {
            return '';
        }
        $url = esc_url_raw($url);
        if (!preg_match('/^https:\/\/.+/', $url)) {
            add_settings_error(
                'wpdl_avatar_url',
                'invalid_avatar_url',
                'Avatar URL must start with https://',
                'error'
            );
            return '';
        }
        return $url;
    }
    
    public function options_page() {
        ?>
        <div class="wpdl-admin-wrap">
            <h1>🎯 Discord Logger Settings</h1>
            
            <div class="wpdl-tabs">
                <div class="wpdl-tab active" data-tab="settings">⚙️ Settings</div>
                <div class="wpdl-tab" data-tab="logs">📊 Activity Logs</div>
                <div class="wpdl-tab" data-tab="tools">🔧 Tools</div>
                <div class="wpdl-tab" data-tab="help">❓ Help</div>
            </div>
            
            <!-- Settings Tab -->
            <div id="settings" class="wpdl-tab-content active">
                <div class="wpdl-card">
                    <h2>Discord Webhook Configuration</h2>
                    <form action="options.php" method="post">
                        <?php
                        settings_fields('wpdl_settings');
                        ?>
                        
                        <div class="wpdl-field">
                            <label for="wpdl_webhook_url">Discord Webhook URL *</label>
                            <input type="url" 
                                   id="wpdl_webhook_url"
                                   name="wpdl_webhook_url" 
                                   value="<?php echo esc_attr(get_option('wpdl_webhook_url')); ?>" 
                                   placeholder="https://discord.com/api/webhooks/..."
                                   required />
                            <div id="webhook-status" class="wpdl-status"></div>
                            <p class="description">Enter your Discord webhook URL. Must be a valid Discord webhook.</p>
                        </div>
                        
                        <div class="wpdl-field">
                            <label for="wpdl_bot_name">Bot Name</label>
                            <input type="text" 
                                   id="wpdl_bot_name"
                                   name="wpdl_bot_name" 
                                   value="<?php echo esc_attr(get_option('wpdl_bot_name', 'WordPress Logger')); ?>" 
                                   placeholder="WordPress Logger" />
                            <p class="description">The name that will appear in Discord messages.</p>
                        </div>
                        
                        <div class="wpdl-field">
                            <label for="wpdl_avatar_url">Bot Avatar URL (Optional)</label>
                            <input type="url" 
                                   id="wpdl_avatar_url"
                                   name="wpdl_avatar_url" 
                                   value="<?php echo esc_attr(get_option('wpdl_avatar_url')); ?>" 
                                   placeholder="https://example.com/avatar.png" />
                            <p class="description">Optional: Custom avatar image URL for the bot.</p>
                        </div>
                        
                        <?php submit_button('Save Settings', 'primary'); ?>
                    </form>
                </div>
                
                <div class="wpdl-card">
                    <h2>🧪 Test Connection</h2>
                    <p>Test your Discord webhook to ensure it's working correctly.</p>
                    <button type="button" id="test-connection" class="button button-secondary">Send Test Message</button>
                    <div id="test-status" class="wpdl-status"></div>
                </div>
            </div>
            
            <!-- Activity Logs Tab -->
            <div id="logs" class="wpdl-tab-content">
                <div class="wpdl-card">
                    <h2>📊 Recent Activity</h2>
                    <p>View recent Discord webhook activity and troubleshoot issues.</p>
                    <button type="button" id="clear-logs" class="button button-secondary">Clear Logs</button>
                    <div id="activity-logs"></div>
                </div>
            </div>
            
            <!-- Tools Tab -->
            <div id="tools" class="wpdl-tab-content">
                <div class="wpdl-card">
                    <h2>🔧 Import/Export Settings</h2>
                    <p>Backup and restore your Discord Logger settings.</p>
                    
                    <h3>Export Settings</h3>
                    <button type="button" id="export-settings" class="button">Download Settings</button>
                    
                    <h3>Import Settings</h3>
                    <input type="file" id="import-settings" accept=".json" />
                    <p class="description">Select a previously exported settings file.</p>
                </div>
                
                <div class="wpdl-card">
                    <h2>📈 Statistics</h2>
                    <?php
                    global $wpdb;
                    $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table}");
                    $success_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table} WHERE type = 'success'");
                    $error_logs = $wpdb->get_var("SELECT COUNT(*) FROM {$this->log_table} WHERE type = 'error'");
                    ?>
                    <p><strong>Total Messages:</strong> <?php echo $total_logs ?: 0; ?></p>
                    <p><strong>Successful:</strong> <?php echo $success_logs ?: 0; ?></p>
                    <p><strong>Failed:</strong> <?php echo $error_logs ?: 0; ?></p>
                </div>
            </div>
            
            <!-- Help Tab -->
            <div id="help" class="wpdl-tab-content">
                <div class="wpdl-card">
                    <h2>🚀 Setup Instructions</h2>
                    <ol>
                        <li><strong>Create Discord Webhook:</strong>
                            <ul>
                                <li>Go to your Discord server settings</li>
                                <li>Navigate to Integrations → Webhooks</li>
                                <li>Click "New Webhook"</li>
                                <li>Choose the channel for logs</li>
                                <li>Copy the webhook URL</li>
                            </ul>
                        </li>
                        <li><strong>Configure Plugin:</strong>
                            <ul>
                                <li>Paste the webhook URL in the settings</li>
                                <li>Customize bot name and avatar (optional)</li>
                                <li>Save settings</li>
                            </ul>
                        </li>
                        <li><strong>Test Connection:</strong>
                            <ul>
                                <li>Use the "Send Test Message" button</li>
                                <li>Check your Discord channel for the test message</li>
                            </ul>
                        </li>
                    </ol>
                </div>
                
                <div class="wpdl-card">
                    <h2>📝 Logged Events</h2>
                    <h3>WordPress Events:</h3>
                    <ul>
                        <li>👤 User registrations, logins, logouts</li>
                        <li>📝 Post publications, updates, deletions</li>
                        <li>💬 Comments posted, approved, deleted</li>
                        <li>🔌 Plugin activations/deactivations</li>
                        <li>🎨 Theme changes</li>
                        <li>🔄 Core updates</li>
                    </ul>
                    
                    <?php if (class_exists('WooCommerce')): ?>
                    <h3>WooCommerce Events:</h3>
                    <ul>
                        <li>🛒 New orders and status changes</li>
                        <li>💰 Payment completions and refunds</li>
                        <li>📦 Product additions and updates</li>
                        <li>👥 Customer registrations</li>
                        <li>🛍️ Cart activities</li>
                        <li>🎟️ Coupon usage</li>
                        <li>📊 Stock changes</li>
                    </ul>
                    <?php endif; ?>
                </div>
                
                <div class="wpdl-card">
                    <h2>🔧 Troubleshooting</h2>
                    <h3>Common Issues:</h3>
                    <ul>
                        <li><strong>ENOTFOUND Error:</strong> Check webhook URL format and internet connection</li>
                        <li><strong>403/404 Errors:</strong> Verify webhook URL is correct and active</li>
                        <li><strong>No Messages:</strong> Ensure webhook URL is saved and events are triggering</li>
                        <li><strong>Rate Limiting:</strong> Discord may limit message frequency</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    // Missing method for wp_die logging
    public function log_wp_die($message) {
        $this->send_to_discord([
            'title' => '💥 WordPress Error',
            'description' => "WordPress encountered an error: **{$message}**",
            'color' => 15158332, // Red
            'fields' => [
                ['name' => 'Error', 'value' => substr($message, 0, 200), 'inline' => false],
                ['name' => 'Time', 'value' => current_time('mysql'), 'inline' => true],
                ['name' => 'User IP', 'value' => $this->get_user_ip(), 'inline' => true]
            ]
        ]);
    }
    
    // Database table creation
    public function create_log_table() {
        global $wpdb;
        
        $table_name = $this->log_table;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            type varchar(20) NOT NULL,
            message text NOT NULL,
            details longtext,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_id bigint(20) UNSIGNED,
            PRIMARY KEY (id),
            KEY type (type),
            KEY timestamp (timestamp),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
new WP_Discord_Logger();

// Activation hook
register_activation_hook(__FILE__, 'wp_discord_logger_activate');
function wp_discord_logger_activate() {
    // Set default options
    add_option('wpdl_bot_name', 'WordPress Logger');
    
    // Create database table
    $logger = new WP_Discord_Logger();
    $logger->create_log_table();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_discord_logger_deactivate');
function wp_discord_logger_deactivate() {
    // Cleanup if needed
}
?>
