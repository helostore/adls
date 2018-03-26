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
use HeloStore\ADLS\Utils;

/**
 * Class PlatformVersionRepository
 *
 * @package HeloStore\ADLS
 */
class PlatformVersionRepository extends EntityRepository
{
	protected $table = '?:adls_platform_versions';

    /**
     * @param integer $platformId
     * @param string $version
     * @param integer $editionId
     * @param string $description
     * @param \DateTime $releaseDate
     *
     * @return mixed
     */
    public function add($platformId, $version, $editionId = null, $description = null, $releaseDate = null)
    {
        $query = db_quote('INSERT INTO ?p ?e',
            $this->table,
            array(
                'number' => Utils::versionToInteger($version),
                'version' => $version,
                'platformId' => $platformId,
                'editionId' => $editionId,
                'description' => $description,
                'releaseDate' => $releaseDate
            ));

        return db_query($query);
    }

    /**
     * @param PlatformVersion $version
     *
     * @return bool
     */
    public function update(PlatformVersion $version)
    {
        $data = $version->toArray();
        $query = db_quote('UPDATE ' . $this->table . ' SET ?u WHERE id = ?d', $data, $version->getId());
        $result = db_query($query);

        return true;
    }

    /**
     * @param array $params
     *
     * @return array|PlatformVersion[]|null
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
            'id' => "version.id",
            'version' => "version.number",
        );
        $sorting = db_sort($params, $sortingFields, 'version', 'desc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'version.*';
        $group = 'version.id';

        $joins[] = db_quote('LEFT JOIN ?:adls_platforms AS platform ON platform.id = version.platformId');
        $fields[] = 'platform.name AS platform$name';

        $joins[] = db_quote('LEFT JOIN ?:adls_platform_editions AS edition ON edition.id = version.editionId');
        $fields[] = 'edition.name AS edition$name';

        if (isset($params['version'])) {
            $condition[] = db_quote('version.version = ?s', $params['version']);
        }
        if (isset($params['editionId'])) {
            $condition[] = db_quote('version.editionId = ?i', $params['editionId']);
        }
        if (isset($params['platformId'])) {
            $condition[] = db_quote('version.platformId = ?i', $params['platformId']);
        }
        if (isset($params['id'])) {
            $condition[] = db_quote('version.id = ?i', $params['id']);
        }

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'version.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(*) FROM ?p AS version ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS version ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);
        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new PlatformVersion($v);
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
     * @return PlatformVersion|null
     */
    public function findOne($params = array()) {
        $params['one'] = true;

        return $this->find($params);
    }

    /**
     *
     * @param $platformId
     * @param $version
     *
     * @param array $params
     *
     * @return PlatformVersion|null
     */
    public function findOneByVersion($platformId, $version, $params = array()) {
        $params['platformId'] = $platformId;
        $params['version'] = $version;
        return $this->findOne($params);
    }

    /**
     *
     * @param $id
     *
     * @return PlatformVersion|null
     */
    public function findOneById( $id ) {
        return $this->findOne(array(
            'id' => $id
        ));
    }

    /**
     *
     * @param $id
     *
     * @param array $params
     *
     * @return array
     */
    public function findByPlatformId( $id, $params = array()) {
        $params['platformId'] = $id;
        return $this->find($params);
    }
}
