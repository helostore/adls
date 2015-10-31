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

use HeloStore\ADLS\LicenseManager;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'verify') {
        return array (CONTROLLER_STATUS_REDIRECT, '');
    }
}

if ($mode == 'test') {
	$manager = LicenseManager::instance();

	$productId = 2;
	$orderId = 15;
	$userId = 2;

	$order_info = fn_get_order_info($orderId);
	fn_adls_process_order($order_info);




	exit;
}
if ($mode == 'manage') {

}
