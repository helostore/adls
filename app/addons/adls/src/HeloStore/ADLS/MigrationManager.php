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
            $addonId = $storeProduct['adls_addon_id'];

            list($releases, ) = $releaseRepository->findByProductId($productId);

            if (!empty($releases)) {
                fn_print_r('Skipping `' . $storeProduct['name'] . '` already got ' . count($releases) . ' releases');
                continue;
            }

            list($files, ) = fn_get_product_files(array(
                'product_id' => $productId
            ));
	        $version = $storeProduct['version'];
	        if ( ! empty( $storeProduct['adls_release_version'] ) ) {
		        $version = $storeProduct['adls_release_version'];
	        }
	        $releaseId = null;
            if (!empty($files)) {
	            $latestFile = array_pop($files);
	            $fileSize = $latestFile['file_size'];
	            $fileName = $latestFile['file_name'];


	            $releaseId = $releaseManager->createRelease(
		            $productId
		            , $version
		            , $fileName
		            , $fileSize
	            );
	            if ( ! empty( $releaseId ) ) {
		            if (fn_delete_product_files($latestFile['file_id']) == false) {
			            fn_print_r(' - OK `' . $storeProduct['name'] . '`, deleted edp file');
		            }
	            }
            } else {
	            $developerReleaseManager = DeveloperReleaseManager::instance();
	            $output = array();
	            if ($developerReleaseManager->pack($addonId, $output)) {

		            // attempt to release the newly packed add-on
		            $result = null;
		            try {
			            $releaseId = $developerReleaseManager->release($addonId, $output);
		            } catch (\Exception $e) {
			            fn_print_r( 'Release error: ' . $e->getMessage() );
		            }
		            if ($result !== null) {
			            if ($result) {
				            fn_print_r( 'Attached release to product: ' . $output['archivePath'] );
			            } else {
				            fn_print_r( 'Release error: Attached release to product: ' . 'Failed attaching release to product: ' . $output['archivePath'] );
			            }
		            }
	            } else if ($developerReleaseManager->hasErrors()) {
		            foreach ($developerReleaseManager->getErrors() as $error) {
			            fn_print_r( 'Packing error: ' . $error );
		            }
	            }
            }


	        if ( $releaseId == null ) {
		        fn_print_r('Skipping `' . $storeProduct['name'] . '`, failed to release: no release files found OR packing failed');
		        continue;
	        }



			if (!empty($releaseId)) {
                fn_print_r('OK `' . $storeProduct['name'] . '`');
            } else {
                throw new \Exception('Failed creating release for `' . $storeProduct['name'] . '`');
            }

        }
    }

}