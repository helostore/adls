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

/**
 * Class ProductRepository
 *
 * @package HeloStore\ADLS
 */
class ProductRepository extends EntityRepository
{
    /**
     * @var string
     */
    protected $table = '?:products';

    /**
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
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
            'id' => "product.product_id",
            'name' => 'productDesc$name',
        );
        $sorting = db_sort($params, $sortingFields, 'name', 'asc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'product.product_id';
        $fields[] = 'product.adls_addon_id';
        $fields[] = 'product.adls_release_version';
        $fields[] = 'product.adls_release_date';
        $fields[] = 'product.adls_subscription_id';
        $fields[] = 'product.adls_slug';
        $group = 'product.product_id';

        $joins[] = db_quote('LEFT JOIN ?:product_descriptions AS productDesc ON productDesc.product_id = product.product_id');
        $fields[] = 'productDesc.product AS productDesc$name';
        $fields[] = 'productDesc.product AS name';

        if (isset($params['sourcePlatformId'])) {
            $joins[] = db_quote('INNER JOIN ?:adls_sources AS source ON source.productId = product.product_id');
            $condition[] = db_quote('source.platformId = ?i', $params['sourcePlatformId']);
//            $fields[] = 'productDesc.product AS productDesc$name';
        }
        if (isset($params['ids'])) {
            $condition[] = db_quote('product.product_id IN (?a)', $params['ids']);
        }
        if (isset($params['id'])) {
            $condition[] = db_quote('product.product_id = ?i', $params['id']);
        }
        if (isset($params['slug'])) {
            $condition[] = db_quote('product.adls_slug = ?s', $params['slug']);
        }
        $condition[] = db_quote('product.product_type = ?s', ADLS_PRODUCT_TYPE_ADDON);
        $condition[] = db_quote('product.status IN (?a)', array('A', 'H'));

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'product.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(product.*) FROM ?p AS product ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS product ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);

        if ( ! empty($params['hashArray'])) {
            $items = db_get_hash_array($query, $params['hashArray']);
        } else {
            $items = db_get_array($query);
        }
//        if (!empty($items)) {
//            foreach ($items as $k => $v) {
//                $items[$k] = new Source($v);
//            }
//        }

        if (isset($params['one'])) {
            $items = !empty($items) ? reset($items) : null;

            return $items;
        }

        return array($items, $params);
    }

    /**
     * @param $ids
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function findById($ids, $params = array()) {
        $params['ids'] = $ids;

        return $this->find($params);
    }

    /**
     *
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function findOne($params = array()) {
        $params['one'] = true;

        return $this->find($params);
    }

    /**
     *
     * @param $id
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function findOneById($id, $params = array()) {
        $params['id'] = $id;

        return $this->findOne($params);
    }

    /**
     *
     * @param $slug
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function findOneBySlug($slug, $params = array()) {
        $params['slug'] = $slug;

        return $this->findOne($params);
    }

    /**
     * @param $sourcePlatformId
     * @param array $params
     *
     * @return array|null
     * @throws \Exception
     */
    public function findBySourcePlatformId($sourcePlatformId, $params = array())
    {
        $params['sourcePlatformId'] = $sourcePlatformId;

        return $this->find($params);
    }
}