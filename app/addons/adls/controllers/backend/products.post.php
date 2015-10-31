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

	$params = $_REQUEST;
	list($allItems, $search) = fn_get_addons($params);
	$addOns = array();

	foreach ($allItems as $name => $item) {
		$scheme = SchemesManager::getSchemeExt($name);
		if (empty($scheme)) {
			continue;
		}

		if (method_exists($scheme, 'hasAuthor') && $scheme->hasAuthor(ADLS_AUTHOR_NAME)) {
			$item['version'] = $scheme->getVersion();
			$addOns[$name] = $item;
		}
	}

	$view->assign('adls_addons', $addOns);
}
