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
 * Class PlatformEditionRepository
 *
 * @package HeloStore\ADLS
 */
class PlatformEditionRepository extends EntityRepository
{
	protected $table = '?:adls_platform_editions';

    /**
     * @param $platformId
     * @param $name
     *
     * @return mixed
     */
    public function add($platformId, $name)
    {
        $query = db_quote('INSERT INTO ?p ?e',
            $this->table,
            array(
                'name' => $name,
                'platformId' => $platformId,
            ));

        return db_query($query);
    }

    /**
     * @param array $params
     *
     * @return array|PlatformEdition[]|null
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
            'id' => "edition.id",
            'name' => "edition.name",
        );
        $sorting = db_sort($params, $sortingFields, 'name', 'asc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'edition.*';
        $group = 'edition.id';

        if (isset($params['name'])) {
            $condition[] = db_quote('edition.name = ?s', $params['name']);
        }
        if (isset($params['id'])) {
            $condition[] = db_quote('edition.id = ?i', $params['id']);
        }

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'edition.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(edition.*) FROM ?p AS edition ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS edition ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);
        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new PlatformEdition($v);
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
     * @return PlatformEdition|null
     */
    public function findOne($params = array()) {
        $params['one'] = true;

        return $this->find($params);
    }

    /**
     *
     * @param $name
     *
     * @return PlatformEdition|null
     */
    public function findOneByName( $name ) {
        return $this->findOne(array(
            'name' => $name
        ));
    }
}