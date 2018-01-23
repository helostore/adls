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
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'update') {
	$view = &Tygh::$app['view'];

	Registry::set('navigation.tabs.adls', array (
		'title' => __('adls'),
		'js' => true
	));

	$manager = ProductManager::instance();
	$products = $manager->getStoreProducts();

	// product type
	$types = array(
		'P' => 'Standard',
		'C' => 'Configurable',
		ADLS_PRODUCT_TYPE_ADDON => 'Add-on',
		ADLS_PRODUCT_TYPE_THEME => 'Theme'
	);

	$subscriptions = $manager->getSubscriptionPlans();

	$view->assign('adls_addons', $products);
	$view->assign('adls_product_types', $types);
	$view->assign('adls_subscriptions', $subscriptions);

    $product = $view->getTemplateVars('product_data');

    $usage = \HeloStore\ADLS\Usage::productPlatforms($product['adls_addon_id']);
    \Tygh\Registry::get('view')->assign('usage', $usage);

    $usage = \HeloStore\ADLS\Usage::productVersions($product['adls_addon_id']);
    \Tygh\Registry::get('view')->assign('usageProductVersions', $usage);

	Registry::set('navigation.tabs.adls_releases', array (
		'title' => __('adls.releases'),
		'js' => true
	));
	$productId = ! empty( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : 0;
	list ( $releases, $search ) = \HeloStore\ADLS\ReleaseRepository::instance()->findByProductId($productId, array('getUserCount' => true));
	$view->assign('adls_releases', $releases);
}
