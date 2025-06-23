# WP Discord Logger

**Professional WordPress and WooCommerce activity logger that sends real-time events to Discord via webhook.**

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)

## 🚀 Features

### WordPress Event Monitoring
- 👤 **User Activities**: Registration, login/logout, profile updates, deletions
- 📝 **Content Management**: Post publications, updates, deletions, status changes
- 💬 **Comment System**: New comments, status changes, deletions
- 🔌 **Plugin Management**: Activations, deactivations
- 🎨 **Theme Changes**: Theme switching notifications
- 🔄 **Core Updates**: WordPress version updates
- 💥 **Error Logging**: Critical WordPress errors

### WooCommerce Integration
- 🛒 **Order Management**: New orders, status changes, refunds
- 💰 **Payment Processing**: Payment completions, failures
- 📦 **Product Management**: New products, updates, inventory changes
- 👥 **Customer Activities**: New registrations, address updates
- 🛍️ **Shopping Cart**: Add to cart, item removals
- 🎟️ **Coupon Usage**: Coupon applications and usage tracking
- 📊 **Stock Management**: Stock level changes and alerts

### Professional Admin Interface
- ⚙️ **Settings Management**: Easy webhook configuration
- 📊 **Activity Logs**: Comprehensive logging with search and filtering
- 🔧 **Tools**: Settings import/export, connection testing
- 📈 **Statistics**: Success/failure rates and activity metrics
- 🎯 **Dashboard Widget**: Quick activity overview
- ❓ **Help Documentation**: Complete setup and troubleshooting guide

## 📋 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher
- **WooCommerce**: 3.0+ (optional, for e-commerce features)
- **Discord Server**: With webhook permissions

## 🛠️ Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file
2. Go to **Plugins > Add New > Upload Plugin**
3. Choose the ZIP file and click **Install Now**
4. Activate the plugin

### Method 2: Manual Installation
1. Extract the plugin files
2. Upload the `wp-discord-logger` folder to `/wp-content/plugins/`
3. Activate the plugin through the WordPress admin

### Method 3: WP-CLI
```bash
wp plugin install wp-discord-logger.zip --activate
```

## ⚙️ Configuration

### 1. Create Discord Webhook
1. Open your Discord server settings
2. Navigate to **Integrations → Webhooks**
3. Click **"New Webhook"**
4. Choose the channel for notifications
5. Copy the webhook URL

### 2. Configure Plugin
1. Go to **Settings → Discord Logger**
2. Paste your webhook URL
3. Customize bot name and avatar (optional)
4. Save settings
5. Test the connection

### 3. Verify Setup
- Use the **"Send Test Message"** button
- Check your Discord channel for the test notification
- Monitor the activity logs for any issues

## 🎯 Usage

Once configured, the plugin automatically monitors and logs:

### WordPress Events
- User registrations and login activities
- Content publishing and modifications
- Comment interactions
- Plugin and theme changes
- System updates and errors

### WooCommerce Events (if installed)
- Order lifecycle management
- Payment processing
- Product catalog changes
- Customer interactions
- Shopping cart activities
- Promotional code usage

## 🔧 Advanced Configuration

### Custom Bot Settings
```php
// Customize bot appearance
update_option('wpdl_bot_name', 'Your Custom Bot Name');
update_option('wpdl_avatar_url', 'https://your-domain.com/avatar.png');
```

### Event Filtering
The plugin provides hooks for custom event filtering:

```php
// Disable specific events
add_filter('wpdl_log_user_login', '__return_false');

// Custom event data
add_filter('wpdl_embed_data', function($data, $event_type) {
    // Modify embed data before sending
    return $data;
}, 10, 2);
```

## 📊 Monitoring & Analytics

### Activity Logs
- Real-time event tracking
- Success/failure monitoring
- Detailed error reporting
- Export capabilities

### Dashboard Widget
- Quick activity overview
- Recent events summary
- System health indicators

### Statistics
- Total messages sent
- Success/failure rates
- Performance metrics

## 🔒 Security & Privacy

### Data Protection
- All webhook communications use HTTPS
- User data is sanitized before transmission
- Local activity logs for troubleshooting
- No sensitive data stored externally

### Privacy Compliance
- Configurable data retention
- User consent mechanisms
- GDPR-friendly logging options
- Transparent data handling

### Security Features
- Nonce verification for all AJAX requests
- Capability checks for admin functions
- Input sanitization and validation
- SQL injection prevention

## 🛠️ Troubleshooting

### Common Issues

**Connection Failed**
- Verify webhook URL format
- Check Discord server permissions
- Test internet connectivity

**Missing Events**
- Confirm plugin activation
- Check event hook compatibility
- Review activity logs for errors

**Rate Limiting**
- Discord limits message frequency
- Plugin includes automatic retry logic
- Monitor for 429 error responses

### Debug Mode
Enable WordPress debug mode for detailed logging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contributing

We welcome contributions! Please:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup
```bash
git clone https://github.com/shivvx/wp-discord-logger.git
cd wp-discord-logger
# Set up local WordPress development environment
```

## 📝 Changelog

### Version 1.0.0
- Initial release
- Complete WordPress event monitoring
- Full WooCommerce integration
- Professional admin interface
- Comprehensive documentation

## 📄 License

This plugin is licensed under the GNU General Public License v2 or later.

**Copyright (C) 2024 Shivam Kumar**

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

See [LICENSE.txt](LICENSE.txt) for full license details.

## 👨‍💻 Author

**Shivam Kumar**
- LinkedIn: [https://www.linkedin.com/in/shivvx/](https://www.linkedin.com/in/shivvx/)
- Professional WordPress Developer
- Discord Integration Specialist

## 🙏 Acknowledgments

- WordPress community for excellent documentation
- Discord for providing robust webhook API
- WooCommerce team for comprehensive hook system
- Beta testers and early adopters

## 📞 Support

For support, feature requests, or bug reports:

1. Check the [troubleshooting guide](#-troubleshooting)
2. Review existing [GitHub issues](https://github.com/shivvx/wp-discord-logger/issues)
3. Contact via [LinkedIn](https://www.linkedin.com/in/shivvx/)

---

**Made with ❤️ for the WordPress community**
