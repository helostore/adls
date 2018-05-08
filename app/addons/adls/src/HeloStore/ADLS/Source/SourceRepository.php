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
namespace HeloStore\ADLS\Source;

use HeloStore\ADLS\EntityRepository;

/**
 * Class SourceRepository
 *
 * @package HeloStore\ADLS
 */
class SourceRepository extends EntityRepository
{
    /**
     * @var string
     */
    protected $table = '?:adls_sources';

    /**
     * @param Source $source
     *
     * @return mixed
     */
    public function delete(Source $source)
    {
        $query = db_quote('DELETE FROM ?p WHERE id = ?i',
            $this->table,
            $source->getId()
        );

        return db_query($query);
    }
    /**
     * @param Source $source
     *
     * @return mixed
     */
    public function add(Source $source)
    {
        $query = db_quote('INSERT INTO ?p ?e',
            $this->table,
            $source->toArray()
        );

        return db_query($query);
    }

    /**
     * @param Source $source
     *
     * @return mixed
     */
    public function update(Source $source)
    {
        $query = db_quote('UPDATE ?p SET ?u WHERE id = ?i',
            $this->table,
            $source->toArray(),
            $source->getId()
        );

        return db_query($query);
    }

    /**
     * @param array $params
     *
     * @return array|Source[]|null
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
            'id' => "source.id",
        );
        $sorting = db_sort($params, $sortingFields, 'id', 'asc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'source.*';
        $group = 'source.id';

        $joins[] = db_quote('LEFT JOIN ?:adls_platforms AS platform ON platform.id = source.platformId');
        $fields[] = 'platform.name AS platform$name';

        if (isset($params['id'])) {
            $condition[] = db_quote('source.id = ?i', $params['id']);
        }
        if (isset($params['platformId'])) {
            $condition[] = db_quote('source.platformId = ?i', $params['platformId']);
        }
        if (isset($params['productId'])) {
            $condition[] = db_quote('source.productId = ?i', $params['productId']);
        }

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'source.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(source.*) FROM ?p AS source ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS source ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);
        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new Source($v);
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
     * @return Source|null
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
     * @return Source|null
     * @throws \Exception
     */
    public function findOneById($id, $params = array()) {
        $params['id'] = $id;

        return $this->findOne($params);
    }
}