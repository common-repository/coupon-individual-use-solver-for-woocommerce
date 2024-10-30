<?php

namespace LWSDEV\COUPONCONFLICTS;

// don't call the file directly
if (!defined('ABSPATH')) exit();

/**	Add a new panel in coupon edition screen about this plugin.
 *	It is just some kind of inline documentation. */
class Panel
{
	const SLUG   = 'lws_coupon_cat';
	const TAB    = 'lwsdev_exclusive_coupon_data';
	const SCRIPT = 'lws-coupon-exclusive';

	static public function install()
	{
		$me = new self();
		\add_filter('woocommerce_coupon_data_tabs', array($me, 'addTab'), 10, 1);
		\add_action('woocommerce_coupon_data_panels', array($me, 'output'), 10, 2);
		\add_action('admin_enqueue_scripts', array($me, 'registerScripts'));
	}

	public function registerScripts($hook)
	{
		if (\in_array($hook, array('post.php', 'post-new.php'))) {
			\wp_register_script(self::SCRIPT, LWSDEV_COUPON_CONFLICTS_JS . '/panel.js', array('jquery'), LWSDEV_COUPON_CONFLICTS_VERSION, true);
		}
	}

	public function addTab($tabs)
	{
		$tabs[self::TAB] = array(
			'label'  => __('Exclusive categories', 'lws-cius'),
			'target' => self::TAB,
			'class'  => 'lwsdev_exclusive_panel',
		);
		return $tabs;
	}

	public function output($couponId, $coupon)
	{
		\wp_enqueue_script(self::SCRIPT);
		$allowedHTML = \wp_kses_allowed_html('data');
?>
		<div id='lwsdev_exclusive_coupon_data' class='panel woocommerce_options_panel' data-description='<?php esc_attr_e("Check this box if you want to enable categories restrictions for this coupon.", 'lws-cius') ?>'>
			<div class='options_group copy'>
				<!-- Rest of content appended here by JS -->
			</div>
			<div class='options_group'>
				<p class='form-field inline-doc' style='font-weight:bold;'><?php esc_html_e("You can use coupon categories to allow some coupons to be used together while other won't be compatible.", 'lws-cius'); ?></p>
				<p class='form-field inline-doc'><?php echo sprintf(\wp_kses(_x('If you want to use categories restrictions, please <b>check</b> the %2$s box <b>and</b> select an %1$s in the meta-box on your right.', '1: excl. cat; 2: ind. use only', 'lws-cius'), $allowedHTML), '<i>' . \esc_html(__('“Exclusive category”', 'lws-cius')) . '</i>', '<i>' . \esc_html(__('“Individual use only”', 'lws-cius')) . '</i>'); ?></p>
				<p class='form-field inline-doc'><?php echo '<b>' . \esc_html(__("Coupons inside a same category are mutually exclusives.", 'lws-cius')). '</b> ' . \esc_html(__("Two coupons with different categories can be added to the cart together", 'lws-cius')); ?></p>
				<p class='form-field inline-doc'><?php echo sprintf(\esc_html(_x('It means that the %1$s feature %2$scan accept exceptions%3$s.', '1: ind. use only, 2-3: <b>-</b>', 'lws-cius')), '<i>' . \esc_html(__('“Individual use only”', 'lws-cius')) . '</i>', '<b>', '</b>'); ?></p>
				<p class='form-field inline-doc'><?php echo sprintf(\esc_html(_x('If you don\'t select an %1$s, %2$s will work as usual. It won\'t allow any other coupon on the cart, whatever categories you selected in the other coupons.', '1: excl. cat; 2: ind. use only', 'lws-cius')), '<i>' . \esc_html(__('“Exclusive category”', 'lws-cius')) . '</i>', '<i>' . \esc_html(__('“Individual use only”', 'lws-cius')) . '</i>'); ?></p>
				<p class='form-field inline-doc'><?php echo sprintf(\esc_html(__("If %s is unchecked, no exclusivity is tested until another coupon with %s is added to cart.", 'lws-cius')), '<i>' . \esc_html(__('“Individual use only”', 'lws-cius')) . '</i>', '<i>' . \esc_html(__('“Individual use only”', 'lws-cius')) . '</i>'); ?></p>
			</div>

			<div class='options_group'>
				<p class='form-field inline-doc'><label><?php esc_html_e("Use-cases", 'lws-cius'); ?></label></p>

				<style type="text/css">
					.ta1 {
						margin: -5ex 10px 2em 100px;
						border-collapse: collapse;
						text-align: center;
						width: calc(100% - 110px);
					}

					.ta1 tbody tr:hover {
						background: #D3D3D3;
					}

					.ta1 td {
						padding: 3px;
						border-left: solid 1px darkgrey;
					}

					.ta1 tr td.ce0 {
						border-left: none;
					}

					.ta1 thead {
						font-weight: bold;
					}

					.ta1 thead .ce2 {
						border-bottom: solid 2px black;
						font-style: italic;
					}

					.ta1 td.ce12 {
						border-left: solid 2px black;
					}
				</style>
				<table border="0" lspacing="0" lpadding="0" class="ta1">
					<thead>
						<tr class="ro1">
							<td colspan="2" class="ce0"><?php esc_html_e("Coupon 1", 'lws-cius'); ?></td>
							<td colspan="2" class=""><?php esc_html_e("Coupon 2", 'lws-cius'); ?></td>
							<td colspan="2" class=""><?php esc_html_e("Coupon 3", 'lws-cius'); ?></td>
							<td colspan="3" class="ce12"><?php esc_html_e("Cart", 'lws-cius'); ?></td>
						</tr>
						<tr class="ro1">
							<td class="ce2 ce0"><?php esc_html_e("Ind. Use", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Category", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Ind. Use", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Category", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Ind. Use", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Category", 'lws-cius'); ?></td>
							<td class="ce2 ce12"><?php esc_html_e("Coupon 1", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Coupon 2", 'lws-cius'); ?></td>
							<td class="ce2"><?php esc_html_e("Coupon 3", 'lws-cius'); ?></td>
						</tr>
					</thead>
					<tbody>
						<tr class="ro1">
							<td class="ce0">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-yes">✔</td>
							<td class="res-yes">✔</td>
						</tr>
						<tr class="ro1">
							<td class="ce0">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">A</td>
							<td class="">☐</td>
							<td class="">A</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-yes">✔</td>
							<td class="res-yes">✔</td>
						</tr>
						<tr class="ro1">
							<td class="ce0">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">A</td>
							<td class="">☐</td>
							<td class="">B</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-yes">✔</td>
							<td class="res-yes">✔</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-no">✗</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td>A</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-no">✗</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td>A</td>
							<td class="">☐</td>
							<td>A</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-no">✗</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td>A</td>
							<td class="">☐</td>
							<td class="">B</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-yes">✔</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td class="">-</td>
							<td class="">☑</td>
							<td class="">-</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-no">✗</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td>A</td>
							<td class="">☑</td>
							<td>A</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-no">✗</td>
							<td class="res-no">✗</td>
						</tr>
						<tr class="ro1">
							<td class=" ce0">☑</td>
							<td>A</td>
							<td class="">☑</td>
							<td class="">B</td>
							<td class="">☐</td>
							<td class="">-</td>
							<td class="ce12 res-yes">✔</td>
							<td class="res-yes">✔</td>
							<td class="res-no">✗</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div class='options_group'>
				<p class='form-field inline-doc'><label><?php esc_html_e("Compatibility", 'lws-cius'); ?></label></p>
				<p class='form-field inline-doc'><?php echo sprintf(\esc_html(__("You can combine this plugin with %s to have a loyalty program where customers can use their loyalty points in addition to some coupons but not others.", 'lws-cius')), "<a href='https://wordpress.org/plugins/woorewards/' target='_blank'><b>MyRewards</b></a>"); ?></p>
			</div>
		</div>
<?php
	}
}
