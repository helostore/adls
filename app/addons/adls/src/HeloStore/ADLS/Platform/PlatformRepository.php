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
namespace HeloStore\ADLS\Platform;

use HeloStore\ADLS\EntityRepository;

/**
 * Class PlatformRepository
 *
 * @package HeloStore\ADLS
 */
class PlatformRepository extends EntityRepository
{
	protected $table = '?:adls_platforms';

    /**
     * @param $name
     *
     * @param $slug
     * @return mixed
     */
    public function add($name, $slug)
    {
        $query = db_quote('INSERT INTO ?p ?e',
            $this->table,
            array(
                'name' => $name,
                'slug' => $slug,
            ));

        return db_query($query);
    }

    /**
     * @param array $params
     *
     * @return array|Platform[]|null
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
            'id' => "platform.id",
            'name' => "platform.name",
        );
        $sorting = db_sort($params, $sortingFields, 'name', 'asc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'platform.*';
        $group = 'platform.id';

        if (isset($params['name'])) {
            // @TODO: find alternative for this dirty hack which works around the fact that CS-Cart multi-vendor edition report itself as "Multi-Vendor" and not CS-Cart;
            if ($params['name'] == 'Multi-Vendor') {
                $params['name'] = 'CS-Cart';
            }

            $condition[] = db_quote('platform.name = ?s', $params['name']);
        }
        if (isset($params['id'])) {
            $condition[] = db_quote('platform.id = ?i', $params['id']);
        }

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'platform.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(platform.*) FROM ?p AS platform ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS platform ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);
        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new Platform($v);
            }
        }

        if (isset($params['one'])) {
            $items = !empty($items) ? reset($items) : null;

            return $items;
        }

        return array($items, $params);
    }

    /**
     *
     * @param array $params
     *
     * @return Platform|null
     */
    public function findOne($params = array()) {
        $params['one'] = true;

        return $this->find($params);
    }

    /**
     *
     * @param $name
     *
     * @return Platform|null
     */
    public function findOneByName( $name ) {
        return $this->findOne(array(
            'name' => $name
        ));
    }

    /**
     * @return Platform|null
     */
    public function findDefault() {
        return $this->findOne(array(
            'name' => 'CS-Cart'
        ));
    }

    /**
     * @param $id
     *
     * @return Platform|null
     */
    public function findOneById($id) {
        return $this->findOne(array(
            'id' => $id
        ));
    }
}
