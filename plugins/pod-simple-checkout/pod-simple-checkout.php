<?php
/**
 * Plugin Name: POD Simple Checkout
 * Description: Buy Now, progressive AJAX cart and a clean native WooCommerce checkout. No gateway changes.
 * Version: 1.1.0
 * Requires at least: 7.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Text Domain: pod-simple-checkout
 * WC requires at least: 11.3
 * WC tested up to: 11.3
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'POD_SC_VERSION', '1.1.0' );
define( 'POD_SC_FILE', __FILE__ );
define( 'POD_SC_PATH', plugin_dir_path( __FILE__ ) );
define( 'POD_SC_URL', plugin_dir_url( __FILE__ ) );

add_action( 'before_woocommerce_init', static function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', POD_SC_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', POD_SC_FILE, true );
	}
} );

require_once POD_SC_PATH . 'includes/class-settings.php';
require_once POD_SC_PATH . 'includes/class-cart.php';
require_once POD_SC_PATH . 'includes/class-checkout.php';
require_once POD_SC_PATH . 'includes/class-shopping-ux.php';
require_once POD_SC_PATH . 'includes/class-plugin.php';
add_action( 'plugins_loaded', array( 'POD_SC_Plugin', 'boot' ), 20 );
