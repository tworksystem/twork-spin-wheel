# T-Work Spin Wheel System

<div align="center">

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

**Professional Spin Wheel Management System for WordPress**

A comprehensive, enterprise-grade spin wheel plugin with REST API, advanced analytics, and mobile app integration capabilities.

[Features](#-features) • [Installation](#-installation) • [Documentation](#-documentation) • [API Reference](#-rest-api-endpoints) • [Contributing](#-contributing)

</div>

---

## 📋 Table of Contents

- [Overview](#-overview)
- [Features](#-features)
- [Requirements](#-requirements)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [REST API Endpoints](#-rest-api-endpoints)
- [Shortcodes](#-shortcodes)
- [Admin Interface](#-admin-interface)
- [Security](#-security)
- [Performance](#-performance)
- [Development](#-development)
- [Contributing](#-contributing)
- [License](#-license)

---

## 🎯 Overview

T-Work Spin Wheel System is a professional WordPress plugin designed for creating engaging spin wheel campaigns with comprehensive management tools. Built with enterprise-grade security, performance optimization, and mobile-first architecture.

### Key Highlights

- 🎡 **Complete Spin Wheel System** - Full-featured wheel with customizable prizes and animations
- 🔌 **REST API** - Production-ready API endpoints for mobile app integration
- 📊 **Advanced Analytics** - Real-time statistics, charts, and comprehensive reporting
- 🎨 **Professional Admin Interface** - Intuitive 11-tab management panel
- 🔒 **Enterprise Security** - Rate limiting, SQL injection protection, XSS prevention
- ⚡ **High Performance** - Object caching, query optimization, scheduled tasks
- 📱 **Mobile Ready** - Optimized for mobile apps with JSON responses

---

## 🚀 Features

### Core Features

| Feature | Description |
|---------|-------------|
| **🎡 Spin Wheel System** | Complete spin wheel with customizable prizes, colors, animations, and sounds |
| **🔌 REST API** | Mobile app ready REST API endpoints with rate limiting and authentication |
| **🎨 Admin Interface** | Comprehensive admin panel with 11 organized tabs for complete control |
| **📊 Analytics Dashboard** | Real-time statistics, interactive charts, and detailed reporting |
| **🎁 Multiple Prize Types** | Support for points, coupons, products, and custom messages |
| **⚙️ Highly Customizable** | Colors, animations, sounds, limits, probability weights, and more |
| **🔒 Enterprise Security** | Rate limiting, SQL injection protection, input validation, XSS prevention |
| **📱 Mobile Ready** | Optimized for mobile app integration with JSON responses |

### Advanced Features

- **📤 Export/Import** - CSV history export, JSON prizes/settings import/export
- **📧 Email Notifications** - Automated emails on spin results with customizable templates
- **🎯 Shortcodes** - `[spin_wheel]`, `[spin_wheel_stats]`, `[spin_wheel_history]`
- **⚡ AJAX Handlers** - Real-time updates, bulk actions, dynamic loading
- **📝 Logging System** - Comprehensive logging with database storage and retention
- **💾 Cache Management** - Object cache integration for optimal performance
- **🧹 Scheduled Tasks** - Daily log cleanup, analytics updates, background processing
- **📈 Statistics** - Prize distribution, top users, daily/weekly/monthly stats
- **🎨 Frontend Assets** - Modern CSS and JavaScript for beautiful frontend display

### Enterprise Features

- **🔗 Webhooks Integration** - External service integration with event-based triggers
- **📋 Prize Templates** - Pre-configured templates for quick prize creation
- **🔄 Bulk Operations** - Bulk delete, activate/deactivate, and update operations
- **🏥 Health Check** - System diagnostics, database health, cache status
- **💾 Backup/Restore** - Full system backup and restore functionality
- **🏷️ Prize Categories** - Organize prizes with categories, colors, and icons
- **📝 Custom Fields** - Flexible custom fields per prize for extended data
- **🧪 A/B Testing** - Variant assignment and conversion tracking
- **📊 Conversion Tracking** - Event logging and conversion rate calculation
- **📚 API Documentation** - Auto-generated API documentation endpoint

---

## 📦 Requirements

- **WordPress**: 5.0 or higher
- **PHP**: 7.4 or higher (PHP 8.0+ recommended)
- **WooCommerce**: 5.0+ (required for coupon prizes)
- **MySQL**: 5.6+ or MariaDB 10.0+

---

## 🔧 Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin ZIP file
2. Navigate to **Plugins → Add New** in WordPress admin
3. Click **Upload Plugin** and select the ZIP file
4. Click **Install Now** and then **Activate**

### Method 2: Manual Installation

1. Upload the `twork-spin-wheel` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Navigate to **Spin Wheel** in the admin menu to configure

### Method 3: Git Clone

```bash
cd wp-content/plugins/
git clone https://github.com/tworksystem/twork-spin-wheel.git
```

---

## 🚀 Quick Start

1. **Activate the Plugin**
   - Go to **Plugins** → Find **T-Work Spin Wheel System** → Click **Activate**

2. **Configure Your First Wheel**
   - Navigate to **Spin Wheel** in the admin menu
   - Go to the **Settings** tab
   - Configure wheel title, description, colors, and limits
   - Set points per spin and daily limits

3. **Add Prizes**
   - Go to the **Prizes** tab
   - Click **Add New Prize**
   - Configure prize type, name, probability weight, and color
   - Save your prize

4. **Display on Frontend**
   - Use shortcode: `[spin_wheel wheel_id="1"]`
   - Or integrate via REST API for mobile apps

5. **View Analytics**
   - Go to the **Analytics** tab
   - View real-time statistics and charts

---

## 🔌 REST API Endpoints

### Base URL
```
/wp-json/twork/v1/spin-wheel
```

### Endpoints

#### Get Wheel Configuration
```http
GET /wp-json/twork/v1/spin-wheel/config/{user_id}
```

**Response:**
```json
{
  "wheel": {
    "id": 1,
    "title": "Lucky Spin",
    "points_per_spin": 100,
    "daily_limit": 3
  },
  "prizes": [...],
  "user_spins_today": 2
}
```

#### Process Spin
```http
POST /wp-json/twork/v1/spin-wheel/spin
Content-Type: application/json

{
  "user_id": 123,
  "wheel_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "prize": {
    "id": 5,
    "name": "100 Points",
    "type": "points",
    "value": 100
  },
  "spins_remaining": 1
}
```

#### Get Spin History
```http
GET /wp-json/twork/v1/spin-wheel/prizes?user_id=123&page=1&per_page=20
```

#### Get Banner
```http
GET /wp-json/twork/v1/spin-wheel/banner
```

#### API Documentation
```http
GET /wp-json/twork/v1/spin-wheel/docs
```

### Authentication

For authenticated requests, include WordPress nonce or use application passwords:

```http
X-WP-Nonce: {nonce}
```

### Rate Limiting

- Default: 10 requests per minute per IP/user
- Configurable in **Advanced** tab
- Returns `429 Too Many Requests` when exceeded

---

## 🎯 Shortcodes

### Display Spin Wheel
```php
[spin_wheel wheel_id="1" width="320" height="320"]
```

**Attributes:**
- `wheel_id` - Wheel ID to display (required)
- `width` - Wheel width in pixels (default: 320)
- `height` - Wheel height in pixels (default: 320)

### Display User Statistics
```php
[spin_wheel_stats user_id="123" show_total_spins="yes" show_points_spent="yes"]
```

**Attributes:**
- `user_id` - User ID (required)
- `show_total_spins` - Show total spins (yes/no)
- `show_points_spent` - Show points spent (yes/no)

### Display Spin History
```php
[spin_wheel_history user_id="123" limit="10"]
```

**Attributes:**
- `user_id` - User ID (required)
- `limit` - Number of entries to display (default: 10)

---

## 🎨 Admin Interface

The plugin provides a comprehensive admin interface with 11 organized tabs:

### 1. Settings Tab
Configure wheel appearance, limits, colors, animations, and visual effects.

### 2. Prizes Tab
Manage prizes with probability weights, colors, icons, and bulk operations.

### 3. History Tab
View all spin history with filtering, pagination, and export functionality.

### 4. Analytics Tab
Real-time statistics dashboard with charts, prize distribution, and top users.

### 5. Export/Import Tab
Export data to CSV/JSON, import prizes, and manage data migration.

### 6. Advanced Tab
Cache management, logging settings, rate limiting, and email notifications.

### 7. Templates Tab
Pre-configured prize templates for quick prize creation.

### 8. Webhooks Tab
External service integration with event-based triggers and authentication.

### 9. Health Check Tab
System diagnostics, database health, cache status, and API testing.

### 10. Backup/Restore Tab
Full system backup and restore functionality with JSON export/import.

### 11. API Docs Tab
Auto-generated API documentation with endpoint details and examples.

---

## 🔒 Security

The plugin implements enterprise-grade security measures:

### Security Features

- ✅ **Rate Limiting** - Prevents API abuse with configurable limits
- ✅ **SQL Injection Prevention** - All queries use prepared statements
- ✅ **XSS Protection** - Output escaping and input sanitization
- ✅ **Input Validation** - Comprehensive validation for all inputs
- ✅ **Nonce Verification** - CSRF protection on all forms
- ✅ **Capability Checks** - Permission validation for admin functions
- ✅ **IP Address Logging** - Security audit trail
- ✅ **Security Event Logging** - Threat detection and monitoring

### Best Practices

- All user inputs are sanitized and validated
- Database queries use `$wpdb->prepare()`
- Output is escaped using WordPress functions
- Nonces are verified on all form submissions
- User capabilities are checked before admin operations

---

## ⚡ Performance

Optimized for high performance with enterprise-grade features:

### Performance Features

- ✅ **Object Cache Integration** - Reduces database queries
- ✅ **Database Query Optimization** - Efficient queries with proper indexes
- ✅ **Scheduled Background Tasks** - Daily cleanup and analytics updates
- ✅ **Lazy Loading** - Assets loaded only when needed
- ✅ **Cache Invalidation** - Smart cache updates on data changes
- ✅ **Efficient Database Indexes** - Optimized table structures

### Caching Strategy

- Wheel configurations cached for 1 hour
- User spin counts cached for 5 minutes
- Analytics data cached for 15 minutes
- Cache automatically invalidated on updates

---

## 🗄️ Database Structure

The plugin creates the following database tables:

| Table | Description |
|-------|-------------|
| `wp_twork_spin_wheels` | Wheel configurations |
| `wp_twork_spin_wheel_prizes` | Prize definitions |
| `wp_twork_spin_wheel_history` | Spin history records |
| `wp_twork_spin_wheel_analytics` | Analytics data |
| `wp_twork_spin_wheel_logs` | Log entries (optional) |

All tables include proper indexes for optimal query performance.

---

## 🛠️ Development

### Code Structure

```
twork-spin-wheel/
├── twork-spin-wheel.php    # Main plugin file
├── uninstall.php           # Uninstall script
├── includes/               # Core classes
│   ├── class-database.php
│   ├── class-rest-api.php
│   ├── class-admin.php
│   └── ...
├── assets/                 # Frontend assets
│   ├── css/
│   └── js/
└── languages/              # Translation files
```

### Coding Standards

- ✅ WordPress Coding Standards (WPCS) compliant
- ✅ PHPDoc documentation for all functions
- ✅ Proper error handling and exception management
- ✅ Modular architecture with separation of concerns
- ✅ Singleton pattern for main plugin class

### Hooks and Filters

The plugin provides various hooks for customization:

```php
// Filter wheel configuration
apply_filters('twork_spin_wheel_config', $config, $wheel_id, $user_id);

// Filter prize result
apply_filters('twork_spin_wheel_prize', $prize, $wheel_id, $user_id);

// Action after spin
do_action('twork_spin_wheel_after_spin', $prize, $wheel_id, $user_id);
```

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes.

### Version 1.0.0
- Initial release
- Complete spin wheel system
- REST API endpoints
- Admin interface with 11 tabs
- Analytics dashboard
- Export/Import functionality
- Security features
- Performance optimizations

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'feat: Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Commit Message Format

```
feat: 16012026 - Add new feature description
fix: 16012026 - Fix bug description
docs: 16012026 - Update documentation
```

---

## 📄 License

This plugin is licensed under the **GPL v2 or later**.

```
Copyright (C) 2024 T-Work System

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

---

## 👥 Author

**T-Work System**

- Website: [https://twork.com](https://twork.com)
- GitHub: [@tworksystem](https://github.com/tworksystem)

---

## 📞 Support

For support, feature requests, or bug reports:

- 📧 Email: support@twork.com
- 🐛 Issues: [GitHub Issues](https://github.com/tworksystem/twork-spin-wheel/issues)
- 📚 Documentation: [Full Documentation](https://github.com/tworksystem/twork-spin-wheel/wiki)

---

## ⭐ Acknowledgments

- Built with WordPress best practices
- Follows WordPress Coding Standards
- Optimized for performance and security
- Designed for enterprise use

---

<div align="center">

**Made with ❤️ by T-Work System**

[⬆ Back to Top](#t-work-spin-wheel-system)

</div>
