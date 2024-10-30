<?php
/**
 * Plugin Name: Coupon Individual-use Solver for WooCommerce
 * Description: Improve the basic coupon individual use of WooCommerce. Manage coupon by category. New options added to the Coupon edition screen.
 * Plugin URI: https://plugins.longwatchstudio.com/lws-coupon-individual-use-solver/
 * Author: Long Watch Studio
 * Author URI: https://longwatchstudio.com
 * Version: 1.1.5
 * License: Copyright LongWatchStudio 2023
 * Text Domain: lws-cius
 * Domain Path: /languages
 * WC requires at least: 7.1.0
 * WC tested up to: 9.0
 *
 *
 */


// don't call the file directly
if( !defined( 'ABSPATH' ) ) exit();

/** That class holds the entire plugin. */
final class Lwsdev_CouponConflicts
{
	public static function init()
	{
		static $instance = false;
		if( !$instance )
		{
			$instance = new self();
			$instance->defineConstants();

			\add_action('before_woocommerce_init', function() {
				if (\class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
					\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
				}
			});

			\add_action('plugins_loaded', array($instance, 'load_plugin_textdomain'));
			$instance->install();
		}
		return $instance;
	}

	/** Read version from main plugin file.
	 *	@return string */
	public function v()
	{
		static $version = '';
		if( empty($version) ){
			if( !function_exists('get_plugin_data') ) require_once(ABSPATH . 'wp-admin/includes/plugin.php');
			$data = \get_plugin_data(__FILE__, false);
			$version = (isset($data['Version']) ? $data['Version'] : '0');
		}
		return $version;
	}

	/** Load translation file */
	function load_plugin_textdomain()
	{
		load_plugin_textdomain('lws-cius', FALSE, basename(dirname(__FILE__)) . '/languages/');
	}

	private function defineConstants()
	{
		define('LWSDEV_COUPON_CONFLICTS_VERSION' , '1.1.5');
		define('LWSDEV_COUPON_CONFLICTS_FILE'    , __FILE__);
		define('LWSDEV_COUPON_CONFLICTS_DOMAIN'  , 'lws-cius');

		define('LWSDEV_COUPON_CONFLICTS_PATH'    , dirname(LWSDEV_COUPON_CONFLICTS_FILE));
		define('LWSDEV_COUPON_CONFLICTS_INCLUDES', LWSDEV_COUPON_CONFLICTS_PATH);

		define('LWSDEV_COUPON_CONFLICTS_JS'      , plugins_url('', LWSDEV_COUPON_CONFLICTS_FILE));
	}

	private function install()
	{
		require_once LWSDEV_COUPON_CONFLICTS_INCLUDES . '/taxonomy.php';
		\LWSDEV\COUPONCONFLICTS\Taxonomy::install();
		require_once LWSDEV_COUPON_CONFLICTS_INCLUDES . '/bridge.php';
		\LWSDEV\COUPONCONFLICTS\Bridge::install();

		\add_action('woocommerce_init', array($this, 'afterInit'));
	}

	/** Instanciate plugin features.
	 *	Call this only if WC is active. */
	public function afterInit()
	{
		if (self::isCouponEnabled()) {
			require_once LWSDEV_COUPON_CONFLICTS_INCLUDES . '/panel.php';
			\LWSDEV\COUPONCONFLICTS\Panel::install();
			require_once LWSDEV_COUPON_CONFLICTS_INCLUDES . '/solver.php';
			\LWSDEV\COUPONCONFLICTS\Solver::install();
			require_once LWSDEV_COUPON_CONFLICTS_INCLUDES . '/couponlist.php';
			\LWSDEV\COUPONCONFLICTS\CouponList::install();
		}
	}

	static public function isCouponEnabled()
	{
		return 'yes' === \get_option('woocommerce_enable_coupons');
	}

	/**	Is WooCommerce installed and activated.
	 *	Could be sure only after hook 'plugins_loaded'.
	 *	@return bool is WooCommerce installed and activated. */
	static public function isWC()
	{
		return function_exists('wc');
	}
}

\Lwsdev_CouponConflicts::init();
