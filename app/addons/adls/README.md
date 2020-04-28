# Genesis 
```
// npm install -g polymer-cli

```

# Setup
```
// Initially import platforms, editions, versions
php hsw.php --dispatch=adls_setup.platforms

// Synchronize import platforms versions (only WP supported for now)
php hsw.php --dispatch=adls_setup.platforms_sync
```

# Configuration
```
define('ADLS_SUBSCRIPTIONS_NO_EMAILS', true);
define('ADLS_MAGIC_TOKEN', 'your-secret-token');
define('ADLS_MAGIC_LICENSE_KEY', 'your-secret-magic-license');
define('ADLS_SKIP_STRICT_DOMAIN_VALIDATION', true);
define('ADLS_API_TOKEN_EXPIRATION', 1);
define('ADLS_SKIP_PRODUCT_VERSION_VALIDATION', true);

define('ADLS_RELEASE_PATH', '/home/helostore/repository/helostore');
define('ADLS_SOURCE_PATH', '/home/helostore/repository/helostore');

define('HCAPTCHA_API_KEY', '');
define('HCAPTCHA_SECRET_KEY', '');
define('HCAPTCHA_REFERRAL_CODE', '');
define('HCAPTCHA_SITE_KEY_SINK', '');
```