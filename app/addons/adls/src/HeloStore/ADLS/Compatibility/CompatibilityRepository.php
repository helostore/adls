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
namespace HeloStore\ADLS\Compatibility;

use HeloStore\ADLS\EntityRepository;
use HeloStore\ADLS\Platform\PlatformVersion;
use HeloStore\ADLS\Release;

/**
 * Class CompatibilityRepository
 *
 * @package HeloStore\ADLS
 */
class CompatibilityRepository extends EntityRepository
{
	protected $table = '?:adls_compatibility';

    public function assign($productId, $releaseId, PlatformVersion $platformVersion)
    {
        $data = [
            'releaseId' => $releaseId,
            'platformVersionId' => $platformVersion->getId(),
            'platformId' => $platformVersion->getPlatformId(),
            'editionId' => $platformVersion->getEditionId(),
            'productId' => $productId,
        ];
        $query = db_quote('REPLACE INTO ?p ?e', $this->table, $data);
        return db_query($query);
    }

    /**
     * @param $releaseId
     *
     * @return mixed
     */
    public function deleteByReleaseId($releaseId) {
        return db_query('DELETE FROM ?p WHERE releaseId = ?i',
            $this->table,
            $releaseId
        );
    }

    /**
     * @param $releaseId
     * @param $platformVersionId
     *
     * @return mixed
     */
    public function unassign($releaseId, $platformVersionId)
    {
        $query = db_quote('DELETE FROM ?p WHERE releaseId = ?i AND platformVersionid = ?i', $this->table, $releaseId, $platformVersionId);

        return db_query($query);
    }

    /**
     * @param array $params
     *
     * @return array|Compatibility|null
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
            'releaseId' => "compatibility.releaseId",
            'platformVersionId' => "compatibility.platformVersionId",
            'platformId' => "compatibility.platformId",
            'editionId' => "compatibility.editionId",
            'productId' => "compatibility.productId",
            'platformVersion' => "version.number",
        );
        $sorting = db_sort($params, $sortingFields, 'platformVersion', 'asc');

        $condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'compatibility.*';
        $group = '';

        $joins[] = db_quote('LEFT JOIN ?:adls_platforms AS platform ON platform.id = compatibility.platformId');
        $fields[] = 'platform.name AS platform$name';

        $joins[] = db_quote('LEFT JOIN ?:adls_platform_editions AS edition ON edition.id = compatibility.editionId');
        $fields[] = 'edition.name AS edition$name';

        $joins[] = db_quote('LEFT JOIN ?:adls_platform_versions AS version ON version.id = compatibility.platformVersionId');
        $fields[] = 'version.version AS platform$version';

        if (isset($params['releaseId'])) {
            $condition[] = db_quote('compatibility.releaseId = ?i', $params['releaseId']);
        }
        if (isset($params['platformVersionId'])) {
            $condition[] = db_quote('compatibility.platformVersionId = ?i', $params['platformVersionId']);
        }
        if (isset($params['platformId'])) {
            $condition[] = db_quote('compatibility.platformId = ?i', $params['platformId']);
        }
        if (isset($params['editionId'])) {
            $condition[] = db_quote('compatibility.editionId = ?i', $params['editionId']);
        }
        if (isset($params['productId'])) {
            $condition[] = db_quote('compatibility.productId = ?i', $params['productId']);
        }

        if ( ! empty($params['releaseStatus'])) {
            $joins[] = db_quote('
				INNER JOIN ?:adls_releases AS releases
                    ON releases.id = compatibility.releaseId
                    AND releases.status = ?s
                    ',
                $params['releaseStatus']);
        }

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'compatibility.*' : implode(', ', $fields);
        $condition = implode(' AND ', $condition);
        $conditions = !empty($condition) ? ' WHERE (' . $condition . ')' : '';
        $group = !empty($group) ? ' GROUP BY ' . $group . '' : '';

        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(compatibility.*) FROM ?p AS compatibility ?p ?p ?p', $this->table, $joins, $conditions, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

        $query = db_quote('SELECT ?p FROM ?p AS compatibility ?p ?p ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);

        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new Compatibility($v);
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
     * @return Compatibility|null
     */
    public function findOne($params = array()) {
        $params['one'] = true;

        return $this->find($params);
    }

    /**
     * @param $productId
     * @param $platformId
     * @param array $params
     *
     * @return array
     */
    public function findMinMax($productId, $platformId, $params = array()) {
        $min = $this->findOne([
            'productId' => $productId,
            'platformId' => $platformId,
            'releaseStatus' => Release::STATUS_PRODUCTION,
            'sort_by' => 'platformVersion',
            'sort_order' => 'asc'
        ]);
        $max = $this->findOne([
            'productId' => $productId,
            'platformId' => $platformId,
            'releaseStatus' => Release::STATUS_PRODUCTION,
            'sort_by' => 'platformVersion',
            'sort_order' => 'desc'
        ]);

        return ['min' => $min, 'max' => $max];
    }

//    /**
//     *
//     * @param Release $release
//     * @param $platformId
//     * @param array $params
//     *
//     * @return Compatibility|null
//     */
//    public function findForRelease(Release $release, $platformId, $params = array()) {
//        $params['releaseId'] = $release->getId();
//        $params['productId'] = $release->getProductId();
//        $params['platformId'] = $platformId;
//        $params['releaseStatus'] = Release::STATUS_PRODUCTION;
//        $params['sort_by'] = 'platformVersion';
//        $params['sort_order'] = 'desc';
//        $params['page'] = 'desc';
//
//        return $this->find($params);
//    }
}
