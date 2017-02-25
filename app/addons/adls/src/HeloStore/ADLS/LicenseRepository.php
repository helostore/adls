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

use Tygh\Registry;

/**
 * Class LicenseRepository
 *
 * @package HeloStore\ADLS
 */
class LicenseRepository extends EntityRepository
{
	protected $table = '?:adls_licenses';

	/**
	 * @param array $params
	 *
	 * @return License[]|License|null
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
            'id' => "license.id",
            'status' => "license.status",
            'orderId' => "license.orderId",
            'updatedAt' => "license.updatedAt",
            'createdAt' => "license.createdAt",
            'orderItemId' => "license.orderItemId",
            'userId' => "license.userId",
            'licenseKey' => "license.licenseKey",
            'customer' => array("user.lastname", "user.firstname"),
            'product' => "productDesc.product",
        );
        $sorting = db_sort($params, $sortingFields, 'updatedAt', 'desc');

		$condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'license.*';
        $langCode = !empty($params['langCode']) ? $params['langCode'] : CART_LANGUAGE;

        if (isset($params['id'])) {
			$condition[] = db_quote('license.id = ?n', $params['id']);
		}
        if (isset($params['userId'])) {
            $condition[] = db_quote('license.userId = ?n', $params['userId']);
        }
        if (isset($params['orderId'])) {
            $condition[] = db_quote('license.orderId = ?n', $params['orderId']);
        }
        if (isset($params['orderItemId'])) {
            $condition[] = db_quote('license.orderItemId = ?s', $params['orderItemId']);
        }
        if (isset($params['productId'])) {
            $condition[] = db_quote('license.productId = ?n', $params['productId']);
        }
        if (isset($params['status'])) {
            $condition[] = db_quote('license.status = ?s', $params['status']);
        }
		if (isset($params['extended'])) {
            $joins[] = db_quote('LEFT JOIN ?:users AS user ON user.user_id = license.userId');
            $fields[] = 'user.user_id AS user$id';
            $fields[] = 'user.email AS user$email';
            $fields[] = 'user.firstname AS user$firstName';
            $fields[] = 'user.lastname AS user$lastName';

            $joins[] = db_quote('LEFT JOIN ?:product_descriptions AS productDesc 
                ON productDesc.product_id = license.productId 
                AND productDesc.lang_code = ?s'
                , $langCode
            );
            $fields[] = 'productDesc.product_id AS product$id';
            $fields[] = 'productDesc.product AS product$name';

            $joins[] = db_quote('LEFT JOIN ?:order_details AS orderItem 
                ON orderItem.item_id = license.orderItemId 
                AND orderItem.order_id = license.orderId'
            );
            $fields[] = 'orderItem.price AS orderItem$price';
		}

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'license.*' : implode(', ', $fields);
		$condition = !empty($condition) ? ' WHERE ' . implode(' AND ', $condition) . '' : '';


        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(DISTINCT license.id) FROM ?p AS license ?p ?p ?p', $this->table, $joins, $condition, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

		$query = db_quote('SELECT ?p FROM ?p AS license ?p ?p ?p ?p', $fields, $this->table, $joins, $condition, $sorting, $limit);

		$items = db_get_array($query);

		if (empty($items)) {
			return array(null, $params);
		}

		foreach ($items as $k => $v) {
			$items[$k] = new License($v);
		}

		if (isset($params['one'])) {
			$items = !empty($items) ? reset($items) : null;
		}

		return array($items, $params);
	}

	/**
	 * @param array $params
	 *
	 * @return License|null
	 */
	public function findOne($params = array())
	{
		$params['one'] = true;
        list($item, ) = $this->find($params);

		return $item;
	}

	/**
	 * @param $id
	 *
	 * @return License|null
	 */
	public function findOneById($id)
	{
		return $this->findOne(array(
			'id' => $id
		));
	}


}