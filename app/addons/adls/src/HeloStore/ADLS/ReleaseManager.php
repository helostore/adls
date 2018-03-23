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

use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\Source\SourceFileRepository;
use HeloStore\ADLS\Source\SourceRepository;
use HeloStore\ADLSS\Subscription;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;

/**
 * Class ReleaseManager
 * 
 * @package HeloStore\ADLS
 */
class ReleaseManager extends Manager
{
    /**
     * @var ReleaseRepository
     */
    protected $repository;

    /**
     * ReleaseManager constructor.
     */
    public function __construct()
    {
        $this->setRepository(ReleaseRepository::instance());
    }

    /**
     * Adds a new release DB entry
     *
     * @param $storeProduct
     * @param $params
     *
     * @return bool
     * @throws ReleaseException
     * @throws \Exception
     */
	public function release($storeProduct, $params)
	{
        if (empty($storeProduct) || empty($storeProduct['product_id'])) {
            throw new ReleaseException("Store product corresponding to release data not found (" . $storeProduct['name'] . ')');
        }
        $productId = intval($storeProduct['product_id']);

        if (empty($params['version'])) {
            throw new ReleaseException("Missing version parameter in release data (" . $storeProduct['name'] . ")");
        }
        $version = $params['version'];

        if (empty($params['filename'])) {
            throw new ReleaseException("Missing filename parameter in release data (" . $storeProduct['name'] . ")");
        }
        $fileName = $params['filename'];

        if (!empty($params['filesize'])) {
            $fileSize = intval($params['filesize']);
        } else {
            if (empty($params['archivePath'])) {
                throw new ReleaseException("Missing archivePath parameter in release data (" . $storeProduct['name'] . ")");
            }
            $fileSize = filesize($params['archivePath']);
        }
        $platform = PlatformRepository::instance()->findDefault();
        $source = SourceRepository::instance()->findOne(array(
            'platformId' => $platform->getId(),
            'productId' => $productId
        ));

        if (empty($source)) {
            throw new \Exception('Source not found by platform: ' . $platform->getId() .  ', product:  ' . $productId);
        }

        $releaseId = $this->createRelease($productId, $version, $fileName, $fileSize, $source->getId());

		return $releaseId;
	}

    /**
     * Create a release entry in DB
     *
     * @param $productId
     * @param $version
     * @param $fileName
     * @param $fileSize
     * @param $sourceId
     *
     * @return bool|int
     * @throws \Exception
     */
	public function createRelease($productId, $version, $fileName, $fileSize, $sourceId)
	{
        $existingRelease = $this->repository->findOneByProductVersion($productId, $version);

        if (!empty($existingRelease)) {
            return $existingRelease->getId();
//            throw new ReleaseException('Specified version already released!');
        }

		$hash = hash('md5',uniqid($productId.$version.$fileName.$fileSize, true));

		$release = new Release();
		$release
			->setProductId($productId)
            ->setVersion($version)
            ->setFileName($fileName)
            ->setFileSize($fileSize)
            ->setDownloads(0)
			->setHash($hash)
            ->setSourceId($sourceId)
            ->setStatus(Release::STATUS_ALPHA);

        $result = $this->repository->create($release);
        if ($result) {
            $this->updateProduct($productId, $version, TIME);
        }

        return $result;
	}

    /**
     * @TODO: releases are linked with products, discard these 2 custom product fields
     *
     * @param $productId
     * @param $version
     * @param $date
     * @return mixed
     */
    public function updateProduct($productId, $version, $date)
    {
        $productData = array(
            'adls_release_version' => $version
            , 'adls_release_date' => $date
        );
        return db_query('UPDATE ?:products SET ?u WHERE product_id = ?i', $productData, $productId);
    }

    /**
     * @param $productId
     * @param $requestVersion
     * @return bool
     */
    public function isValidVersion($productId, $requestVersion)
    {
        $release = $this->repository->findOneByProductVersion($productId, $requestVersion);

        return !empty($release);
    }

    /**
     * Check if a subscription has access to a release's version (test release version within dates range)
     *
     * @param Subscription $subscription
     * @param $version
     * @return bool
     */
    public function isVersionAvailableToSubscription(Subscription $subscription, $version)
    {
        list($releases, ) = $this->repository->findBySubscriptionAndVersion($subscription, $version);

        return (!empty($releases));
    }

	/**
	 * @param Release $release
	 *
	 * @param $userId
	 *
	 * @return bool
	 */
	public function isReleaseAvailableToUser(Release $release, $userId)
	{

		$release = $this->repository->findOneById( $release->getId() );
		if ( empty( $release ) ) {
			return false;
		}
        if (!$release->isProduction()) {
            return false;
        }
		$product = ProductManager::instance()->getProductById( $release->getProductId() );
		if ( empty( $product ) ) {
			return false;
		}
		if ( ProductManager::instance()->isFreeSubscription($product['adls_subscription_id']) ) {
			return true;
		}

		$release = $this->repository->findOneByHashUser( $release->getHash(), $userId );

		return (!empty($release));
	}

    /**
     * @param $productSlug
     * @param Release $release
     *
     * @return string
     * @throws \Exception
     */
    public function getReleasePath($productSlug, Release $release)
    {
        $source = SourceRepository::instance()->findOneById($release->getSourceId());
        $platform = PlatformRepository::instance()->findOneById($source->getPlatformId());

        return SourceFileRepository::getReleasePath($productSlug, $platform->getSlug(), $release->getVersion());
    }

    /**
     * @param $userId
     * @param $product
     *
     * @param int $itemsPerPage
     *
     * @return mixed
     * @throws \Exception
     */
    public function getOrderItemReleases($userId, $product, $itemsPerPage = 1)
    {
        $productId = $product['product_id'];
        if (!empty($product['subscription'])) {
            /** @var Subscription $subscription */
            $subscription = $product['subscription'];
            list($releases, ) = $this->repository->findBySubscription($subscription, array(
	            'items_per_page' => $itemsPerPage
            ));
        } else {
            list($releases, ) = $this->repository->findByProductId($productId, array(
            	'userId' => $userId,
	            'items_per_page' => $itemsPerPage
            ));
        }
        if (empty($releases)) {
            return array();
        }

        return $releases;
    }

    /**
     * @param Release[] $releases
     *
     * @return mixed
     * @throws \Exception
     */
    public function checkFileIntegrity($releases)
    {
        if (empty($releases)) {
            return array();
        }
        $productIds = array();
        foreach ($releases as $k => $release) {
            if ( ! in_array($release->getProductId(), $productIds)) {
                $productIds[] = $release->getProductId();
            }
        }

        list($technicalProducts, ) = ProductRepository::instance()->findById($productIds, array(
            'hashArray' => 'product_id'
        ));

        /**
         * @var integer $k
         * @var Release $release
         */
        foreach ($releases as $k => $release) {
            $productSlug = $technicalProducts[$release->getProductId()]['adls_slug'];
            $filePath = $this->getReleasePath($productSlug, $release);
            $release->setFileFound(file_exists($filePath));
            if (!file_exists($filePath)) {
                error_log("Release file not found: " . $filePath);
            }
        }

        return $releases;
    }

    /**
     * @param Release $release
     *
     * @return bool
     * @throws \Exception
     */
	public function prepareForDownload(Release $release)
	{
        $product = ProductRepository::instance()->findOneById($release->getProductId());
        $productSlug = $product['adls_slug'];
        $filePath = $this->getReleasePath($productSlug, $release);
		$release->download();
		$this->repository->update($release);

		return $filePath;
	}

    /**
     * @param Release $release
     *
     * @return bool
     * @throws \Exception
     */
    public function download(Release $release)
    {
        return fn_get_file($this->prepareForDownload($release));
    }

    /**
     * @param $userId
     * @param $productId
     * @param null $licenseId
     * @param null $subscriptionId
     * @param null $startDate
     * @param null $endDate
     *
     * @throws \Exception
     */
    public function addUserLinks($userId, $productId, $licenseId = null, $subscriptionId = null, $startDate = null, $endDate = null) {
        $releases = array();
	    if ( !empty( $startDate ) && !empty( $endDate ) ) {
//		    list($releases, ) = $this->repository->findByProductInRange( $productId, $startDate, $endDate );
		    list($releases, ) = $this->repository->findProductionByProductInRange( $productId, null, $endDate );
		    if ( empty( $releases ) ) {
			    $release = ReleaseRepository::instance()->findProductionOneLatestByProduct($productId, $endDate);
			    $releases = array( $release );
		    }
	    } else if ($subscriptionId === null) {
		    // This is a free product.
		    list($releases, ) = ReleaseRepository::instance()->findProduction(array(
			    'productId' => $productId
		    ));
	    }

	    if ( fn_is_empty( $releases ) ) {
            throw new \Exception('Unable to find releases for given params, product #' . $productId);
	    }
	    foreach ( $releases as $release ) {
            if (empty($release)) {
                throw new \Exception('Invalid release');
            }
		    ReleaseAccessRepository::instance()->addLink($userId, $productId, $release->getId(), $licenseId, $subscriptionId);
	    }
	}

    /**
     * @param Release $release
     *
     * @return array
     */
	public function unpublish(Release $release) {
		list ($links, ) = ReleaseAccessRepository::instance()->findByRelease($release);
		$premium = 0;
		$free = 0;
		foreach ( $links as $link ) {
			if ( !empty( $link['licenseId'] ) && ! empty( $link['subscriptionId'] ) ) {
				$premium++;
			} else {
				$free++;
			}
		}
		ReleaseAccessRepository::instance()->deleteByReleaseId($release->getId());

		return array($premium, $free);
	}

    /**
     * @param Release $release
     *
     * @return array
     */
	public function publish(Release $release) {
		$a = $this->publishPremium( $release );
		$b = $this->publishFree( $release );

		return array($a, $b);
	}

	/**
	 * Give customers access to release
	 *
	 * @param Release $release
	 *
	 * @return int
	 */
	public function publishPremium(Release $release) {

	    // @TODO: decouple from publishing from subscriptions
		list ( $subscriptions, ) = SubscriptionRepository::instance()->find(array(
			'extended' => true,
			'status' => Subscription::STATUS_ACTIVE,
			'productId' => $release->getProductId()
		));

		$count = 0;
		if ( ! empty( $subscriptions ) ) {
			/** @var Subscription $subscription */
			foreach ( $subscriptions as $subscription ) {
				$license = $subscription->getLicense();
				$licenseId = !empty($license) ? $license->getId() : null;
				$result = ReleaseAccessRepository::instance()->addLink(
					$subscription->getUserId(),
					$release->getProductId(),
					$release->getId(),
					$licenseId,
					$subscription->getId()
				);
				if ( $result ) {
					$count++;
				}
			}
		}
		return $count;
	}

	/**
	 * @param Release $release
	 *
	 * @return int
	 */
	public function publishFree(Release $release) {

		list ($links, ) = ReleaseAccessRepository::instance()->find(array(
			'productId' => $release->getProductId(),
			'licenseId' => 0,
			'subscriptionId' => 0,
			'distinctUserId' => true
		));

		$count = 0;
		if ( ! empty( $links ) ) {
			foreach ( $links as $link ) {
				$result = ReleaseAccessRepository::instance()->addLink(
					$link['userId'],
					$release->getProductId(),
					$release->getId()
				);
				if ( $result ) {
					$count++;
				}
			}
		}

		return $count;
	}
}