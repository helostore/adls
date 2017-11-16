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


class MigrationManager extends Manager
{

    public function migrate()
    {

        $storeProducts = ProductManager::instance()->getStoreProductsData();
        $releaseRepository = ReleaseRepository::instance();
        $releaseManager = ReleaseManager::instance();

        foreach ($storeProducts as $storeProduct) {
            if (empty($storeProduct['product_id'])) {
                fn_print_r('Skipping `' . $storeProduct['name'] . ': no store product found');
                continue;
            }
            $productId = $storeProduct['product_id'];

            list($releases, ) = $releaseRepository->findByProductId($productId);

            if (!empty($releases)) {
                fn_print_r('Skipping `' . $storeProduct['name'] . '` already got ' . count($releases) . ' releases');
                continue;
            }

            list($files, ) = fn_get_product_files(array(
                'product_id' => $productId
            ));
            if (empty($files)) {
                fn_print_r('Skipping `' . $storeProduct['name'] . '`: no release files found');
                continue;
            }

            $latestFile = array_pop($files);
            $fileSize = $latestFile['file_size'];
            $fileName = $latestFile['file_name'];


            $releaseId = $releaseManager->createRelease(
                $productId
                , $storeProduct['version']
                , $fileName
                , $fileSize
            );
            if ($releaseId === null) {
                fn_print_r('Skipping existing release for `' . $storeProduct['name'] . '`');
            } else if (!empty($releaseId)) {
                fn_print_r('OK `' . $storeProduct['name'] . '`');
            } else {
                throw new \Exception('Failed creating release for `' . $storeProduct['name'] . '`');
            }

            if (fn_delete_product_files($latestFile['file_id']) == false) {
                fn_print_r(' - OK `' . $storeProduct['name'] . '`, deleted edp file');
            }

        }
    }

}