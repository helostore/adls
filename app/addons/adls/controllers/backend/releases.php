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

use HeloStore\ADLS\Compatibility\Compatibility;
use HeloStore\ADLS\Platform\PlatformRepository;
use HeloStore\ADLS\Platform\PlatformVersion;
use HeloStore\ADLS\Platform\PlatformVersionRepository;
use HeloStore\ADLS\ProductManager;
use HeloStore\Developer\ReleaseManager;
use Tygh\Registry;


if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $addonId = $_REQUEST['addon_id'];
        if (empty($addonId)) {
            throw new \Exception('Addon ID not specified');
        }

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
            return [CONTROLLER_STATUS_REDIRECT, 'releases.manage?id=' . $addonId];
        }

        $release = \HeloStore\ADLS\ReleaseRepository::instance()->findOneById($releaseId);
        if (empty($release)) {
            throw new \Exception('Unable to find release');
        }

        $productId = $release->getProductId();
        $compatiblePlatformVersions = [];
        if (isset($_POST['compatibility'])) {
            $compatiblePlatformVersions = $_POST['compatibility'];
        }

        $platform = PlatformRepository::instance()->findDefault();

        list($previousCompatibilities, ) = \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->find(array(
            'releaseId' => $release->getId(),
            'platformId' => $platform->getId()
        ));

        $previousCompatiblePlatformVersionIds = [];
        if ( ! empty($previousCompatibilities)) {
            $previousCompatiblePlatformVersionIds = array_map(function (Compatibility $compatibility) {
                return $compatibility->getPlatformVersionId();
            }, $previousCompatibilities);
        }

        $deleteIds = array_diff($previousCompatiblePlatformVersionIds, $compatiblePlatformVersions);
        $addIds = array_diff($compatiblePlatformVersions, $previousCompatiblePlatformVersionIds);
        $platformVersionRepository = PlatformVersionRepository::instance();


        foreach ($deleteIds as $id) {
            \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->unassign($releaseId, $id);
        }

        foreach ($addIds as $id) {
            $platformVersion = $platformVersionRepository->findOneById($id);
            \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->assign($productId, $releaseId, $platformVersion);
        }

        return [CONTROLLER_STATUS_REDIRECT, 'releases.update?release_id=' . $releaseId];
    }
}


$manager = ProductManager::instance();

if ($mode == 'delete' && !empty($_REQUEST['release_id'])) {
    $release = \HeloStore\ADLS\ReleaseRepository::instance()->findOneById($_REQUEST['release_id']);

    if (empty($release)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (\HeloStore\ADLS\ReleaseRepository::instance()->delete($release)) {
        fn_set_notification('N', __('notice'), 'Release deleted.');
    } else {
        fn_set_notification('E', __('error'), 'Failed deleting release.');
    }

    return array( CONTROLLER_STATUS_REDIRECT, $_SERVER['HTTP_REFERER'] );
}

if ($mode == 'add') {
    $releaseId = 0;
    if (empty($_REQUEST['addonId'])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    $addonId = $_REQUEST['addonId'];
}

if ($mode == 'update') {
    $releaseId = !empty($_REQUEST['release_id']) ? $_REQUEST['release_id'] : 0;
    $release = \HeloStore\ADLS\ReleaseRepository::instance()->findOneById($releaseId);

    if (empty($release)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $productId = $release->getProductId();
    $partialProduct = $manager->getProductById($productId);
    $addonId = $partialProduct['adls_addon_id'];

    Registry::get('view')->assign('release', $release);
}

if ($mode == 'update' || $mode == 'add') {

    $products = $manager->getStoreProductsData();
    $product = $products[$addonId];
//    if ($mode == 'add' && !$product['has_unreleased_version']) {
//        fn_set_notification('W', __('warning'), 'This product has no unreleased versions. Suggestion: update latest version instead (it will be repacked as well)');
//
//        return [CONTROLLER_STATUS_REDIRECT, 'releases.manage?id=' . $addonId];
//    }
    Registry::get('view')->assign('product', $product);

    $platform = PlatformRepository::instance()->findDefault();
    list($availableVersions, ) = PlatformVersionRepository::instance()->findByPlatformId($platform->getId(), [
        'items_per_page' => 35
    ]);
    Registry::get('view')->assign('availableVersions', $availableVersions);
    Registry::get('view')->assign('platform', $platform);

    $compatibilities = [];
    if ( ! empty($release)) {
        list($compatibilities, ) = \HeloStore\ADLS\Compatibility\CompatibilityRepository::instance()->find(array(
            'releaseId' => $release->getId(),
            'platformId' => $platform->getId()
        ));
    }

    Registry::get('view')->assign('compatibilities', $compatibilities);

    $compatiblePlatformVersionIds = [];
    if ( ! empty($compatibilities)) {
        $compatiblePlatformVersionIds = array_map(function (Compatibility $compatibility) {
            return $compatibility->getPlatformVersionId();
        }, $compatibilities);
    }
    Registry::get('view')->assign('compatiblePlatformVersionIds', $compatiblePlatformVersionIds);
}


if ($mode == 'manage' && !empty($_REQUEST['id'])) {
    $addonId = $_REQUEST['id'];
    $manager = ProductManager::instance();
    $products = $manager->getStoreProductsData();
    $product = $products[$addonId];

    if ( ! isset($products[$addonId])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    Registry::get('view')->assign('product', $product);
    Registry::get('view')->assign('addonId', $addonId);
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

if ($mode == 'overview') {
    $manager = ProductManager::instance();
    $products = $manager->getStoreProductsData();
    Registry::get('view')->assign('products', $products);
}

