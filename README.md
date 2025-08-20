# Shoplic Logger

A powerful tag-based logging system for WordPress that gives you complete control over which logs appear in your console.

![Main Screenshot Placeholder](screenshots/main-screenshot.png)

## Features

- **Tag-based Control**: All logs are `@off` by default - turn on only what you need
- **Smart Filtering**: Filter logs by plugin, file, class, or custom tags
- **Multiple Log Levels**: LOG, ERROR, INFO, WARNING, DEBUG
- **Zero Performance Impact**: Disabled logs have minimal overhead
- **Live Toggle**: Enable/disable logs without code changes

## How It Works

![Demo GIF Placeholder](screenshots/demo.gif)

## Installation

1. Download and install the plugin
2. Activate through WordPress admin panel
3. Start adding logs to your code

## Usage

### PHP
```php
do_action('sl_log', 
    'ERROR', 
    'woocommerce', 
    __FILE__, 
    __CLASS__, 
    __METHOD__, 
    'Payment failed', 
    ['order_id' => 123], 
    ['slt#payment@off', 'slt#critical@off']
);
```

### JavaScript
```javascript
window.sl && window.sl(
    'LOG',
    'my-plugin',
    '/path/to/file.js',
    'ClassName',
    'methodName',
    'User action tracked',
    { userId: 456 },
    ['slt#tracking@off', 'slt#user@off']
);
```

## Tag Management

![Tag Management Screenshot Placeholder](screenshots/tag-management.png)

### Enable specific tags
Toggle tags from `@off` to `@on` in your code to see related logs.

### Reset all logs
```bash
find . -type f \( -name "*.php" -o -name "*.js" \) -exec sed -i 's/slt#\(.*\)@on/slt#\1@off/g' {} +
```

## Screenshots

### Console Output
![Console Screenshot Placeholder](screenshots/console-output.png)

### Admin Interface
![Admin Interface Placeholder](screenshots/admin-interface.png)

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

GPL v2 or later

## Support

For issues and feature requests, please use the GitHub issues page.