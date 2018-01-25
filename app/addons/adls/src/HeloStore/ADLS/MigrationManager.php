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

use HeloStore\Developer\ReleaseManager AS DeveloperReleaseManager;


class MigrationManager extends Manager
{

    /**
     * @throws \Exception
     */
    public function migrate()
    {
        $storeProducts = ProductManager::instance()->getStoreProductsData();
        foreach ($storeProducts as $storeProduct) {
            if (empty($storeProduct['product_id'])) {
                fn_print_r('Skipping `' . $storeProduct['name'] . ': no store product found');
                continue;
            }
            $this->migrateProduct($storeProduct);
        }
    }

    /**
     * @param $storeProduct
     *
     * @return bool
     * @throws \Exception
     */
    public function migrateProduct($storeProduct)
    {
        $releaseRepository = ReleaseRepository::instance();
        $releaseManager    = ReleaseManager::instance();

        $productId = $storeProduct['product_id'];
        $addonId   = $storeProduct['adls_addon_id'];

        list($releases,) = $releaseRepository->findByProductId($productId);

        if ( ! empty($releases)) {
            fn_print_r('Skipping `' . $storeProduct['name'] . '` already got ' . count($releases) . ' releases');

            return false;
        }

//        if ($addonId !== 'dummy') {
//            fn_print_r('Skipping `' . $storeProduct['name'] . '` !!!!!');
//            return false;
//        }

        $releaseIds = [];
        $releaseIds += $this->createReleaseFromArchives($storeProduct);
        $releaseId  = $this->migrateReleaseFromAttachments($storeProduct);
        if ( ! empty($releaseId)) {
            $releaseIds[] = $releaseId;
        }

        if (empty($releaseIds)) {
            $releaseId = $this->createReleaseFromPack($storeProduct);
            if ( ! empty($releaseId)) {
                $releaseIds[] = $releaseId;
            }
        }

        if ( ! empty($releaseIds)) {
            $dates = file_get_contents(DIR_ROOT . '/app/addons/local/controllers/backend/migrate/release_dates.json');
            $dates = json_decode($dates, true);
            $dates = $dates[$addonId];
            foreach ($releaseIds as $releaseId) {
                if (empty($releaseId)) {
                    continue;
                }

                $release = ReleaseRepository::instance()->findOneById($releaseId);
                if (empty($release)) {
                    continue;
                }

                $timestamp = $dates[$release->getVersion()]['timestamp'];
                $releaseDate = new \DateTime();
                $releaseDate->setTimestamp($timestamp);
                $release->setCreatedAt($releaseDate);
                ReleaseRepository::instance()->update($release);
            }
        }

        fn_print_r('OK `' . $storeProduct['name'] . '` ' . count($releaseIds) . ' releases');

        return true;
    }

    /**
     * @param $storeProduct
     *
     * @return array
     * @throws ReleaseException
     */
    public function createReleaseFromArchives($storeProduct)
    {
        $productId               = $storeProduct['product_id'];
        $addonId                 = $storeProduct['adls_addon_id'];
        $developerReleaseManager = DeveloperReleaseManager::instance();

        $releaseRepositoryPath = $developerReleaseManager->getOutputPath($addonId);

        $files      = glob($releaseRepositoryPath . '/' . $addonId . '*');
        $releaseIds = [];
        foreach ($files as $filePath) {
            $fileName     = basename($filePath);
            $version      = Utils::matchVersion($fileName);
            $entry        = [
                'version'  => $version,
                'filename' => $fileName,
                'filesize' => filesize($filePath),
            ];
            $releaseIds[] = $releaseId = ReleaseManager::instance()->release($storeProduct, $entry);
        }

        return $releaseIds;
    }

    public function createReleaseFromPack($storeProduct)
    {

        $productId               = $storeProduct['product_id'];
        $addonId                 = $storeProduct['adls_addon_id'];
        $developerReleaseManager = DeveloperReleaseManager::instance();

        $output    = array();
        $releaseId = null;
        if ($developerReleaseManager->pack($addonId, $output)) {

            // attempt to release the newly packed add-on
            $result = null;
            try {
                $releaseId = $developerReleaseManager->release($addonId, $output);
            } catch (\Exception $e) {
                fn_print_r('Release error: ' . $e->getMessage());
            }
            if ($result !== null) {
                if ($result) {
                    fn_print_r('Attached release to product: ' . $output['archivePath']);
                } else {
                    fn_print_r('Release error: Attached release to product: ' . 'Failed attaching release to product: ' . $output['archivePath']);
                }
            }
        } elseif ($developerReleaseManager->hasErrors()) {
            foreach ($developerReleaseManager->getErrors() as $error) {
                fn_print_r('Packing error: ' . $error);
            }
        }


        if ($releaseId == null) {
            fn_print_r('Skipping `' . $storeProduct['name'] . '`, failed to release: no release files found OR packing failed');

            return false;
        }

        if (empty($releaseId)) {
            throw new \Exception('Failed creating release for `' . $storeProduct['name'] . '`');
        }

        return $releaseId;
    }

    /**
     * @param $storeProduct
     *
     * @return bool
     * @throws \Exception
     */
    public function migrateReleaseFromAttachments($storeProduct)
    {
        $productId = $storeProduct['product_id'];

        $releaseManager = ReleaseManager::instance();

        list($files,) = fn_get_product_files(array(
            'product_id' => $productId
        ));

        $releaseId = null;
        if (empty($files)) {
            return false;
        }

        foreach ($files as $file) {
            $fileSize = $file['file_size'];
            $fileName = $file['file_name'];
            $version  = Utils::matchVersion($fileName);
            if (empty($version)) {
                throw new ReleaseException('Unable to get version from string ' . $fileName);
            }

            $releaseId = $releaseManager->createRelease(
                $productId
                , $version
                , $fileName
                , $fileSize
            );
            if ( ! empty($releaseId)) {
                if (fn_delete_product_files($file['file_id']) == false) {
                    fn_print_r(' - OK `' . $storeProduct['name'] . '`, deleted edp file');
                }
            }
        }

        return $releaseId;

    }

}