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
use HeloStore\ADLS\Subscription\Subscription;

/**
 * Class ReleaseRepository
 *
 * @package HeloStore\ADLS
 */
class ReleaseRepository extends EntityRepository
{
	protected $table = '?:adls_releases';

    /**
     * Create new release
     *
     * @param Release $release
     * @return bool|int
     */
	public function create(Release $release)
	{
        $date = Utils::instance()->getCurrentDate();

        $release->setCreatedAt($date);
        $data = $release->toArray();

		$id = db_query('INSERT INTO ' . $this->table . ' ?e', $data);

		return $id;
	}


    /**
     * @param Release $release
     *
     * @return bool
     */
    public function update(Release $release)
    {
//        $release->setUpdatedAt(new \DateTime());
        $data = $release->toArray();
//        $data['updatedAt'] = Utils::instance()->getCurrentDate()->format('Y-m-d H:i:s');
        $query = db_quote('UPDATE ' . $this->table . ' SET ?u WHERE id = ?d', $data, $release->getId());
        $result = db_query($query);

        return true;
    }

    /**
     * @param Release $release
     * @return mixed
     */
    public function delete(Release $release)
    {
        return db_query('DELETE FROM ?p WHERE id = ?i', $this->table, $release->getId());
    }

	/**
	 * @param array $params
	 *
	 * @return Release[]|Release|null
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
            'id' => "releases.id",
            'productId' => "releases.productId",
            'createdAt' => "releases.createdAt",
            'fileId' => "releases.fileId",
            'version' => "releases.version",
            'product' => "productDesc.product",
        );
        $sorting = db_sort($params, $sortingFields, 'createdAt', 'desc');

		$condition = array();
        $joins = array();
        $fields = array();
        $fields[] = 'releases.*';
        $langCode = !empty($params['langCode']) ? $params['langCode'] : CART_LANGUAGE;

        if (!empty($params['id'])) {
			$condition[] = db_quote('releases.id = ?n', $params['id']);
		}
        if (!empty($params['fileId'])) {
            $condition[] = db_quote('releases.fileId = ?n', $params['fileId']);
        }
        if (!empty($params['version'])) {
            $condition[] = db_quote('releases.version = ?s', $params['version']);
        }
        if (!empty($params['productId'])) {
            $condition[] = db_quote('releases.productId = ?n', $params['productId']);
        }

        $hasStartDate = !empty($params['startDate']);
        $hasEndDate = !empty($params['endDate']);
        if ($hasStartDate || $hasEndDate) {
            if ($hasStartDate) {
                $startDate = $params['startDate']->format('Y-m-d H:i:s');
                $condition[] = db_quote('releases.createdAt >= ?s', $startDate);
            }
            if ($hasEndDate) {
                $endDate = $params['endDate']->format('Y-m-d H:i:s');
                $condition[] = db_quote('releases.createdAt <= ?s', $endDate);
            }
        }

		if (!empty($params['extended'])) {
            $joins[] = db_quote('LEFT JOIN ?:product_descriptions AS productDesc 
                ON productDesc.product_id = releases.productId 
                AND productDesc.lang_code = ?s'
                , $langCode
            );
            $fields[] = 'productDesc.product_id AS product$id';
            $fields[] = 'productDesc.product AS product$name';
		}
        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'releases.*' : implode(', ', $fields);
		$condition = !empty($condition) ? ' WHERE ' . implode(' AND ', $condition) . '' : '';


        $limit = '';
        if (isset($params['one'])) {
            $limit = 'LIMIT 0,1';
        } else if (!empty($params['items_per_page'])) {
            $query = db_quote('SELECT COUNT(DISTINCT releases.id) FROM ?p AS releases ?p ?p ?p', $this->table, $joins, $condition, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS releases ?p ?p ?p ?p', $fields, $this->table, $joins, $condition, $sorting, $limit);

        $items = db_get_array($query);

        if (!empty($items)) {
            foreach ($items as $k => $v) {
                $items[$k] = new Release($v);
            }
        }

		if (isset($params['one'])) {
			$items = !empty($items) ? reset($items) : null;

            return $items;
		}

		return array($items, $params);
	}

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $params
     * @return Release|null
     */
	public function findInRange(\DateTime $startDate, \DateTime $endDate, $params = array())
    {
        $params['startDate'] = $startDate;
        $params['endDate'] = $endDate;

        return $this->find($params);
    }

    /**
     * @param $productId
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $params
     * @return Release[]|null
     */
    public function findByProductInRange($productId, \DateTime $startDate, \DateTime $endDate, $params = array())
    {
        $params['productId'] = $productId;
        return $this->findInRange(
            $startDate
            , $endDate
            , $params
        );
    }

    /**
     * @param Subscription $subscription
     * @param array $params
     * @return Release[]|null
     */
    public function findBySubscription(Subscription $subscription, $params = array())
    {
        $params['status'] = Release::STATUS_ACTIVE;

        $x = null;
        if ($subscription->hasStartDate()) {
            $x = clone $subscription->getStartDate();
            $x->modify('-1 day');
        }
        $y = null;
        if ($subscription->hasEndDate()) {
            $y = clone $subscription->getEndDate();
        }

        return $this->findByProductInRange(
            $subscription->getProductId()
            , $x
            , $y
            , $params
        );
    }

    /**
     * @param Subscription $subscription
     * @param $version
     * @return Release[]|null
     */
    public function findBySubscriptionAndVersion(Subscription $subscription, $version)
    {
        return $this->findBySubscription($subscription, array(
            'version' => $version
        ));
    }

    /**
     * @param Subscription $subscription
     * @param $releaseId
     * @return Release|null
     */
    public function findOneBySubscriptionAndId(Subscription $subscription, $releaseId)
    {
        return $this->findBySubscription($subscription, array(
            'one' => true,
            'id' => $releaseId
        ));
    }


    /**
     * @param $productId
     * @param array $params
     * @return Release|null
     */
    public function findByProductId($productId, $params = array())
    {
        $params['productId'] = $productId;

        return $this->find($params);
    }

	/**
	 * @param array $params
	 *
	 * @return Release|null
	 */
	public function findOne($params = array())
	{
		$params['one'] = true;
        $item = $this->find($params);

		return $item;
	}

	/**
	 * @param $id
	 *
	 * @return Release|null
	 */
	public function findOneById($id)
	{
		return $this->findOne(array(
			'id' => $id
		));
	}

	/**
	 * @param $productId
	 * @param $version
	 * @return Release|null
	 */
	public function findOneByProductVersion($productId, $version)
	{
		return $this->findOne(array(
			'productId' => $productId,
			'version' => $version
		));
	}
}