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
use HeloStore\ADLS\Compatibility\CompatibilityRepository;
use HeloStore\ADLSS\Subscription;

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
        $release->setNumber(Utils::versionToInteger($release->getVersion()));

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
     *
     * @return mixed
     */
    public function delete(Release $release)
    {
	    return $this->deleteById($release->getId());
    }

	/**
	 * @param $id
	 *
	 * @return mixed
	 */
    public function deleteById($id)
    {
	    ReleaseLinkRepository::instance()->deleteByReleaseId( $id );
	    CompatibilityRepository::instance()->deleteByReleaseId( $id );

        return db_query('DELETE FROM ?p WHERE id = ?i', $this->table, $id);
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
            'id' => "releases.id",
            'productId' => "releases.productId",
            'createdAt' => "releases.createdAt",
            'fileId' => "releases.fileId",
            'version' => "releases.number",
            'product' => array("productDesc.product", "releases.number"),
        );
        $sorting = db_sort($params, $sortingFields, 'version', 'desc');

		$condition = array();
        $orCondition = array();
        $joins = array();
		$group = 'releases.id';
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
		if (!empty($params['hash'])) {
			$condition[] = db_quote('releases.hash = ?s', $params['hash']);
		}
        if (!empty($params['productId'])) {
            $condition[] = db_quote('releases.productId = ?n', $params['productId']);
        }

        if ( ! empty($params['compatibilityPlatformId'])
             || ! empty($params['compatibilityPlatformVersionId'])
             || ! empty($params['compatibilityPlatformEditionId'])
        ) {
            $joins[] = db_quote('
				INNER JOIN ?:adls_compatibility AS compatibility 
                    ON compatibility.releaseId = releases.id
                    ' .
                    (! empty( $params['productId'] ) ?
                        db_quote(' AND compatibility.productId = ?i', $params['productId']) : '') .

                    (! empty( $params['compatibilityPlatformId'] ) ?
                        db_quote(' AND compatibility.platformId = ?i', $params['compatibilityPlatformId']) : '') .

                    (! empty( $params['compatibilityPlatformVersionId'] ) ?
                        db_quote(' AND compatibility.platformVersionId = ?i', $params['compatibilityPlatformVersionId']) : '') .

                    (! empty( $params['compatibilityPlatformEditionId'] ) ?
                        db_quote(' AND compatibility.editionId = ?i', $params['compatibilityPlatformEditionId']) : '')
            );
        }
//        if ( ! empty($params['compatibilityPlatformVersion'])) {
//
//        }


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
		if ( ! empty( $params['subscriptionId'] ) || ! empty( $params['userId'] ) ) {
			$joins[] = db_quote('
				INNER JOIN ?:adls_release_links AS releaseLink 
                    ON releaseLink.releaseId = releases.id' .
                    (! empty( $params['subscriptionId'] ) ?
	                    db_quote(' AND releaseLink.subscriptionId = ?i', $params['subscriptionId']) : '') .
                    (! empty( $params['userId'] ) ?
	                    db_quote(' AND releaseLink.userId = ?i', $params['userId']) : '')
			);
			$fields[] = 'releaseLink.licenseId AS link$licenseId';
			$fields[] = 'releaseLink.subscriptionId AS link$subscriptionId';
		}
		if ( ! empty($params['getUserCount']) ) {
			$joins[] = db_quote('
				LEFT JOIN ?:adls_release_links AS releaseLinkUserCount
                    ON releaseLinkUserCount.releaseId = releases.id');
			$fields[] = 'COUNT(DISTINCT releaseLinkUserCount.userId) AS releaseLink$userCount';
		}
        if (!empty($params['fromReleaseId'])) {
            $orCondition[] = db_quote('releases.id = ?i', $params['fromReleaseId']);
        }

		if (!empty($params['extended'])) {
            $joins[] = db_quote('LEFT JOIN ?:product_descriptions AS productDesc 
                ON productDesc.product_id = releases.productId 
                AND productDesc.lang_code = ?s'
                , $langCode
            );
            $fields[] = 'productDesc.product_id AS product$id';
            $fields[] = 'productDesc.product AS product$name';

			$joins[] = db_quote('LEFT JOIN ?:products AS product
                ON product.product_id = releases.productId'
				, $langCode
			);
			$fields[] = 'product.adls_subscription_id AS product$adls_subscription_id';

			if ( isset( $params['product$adls_subscription_id'] ) ) {
				$condition[] = db_quote( 'product.adls_subscription_id = ?i', $params['product$adls_subscription_id'] );
			}
		}

		if ( ! empty( $params['latest'] ) ) {
        	$subJoin = '';
			if ( ! empty( $params['userId'] ) ) {
				$subJoin = db_quote('
					INNER JOIN ?:adls_release_links
					    ON ?:adls_release_links.releaseId = ?:adls_releases.id 
					    AND ?:adls_release_links.userId = ?i', $params['userId']);
			}
			$joins[] = db_quote('
				INNER JOIN (
					SELECT ?:adls_releases.productId, MAX(number) AS maxVersionNumber 
					FROM ?:adls_releases
					' . $subJoin . ' 
					GROUP BY ?:adls_releases.productId
				) AS latestRelease ON releases.productId = latestRelease.productId AND releases.number = latestRelease.maxVersionNumber
			');

			$group = 'releases.productId';
		}

        $joins = empty($joins) ? '' : implode(' ', $joins);
        $fields = empty($fields) ? 'releases.*' : implode(', ', $fields);
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
            $query = db_quote('SELECT COUNT(DISTINCT releases.id) FROM ?p AS releases ?p ?p GROUP BY ?p ?p', $this->table, $joins, $conditions, $group, $limit);
            $params['total_items'] = db_get_field($query);
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
        $query = db_quote('SELECT ?p FROM ?p AS releases ?p ?p GROUP BY ?p ?p ?p', $fields, $this->table, $joins, $conditions, $group, $sorting, $limit);

        $items = db_get_array($query);
        aa($query, $items);
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
	 * Find latest releases of all products
	 *
	 * @param array $params
	 *
	 * @return array|null
	 */
	public function findLatest($params = array())
	{
		$params['latest'] = true;

		return $this->find(
			$params
		);
	}

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     * @param array $params
     * @return array|null
     */
	public function findInRange(\DateTime $startDate = null, \DateTime $endDate = null, $params = array())
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
     * @return array|null
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
     * @param $productId
     * @param \DateTime $endDate
     * @param array $params
     * @return array|null
     */
    public function findOneLatestByProduct($productId, \DateTime $endDate = null, $params = array())
    {
        $params['productId'] = $productId;
        $params['items_per_page'] = 1;
        $params['one'] = true;

        return $this->findInRange(
            null
            , $endDate
            , $params
        );
    }

    /**
     * @param Subscription $subscription
     * @param array $params
     * @return array|null
     */
    public function findBySubscription(Subscription $subscription, $params = array())
    {
	    $params['status'] = Release::STATUS_ACTIVE;
        $params['productId'] = $subscription->getProductId();
        $params['userId'] = $subscription->getUserId();
        $params['subscriptionId'] = $subscription->getId();

        return $this->find($params);
//        $params['status'] = Release::STATUS_ACTIVE;
//
//        $x = null;
//        if ($subscription->hasStartDate()) {
//            $x = clone $subscription->getStartDate();
//            $x->modify('-1 day');
//        }
//        $y = null;
//        if ($subscription->hasEndDate()) {
//            $y = clone $subscription->getEndDate();
//        }
//
//        if ($subscription->getReleaseId() && !isset($params['one'])) {
//            $params['fromReleaseId'] = $subscription->getReleaseId();
//        }
//
//        return $this->findByProductInRange(
//            $subscription->getProductId()
//            , $x
//            , $y
//            , $params
//        );
    }

    /**
     * @param Subscription $subscription
     * @param $version
     * @return array|null
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

	/**
	 * @param $hash
	 * @param $userId
	 *
	 * @return Release|null
	 */
	public function findOneByHashUser($hash, $userId)
	{
		return $this->findOne(array(
			'hash' => $hash,
			'userId' => $userId
		));
	}

	/**
	 * @param $productId
	 *
	 * @return array
	 */
	public function countByProductId($productId) {
		return db_get_field( 'SELECT COUNT(*) FROM ' . $this->table . ' WHERE productId = ?i', $productId );
	}
}