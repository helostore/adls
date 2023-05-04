# Genesis 
```
// npm install -g polymer-cli

```

# Setup
```
// Initially import platforms, editions, versions
php hsw.php --dispatch=adls_setup.platforms

// Update latest platforms versions in DB, from external source
php hsw.php --dispatch=adls_setup.platforms_sync
php hsw.php --dispatch=adls_setup.platforms_sync --platform=cscart
php hsw.php --dispatch=adls_setup.platforms_sync --platform=wordpress
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


#### Update 2023-05-04
```shell


ALTER TABLE `cscart_adls_releases`
ADD COLUMN `changeLog`  mediumtext NULL AFTER `status`;

```
