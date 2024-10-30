<?php
namespace LWSDEV\COUPONCONFLICTS;

// don't call the file directly
if( !defined( 'ABSPATH' ) ) exit();

/**	Add a custom taxonomy to the coupon
 *	to manage conflicts on it.
 *	Ensure admin menu entry is well placed relative to WC coupons.
 *
 *	Require to have wc coupon enabled. */
class Taxonomy
{
	const SLUG = 'lws_coupon_cat';
	const POST_TYPE = 'shop_coupon';
	const MENU_SLUG = 'woocommerce-marketing';

	static public function install()
	{
		$me = new self();
		\add_action('init', array($me, 'register'), 4);
		\add_filter('woocommerce_register_post_type_shop_coupon', array($me, 'addToCouponType'));
		// add it to wc marketing menu
		\add_action('admin_menu', array($me, 'moveToMarketing'), 999); // standard menu
		\add_filter('woocommerce_marketing_menu_items', array($me, 'addToMarketingNav')); // wc style admin navigation
		\add_filter('parent_file', array($me, 'fixMenuHighlight'), 999);
	}

	/**	Add a custom taxonomy to the coupon
	 *	to manage conflicts on it.
	 *	@see https://developer.wordpress.org/reference/functions/register_taxonomy/ */
	public function register()
	{
		\register_taxonomy(
			self::SLUG,
			\apply_filters('lwsdev_taxonomy_objects_coupon_cat', array(
				self::POST_TYPE,
			)),
			\apply_filters('lwsdev_taxonomy_args_coupon_cat', array(
				'public'                => false,
				'show_ui'               => true,
				'show_admin_column'     => true,
				'hierarchical'          => true,
				'capabilities'          => array(
					'manage_terms' => 'manage_shop_coupon_terms',
					'edit_terms'   => 'edit_shop_coupon_terms',
					'delete_terms' => 'delete_shop_coupon_terms',
					'assign_terms' => 'assign_shop_coupon_terms',
				),
				'label'                 => __('Exclusive', 'lws-cius'),
				'labels'                => array(
					'name'                  => __('Exclusive categories', 'lws-cius'),
					'singular_name'         => __('Exclusive categorie', 'lws-cius'),
					'menu_name'             => _x('Exclusive categories', 'Admin menu name', 'lws-cius'),
					'search_items'          => __('Search exclusive categories', 'lws-cius'),
					'all_items'             => __('All exclusive categories', 'lws-cius'),
					'parent_item'           => __('Parent exclusive category', 'lws-cius'),
					'parent_item_colon'     => __('Parent exclusive category:', 'lws-cius'),
					'edit_item'             => __('Edit exclusive category', 'lws-cius'),
					'update_item'           => __('Update exclusive category', 'lws-cius'),
					'add_new_item'          => __('Add new exclusive category', 'lws-cius'),
					'new_item_name'         => __('New exclusive category name', 'lws-cius'),
					'not_found'             => __('No exclusive categories found', 'lws-cius'),
					'item_link'             => __('Coupon exclusive category Link', 'lws-cius'),
					'item_link_description' => __("A link to a coupon's exclusive category.", 'lws-cius'),
				),
				'description'             => __('Coupons inside a same category are mutually exclusives. Two coupons in different categories can be applied to the cart together.', 'lws-cius'),
				'update_count_callback'   => array($this, '_updateCount'),
			))
		);
	}

	/** Called when taxonomy usage count change.
	 *	In addition to shop_coupon status (trashed, etc.), looks at usage limits.
	 *	@param $terms (int[]) List of term taxonomy IDs
	 *	@param $taxonomy (\WP_Taxonomy) Current taxonomy object of terms, should be `lws_coupon_cat` */
	public function _updateCount($terms, $taxonomy)
	{
		if (self::SLUG != $taxonomy->name)
			\_update_generic_term_count($terms, $taxonomy);
		if (!$terms)
			return;
		if (!\apply_filters('lwsdev_coupon_individual_use_solver_recount_terms', true))
			return;

		global $wpdb;
		foreach (\array_unique((array)$terms) as $term) {
			\do_action('edit_term_taxonomy', $term, $taxonomy->name);

			$query = array(
				'query' => array(
					'select' => "SELECT COUNT(r.object_id)",
					'from'   => "FROM {$wpdb->term_relationships} as r",
					'join'   => array(
					),
					'where'  => array(
						'r.term_taxonomy_id = %d',
					),
					'group'  => "GROUP BY r.term_taxonomy_id"
				),
				'args' => array(
					(int)$term,
				),
			);

			$status = \apply_filters('lwsdev_coupon_individual_use_solver_recount_status', array(
				'publish', 'pending', 'draft',
			));
			if ($status) {
				$query['query']['join']['posts'] = "LEFT JOIN {$wpdb->posts} as p ON p.ID=r.object_id";
				$query['query']['where']['posts'] = sprintf("p.post_status IN ('%s')", \implode("','", \array_map('\esc_sql', $status)));
			}

			if (\apply_filters('lwsdev_coupon_individual_use_solver_recount_usable_only', true)) {
				$query['query']['join']['limit'] = "LEFT JOIN {$wpdb->postmeta} as l ON l.post_id=r.object_id AND l.meta_key='usage_limit'";
				$query['query']['join']['used']  = "LEFT JOIN {$wpdb->postmeta} as u ON u.post_id=r.object_id AND u.meta_key='usage_count'";
				$query['query']['join']['date']  = "LEFT JOIN {$wpdb->postmeta} as d ON d.post_id=r.object_id AND d.meta_key='date_expires'";
				$query['query']['where']['used'] = ('(' . \implode(' OR ', array(
					"l.meta_value IS NULL",
					"l.meta_value='0'",
					"l.meta_value=''",
					"u.meta_value IS NULL",
					"u.meta_value='0'",
					"u.meta_value=''",
					"u.meta_value < l.meta_value",
				)) . ')');
				$query['query']['where']['date'] = ('(' . \implode(' OR ', array(
					"d.meta_value IS NULL",
					"d.meta_value=''",
					sprintf("d.meta_value > %d", (int)\time()),
				)) . ')');
			}

			// run the counting
			$query = \apply_filters('lwsdev_coupon_individual_use_solver_recount_query', $query);
			if ($query['query']['where'])
				$query['query']['where'] = ('WHERE ' . \implode("\nAND ", $query['query']['where']));
			$query['query']['join'] = \implode("\n", $query['query']['join']);
			$query['query'] = \implode("\n", \array_filter($query['query']));
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$count = $wpdb->get_var($wpdb->prepare($query['query'], $query['args']));

			// save the count
			$wpdb->update($wpdb->term_taxonomy, array('count' => (int)$count), array('term_taxonomy_id' => $term));
			\do_action('edited_term_taxonomy', $term, $taxonomy->name);
		}
	}

	/**	Alter shop_coupon register_post_type() */
	public function addToCouponType($args)
	{
		$taxo = array(self::SLUG);
		if (isset($args['taxonomies']) && $args['taxonomies']) {
			if (\is_array($args['taxonomies']))
				$taxo = \array_merge($args['taxonomies'], $taxo);
			else
				$taxo[] = $args['taxonomies'];
		}
		$args['taxonomies'] = $taxo;
		return $args;
	}

	public function moveToMarketing()
	{
		\add_submenu_page(
			self::MENU_SLUG, // parent_slug: previously, it was 'woocommerce'
			'', // page_title
			__('Exclusive Categories', 'lws-cius'), // menu_title
			'manage_woocommerce', // capability
			self::getEditLink(), // menu_slug
			null, // callback function
		);
	}

	public function fixMenuHighlight($parentFile)
	{
		global $submenu_file, $current_screen, $pagenow, $taxonomy;
		if ($current_screen->post_type == self::POST_TYPE) {
			if (\in_array($pagenow, array('edit-tags.php', 'term.php')) && self::SLUG == $taxonomy) {
				$submenu_file = self::getEditLink();
			}
			$parentFile = self::MENU_SLUG;
		}
		return $parentFile;
	}

	public function addToMarketingNav($pages)
	{
		$pages[] = array(
			'id'            => 'edit-' . self::SLUG, // @type string Id to reference the page.
			'title'         => __('Exclusive Categories', 'lws-cius'), // @type string|array Page title. Used in menus and breadcrumbs.
			'parent'        => self::MENU_SLUG, // @type string|null Parent ID. Null for new top level page.
			'screen_id'     => 'edit-' . self::SLUG,
			'path'          => self::getEditLink(), // @type string Path for this page. E.g. admin.php?page=wc-settings&tab=checkout
			'capability'    => 'manage_woocommerce', // @type string Capability needed to access the page.
			'existing_page' => true,
			'nav_args'      => array(
				'order'  => 100,
				'parent' => self::MENU_SLUG,
				'matchExpression' => sprintf(
					'term.php(?=.*[?|&]taxonomy=%s(&|$|#))|edit-tags.php(?=.*[?|&]taxonomy=%s(&|$|#))',
					self::SLUG, self::SLUG
				),
			),
		);
		return $pages;
	}

	static public function getEditLink()
	{
		return sprintf('edit-tags.php?taxonomy=%s&post_type=%s', self::SLUG, self::POST_TYPE);
	}

	/**	@param $obj (WC_Coupon, string, int) the coupon, the code or the id.
	 *	@param $fields (string) Term fields to query for. Default 'all'.
	 *	Accepts:
	 *	*	'all' Returns an array of complete term objects (WP_Term[]).
	 *	*	'all_with_object_id' Returns an array of term objects with the 'object_id' param (WP_Term[]). Works only when the $object_ids parameter is populated.
	 *	*	'ids' Returns an array of term IDs (int[]).
	 *	*	'tt_ids' Returns an array of term taxonomy IDs (int[]).
	 *	*	'names' Returns an array of term names (string[]).
	 *	*	'slugs' Returns an array of term slugs (string[]).
	 *	*	'count' Returns the number of matching terms (int).
	 *	*	'id=>parent' Returns an associative array of parent term IDs, keyed by term ID (int[]).
	 *	*	'id=>name' Returns an associative array of term names, keyed by term ID (string[]).
	 *	*	'id=>slug' Returns an associative array of term slugs, keyed by term ID (string[]).
	 */
	static public function getByCoupon($obj, $fields='all')
	{
		if (!$obj)
			return array();
		// assume id
		if (\is_int($obj)) {
			return \wp_get_object_terms($obj, self::SLUG, array(
				'fields' => $fields,
			));
		}
		// need coupon enabled
		if (!\class_exists('\WC_Coupon'))
			return array();
		// assume coupon code
		if (\is_string($obj)) {
			$obj = new \WC_Coupon($obj);
		}
		// assume WC_Coupon instance
		if (\is_object($obj)) {
			if ($obj->get_virtual()) {
				$cats = $obj->get_meta('_lwsdev_coupon_virtual_taxonomy');
				if (!\is_array($cats)) {
					$cats = ($cats ? array($cats) : array());
				}
				$cats = \apply_filters('lwsdev_coupon_virtual_taxonomy', $cats, $obj);
				if ('ids' != $fields) {
					$cats = \get_terms(array(
						'include'    => $cats,
						'taxonomy'   => self::SLUG,
						'hide_empty' => false,
						'fields'     => $fields,
					));
				}
				return $cats;
			} else {
				return \wp_get_object_terms($obj->get_id(), self::SLUG, array(
					'fields' => $fields,
				));
			}
		}
		// fallback
		return array();
	}
}