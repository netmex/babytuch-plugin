# Installation

Make sure that you have composer v1.10.6 installed (` composer self-update 1.10.6`) It will not work with newer versions.

1. Run `composer install`
2. Create db called `wordpress_test`
3. Run `/bin/bash wp-content/plugins/babytuch-plugin/tests/bin/install-wp-tests.sh wordpress_test root '' 127.0.0.1` to install WP test suite

# Building the Plugin
1. Run `composer archive --format=zip --file babytuch-plugin`
2. Run `.github/install-fonts.sh` which will install the fonts and remove any unused fonts

Tagging a new version on GitHub will automatically trigger a new build. 

# Pages
- `ReturnAndReplacements.php` - page where a customer can select whether to return (i.e. receive a refund) or replace (i.e. receive another product) the ordered product.
- `Replacements.php` - page where a customer can select a replacement product for all the products that were ordered
- `ReturnReceiving.php` - page where logistics can control the returned order and mark it as "ok" / "not ok"
- `OrderProcessing.php` - page where logistics can accept / acknowledge the shipping order after an order was paid and the pdf was sent to them via e-mail
- `OrderSending.php` - page where logistics marks the order as finished packing (and ready to be sent)

# WooCommerce Statuses
- **Pending:** Bestellung wurde erfasst [WooCommerce]
- **On-Hold:** Bestellung wurde noch nicht bezahlt [WooCommerce]
- **Failed:** Bezahlung ist fehlgeschlagen [WooCommerce]
- **Processing:** Bestellung wurde bezahlt und ist bereit zur Verarbeitung [WooCommerce]
- **Packing:** Bestellung ist im Verpackungsprozess [Custom]
- **Cancelled:** Bestellung wurde abgebrochen [WooCommerce]
- **Completed:** Bestellung wurde verarbeitet und versandt [WooCommerce]
- **Returning:** Ware ist auf dem Weg zurück vom Kunden [Custom]
- **Awaiting-return:** Ersatzbestellung wartet auf Rücksendung von Originalbestellung [Custom]
- **Return-received:** Rücksendung wurde von Logistik empfangen [Custom]
- **Action-required:** Bestellung benötigt manuellen Eingriff [Custom]
- **Refund-required:** Bestellung muss manuell zurückerstattet werden [Custom]
- **Refunded:** Bestellung wurde vollständig zurückerstattet [WooCommerce]
- **Partially-refunded:** Bestellung wurde teilweise zurückerstattet [Custom]
- **Replaced:** Ware wurde ersetzt (neue Bestellung ausgelöst) [Custom]

# Testing
1. Run `composer install`
2. Make sure you have MySQL installed in the CLI (check with running `mysql`)
3. Create MySQL db called `wordpress_test`
4. run `tests/bin/install-wp-tests.sh <db-name> <db-user> <db-pass>` e.g: `tests/bin/install-wp-tests.sh wordpress_test root "" 127.0.0.1 5.7.2`
5. run `./vendor/bin/phpunit tests` to run the tests