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

use HeloStore\ADLSS\Subscription;
use HeloStore\ADLSS\Subscription\SubscriptionRepository;
use Tygh\Registry;

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
     * @return bool
     * @throws ReleaseException
     */
	public function release($storeProduct, $params)
	{
        if (empty($storeProduct) || empty($storeProduct['product_id'])) {
            throw new ReleaseException("Store product corresponding to release data not found (" . $storeProduct['name'] . ')');
        }
        $productId = intval($storeProduct['product_id']);

        if (empty($params['version'])) {
            throw new ReleaseException("Missing version parameter in release data");
        }
        $version = $params['version'];

        if (empty($params['filename'])) {
            throw new ReleaseException("Missing filename parameter in release data");
        }
        $fileName = $params['filename'];

        if (!empty($params['filesize'])) {
            $fileSize = intval($params['filesize']);
        } else {
            if (empty($params['archivePath'])) {
                throw new ReleaseException("Missing archivePath parameter in release data");
            }
            $fileSize = filesize($params['archivePath']);
        }

        $releaseId = $this->createRelease($productId, $version, $fileName, $fileSize);

		return $releaseId;
	}

    /**
     * Create a release entry in DB
     *
     * @param $productId
     * @param $version
     * @param $fileName
     * @param $fileSize
     * @return bool|int
     * @throws ReleaseException
     */
	public function createRelease($productId, $version, $fileName, $fileSize)
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
            ->setStatus(Release::STATUS_ACTIVE);

        $result = $this->repository->create($release);
        if ($result) {
            $this->updateProduct($productId, $version, TIME);
        }

        return $result;
	}

	/**
	 * Create a new release file and attach it to the CS-Cart product.
	 *
	 * @param $productId
	 * @param $params
	 * @return bool|int
	 */
//	public function createFile($productId, $params)
//	{
//		$filename = $params['filename'];
//        $file = array(
//            'product_id' => $productId,
//            'file_name' => $filename,
//            'position' => 0,
//            'folder_id' => null,
//            'activation_type' => 'M',
//            'max_downloads' => 0,
//            'license' => '',
//            'agreement' => 'Y',
//            'readme' => '',
//        );
//        $fileId = 0;
//		$file['file_name'] = $filename;
//
//		$_REQUEST['file_base_file'] = array(
//			$fileId => $params['archiveUrl']
//		);
//		$_REQUEST['type_base_file'] = array(
//			$fileId => 'url'
//		);
//		$fileId = fn_update_product_file($file, $fileId);
//
//		return $fileId;
//	}

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
	 * @param $userId
	 * @param $product
	 *
	 * @return mixed
	 */
    public function getOrderItemReleases($userId, $product, $itemsPerPage = 1)
    {
        if (!empty($product['subscription'])) {
            /** @var Subscription $subscription */
            $subscription = $product['subscription'];
            list($releases, ) = $this->repository->findBySubscription($subscription, array(
	            'items_per_page' => $itemsPerPage
            ));
        } else {
            $productId = $product['product_id'];
            list($releases, ) = $this->repository->findByProductId($productId, array(
            	'userId' => $userId,
	            'items_per_page' => $itemsPerPage
            ));
        }

        if (!empty($releases)) {
            $developerReleaseManager = \HeloStore\Developer\ReleaseManager::instance();
            $addonId = $product['adls_addon_id'];
            /**
             * @var integer $k
             * @var Release $release
             */
            foreach ($releases as $k => $release) {
                $filePath = $developerReleaseManager->getOutputPath($addonId, $release->getFileName());
                if (!file_exists($filePath)) {
                    error_log("Release file not found: " . $filePath);
                    unset($releases[$k]);
                }
            }
        }


        return $releases;
    }

	/**
	 * @param Release $release
	 *
	 * @return bool
	 */
	public function prepareForDownload(Release $release)
	{
		$developerReleaseManager = \HeloStore\Developer\ReleaseManager::instance();
        $product = ProductManager::instance()->getProductById($release->getProductId());
        $addonId = $product['adls_addon_id'];
		$filePath = $developerReleaseManager->getOutputPath($addonId, $release->getFileName());

		$release->download();
		$this->repository->update($release);

		return $filePath;
	}

	/**
	 * @param Release $release
	 *
	 * @return bool
	 */
    public function download(Release $release)
    {
        return fn_get_file($this->prepareForDownload($release));
    }


//	public function removeUserLinks($userId, $productId, $startDate = null, $endDate = null) {
//		if ( empty( $startDate ) && empty( $endDate ) ) {
//			return;
//		}
//		list($releases, ) = $this->repository->findByProductInRange( $productId, $startDate, $endDate );
//		list($latestReleases, ) = ReleaseRepository::instance()->findLatestByProduct($productId, $endDate);
//		$releases += $latestReleases;
//		foreach ( $releases as $release ) {
//			ReleaseLinkRepository::instance()->removeLink($userId, $release->getId());
//		}
//	}

    public function addUserLinks($userId, $productId, $licenseId = null, $subscriptionId = null, $startDate = null, $endDate = null) {
	    if ( !empty( $startDate ) && !empty( $endDate ) ) {
		    list($releases, ) = $this->repository->findByProductInRange( $productId, $startDate, $endDate );
		    if ( empty( $releases ) ) {
			    $release = ReleaseRepository::instance()->findOneLatestByProduct($productId, $endDate);
			    $releases = array( $release );
		    }
	    } else if ($subscriptionId === null) {
		    // This is a free product.
		    list($releases, ) = ReleaseRepository::instance()->find(array(
			    'productId' => $productId
		    ));

	    }

	    if ( empty( $releases ) ) {
		    throw new \Exception('Unable to find releases for given params');
	    }
	    foreach ( $releases as $release ) {
		    ReleaseLinkRepository::instance()->addLink($userId, $productId, $release->getId(), $licenseId, $subscriptionId);
	    }
	}

	public function unpublish(Release $release) {
		list ($links, ) = ReleaseLinkRepository::instance()->findByRelease($release);
		$premium = 0;
		$free = 0;
		foreach ( $links as $link ) {
			if ( !empty( $link['licenseId'] ) && ! empty( $link['subscriptionId'] ) ) {
				$premium++;
			} else {
				$free++;
			}
		}
		ReleaseLinkRepository::instance()->deleteByReleaseId($release->getId());

		return array($premium, $free);
	}

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
				$result = ReleaseLinkRepository::instance()->addLink(
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

		list ($links, ) = ReleaseLinkRepository::instance()->find(array(
			'productId' => $release->getProductId(),
			'licenseId' => 0,
			'subscriptionId' => 0,
			'distinctUserId' => true
		));

		$count = 0;
		if ( ! empty( $links ) ) {
			foreach ( $links as $link ) {
				$result = ReleaseLinkRepository::instance()->addLink(
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