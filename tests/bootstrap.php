<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Babytuch_Plugin
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );


// Determine which version of WooCommerce we're testing against.
$wc_version    = getenv('WC_VERSION') ?: '5.5';
$target_suffix = preg_match( '/\d+\.\d+/', $wc_version, $match ) ? $match[0] : 'latest';
$target_dir    = dirname( __DIR__ ) . '/vendor/woocommerce/woocommerce-src-' . $target_suffix;

// Determine where to load the sqrip plugin from
// assumes that sqrip is in a folder next to this plugin (e.g. the wp plugins folder)
$sqrip_file    = dirname(__DIR__, 2) . '/sqrip-swiss-qr-invoice/sqrip-woocommerce.php';

// Attempt to install the given version of WooCommerce if it doesn't already exist.
if ( ! is_dir( $target_dir ) ) {
	try {
		exec(
			sprintf(
				'%1$s/bin/install-woocommerce.sh %2$s',
				__DIR__,
				escapeshellarg( $wc_version )
			),
			$output,
			$exit
		);

		if (0 !== $exit) {
			throw new \RuntimeException( sprintf( 'Received a non-zero exit code: %1$d', $exit ) );
		}
	} catch ( \Throwable $e ) {
		printf( '\033[0;31mUnable to install WooCommerce@%s\033[0;0m' . PHP_EOL, $wc_version );
		printf( 'Please run `sh tests/bin/install-woocommerce.sh %1$s` manually.' . PHP_EOL, $wc_version );

		exit( 1 );
	}
}

// Locate the WooCommerce test bootstrap file for this release.
$_bootstrap = $target_dir . '/tests/legacy/bootstrap.php';

if ( ! file_exists( $_bootstrap ) ) {
	printf(
		"\033[0;31mUnable to find the the test bootstrap file for WooCommerce@$wc_version at $_bootstrap, aborting.\033[0;m\n"
	);
	exit( 1 );
}



if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/babytuch-plugin.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Include WooCommerce Dependencies
require_once dirname( __DIR__ ) . '/vendor/autoload.php';
require_once $_bootstrap;

// Include sqrip
require_once $sqrip_file;


// Activate sqrip plugin
tests_add_filter('option_active_plugins', function($activePlugins) {
	return array_unique(
		array_merge([
			'sqrip-swiss-qr-invoice/sqrip-woocommerce.php',
		], $activePlugins ?: [])
	);
});


echo esc_html( sprintf(
	/* Translators: %1$s is the WooCommerce release being loaded. */
		__( 'Using WooCommerce %1$s.', 'woocommerce-custom-orders-table' ),
		WC_VERSION
	) ) . PHP_EOL;


require_once __DIR__ . '/BT_TestCase.php';


// Start up the WP testing environment.
//require $_tests_dir . '/includes/bootstrap.php';

// Activate the plugin *after* WooCommerce has been bootstrapped.
\Inc\Base\Activate::activate();