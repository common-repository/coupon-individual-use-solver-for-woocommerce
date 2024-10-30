<?php
namespace LWSDEV\COUPONCONFLICTS;

// don't call the file directly
if( !defined( 'ABSPATH' ) ) exit();

/**	Manage usage by third party.
 *	Such ajax source control. */
class Bridge
{
	const SLUG   = 'lws_coupon_cat';

	static public function install()
	{
		$me = new self();
		\add_filter('lwsdev_coupon_individual_use_solver_taxonomy', array($me, 'getSlug'), 10, 1);
		\add_filter('lwsdev_coupon_individual_use_solver_exists', array($me, 'exists'), 10, 1);
		\add_filter('wp_ajax_lwsdev_coupon_individual_use_solver_categories', array($me, 'getLacSource'), 10, 3);
		\add_action('lwsdev_coupon_individual_use_solver_apply', array($me, 'apply'), 10, 2);
	}

	public function exists($yes)
	{
		return true;
	}

	public function getSlug($yes)
	{
		return self::SLUG;
	}

	/** Replace categories on a coupon. */
	public function apply($objectId, $terms)
	{
		if (\taxonomy_exists(self::SLUG)) {
			\wp_set_object_terms($objectId, $terms, self::SLUG, false);
		}
	}

	/** Provided to be called by LAC controls via ajax.
	 *	@param $_REQUEST['term'] (string) filter on taxonomy id, slug or name.
	 *	@param $_REQUEST['spec'] (array, json base64 encoded /optional) @see specToFilter.
	 *	@param $_REQUEST['page'] (int /optional) result page, not set means return all.
	 *	@param $_REQUEST['count'] (int /optional) number of result per page, default is 10 if page is set. */
	public function getLacSource()
	{
		$fromValue = (isset($_REQUEST['fromValue']) && 0 !== \strlen(\sanitize_key($_REQUEST['fromValue'])));
		$term = $this->getTerm($fromValue);

		global $wpdb;
		$sql = array(
			'select' => "SELECT t.term_id as value, t.name as label FROM {$wpdb->terms} as t",
			'join'   => "INNER JOIN {$wpdb->term_taxonomy} as x ON t.term_id=x.term_id",
			'where'  => array(
				sprintf("x.taxonomy='%s'", self::SLUG),
			),
			'limit'  => '',
		);

		if ($term) {
			if ($fromValue) {
				$sql['where'][] = ("t.term_id IN (" . implode(',', $term) . ")");
			} else {
				$term = "%$term%";
				$sql['where'][] = $wpdb->prepare("(t.name LIKE %s OR t.slug LIKE %s)", $term, $term); // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			}
		}

		if ($sql['where']) {
			$sql['where'] = (' WHERE ' . implode(' AND ', $sql['where']));
		}

		$page_safe = isset($_REQUEST['page']) ? \preg_replace('/[^\d]/', '', \sanitize_key($_REQUEST['page'])) : '';
		if (\strlen($page_safe)) {
			$count_safe = isset($_REQUEST['count']) ? \preg_replace('/[^\d]/', '', \sanitize_key($_REQUEST['count'])) : '';
			$count  = \strlen($count_safe) ? (int)$count_safe : 10;
			$offset = (int)$page_safe * $count;
			$sql['limit']  = " LIMIT {$offset}, {$count}";
		}

		$sql = \implode("\n", \array_filter($sql));
		\wp_send_json($wpdb->get_results($sql, OBJECT_K)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPressDotOrg.sniffs.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @param $readAsIdsArray (bool) true if term is an array of ID or false if term is a string
	 *	@param $_REQUEST['term'] (string) filter on post_title or if $readAsIdsArray (array of int) filter on ID.
	 *	@return array|string an array of int if $readAsIdsArray, else a string. */
	private function getTerm($readAsIdsArray)
	{
		$term = '';
		if (isset($_REQUEST['term'])) {
			if ($readAsIdsArray) {
				if (isset($_REQUEST['term']) && \is_array($_REQUEST['term'])) { // isset already tested 2 lines above but hey! it seems to be wp requirements
					$term_safe = array_map('\sanitize_key', (array)$_REQUEST['term']);
					$term = array_map('\intval', $term_safe);
				} else {
					$term = array((int)$_REQUEST['term']);
				}
			} else {
				$term = \sanitize_text_field(trim($_REQUEST['term']));
			}
		}
		return $term;
	}
}