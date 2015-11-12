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

use HeloStore\ADLS\Addons\SchemesManager;
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
}
