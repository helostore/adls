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

        return $this->createRelease($productId, $version, $fileName, $fileSize);
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

		$release = new Release();
		$release
			->setProductId($productId)
            ->setVersion($version)
            ->setFileName($fileName)
            ->setFileSize($fileSize)
            ->setDownloads(0)
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

    public function getOrderItemReleases($product)
    {
        if (!empty($product['subscription'])) {
            $subscription = $product['subscription'];
            list($releases, ) = $this->repository->findBySubscription($subscription);
        } else {
            $productId = $product['product_id'];
            list($releases, ) = $this->repository->findByProductId($productId);
        }
        if (!empty($releases)) {
            $developerReleaseManager = \HeloStore\Developer\ReleaseManager::instance();
            /**
             * @var integer $k
             * @var Release $release
             */
            foreach ($releases as $k => $release) {
                $filePath = $developerReleaseManager->getOutputPath($release->getFileName());
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
     */
    public function download(Release $release)
    {
        $developerReleaseManager = \HeloStore\Developer\ReleaseManager::instance();
        $filePath = $developerReleaseManager->getOutputPath($release->getFileName());
        $release->download();
        $this->repository->update($release);
        
        fn_get_file($filePath);
    }
}