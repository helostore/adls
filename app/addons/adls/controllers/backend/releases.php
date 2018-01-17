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

use HeloStore\ADLS\ProductManager;
use HeloStore\Developer\ReleaseManager;


if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $addonId = $_REQUEST['id'];

        // app/addons/developer/controllers/backend/addons.post.php:106
        $manager = ReleaseManager::instance();
        if ($manager->pack($addonId, $output)) {
            fn_set_notification('N', __('notice'), 'Packed to ' . $output['archivePath']);

            // attempt to release the newly packed add-on
            $releaseId = null;
            try {
                $releaseId = $manager->release($addonId, $output);
            } catch (\Exception $e) {
                fn_set_notification('W', __('warning'), $e->getMessage(), 'I');
            }
            if ($releaseId !== null) {
                if ($releaseId) {
                    fn_set_notification('N', __('notice'), 'Attached release to product: ' . $output['archivePath']);
                } else {
                    fn_set_notification('E', __('error'), 'Failed attaching release to product: ' . $output['archivePath']);
                }
            }
        } else if ($manager->hasErrors()) {
            foreach ($manager->getErrors() as $error) {
                fn_set_notification('E', __('error'), $error);
            }
        }

        if ( empty($releaseId)) {
            return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
        }

        $release = \HeloStore\ADLS\ReleaseRepository::instance()->findOneById($releaseId);
        if (empty($release)) {
            throw new \Exception('Unable to find release');
        }

        $productId = $release->getProductId();

        if ( ! empty($_POST['compatibility'])) {
            $compatiblePlatformVersions = $_POST['compatibility'];
            $entry = new \HeloStore\ADLS\Compatibility\Compatibility();
            $platformVersionRepository = \HeloStore\ADLS\Platform\PlatformVersionRepository::instance();
            foreach ($compatiblePlatformVersions as $id) {
                $platformVersion = $platformVersionRepository->findOneById($id);
                \HeloStore\ADLS\Compatibility\CompatibilityManager::instance()->assign($productId, $releaseId, $platformVersion);
            }
        }

        return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
    }
}


if ($mode == 'update' && !empty($_REQUEST['id'])) {
    $addonId = $_REQUEST['id'];
    $manager = ProductManager::instance();
    $products = $manager->getStoreProductsData();
    $product = $products[$addonId];

    if ( ! isset($products[$addonId])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    \Tygh\Registry::get('view')->assign('product', $product);
    \Tygh\Registry::get('view')->assign('addonId', $addonId);

    $platform = \HeloStore\ADLS\Platform\PlatformRepository::instance()->findDefault();
    list($availableVersions, ) = \HeloStore\ADLS\Platform\PlatformVersionRepository::instance()->findByPlatformId($platform->getId(), [
        'items_per_page' => 35
    ]);
    \Tygh\Registry::get('view')->assign('availableVersions', $availableVersions);

}

if (($mode == 'publish' || $mode == 'unpublish') && !empty($_REQUEST['release_id'])) {
	$releaseId = $_REQUEST['release_id'];
	$release = \HeloStore\ADLS\ReleaseRepository::instance()->findOneById( $releaseId );

	if ( empty( $release ) ) {
		throw new \Exception('Release not found');
	}

	if ($mode == 'unpublish' ) {
		list ($premiumCount, $freeCount) = \HeloStore\ADLS\ReleaseManager::instance()->unpublish($release);
		fn_set_notification('N', __('notice'), 'Un-published from: ' . $premiumCount . ' premium, ' . $freeCount . ' free');
	}

	if ($mode == 'publish') {
		list ($premiumCount, $freeCount) = \HeloStore\ADLS\ReleaseManager::instance()->publish($release);
		fn_set_notification('N', __('notice'), 'Published to: ' . $premiumCount . ' premium, ' . $freeCount . ' free');
	}

	if (!empty($_SERVER['HTTP_REFERER'])) {
		return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
	}

	return array(CONTROLLER_STATUS_OK, 'releases.manage');
}

if ($mode == 'manage') {
    $manager = ProductManager::instance();
    $products = $manager->getStoreProductsData();
    \Tygh\Registry::get('view')->assign('products', $products);
}

