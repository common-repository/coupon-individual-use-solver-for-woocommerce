<?php
namespace LWSDEV\COUPONCONFLICTS;

// don't call the file directly
if( !defined( 'ABSPATH' ) ) exit();

/**	Add a column to show categories in shop_coupon table list.
 *	Add a bulk to add/remove category. */
class CouponList
{
	const SLUG = 'lws_coupon_cat';
	const POST_TYPE = 'shop_coupon';

	private $notices = array();

	static public function install()
	{
		$me = new self();
		$type = self::POST_TYPE;
		\add_filter("manage_{$type}_posts_columns", array($me, 'column'), 20);
		\add_filter("manage_{$type}_posts_custom_column", array($me, 'cell'), 10, 3);
		//~ \add_filter('default_hidden_columns', array($me, 'defaultHiddenColumns' ), 10, 2);
		\add_action('manage_posts_extra_tablenav', array($me, 'addAction'), 10, 1);
		\add_action('wp', array($me, 'checkAction'));
		\add_action('admin_notices', array($me, 'notices'));
	}

	protected function userCan()
	{
		return \current_user_can('assign_shop_coupon_terms');
	}

	protected function addNotice($message, $level)
	{
		$this->notices[] = array($message, $level);
	}

	public function notices()
	{
		foreach ($this->notices as $notice) {
			echo \wp_kses_post(sprintf('<div class="notice updated notice-%s"><p>%s</p></div>', \esc_attr($notice[1]), $notice[0]));
		}
	}

	public function checkAction()
	{
		global $post_type;
		if (self::POST_TYPE == $post_type && $this->userCan()) {
			$nonce = ('_' . self::SLUG . '_nonce');
			if (isset($_REQUEST[$nonce])) {
				if (isset($_REQUEST['add_lws_coupon_cat_action'])) {
					$this->add($nonce);
				} elseif (isset($_REQUEST['rem_lws_coupon_cat_action'])) {
					$this->remove($nonce);
				}
			}
		}
	}

	protected function add($nonce)
	{
		if (\check_admin_referer($nonce, $nonce)) {
			$coupons = false;
			if (isset($_REQUEST['post']) && \is_array($_REQUEST['post'])) {
				$coupons = \array_filter(\array_map('\intval', (array)$_REQUEST['post']));
			}
			$cat = (isset($_REQUEST['selected_lws_coupon_cat']) ? (int)\sanitize_key($_REQUEST['selected_lws_coupon_cat']) : false);
			if ($coupons && $cat) {
				foreach ($coupons as $couponId) {
					\wp_set_object_terms($couponId, $cat, self::SLUG, true);
				}
				$this->addNotice(sprintf(__("%d coupons updated.", 'lws-cius'), count($coupons)), 'success'); // success, error, notice
			} else {
				$this->addNotice(__("No coupon or category selected.", 'lws-cius'), 'error'); // success, error, notice
			}
		}
	}

	protected function remove($nonce)
	{
		if (\check_admin_referer($nonce, $nonce)) {
			$coupons = false;
			if (isset($_REQUEST['post']) && \is_array($_REQUEST['post'])) {
				$coupons = \array_filter(\array_map('\intval', (array)$_REQUEST['post']));
			}
			$cat = (isset($_REQUEST['selected_lws_coupon_cat']) ? (int)\sanitize_key($_REQUEST['selected_lws_coupon_cat']) : false);
			if ($coupons && $cat) {
				foreach ($coupons as $couponId) {
					\wp_remove_object_terms($couponId, $cat, self::SLUG);
				}
				$this->addNotice(sprintf(__("%d coupons updated.", 'lws-cius'), count($coupons)), 'success'); // success, error, notice
			} else {
				$this->addNotice(__("No coupon or category selected.", 'lws-cius'), 'error'); // success, error, notice
			}
		}
	}

	public function addAction($which=true)
	{
		global $post_type;
		if ('top' == $which && self::POST_TYPE == $post_type && $this->userCan()) {
			$nonce = ('_' . self::SLUG . '_nonce');

			// list of cat
			$options = array();
			$cats = \get_terms(array(
				'taxonomy'   => self::SLUG,
				'hide_empty' => false,
				'fields'     => 'all',
			));
			foreach ($cats as $term) {
				$options[$term->term_id] = sprintf(
					'<option value="%s">%s</option>', \esc_attr($term->term_id), \wp_kses($term->name, array())
				);
			}

			if ($options) {
				$content_safe = sprintf(
					'<select name="selected_%s" id="dropdown_%s"><option value="">%s</option>%s</select>',
					self::SLUG, self::SLUG,
					__('Exclusive categories', 'lws-cius'),
					\implode("\n", $options)
				);
				$content_safe .= sprintf(
					'<input type="submit" name="add_lws_coupon_cat_action" class="button" value="%s">',
					\esc_attr(_x("Add", 'Coupon cat', 'lws-cius')),
				);
				$content_safe .= sprintf(
					'<input type="submit" name="rem_lws_coupon_cat_action" class="button" value="%s">',
					\esc_attr(_x("Remove", 'Coupon cat', 'lws-cius')),
				);
				$content_safe .= \wp_nonce_field($nonce, $nonce, false, false);
				echo "<div class='alignleft actions'>{$content_safe}</div>";
			}
		}
	}

	public function column($column)
	{
		$column[self::SLUG] = __("Exclusive categories", 'lws-cius');
		return $column;
	}

	public function cell($column, $couponId)
	{
		if (self::SLUG == $column) {
			$cats_safe = \LWSDEV\COUPONCONFLICTS\Taxonomy::getByCoupon($couponId, 'names');
			if ($cats_safe) {
				echo \esc_html(\implode(', ', $cats_safe));
			} else {
				\esc_html_e("–", 'lws-cius');
			}
		}
	}

	public function defaultHiddenColumns($hidden, $screen)
	{
		if (isset($screen->id) && ('edit-' . self::POST_TYPE) === $screen->id) {
			$hidden[self::SLUG] = self::SLUG;
		}
		return $hidden;
	}
}