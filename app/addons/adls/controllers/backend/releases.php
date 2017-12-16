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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

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