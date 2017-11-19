<?php
/**
 * HELOstore
 *
 * This source file is part of a commercial software. Only users who have purchased a valid license through
 * https://helostore.com/ and accepted to the terms of the License Agreement can install this product.
 *
 * @category   Add-ons
 * @package    HELOstore
 * @copyright  Copyright (c) 2015-2016 HELOstore. (https://helostore.com/)
 * @license    https://helostore.com/legal/license-agreement/   License Agreement
 * @version    $Id$
 */
namespace HeloStore\ADLS;
use HeloStore\ADLSS\Subscription;

/**
 * Class ReleaseLinkRepository
 *
 * @package HeloStore\ADLS
 */
class ReleaseLinkRepository extends EntityRepository
{
	protected $table = '?:adls_release_links';

	public function addLink($userId, $releaseId, $licenseId, $subscriptionId = null) {
		$query = db_quote( 'REPLACE INTO ?p ?e',
			$this->table,
			array(
				'userId'         => $userId,
				'releaseId'      => $releaseId,
				'subscriptionId' => $subscriptionId,
				'licenseId'      => $licenseId
			) );
		db_query($query);
	}

	public function removeLink($link) {
		db_query('DELETE FROM ?p WHERE userId = ?i AND releaseId = ?i  AND subscriptionId = ?i  AND licenseId = ?i ',
			$this->table,
			$link['userId'],
			$link['releaseId'],
			$link['subscriptionId'],
			$link['licenseId']
		);
	}

	/**
	 * @param array $params
	 *
	 * @return array|null
	 */
	public function find($params = array())
	{
		// Set default values to input params
		$defaultParams = array (
			'page' => 1,
			'items_per_page' => 0
		);

		$params = array_merge($defaultParams, $params);

		$sortingFields = array (
			'id' => "releaseLinks.releaseId",
		);
		$sorting = db_sort($params, $sortingFields, 'id', 'asc');

		$condition = array();
		$orCondition = array();
		$joins = array();
		$fields = array();
		$fields[] = 'releaseLinks.*';

		if (!empty($params['releaseId'])) {
			$condition[] = db_quote('releaseLinks.releaseId = ?n', $params['releaseId']);
		}

		if (!empty($params['userId'])) {
			$condition[] = db_quote('releaseLinks.userId = ?n', $params['userId']);
		}
		if (!empty($params['subscriptionId'])) {
			$condition[] = db_quote('releaseLinks.subscriptionId = ?n', $params['subscriptionId']);
		}

		$joins = empty($joins) ? '' : implode(' ', $joins);
		$fields = empty($fields) ? 'releaseLinks.*' : implode(', ', $fields);
		$condition = implode(' AND ', $condition);
		$orCondition = implode(' AND ', $orCondition);
		$conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';
		if (!empty($orCondition)) {
			$conditions .= ' OR (' . $orCondition . ')';
		}

		$limit = '';
		if (isset($params['one'])) {
			$limit = 'LIMIT 0,1';
		} else if (!empty($params['items_per_page'])) {
			$query = db_quote('SELECT COUNT(releaseLinks.*) FROM ?p AS releaseLinks ?p ?p GROUP BY releaseLinks.releaseId, releaseLinks.userId ?p', $this->table, $joins, $conditions, $limit);
			$params['total_items'] = db_get_field($query);
			$limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
		}
		$query = db_quote('SELECT ?p FROM ?p AS releaseLinks ?p ?p GROUP BY releaseLinks.releaseId, releaseLinks.userId ?p ?p', $fields, $this->table, $joins, $conditions, $sorting, $limit);

		$items = db_get_array($query);

		if (isset($params['one'])) {
			$items = !empty($items) ? reset($items) : null;

			return $items;
		}

		return array($items, $params);
	}
}