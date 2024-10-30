<?php
namespace LWSDEV\COUPONCONFLICTS;

// don't call the file directly
if( !defined( 'ABSPATH' ) ) exit();

/**	Manage coupon conflicts on cart. */
class Solver
{
	const SLUG   = 'lws_coupon_cat';

	static public function install()
	{
		$me = new self();
		\add_filter('woocommerce_apply_with_individual_use_coupon', array($me, 'isCompatible'), 10, 4);
		\add_filter('woocommerce_apply_individual_use_coupon', array($me, 'toKeep'), 10, 3);
	}

	/** Filters if a coupon can be applied alongside other individual use coupons.
	 *	This can prevent the new coupon to be added to the cart.
	 *
	 *	@param $compatible (bool) default is false
	 *	@param $addedCoupon (\WC_Coupon) the new coupon
	 *	@param $onCartCoupon (\WC_Coupon) a coupon already in the cart, to test against
	 *	@param $appliedCoupons (string[]) all applied cart coupon codes
	 *	@return true if $addedCoupon can be used with $onCartCoupon. */
	public function isCompatible($compatible, $addedCoupon, $onCartCoupon, $appliedCoupons)
	{
		// at least on coupon in the conflict has individual use
		if ($addedCoupon->get_individual_use() || $onCartCoupon->get_individual_use()) {
			// one has no category at all (let wc standard behavior)
			$aCat = \LWSDEV\COUPONCONFLICTS\Taxonomy::getByCoupon($addedCoupon, 'ids');
			$cCat = \LWSDEV\COUPONCONFLICTS\Taxonomy::getByCoupon($onCartCoupon, 'ids');
			// those coupons accept exceptions
			if ($aCat && $cCat) {
				// does categories intersects
				if (!\array_intersect($aCat, $cCat)) {
					$compatible = true;
				}
			}
		}
		return $compatible;
	}

	/** Filter coupons to remove when applying an individual use coupon.
	 *	This can prevent already applied coupon to be removed
	 *	when the new one is added to the cart.
	 *
	 *	@param $kept (string[]) coupon codes
	 *	@param $addedCoupon (\WC_Coupon) the new coupon
	 *	@param $appliedCoupons (string[]) coupon codes
	 *	@return array of coupon code to keep in cart, whatever the new $addedCoupon is individual use. */
	public function toKeep($kept, $addedCoupon, $appliedCoupons)
	{
		if (!\is_array($kept)) {
			$kept = ($kept ? array($kept) : array());
		}
		// new coupon has individual use AND some categories
		if ($addedCoupon->get_individual_use()) {
			$aCat = \LWSDEV\COUPONCONFLICTS\Taxonomy::getByCoupon($addedCoupon, 'ids');
			if ($aCat) {
				// could be exceptions
				foreach ($appliedCoupons as $code) {
					$other = new \WC_Coupon($code);
					$oCat = \LWSDEV\COUPONCONFLICTS\Taxonomy::getByCoupon($other, 'ids');
					// does categories intersects
					if ($oCat && !\array_intersect($aCat, $oCat)) {
						$kept[] = $code;
					}
				}
			}
		}
		return $kept;
	}
}