# MotionFlow - Digital Commerce, Redefined

![MotionFlow Banner](assets/motionflow-banner.png)

MotionFlow is a premium WooCommerce plugin that transforms the digital commerce experience with advanced filtering, product displays, and interactions.

## Features

- **AJAX-based Filtering**: Near-instant filtering with support for large catalogs
- **Interactive Product Grid**: Customizable and responsive product layouts
- **Drag-and-Drop Cart**: Intuitive interaction for adding products to cart
- **Quick-View Modal**: View product details without leaving the page
- **Mobile-Optimized**: Designed for touch interactions on all devices
- **Performance-Focused**: Built for speed, even with thousands of products

## Requirements

- WordPress 5.6 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `motionflow` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings through the MotionFlow menu in your WordPress admin

## Documentation

For detailed documentation, visit [motionflow.com/docs](https://motionflow.com/docs)

## Shortcodes

MotionFlow provides several shortcodes to display components on your store:

### Main Shortcode

```
[motionflow layout="default" filters="yes" grid="yes" cart="yes"]
```

Parameters:
- `layout`: The layout to use (default, compact, modern)
- `filters`: Whether to show filters (yes, no)
- `grid`: Whether to show product grid (yes, no)
- `cart`: Whether to show cart (yes, no)

### Individual Component Shortcodes

**Filters Only**
```
[motionflow_filters layout="default"]
```

**Grid Only**
```
[motionflow_grid layout="default" columns_desktop="4" columns_tablet="3" columns_mobile="2"]
```

**Cart Only**
```
[motionflow_cart layout="default"]
```

## Development

### File Structure

```
motionflow/
├── admin/            - Admin interface files
├── includes/         - Core plugin functionality
│   ├── api/          - API classes
│   ├── display/      - Display classes
│   ├── filters/      - Filter classes
│   ├── integrations/ - Integration classes
├── languages/        - Translation files
├── public/           - Public-facing files
│   ├── css/          - Stylesheets
│   ├── js/           - JavaScript files
│   ├── partials/     - Template partials
├── vendor/           - Third-party dependencies
├── motionflow.php    - Main plugin file
└── uninstall.php     - Uninstall file
```

### Building from Source

```bash
# Clone the repository
git clone https://github.com/your-username/motionflow.git

# Install dependencies
composer install

# Build assets
npm install
npm run build
```

## Contributing

Contributions are welcome! Please read our [contribution guidelines](CONTRIBUTING.md) before submitting a pull request.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support, please email support@motionflow.com or visit [motionflow.com/support](https://motionflow.com/support)

---

*MotionFlow - Transform Your WooCommerce Store*