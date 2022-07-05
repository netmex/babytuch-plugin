# Installation

Make sure that you have composer v1.10.6 installed (` composer self-update 1.10.6`) It will not work with newer versions.

1. Run `composer install`
2. Create db called `wordpress_test`
3. Run `/bin/bash wp-content/plugins/babytuch-plugin/tests/bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1` to install WP test suite

# Building the Plugin
1. Run `composer archive --format=zip --file babytuch-plugin`
TODO: find a way to deploy it somewhere for automatic updates



# Pages
- `ReturnAndReplacements.php` - page where a customer can select whether to return (i.e. receive a refund) or replace (i.e. receive another product) the ordered product.
- `Replacements.php` - page where a customer can select a replacement product for all the products that were ordered
- `ReturnReceiving.php` - page where logistics can control the returned order and mark it as "ok" / "not ok"